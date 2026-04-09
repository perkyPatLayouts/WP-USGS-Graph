<?php
/**
 * USGS Data Scraper for USGS Water Levels plugin.
 *
 * Updated to use the new USGS OGC API (api.waterdata.usgs.gov)
 * instead of HTML scraping.
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scraper class for fetching USGS water monitoring data via the new OGC API.
 */
class USGS_Water_Levels_Scraper {

	/**
	 * USGS API endpoint for field measurements.
	 */
	const API_ENDPOINT = 'https://api.waterdata.usgs.gov/ogcapi/v0/collections/field-measurements/items';

	/**
	 * Fetch and parse USGS data from a monitoring location URL.
	 *
	 * @param string $url        USGS monitoring location URL.
	 * @param string $date_start Optional start date (Y-m-d format).
	 * @param string $date_end   Optional end date (Y-m-d format).
	 * @return array|WP_Error Array of measurements on success, WP_Error on failure.
	 */
	public static function scrape_usgs_data( $url, $date_start = null, $date_end = null ) {
		if ( empty( $url ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid USGS URL provided.', 'usgs-water-levels' ) );
		}

		// Extract site ID from URL.
		$site_id = self::extract_site_id( $url );
		if ( ! $site_id ) {
			return new WP_Error(
				'invalid_url',
				__( 'Could not extract monitoring location ID from URL. Expected format: https://waterdata.usgs.gov/monitoring-location/USGS-XXXXXXXXX/', 'usgs-water-levels' )
			);
		}

		// Build API URL parameters.
		$api_params = array(
			'f'                       => 'json',
			'monitoring_location_id'  => $site_id,
			'limit'                   => 1000, // Increase limit for date ranges.
		);

		// Add date range if specified (OGC API datetime parameter format).
		if ( ! empty( $date_start ) && ! empty( $date_end ) ) {
			// OGC API uses datetime=START/END format.
			$start_iso = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date_start ) );
			$end_iso   = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date_end . ' 23:59:59' ) );
			$api_params['datetime'] = $start_iso . '/' . $end_iso;
		} elseif ( ! empty( $date_start ) ) {
			// Only start date - open-ended range.
			$start_iso = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date_start ) );
			$api_params['datetime'] = $start_iso . '/..';
		} elseif ( ! empty( $date_end ) ) {
			// Only end date - from beginning to end date.
			$end_iso = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $date_end . ' 23:59:59' ) );
			$api_params['datetime'] = '../' . $end_iso;
		}

		$api_url = add_query_arg( $api_params, self::API_ENDPOINT );

		// Fetch data from API.
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'    => 60,
				'user-agent' => 'WordPress USGS Water Levels Plugin/' . USGS_WATER_LEVELS_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error(
				'http_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'HTTP request failed with code %d.', 'usgs-water-levels' ),
					$response_code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new WP_Error( 'empty_response', __( 'Empty response from USGS API.', 'usgs-water-levels' ) );
		}

		// Parse JSON response.
		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_error',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Failed to parse JSON response: %s', 'usgs-water-levels' ),
					json_last_error_msg()
				)
			);
		}

		// Extract measurements from API response.
		$measurements = self::parse_api_response( $data );

		if ( empty( $measurements ) ) {
			return new WP_Error(
				'no_data',
				__( 'No water level measurements found for this monitoring location. The site may not have groundwater level data, or there may be no recent measurements.', 'usgs-water-levels' )
			);
		}

		return $measurements;
	}

	/**
	 * Extract site ID from USGS monitoring location URL.
	 *
	 * @param string $url USGS monitoring location URL.
	 * @return string|false Site ID (e.g., "USGS-410858072171501") or false on failure.
	 */
	private static function extract_site_id( $url ) {
		// Remove URL fragment (#...) and query string (?...).
		$url = preg_replace( '/#.*$/', '', $url );
		$url = preg_replace( '/\?.*$/', '', $url );

		// Match pattern: /monitoring-location/USGS-XXXXXXXXX or /USGS-XXXXXXXXX/.
		if ( preg_match( '/USGS-\d+/', $url, $matches ) ) {
			return $matches[0];
		}

		// Try to match just the number and prepend USGS-.
		if ( preg_match( '/(\d{10,})/', $url, $matches ) ) {
			return 'USGS-' . $matches[1];
		}

		return false;
	}

	/**
	 * Parse API response to extract water level measurements.
	 *
	 * @param array $data Decoded JSON response from USGS API.
	 * @return array Array of measurements with 'date' and 'value' keys.
	 */
	private static function parse_api_response( $data ) {
		if ( empty( $data['features'] ) || ! is_array( $data['features'] ) ) {
			return array();
		}

		$measurements = array();

		foreach ( $data['features'] as $feature ) {
			if ( empty( $feature['properties'] ) ) {
				continue;
			}

			$props = $feature['properties'];

			// Skip if missing required fields.
			if ( empty( $props['time'] ) || empty( $props['value'] ) ) {
				continue;
			}

			// Parameter codes for groundwater levels:
			// 62610 = Depth to water level, feet below land surface (MOST COMMON)
			// 62611 = Depth to water level, feet below land surface, NAVD88
			// 72019 = Depth to water level, feet below land surface, NGVD29 (uses different datum - excluded to avoid mixing)
			// 72020 = Groundwater level above NGVD29, feet (elevation, not depth)
			$param_code = isset( $props['parameter_code'] ) ? $props['parameter_code'] : '';

			// Accept modern depth measurements (62610, 62611) which use consistent datums.
			// Exclude 72019 (NGVD29) as it uses a different reference point causing inconsistent values.
			$valid_depth_codes = array( '62610', '62611' );
			if ( ! empty( $param_code ) && ! in_array( $param_code, $valid_depth_codes, true ) ) {
				continue;
			}

			// Parse date from ISO 8601 timestamp.
			$date = self::parse_date( $props['time'] );
			if ( ! $date ) {
				continue;
			}

			// Parse value.
			$value = self::parse_value( $props['value'] );
			if ( false === $value ) {
				continue;
			}

			$measurements[] = array(
				'date'  => $date,
				'value' => $value,
			);
		}

		// Remove duplicates and sort by date.
		$measurements = self::deduplicate_measurements( $measurements );

		return $measurements;
	}

	/**
	 * Parse ISO 8601 date string to Y-m-d format.
	 *
	 * @param string $date_string ISO 8601 date string (e.g., "2023-09-21T17:02:00+00:00").
	 * @return string|false Formatted date string or false on failure.
	 */
	private static function parse_date( $date_string ) {
		$timestamp = strtotime( $date_string );

		if ( false === $timestamp ) {
			return false;
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Parse measurement value to extract numeric water level.
	 *
	 * @param string|float $value_string Value from API.
	 * @return float|false Numeric value or false on failure.
	 */
	private static function parse_value( $value_string ) {
		if ( is_numeric( $value_string ) ) {
			return floatval( $value_string );
		}

		// Extract numeric value from string.
		if ( preg_match( '/-?\d+\.?\d*/', $value_string, $matches ) ) {
			return floatval( $matches[0] );
		}

		return false;
	}

	/**
	 * Remove duplicate measurements (same date) keeping the most recent.
	 *
	 * @param array $measurements Array of measurements.
	 * @return array Deduplicated measurements sorted by date.
	 */
	private static function deduplicate_measurements( $measurements ) {
		$unique = array();

		foreach ( $measurements as $measurement ) {
			$date = $measurement['date'];

			// Keep only one measurement per date (last one wins).
			$unique[ $date ] = $measurement;
		}

		// Sort by date ascending.
		ksort( $unique );

		return array_values( $unique );
	}

	/**
	 * Scrape and save data for a specific graph.
	 *
	 * @param int $graph_id Graph ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function scrape_and_save( $graph_id ) {
		// Get graph configuration.
		$graph = USGS_Water_Levels_Database::get_graph_config( $graph_id );

		if ( ! $graph ) {
			return new WP_Error( 'graph_not_found', __( 'Graph configuration not found.', 'usgs-water-levels' ) );
		}

		if ( ! $graph['is_enabled'] ) {
			return new WP_Error( 'graph_disabled', __( 'Graph is disabled.', 'usgs-water-levels' ) );
		}

		// Auto-update dates if enabled (rolling window).
		if ( ! empty( $graph['auto_update_dates'] ) && ! empty( $graph['date_start'] ) && ! empty( $graph['date_end'] ) ) {
			$old_end   = strtotime( $graph['date_end'] );
			$today     = strtotime( 'today' );
			$days_diff = round( ( $today - $old_end ) / DAY_IN_SECONDS );

			if ( $days_diff > 0 ) {
				// Update end date to today.
				$new_end = gmdate( 'Y-m-d', $today );

				// Move start date forward by the same number of days.
				$old_start = strtotime( $graph['date_start'] );
				$new_start = gmdate( 'Y-m-d', $old_start + ( $days_diff * DAY_IN_SECONDS ) );

				// Update the database with new dates.
				USGS_Water_Levels_Database::update_graph(
					$graph_id,
					array(
						'date_start' => $new_start,
						'date_end'   => $new_end,
					)
				);

				// Use updated dates for scraping.
				$graph['date_start'] = $new_start;
				$graph['date_end']   = $new_end;
			}
		}

		// Scrape USGS data with optional date range.
		$date_start = ! empty( $graph['date_start'] ) ? $graph['date_start'] : null;
		$date_end   = ! empty( $graph['date_end'] ) ? $graph['date_end'] : null;

		$measurements = self::scrape_usgs_data( $graph['usgs_url'], $date_start, $date_end );

		if ( is_wp_error( $measurements ) ) {
			// Log error.
			self::log_error( $graph_id, $measurements->get_error_message() );
			return $measurements;
		}

		// Save measurements to database.
		$saved = USGS_Water_Levels_Database::save_measurements( $graph_id, $measurements );

		if ( ! $saved ) {
			$error = new WP_Error( 'save_failed', __( 'Failed to save measurements to database.', 'usgs-water-levels' ) );
			self::log_error( $graph_id, $error->get_error_message() );
			return $error;
		}

		// Log success.
		self::log_success( $graph_id, count( $measurements ) );

		// Don't prune - USGS data includes historical measurements.
		// USGS_Water_Levels_Database::prune_old_measurements( $graph_id, 730 );

		return true;
	}

	/**
	 * Scrape and save data for all enabled graphs.
	 *
	 * @return array Array of results with graph_id and status.
	 */
	public static function scrape_all_enabled() {
		$graphs  = USGS_Water_Levels_Database::get_enabled_graphs();
		$results = array();

		foreach ( $graphs as $graph ) {
			$result = self::scrape_and_save( $graph['id'] );

			$results[] = array(
				'graph_id' => $graph['id'],
				'title'    => $graph['title'],
				'success'  => ! is_wp_error( $result ),
				'message'  => is_wp_error( $result ) ? $result->get_error_message() : __( 'Data scraped successfully.', 'usgs-water-levels' ),
			);
		}

		return $results;
	}

	/**
	 * Log scraping error.
	 *
	 * @param int    $graph_id      Graph ID.
	 * @param string $error_message Error message.
	 */
	private static function log_error( $graph_id, $error_message ) {
		// Store in WordPress transient (expires after 24 hours).
		$log_key = 'usgs_wl_scrape_log_' . $graph_id;
		$log     = array(
			'timestamp' => current_time( 'mysql' ),
			'status'    => 'error',
			'message'   => $error_message,
		);

		set_transient( $log_key, $log, DAY_IN_SECONDS );

		// Also log to WordPress debug.log if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'USGS Water Levels - Graph #%d: %s', $graph_id, $error_message ) );
		}
	}

	/**
	 * Log scraping success.
	 *
	 * @param int $graph_id           Graph ID.
	 * @param int $measurements_count Number of measurements saved.
	 */
	private static function log_success( $graph_id, $measurements_count ) {
		$log_key = 'usgs_wl_scrape_log_' . $graph_id;
		$log     = array(
			'timestamp' => current_time( 'mysql' ),
			'status'    => 'success',
			'message'   => sprintf(
				/* translators: %d: number of measurements */
				__( 'Successfully scraped and saved %d measurements.', 'usgs-water-levels' ),
				$measurements_count
			),
		);

		set_transient( $log_key, $log, DAY_IN_SECONDS );
	}

	/**
	 * Get scraping log for a graph.
	 *
	 * @param int $graph_id Graph ID.
	 * @return array|false Log data or false if not found.
	 */
	public static function get_scrape_log( $graph_id ) {
		$log_key = 'usgs_wl_scrape_log_' . $graph_id;
		return get_transient( $log_key );
	}
}
