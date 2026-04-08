<?php
/**
 * USGS Water Levels - Database Fix Script
 *
 * This script recreates the database tables and tests insertion.
 * Upload to WordPress root and access via browser.
 * DELETE after use!
 */

// Load WordPress
require_once( dirname(__FILE__) . '/wp-load.php' );

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You must be logged in as an administrator to run this script.' );
}

header( 'Content-Type: text/plain' );

echo "===========================================\n";
echo "DATABASE FIX SCRIPT\n";
echo "===========================================\n\n";

global $wpdb;

// Table names
$graphs_table = $wpdb->prefix . 'usgs_wl_graphs';
$measurements_table = $wpdb->prefix . 'usgs_wl_measurements';

// 1. Check if tables exist
echo "1. CHECKING TABLES\n";
echo "-------------------------------------------\n";

$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$graphs_table}'" );
if ( empty( $tables ) ) {
	echo "✗ Graphs table does NOT exist\n";
} else {
	echo "✓ Graphs table exists: {$graphs_table}\n";
}

$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$measurements_table}'" );
if ( empty( $tables ) ) {
	echo "✗ Measurements table does NOT exist\n";
} else {
	echo "✓ Measurements table exists: {$measurements_table}\n";
}
echo "\n";

// 2. Show table structure
echo "2. TABLE STRUCTURE\n";
echo "-------------------------------------------\n";
$columns = $wpdb->get_results( "DESCRIBE {$measurements_table}" );
if ( $columns ) {
	echo "Measurements table columns:\n";
	foreach ( $columns as $col ) {
		echo "  - {$col->Field} ({$col->Type})\n";
	}
} else {
	echo "Could not describe measurements table\n";
}
echo "\n";

// 3. Check for foreign key constraints
echo "3. FOREIGN KEY CHECK\n";
echo "-------------------------------------------\n";
$fk_query = "SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    TABLE_NAME = '{$measurements_table}'
    AND REFERENCED_TABLE_NAME IS NOT NULL";

$fks = $wpdb->get_results( $fk_query );
if ( ! empty( $fks ) ) {
	echo "Foreign keys found:\n";
	foreach ( $fks as $fk ) {
		echo "  - {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
	}
} else {
	echo "No foreign keys found (might be the issue)\n";
}
echo "\n";

// 4. Test a simple insert
echo "4. TEST INSERT\n";
echo "-------------------------------------------\n";

// Get a graph ID
$graph_id = $wpdb->get_var( "SELECT id FROM {$graphs_table} LIMIT 1" );
if ( ! $graph_id ) {
	echo "✗ No graph found to test with\n";
} else {
	echo "Testing with graph ID: {$graph_id}\n";

	// Try a simple insert
	$test_date = '2026-01-01';
	$test_value = 123.45;

	// Delete test data first if it exists
	$wpdb->delete(
		$measurements_table,
		array(
			'graph_id' => $graph_id,
			'measurement_date' => $test_date,
		),
		array( '%d', '%s' )
	);

	// Try insert
	$result = $wpdb->insert(
		$measurements_table,
		array(
			'graph_id' => $graph_id,
			'measurement_date' => $test_date,
			'water_level' => $test_value,
		),
		array( '%d', '%s', '%f' )
	);

	if ( $result === false ) {
		echo "✗ INSERT FAILED\n";
		echo "Error: " . $wpdb->last_error . "\n";
		echo "Query: " . $wpdb->last_query . "\n";
	} else {
		echo "✓ INSERT SUCCEEDED\n";
		echo "Inserted ID: " . $wpdb->insert_id . "\n";

		// Verify it's there
		$check = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$measurements_table} WHERE graph_id = %d AND measurement_date = %s",
				$graph_id,
				$test_date
			)
		);

		if ( $check ) {
			echo "✓ Data verified in database\n";
			echo "  Date: {$check->measurement_date}, Value: {$check->water_level}\n";

			// Clean up test data
			$wpdb->delete(
				$measurements_table,
				array(
					'graph_id' => $graph_id,
					'measurement_date' => $test_date,
				),
				array( '%d', '%s' )
			);
			echo "✓ Test data cleaned up\n";
		} else {
			echo "✗ Data not found after insert (strange!)\n";
		}
	}
}
echo "\n";

// 5. Test the save_measurements function
echo "5. TEST SAVE_MEASUREMENTS FUNCTION\n";
echo "-------------------------------------------\n";

if ( $graph_id ) {
	$test_measurements = array(
		array( 'date' => '2026-01-01', 'value' => 100.50 ),
		array( 'date' => '2026-01-02', 'value' => 101.25 ),
		array( 'date' => '2026-01-03', 'value' => 102.75 ),
	);

	echo "Testing with " . count( $test_measurements ) . " measurements...\n";

	$result = USGS_Water_Levels_Database::save_measurements( $graph_id, $test_measurements );

	if ( $result ) {
		echo "✓ save_measurements() returned TRUE\n";

		// Check if actually saved
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$measurements_table} WHERE graph_id = %d AND measurement_date >= '2026-01-01' AND measurement_date <= '2026-01-03'",
				$graph_id
			)
		);

		echo "Rows in database: {$count}\n";

		if ( $count == 3 ) {
			echo "✓ All test measurements saved correctly\n";

			// Clean up
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$measurements_table} WHERE graph_id = %d AND measurement_date >= '2026-01-01' AND measurement_date <= '2026-01-03'",
					$graph_id
				)
			);
			echo "✓ Test data cleaned up\n";
		} else {
			echo "✗ Expected 3 rows, found {$count}\n";
		}
	} else {
		echo "✗ save_measurements() returned FALSE\n";
		echo "Error: " . $wpdb->last_error . "\n";
	}
}
echo "\n";

// 6. Recreate tables (optional)
echo "6. RECREATE TABLES (IF NEEDED)\n";
echo "-------------------------------------------\n";
echo "Do you want to recreate the tables?\n";
echo "This will DELETE all existing data!\n";
echo "\n";
echo "To recreate tables:\n";
echo "1. Add ?recreate=yes to the URL\n";
echo "2. Example: https://yoursite.com/fix-database.php?recreate=yes\n";
echo "\n";

if ( isset( $_GET['recreate'] ) && $_GET['recreate'] === 'yes' ) {
	echo "RECREATING TABLES...\n\n";

	// Drop foreign key first
	$wpdb->query( "ALTER TABLE {$measurements_table} DROP FOREIGN KEY fk_graph_id" );

	// Drop tables
	$wpdb->query( "DROP TABLE IF EXISTS {$measurements_table}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$graphs_table}" );

	echo "✓ Old tables dropped\n";

	// Recreate using the plugin function
	USGS_Water_Levels_Database::create_tables();

	echo "✓ Tables recreated\n";

	// Verify
	$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$measurements_table}'" );
	if ( ! empty( $tables ) ) {
		echo "✓ Measurements table exists\n";
	}

	echo "\nNOTE: All your graphs and measurements were deleted!\n";
	echo "You need to:\n";
	echo "1. Add your graph configuration again\n";
	echo "2. Click 'Scrape Now'\n";
} else {
	echo "Tables NOT recreated (add ?recreate=yes to URL to recreate)\n";
}
echo "\n";

echo "===========================================\n";
echo "⚠ DELETE THIS FILE AFTER USE!\n";
echo "===========================================\n";
?>
