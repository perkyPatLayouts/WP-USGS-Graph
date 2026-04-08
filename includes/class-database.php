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
			UNIQUE KEY unique_measurement (graph_id, measurement_date),
			CONSTRAINT fk_graph_id FOREIGN KEY (graph_id) REFERENCES $graphs_table(id) ON DELETE CASCADE
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
		$wpdb->query( "DROP TABLE IF EXISTS $measurements_table" );
		$wpdb->query( "DROP TABLE IF EXISTS $graphs_table" );
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
			'title'           => '',
			'usgs_url'        => '',
			'scrape_interval' => 24,
			'is_enabled'      => 1,
			'custom_css'      => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$table,
			array(
				'title'           => sanitize_text_field( $data['title'] ),
				'usgs_url'        => esc_url_raw( $data['usgs_url'] ),
				'scrape_interval' => absint( $data['scrape_interval'] ),
				'is_enabled'      => absint( $data['is_enabled'] ),
				'custom_css'      => wp_strip_all_tags( $data['custom_css'] ),
			),
			array( '%s', '%s', '%d', '%d', '%s' )
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

		if ( empty( $measurements ) || ! is_array( $measurements ) ) {
			return false;
		}

		$success_count = 0;
		$error_count   = 0;

		// Insert measurements one at a time for SQLite compatibility.
		foreach ( $measurements as $measurement ) {
			if ( ! isset( $measurement['date'] ) || ! isset( $measurement['value'] ) ) {
				continue;
			}

			$date  = sanitize_text_field( $measurement['date'] );
			$value = floatval( $measurement['value'] );

			// Try to insert the measurement.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert(
				$table,
				array(
					'graph_id'         => $graph_id,
					'measurement_date' => $date,
					'water_level'      => $value,
				),
				array( '%d', '%s', '%f' )
			);

			if ( false === $result ) {
				// Insert failed, likely due to duplicate key constraint.
				// Try to update the existing record instead.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$update_result = $wpdb->update(
					$table,
					array( 'water_level' => $value ),
					array(
						'graph_id'         => $graph_id,
						'measurement_date' => $date,
					),
					array( '%f' ),
					array( '%d', '%s' )
				);

				if ( false !== $update_result ) {
					++$success_count;
				} else {
					++$error_count;
				}
			} else {
				++$success_count;
			}
		}

		// Return true if at least some measurements were saved successfully.
		return $success_count > 0;
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT measurement_date, water_level
				FROM $table
				WHERE graph_id = %d
				ORDER BY measurement_date ASC
				LIMIT %d",
				$graph_id,
				$limit
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
