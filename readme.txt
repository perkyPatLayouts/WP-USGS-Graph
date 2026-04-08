=== USGS Water Levels ===
Contributors: yourusername
Tags: usgs, water, monitoring, charts, graphs, gutenberg
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scrape USGS water monitoring data and display it as interactive graphs via Gutenberg blocks.

== Description ==

USGS Water Levels is a WordPress plugin that automatically scrapes water level measurement data from USGS monitoring locations and displays it as beautiful, interactive line charts using Gutenberg blocks.

**Features:**

* Automatic periodic scraping of USGS water monitoring data
* Configurable scrape intervals (hourly to weekly)
* Multiple graph configurations
* Gutenberg block for easy graph insertion
* Customizable colors and dimensions
* Responsive charts using Chart.js
* Data stored in WordPress database
* Admin interface for managing graphs

**Usage:**

1. Install and activate the plugin
2. Go to USGS Water Levels in the admin menu
3. Add a new graph configuration with a USGS monitoring location URL
4. Use the "USGS Water Level Graph" block in the block editor
5. Select your configured graph and customize the appearance

**Example USGS URL:**
https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/usgs-water-levels/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your graphs in USGS Water Levels > Settings
4. Insert the block in any post or page

== Frequently Asked Questions ==

= Where do I find USGS monitoring location URLs? =

Visit https://waterdata.usgs.gov/ and search for a monitoring location. Copy the full URL.

= How often does the plugin scrape data? =

You can configure the scrape interval for each graph individually, from 1 hour to 168 hours (7 days).

= Can I customize the graph appearance? =

Yes! The block includes settings for width, line color, background color, axis color, and label color.

= How much data is stored? =

By default, the plugin keeps the last 2 years of data. Older measurements are automatically pruned.

== Screenshots ==

1. Admin settings page showing configured graphs
2. Graph configuration form
3. Gutenberg block in the editor
4. Frontend display of water level chart

== Changelog ==

= 1.1.0 =
* CRITICAL UPDATE: Migrated to new USGS OGC API (api.waterdata.usgs.gov)
* Fixed: Scraping now works with current USGS infrastructure
* Complete rewrite of scraper to use JSON API instead of HTML parsing
* Improved error messages and debugging
* Added CHANGELOG.md and troubleshooting guide
* Increased API timeout for better reliability
* Better URL parsing and validation

= 1.0.0 =
* Initial release
* Automatic USGS data scraping
* Gutenberg block for graph display
* Admin interface for graph management
* Configurable scrape intervals
* Customizable chart colors and dimensions

== Upgrade Notice ==

= 1.0.0 =
Initial release of USGS Water Levels plugin.
