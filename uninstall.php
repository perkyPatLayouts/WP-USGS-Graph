<?php
/**
 * Uninstall script for USGS Water Levels plugin.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly or not called during uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load database class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';

/**
 * Delete all plugin data.
 */
function usgs_water_levels_uninstall() {
	// Drop database tables.
	USGS_Water_Levels_Database::drop_tables();

	// Delete all transients.
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( 'usgs_wl_' ) . '%'
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Clear any cached data.
	wp_cache_flush();
}

// Run uninstall only if user confirmed.
usgs_water_levels_uninstall();
