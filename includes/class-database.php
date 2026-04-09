<?php
/**
 * Database operations for USGS Water Levels plugin.
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database class for managing plugin tables and data operations.
 */
class USGS_Water_Levels_Database {

	/**
	 * Table name for graph configurations.
	 *
	 * @var string
	 */
	private static $graphs_table = 'usgs_wl_graphs';

	/**
	 * Table name for measurements.
	 *
	 * @var string
	 */
	private static $measurements_table = 'usgs_wl_measurements';

	/**
	 * Create database tables.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Graph configurations table.
		$graphs_table = $wpdb->prefix . self::$graphs_table;
		$graphs_sql   = "CREATE TABLE IF NOT EXISTS $graphs_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			usgs_url text NOT NULL,
			scrape_interval int(11) NOT NULL DEFAULT 24,
			is_enabled tinyint(1) NOT NULL DEFAULT 1,
			date_start date DEFAULT NULL,
			date_end date DEFAULT NULL,
			auto_update_dates tinyint(1) NOT NULL DEFAULT 0,
			custom_css text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_enabled (is_enabled)
		) $charset_collate;";

		// Measurements table.
		$measurements_table = $wpdb->prefix . self::$measurements_table;
		$measurements_sql   = "CREATE TABLE IF NOT EXISTS $measurements_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			graph_id bigint(20) unsigned NOT NULL,
			measurement_date date NOT NULL,
			water_level decimal(10,2) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY graph_id (graph_id),
			KEY measurement_date (measurement_date),
			UNIQUE KEY unique_measurement (graph_id, measurement_date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $graphs_sql );
		dbDelta( $measurements_sql );

		// Store database version.
		update_option( 'usgs_water_levels_db_version', USGS_WATER_LEVELS_VERSION );
	}

	/**
	 * Drop database tables (for uninstall).
	 */
	public static function drop_tables() {
		global $wpdb;

		$measurements_table = $wpdb->prefix . self::$measurements_table;
		$graphs_table       = $wpdb->prefix . self::$graphs_table;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $measurements_table ) );
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $graphs_table ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		delete_option( 'usgs_water_levels_db_version' );
	}

	/**
	 * Get all graph configurations.
	 *
	 * @return array Array of graph configurations.
	 */
	public static function get_all_graphs() {
		global $wpdb;

		$table = $wpdb->prefix . self::$graphs_table;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC", ARRAY_A );

		return $results ? $results : array();
	}

	/**
	 * Get a single graph configuration.
	 *
	 * @param int $graph_id Graph ID.
	 * @return array|null Graph configuration or null if not found.
	 */
	public static function get_graph_config( $graph_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::$graphs_table;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $table WHERE id = %d",
				$graph_id
			),
			ARRAY_A
		);

		return $result;
	}

	/**
	 * Get enabled graphs for scraping.
	 *
	 * @return array Array of enabled graph configurations.
	 */
	public static function get_enabled_graphs() {
		global $wpdb;

		$table = $wpdb->prefix . self::$graphs_table;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( "SELECT * FROM $table WHERE is_enabled = 1", ARRAY_A );

		return $results ? $results : array();
	}

	/**
	 * Create a new graph configuration.
	 *
	 * @param array $data Graph configuration data.
	 * @return int|false Graph ID on success, false on failure.
	 */
	public static function create_graph( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . self::$graphs_table;

		$defaults = array(
			'title'             => '',
			'usgs_url'          => '',
			'scrape_interval'   => 24,
			'is_enabled'        => 1,
			'date_start'        => null,
			'date_end'          => null,
			'auto_update_dates' => 0,
			'custom_css'        => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$table,
			array(
				'title'             => sanitize_text_field( $data['title'] ),
				'usgs_url'          => esc_url_raw( $data['usgs_url'] ),
				'scrape_interval'   => absint( $data['scrape_interval'] ),
				'is_enabled'        => absint( $data['is_enabled'] ),
				'date_start'        => ! empty( $data['date_start'] ) ? sanitize_text_field( $data['date_start'] ) : null,
				'date_end'          => ! empty( $data['date_end'] ) ? sanitize_text_field( $data['date_end'] ) : null,
				'auto_update_dates' => absint( $data['auto_update_dates'] ),
				'custom_css'        => wp_strip_all_tags( $data['custom_css'] ),
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a graph configuration.
	 *
	 * @param int   $graph_id Graph ID.
	 * @param array $data     Graph configuration data.
	 * @return bool True on success, false on failure.
	 */
	public static function update_graph( $graph_id, $data ) {
		global $wpdb;

		$table = $wpdb->prefix . self::$graphs_table;

		$update_data = array();
		$format      = array();

		if ( isset( $data['title'] ) ) {
			$update_data['title'] = sanitize_text_field( $data['title'] );
			$format[]             = '%s';
		}

		if ( isset( $data['usgs_url'] ) ) {
			$update_data['usgs_url'] = esc_url_raw( $data['usgs_url'] );
			$format[]                = '%s';
		}

		if ( isset( $data['scrape_interval'] ) ) {
			$update_data['scrape_interval'] = absint( $data['scrape_interval'] );
			$format[]                       = '%d';
		}

		if ( isset( $data['is_enabled'] ) ) {
			$update_data['is_enabled'] = absint( $data['is_enabled'] );
			$format[]                  = '%d';
		}

		if ( isset( $data['date_start'] ) ) {
			$update_data['date_start'] = ! empty( $data['date_start'] ) ? sanitize_text_field( $data['date_start'] ) : null;
			$format[]                  = '%s';
		}

		if ( isset( $data['date_end'] ) ) {
			$update_data['date_end'] = ! empty( $data['date_end'] ) ? sanitize_text_field( $data['date_end'] ) : null;
			$format[]                = '%s';
		}

		if ( isset( $data['auto_update_dates'] ) ) {
			$update_data['auto_update_dates'] = absint( $data['auto_update_dates'] );
			$format[]                         = '%d';
		}

		if ( isset( $data['custom_css'] ) ) {
			$update_data['custom_css'] = wp_strip_all_tags( $data['custom_css'] );
			$format[]                  = '%s';
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table,
			$update_data,
			array( 'id' => $graph_id ),
			$format,
			array( '%d' )
		);

		// Clear WordPress caches to ensure frontend updates immediately.
		if ( false !== $result ) {
			wp_cache_delete( 'usgs_graph_' . $graph_id, 'usgs_water_levels' );
			wp_cache_delete( 'usgs_measurements_' . $graph_id, 'usgs_water_levels' );

			// Clear all page caches (works with common caching plugins).
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
		}

		return false !== $result;
	}

	/**
	 * Delete a graph configuration and all its measurements.
	 *
	 * @param int $graph_id Graph ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_graph( $graph_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::$graphs_table;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table,
			array( 'id' => $graph_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Save measurements for a graph.
	 *
	 * @param int   $graph_id     Graph ID.
	 * @param array $measurements Array of measurements with 'date' and 'value' keys.
	 * @return bool True on success, false on failure.
	 */
	public static function save_measurements( $graph_id, $measurements ) {
		global $wpdb;

		$table = $wpdb->prefix . self::$measurements_table;
		$debug = array();

		$debug[] = 'Called with graph_id: ' . $graph_id . ', measurements count: ' . count( $measurements );

		if ( empty( $measurements ) || ! is_array( $measurements ) ) {
			$debug[] = 'FAIL: measurements empty or not array';
			set_transient( 'usgs_wl_debug_' . $graph_id, $debug, 300 );
			return false;
		}

		// Show first measurement structure.
		if ( ! empty( $measurements ) ) {
			$debug[] = 'First measurement: ' . print_r( $measurements[0], true );
		}

		$values = array();
		foreach ( $measurements as $measurement ) {
			if ( ! isset( $measurement['date'] ) || ! isset( $measurement['value'] ) ) {
				$debug[] = 'Skipping measurement - keys: ' . implode( ', ', array_keys( $measurement ) );
				continue;
			}

			$date  = sanitize_text_field( $measurement['date'] );
			$value = floatval( $measurement['value'] );

			$values[] = $wpdb->prepare( '(%d, %s, %f)', $graph_id, $date, $value );
		}

		$debug[] = 'Prepared ' . count( $values ) . ' values to insert';

		if ( empty( $values ) ) {
			$debug[] = 'FAIL: values array empty after processing';
			set_transient( 'usgs_wl_debug_' . $graph_id, $debug, 300 );
			return false;
		}

		$values_string = implode( ', ', $values );
		$debug[] = 'First value: ' . ( ! empty( $values ) ? $values[0] : 'none' );

		$insert_query = "INSERT INTO $table (graph_id, measurement_date, water_level)
			VALUES $values_string
			ON DUPLICATE KEY UPDATE water_level = VALUES(water_level)";

		$debug[] = 'Full INSERT query (first 500 chars): ' . substr( $insert_query, 0, 500 );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $insert_query );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$debug[] = 'Query result: ' . var_export( $result, true );
		$debug[] = 'Last error: ' . ( $wpdb->last_error ? $wpdb->last_error : 'none' );
		$debug[] = 'Rows affected: ' . $wpdb->rows_affected;

		// Verify data was saved.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM $table WHERE graph_id = %d",
				$graph_id
			)
		);
		$debug[] = 'Final DB count: ' . $count;

		// Try deleting and re-inserting first row using simple INSERT.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DELETE FROM $table WHERE graph_id = $graph_id" );

		// Insert rows one by one using wpdb->insert().
		$inserted_count = 0;
		foreach ( $measurements as $measurement ) {
			if ( ! isset( $measurement['date'] ) || ! isset( $measurement['value'] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$insert_result = $wpdb->insert(
				$table,
				array(
					'graph_id'         => $graph_id,
					'measurement_date' => sanitize_text_field( $measurement['date'] ),
					'water_level'      => floatval( $measurement['value'] ),
				),
				array( '%d', '%s', '%f' )
			);

			if ( $insert_result ) {
				$inserted_count++;
			}
		}

		$debug[] = 'Inserted one-by-one: ' . $inserted_count . ' rows';

		// Check count again.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$final_count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM $table WHERE graph_id = %d",
				$graph_id
			)
		);
		$debug[] = 'Count after one-by-one insert: ' . $final_count;

		set_transient( 'usgs_wl_debug_' . $graph_id, $debug, 300 );

		// Clear caches so frontend updates immediately.
		if ( $inserted_count > 0 ) {
			wp_cache_delete( 'usgs_graph_' . $graph_id, 'usgs_water_levels' );
			wp_cache_delete( 'usgs_measurements_' . $graph_id, 'usgs_water_levels' );

			// Clear all page caches.
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
		}

		return $inserted_count > 0;
	}

	/**
	 * Get measurements for a graph.
	 *
	 * @param int $graph_id Graph ID.
	 * @param int $limit    Maximum number of measurements to return.
	 * @return array Array of measurements.
	 */
	public static function get_measurements( $graph_id, $limit = 1000 ) {
		global $wpdb;

		$table = $wpdb->prefix . self::$measurements_table;
		$limit = absint( $limit ); // Sanitize limit as integer.

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT measurement_date, water_level
				FROM $table
				WHERE graph_id = %d
				ORDER BY measurement_date ASC
				LIMIT $limit",
				$graph_id
			),
			ARRAY_A
		);

		return $results ? $results : array();
	}

	/**
	 * Delete old measurements for a graph.
	 *
	 * @param int $graph_id   Graph ID.
	 * @param int $days_to_keep Number of days of data to keep.
	 * @return bool True on success, false on failure.
	 */
	public static function prune_old_measurements( $graph_id, $days_to_keep = 365 ) {
		global $wpdb;

		$table      = $wpdb->prefix . self::$measurements_table;
		$cutoff_date = gmdate( 'Y-m-d', strtotime( "-{$days_to_keep} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $table WHERE graph_id = %d AND measurement_date < %s",
				$graph_id,
				$cutoff_date
			)
		);

		return false !== $result;
	}
}
