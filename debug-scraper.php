<?php
/**
 * USGS Water Levels - Scraper Debugging Script
 *
 * Upload this file to your WordPress root directory and access it via:
 * https://yoursite.com/debug-scraper.php
 *
 * DELETE THIS FILE after debugging for security!
 */

// Load WordPress
require_once( dirname(__FILE__) . '/wp-load.php' );

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You must be logged in as an administrator to run this script.' );
}

header( 'Content-Type: text/plain' );

echo "===========================================\n";
echo "USGS WATER LEVELS - DEBUGGING SCRIPT\n";
echo "===========================================\n\n";

// 1. Check Plugin Version
echo "1. PLUGIN VERSION CHECK\n";
echo "-------------------------------------------\n";
if ( defined( 'USGS_WATER_LEVELS_VERSION' ) ) {
	echo "Version: " . USGS_WATER_LEVELS_VERSION . "\n";
	if ( USGS_WATER_LEVELS_VERSION === '1.1.0' ) {
		echo "✓ Correct version (1.1.0)\n";
	} else {
		echo "✗ WRONG VERSION! Should be 1.1.0\n";
		echo "  Please reinstall the plugin.\n";
	}
} else {
	echo "✗ Plugin constant not defined. Plugin may not be activated.\n";
}
echo "\n";

// 2. Check if scraper class exists and has new API endpoint
echo "2. SCRAPER CLASS CHECK\n";
echo "-------------------------------------------\n";
if ( class_exists( 'USGS_Water_Levels_Scraper' ) ) {
	echo "✓ Scraper class exists\n";

	// Check if using new API
	if ( defined( 'USGS_Water_Levels_Scraper::API_ENDPOINT' ) ) {
		$endpoint = USGS_Water_Levels_Scraper::API_ENDPOINT;
		echo "API Endpoint: " . $endpoint . "\n";
		if ( strpos( $endpoint, 'api.waterdata.usgs.gov' ) !== false ) {
			echo "✓ Using NEW API (correct)\n";
		} else {
			echo "✗ Using OLD endpoint (wrong)\n";
		}
	} else {
		echo "✗ API_ENDPOINT constant not found\n";
		echo "  This means the old version is still loaded.\n";
	}
} else {
	echo "✗ Scraper class not found. Plugin not loaded properly.\n";
}
echo "\n";

// 3. Check Graph Configuration
echo "3. GRAPH CONFIGURATION\n";
echo "-------------------------------------------\n";
$graphs = USGS_Water_Levels_Database::get_all_graphs();
if ( empty( $graphs ) ) {
	echo "No graphs configured.\n";
} else {
	foreach ( $graphs as $graph ) {
		echo "Graph ID: " . $graph['id'] . "\n";
		echo "  Title: " . $graph['title'] . "\n";
		echo "  URL: " . $graph['usgs_url'] . "\n";
		echo "  Enabled: " . ( $graph['is_enabled'] ? 'Yes' : 'No' ) . "\n";
		echo "  Interval: " . $graph['scrape_interval'] . " hours\n";

		// Check URL format
		if ( strpos( $graph['usgs_url'], '#' ) !== false ) {
			echo "  ⚠ WARNING: URL contains # (fragment)\n";
			echo "     This is OK, the scraper will handle it.\n";
		}
		if ( strpos( $graph['usgs_url'], '?' ) !== false ) {
			echo "  ⚠ WARNING: URL contains ? (query string)\n";
			echo "     This is OK, the scraper will handle it.\n";
		}
	}
}
echo "\n";

// 4. Test API Connection
echo "4. API CONNECTION TEST\n";
echo "-------------------------------------------\n";
$test_url = 'https://api.waterdata.usgs.gov/ogcapi/v0/collections/field-measurements/items?f=json&monitoring_location_id=USGS-410858072171501&limit=1';
echo "Testing: " . $test_url . "\n\n";

$response = wp_remote_get( $test_url, array( 'timeout' => 30 ) );
if ( is_wp_error( $response ) ) {
	echo "✗ API Request Failed\n";
	echo "Error: " . $response->get_error_message() . "\n";
	echo "\nPossible causes:\n";
	echo "- Firewall blocking outbound HTTPS\n";
	echo "- allow_url_fopen disabled\n";
	echo "- cURL not installed\n";
	echo "- Server can't reach api.waterdata.usgs.gov\n";
} else {
	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	echo "HTTP Code: " . $code . "\n";
	echo "Response Length: " . strlen( $body ) . " bytes\n";

	if ( $code === 200 ) {
		echo "✓ API is accessible\n";

		$data = json_decode( $body, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			echo "✓ JSON parsed successfully\n";
			if ( isset( $data['features'] ) && ! empty( $data['features'] ) ) {
				echo "✓ Data contains features: " . count( $data['features'] ) . " measurement(s)\n";
			} else {
				echo "⚠ No features in response\n";
			}
		} else {
			echo "✗ JSON parse error: " . json_last_error_msg() . "\n";
		}
	} else {
		echo "✗ Unexpected HTTP code\n";
	}
}
echo "\n";

