=== USGS Water Levels ===
Contributors: yourusername
Tags: usgs, water, monitoring, charts, graphs, gutenberg, shortcode
Requires at least: 6.2
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 2.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scrape USGS water monitoring data and display it as interactive graphs via Gutenberg blocks and shortcodes.

== Description ==

USGS Water Levels is a WordPress plugin that automatically scrapes water level measurement data from USGS monitoring locations and displays it as beautiful, interactive charts using Gutenberg blocks or shortcodes.

**Features:**

* Automatic periodic scraping via USGS OGC API
* Three chart types: Line, Area, and Bar charts
* Gutenberg block for modern editor
* Shortcode support for Classic Editor
* Date range filtering for historical data
* Configurable scrape intervals (hourly to weekly)
* Multiple graph configurations
* Customizable colors and dimensions
* Responsive charts using Chart.js
* Historical data preservation (no auto-pruning)
* Security hardened with prepared statements
* Admin interface for managing graphs

**Usage:**

1. Install and activate the plugin
2. Go to USGS Water Levels in the admin menu
3. Add a new graph configuration with a USGS monitoring location URL
4. Display your graph using either:
   - Gutenberg block: "USGS Water Level Graph"
   - Shortcode: [usgs_water_level id="1" chart_type="line"]
5. Choose chart type (Line, Area, or Bar) and customize colors

**Chart Types:**
* Line Chart - Clean line with points, best for trends
* Area Chart - Filled gradient, emphasizes magnitude
* Bar Chart - Vertical bars, best for discrete data

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

= What chart types are available? =

Three types: Line Chart (clean line with points), Area Chart (filled gradient), and Bar Chart (vertical bars). Choose based on your data visualization needs.

= Can I use this with Classic Editor? =

Yes! Use the shortcode: [usgs_water_level id="1" chart_type="area" width="100%" line_color="#0073aa"]

= Can I customize the graph appearance? =

Yes! The block includes settings for chart type, width, line color, background color, axis color, and label color.

= How much data is stored? =

The plugin preserves all historical data without automatic pruning. Use date range filtering if you want to limit displayed data.

= Does this work with date ranges? =

Yes! You can configure optional start/end dates when creating a graph to limit the data scraped from USGS.

== Screenshots ==

1. Admin settings page showing configured graphs
2. Graph configuration form
3. Gutenberg block in the editor
4. Frontend display of water level chart

== Changelog ==

= 2.2.1 =
* Changed: Removed 30 measurement limit - now displays all available data
* Performance: Better historical data analysis with unlimited measurements
* Note: Data still limited by API (1000 per request) and optional date ranges

= 2.2.0 =
* Added: Chart type selection - Line, Area, or Bar charts
* Added: chart_type parameter to shortcode and block
* Enhanced: Frontend Chart.js rendering for all three chart types
* Updated: Admin UI with chart type documentation

= 2.1.3 =
* Fixed: CRITICAL - Date range HTTP 400 errors
* Fixed: Changed to OGC API standard datetime parameter format
* Fixed: Supports datetime=START/END range format

= 2.1.2 =
* Fixed: Data accuracy - excluded incompatible NGVD29 datum (72019)
* Fixed: Now only accepts 62610 and 62611 for consistent depth values

= 2.1.1 =
* Fixed: Expanded parameter code support to include 62611 (NAVD88)

= 2.1.0 =
* Added: Shortcode support for Classic Editor
* Added: [usgs_water_level] shortcode with full parameter support
* Added: Shortcode column in admin for easy copy/paste

= 2.0.2 =
* Security: Fixed SQL injection vulnerabilities
* Security: Fixed XSS in custom CSS output
* Security: Added date validation
* Changed: Requires WordPress 6.2+ for security features

= 1.1.1 =
* Fixed: MySQL 8.0.20+ compatibility issue
* Fixed: Deprecated VALUES() function in ON DUPLICATE KEY UPDATE

= 1.1.0 =
* CRITICAL: Migrated to USGS OGC API
* Fixed: Complete rewrite using JSON API

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.2.1 =
Removes artificial 30 measurement limit. Charts now display complete historical data.

= 2.2.0 =
Adds chart type selection (Line/Area/Bar). Choose the best visualization for your data.

= 2.1.3 =
CRITICAL: Fixes date range filtering. Update immediately if using date ranges.

= 2.0.2 =
SECURITY UPDATE: Fixes SQL injection and XSS vulnerabilities. Update immediately.
