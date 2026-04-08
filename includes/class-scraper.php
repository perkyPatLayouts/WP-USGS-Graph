<?php
/**
 * USGS Data Scraper for USGS Water Levels plugin.
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scraper class for fetching and parsing USGS water monitoring data.
 */
class USGS_Water_Levels_Scraper {

	/**
	 * Fetch and parse USGS data from a monitoring location URL.
	 *
	 * @param string $url USGS monitoring location URL.
	 * @return array|WP_Error Array of measurements on success, WP_Error on failure.
	 */
	public static function scrape_usgs_data( $url ) {
		if ( empty( $url ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid USGS URL provided.', 'usgs-water-levels' ) );
		}

		// Fetch the page content.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
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

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return new WP_Error( 'empty_response', __( 'Empty response from USGS server.', 'usgs-water-levels' ) );
		}

		// Parse the HTML.
		$measurements = self::parse_html( $html );

		if ( empty( $measurements ) ) {
			return new WP_Error( 'no_data', __( 'No measurement data found in USGS page.', 'usgs-water-levels' ) );
		}

		return $measurements;
	}

	/**
	 * Parse HTML to extract measurement data.
	 *
	 * @param string $html HTML content.
	 * @return array Array of measurements with 'date' and 'value' keys.
	 */
	private static function parse_html( $html ) {
		// Suppress warnings from malformed HTML.
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( $html );

		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );

		// Find the table with class "usa-table paginated-table".
		$tables = $xpath->query( "//table[contains(@class, 'usa-table') and contains(@class, 'paginated-table')]" );

		if ( 0 === $tables->length ) {
			return array();
		}

		$table        = $tables->item( 0 );
		$measurements = array();

		// Find all table rows in tbody.
		$rows = $xpath->query( './/tbody/tr', $table );

		foreach ( $rows as $row ) {
			$cells = $xpath->query( './/td', $row );

			if ( $cells->length < 2 ) {
				continue;
			}

			// First cell typically contains the date.
			$date_cell = $cells->item( 0 );
			$date_text = trim( $date_cell->textContent );

			// Second cell typically contains the water level measurement.
			$value_cell = $cells->item( 1 );
			$value_text = trim( $value_cell->textContent );

			// Parse date (format: MM-DD-YYYY or similar).
			$parsed_date = self::parse_date( $date_text );
			if ( ! $parsed_date ) {
				continue;
			}

			// Parse value (extract numeric value, remove units).
			$parsed_value = self::parse_value( $value_text );
			if ( false === $parsed_value ) {
				continue;
			}

			$measurements[] = array(
				'date'  => $parsed_date,
				'value' => $parsed_value,
			);
		}

		return $measurements;
	}

	/**
	 * Parse date string to Y-m-d format.
	 *
	 * @param string $date_string Date string from USGS page.
	 * @return string|false Formatted date string or false on failure.
	 */
	private static function parse_date( $date_string ) {
		// Remove extra whitespace.
		$date_string = trim( $date_string );

		if ( empty( $date_string ) ) {
			return false;
		}

		// Try to parse various date formats.
		$timestamp = strtotime( $date_string );

		if ( false === $timestamp ) {
			return false;
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Parse measurement value to extract numeric water level.
	 *
	 * @param string $value_string Value string from USGS page.
	 * @return float|false Numeric value or false on failure.
	 */
	private static function parse_value( $value_string ) {
		// Remove extra whitespace.
		$value_string = trim( $value_string );

		if ( empty( $value_string ) ) {
			return false;
		}

		// Extract numeric value (handle negative numbers and decimals).
		preg_match( '/-?\d+\.?\d*/', $value_string, $matches );

		if ( empty( $matches ) ) {
			return false;
		}

		$value = floatval( $matches[0] );

		return $value;
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

		// Scrape USGS data.
		$measurements = self::scrape_usgs_data( $graph['usgs_url'] );

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

		// Prune old data (keep last 2 years by default).
		USGS_Water_Levels_Database::prune_old_measurements( $graph_id, 730 );

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
