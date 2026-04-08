<?php
/**
 * Cron management for USGS Water Levels plugin.
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron class for managing scheduled scraping tasks.
 */
class USGS_Water_Levels_Cron {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'usgs_water_levels_scrape_cron';

	/**
	 * Single instance of the class.
	 *
	 * @var USGS_Water_Levels_Cron
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return USGS_Water_Levels_Cron
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Register cron schedules.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );

		// Hook the scraping function to the cron event.
		add_action( self::CRON_HOOK, array( $this, 'run_scheduled_scrape' ) );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function add_cron_schedules( $schedules ) {
		// Add hourly intervals from 1 to 24 hours.
		for ( $hours = 1; $hours <= 24; $hours++ ) {
			$schedules[ "usgs_every_{$hours}_hours" ] = array(
				'interval' => $hours * HOUR_IN_SECONDS,
				'display'  => sprintf(
					/* translators: %d: number of hours */
					__( 'Every %d Hours', 'usgs-water-levels' ),
					$hours
				),
			);
		}

		return $schedules;
	}

	/**
	 * Schedule cron events on plugin activation.
	 */
	public static function schedule_events() {
		// Schedule main cron event if not already scheduled.
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Clear all scheduled cron events on plugin deactivation.
	 */
	public static function clear_scheduled_events() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}

		// Clear all instances of the event.
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Run scheduled scraping for all enabled graphs.
	 *
	 * This function is called by WP-Cron.
	 */
	public function run_scheduled_scrape() {
		$graphs = USGS_Water_Levels_Database::get_enabled_graphs();

		foreach ( $graphs as $graph ) {
			// Check if it's time to scrape this graph based on its interval.
			if ( ! $this->should_scrape_graph( $graph ) ) {
				continue;
			}

			// Run scraping in the background.
			USGS_Water_Levels_Scraper::scrape_and_save( $graph['id'] );
		}
	}

	/**
	 * Check if a graph should be scraped based on its interval.
	 *
	 * @param array $graph Graph configuration.
	 * @return bool True if graph should be scraped, false otherwise.
	 */
	private function should_scrape_graph( $graph ) {
		$graph_id = $graph['id'];
		$interval = absint( $graph['scrape_interval'] );

		if ( $interval <= 0 ) {
			$interval = 24; // Default to 24 hours.
		}

		// Get last scrape time from transient.
		$last_scrape_key  = 'usgs_wl_last_scrape_' . $graph_id;
		$last_scrape_time = get_transient( $last_scrape_key );

		// If never scraped, scrape now.
		if ( false === $last_scrape_time ) {
			set_transient( $last_scrape_key, time(), YEAR_IN_SECONDS );
			return true;
		}

		// Check if enough time has passed based on the interval.
		$time_since_last_scrape = time() - $last_scrape_time;
		$interval_seconds       = $interval * HOUR_IN_SECONDS;

		if ( $time_since_last_scrape >= $interval_seconds ) {
			set_transient( $last_scrape_key, time(), YEAR_IN_SECONDS );
			return true;
		}

		return false;
	}

	/**
	 * Manually trigger scraping for a specific graph.
	 *
	 * @param int $graph_id Graph ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function manual_scrape( $graph_id ) {
		// Force update last scrape time.
		$last_scrape_key = 'usgs_wl_last_scrape_' . $graph_id;
		set_transient( $last_scrape_key, time(), YEAR_IN_SECONDS );

		// Run scraping.
		return USGS_Water_Levels_Scraper::scrape_and_save( $graph_id );
	}

	/**
	 * Get next scheduled scrape time for a graph.
	 *
	 * @param int $graph_id Graph ID.
	 * @return int|false Timestamp of next scrape or false if never scraped.
	 */
	public static function get_next_scrape_time( $graph_id ) {
		$graph = USGS_Water_Levels_Database::get_graph_config( $graph_id );

		if ( ! $graph ) {
			return false;
		}

		$last_scrape_key  = 'usgs_wl_last_scrape_' . $graph_id;
		$last_scrape_time = get_transient( $last_scrape_key );

		if ( false === $last_scrape_time ) {
			return false;
		}

		$interval         = absint( $graph['scrape_interval'] );
		$interval_seconds = $interval * HOUR_IN_SECONDS;

		return $last_scrape_time + $interval_seconds;
	}

	/**
	 * Get last scrape time for a graph.
	 *
	 * @param int $graph_id Graph ID.
	 * @return int|false Timestamp of last scrape or false if never scraped.
	 */
	public static function get_last_scrape_time( $graph_id ) {
		$last_scrape_key  = 'usgs_wl_last_scrape_' . $graph_id;
		$last_scrape_time = get_transient( $last_scrape_key );

		return $last_scrape_time;
	}
}