// 5. Test Scraper Function
echo "5. SCRAPER FUNCTION TEST\n";
echo "-------------------------------------------\n";
$test_monitoring_url = 'https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/';
echo "Testing with: " . $test_monitoring_url . "\n\n";

$result = USGS_Water_Levels_Scraper::scrape_usgs_data( $test_monitoring_url );

if ( is_wp_error( $result ) ) {
	echo "✗ SCRAPING FAILED\n";
	echo "Error Code: " . $result->get_error_code() . "\n";
	echo "Error Message: " . $result->get_error_message() . "\n\n";

	echo "Troubleshooting:\n";
	switch ( $result->get_error_code() ) {
		case 'invalid_url':
			echo "- Check the URL format\n";
			echo "- Should be: https://waterdata.usgs.gov/monitoring-location/USGS-XXXXXXXXX/\n";
			break;
		case 'http_error':
			echo "- API returned an error\n";
			echo "- Check if the site ID is correct\n";
			break;
		case 'no_data':
			echo "- Site may not have groundwater level data\n";
			echo "- Try a different monitoring location\n";
			break;
		case 'json_error':
			echo "- API response is not valid JSON\n";
			echo "- USGS API may have changed\n";
			break;
		default:
			echo "- Unknown error\n";
			echo "- Enable WP_DEBUG and check debug.log\n";
	}
} else {
	echo "✓ SCRAPING SUCCEEDED\n";
	echo "Measurements found: " . count( $result ) . "\n\n";

	if ( ! empty( $result ) ) {
		echo "Sample data (first 3 measurements):\n";
		$sample = array_slice( $result, 0, 3 );
		foreach ( $sample as $measurement ) {
			echo "  Date: " . $measurement['date'] . " | Value: " . $measurement['value'] . " ft\n";
		}
	}
}
echo "\n";

// 6. Check Last Scrape Log
echo "6. LAST SCRAPE LOG\n";
echo "-------------------------------------------\n";
if ( ! empty( $graphs ) ) {
	foreach ( $graphs as $graph ) {
		$log = USGS_Water_Levels_Scraper::get_scrape_log( $graph['id'] );
		echo "Graph #" . $graph['id'] . " (" . $graph['title'] . "):\n";
		if ( $log ) {
			echo "  Status: " . $log['status'] . "\n";
			echo "  Time: " . $log['timestamp'] . "\n";
			echo "  Message: " . $log['message'] . "\n";
		} else {
			echo "  No scrape log found (never scraped)\n";
		}
	}
} else {
	echo "No graphs to check\n";
}
echo "\n";

// 7. Database Check
echo "7. DATABASE CHECK\n";
echo "-------------------------------------------\n";
global $wpdb;
$graphs_table = $wpdb->prefix . 'usgs_wl_graphs';
$measurements_table = $wpdb->prefix . 'usgs_wl_measurements';

$graph_count = $wpdb->get_var( "SELECT COUNT(*) FROM $graphs_table" );
$measurement_count = $wpdb->get_var( "SELECT COUNT(*) FROM $measurements_table" );

echo "Graphs in database: " . $graph_count . "\n";
echo "Measurements in database: " . $measurement_count . "\n";
echo "\n";

// 8. PHP Environment
echo "8. PHP ENVIRONMENT\n";
echo "-------------------------------------------\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "allow_url_fopen: " . ( ini_get( 'allow_url_fopen' ) ? 'Enabled' : 'Disabled' ) . "\n";
echo "cURL: " . ( function_exists( 'curl_version' ) ? 'Available' : 'Not available' ) . "\n";
echo "JSON: " . ( function_exists( 'json_decode' ) ? 'Available' : 'Not available' ) . "\n";
echo "\n";

// Summary
echo "===========================================\n";
echo "SUMMARY\n";
echo "===========================================\n";

$issues = array();

if ( ! defined( 'USGS_WATER_LEVELS_VERSION' ) || USGS_WATER_LEVELS_VERSION !== '1.1.0' ) {
	$issues[] = "Plugin is not version 1.1.0 - reinstall required";
}

if ( is_wp_error( $result ) ) {
	$issues[] = "Scraper test failed: " . $result->get_error_message();
}

if ( empty( $issues ) ) {
	echo "✓ Everything looks good!\n";
	echo "\nNext steps:\n";
	echo "1. Go to WordPress Admin → USGS Water Levels\n";
	echo "2. Click 'Scrape Now' button\n";
	echo "3. Check if data appears\n";
} else {
	echo "✗ Issues found:\n";
	foreach ( $issues as $issue ) {
		echo "  - " . $issue . "\n";
	}
}

echo "\n";
echo "===========================================\n";
echo "⚠ SECURITY: DELETE THIS FILE AFTER DEBUGGING!\n";
echo "===========================================\n";
echo "\nFile location: " . __FILE__ . "\n";
?>
