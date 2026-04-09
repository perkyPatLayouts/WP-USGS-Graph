=== USGS Water Levels ===
Contributors: yourusername
Tags: usgs, water, monitoring, charts, graphs, gutenberg, shortcode
Requires at least: 6.2
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
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
* Rolling date windows - auto-update date ranges to maintain consistent time periods
* Configurable scrape intervals (hourly to weekly)
* Multiple graph configurations
* Customizable colors and dimensions
* Responsive charts using Chart.js
* Unlimited historical data (no artificial limits)
* Historical data preservation (no auto-pruning)
* Automatic database migration system
* Comprehensive cache clearing for all major caching plugins
* Security hardened with prepared statements
* Admin interface for managing graphs
* Instant frontend updates after editing graphs

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

= What is the rolling date window feature? =

Enable "Auto-update date range (rolling window)" to automatically keep your graphs current. The end date updates to today, and the start date moves forward by the same amount, maintaining a consistent time window (e.g., always showing the last 2 years of data).

= Do my graph edits update immediately on the frontend? =

Yes! The plugin includes comprehensive cache clearing for all major WordPress caching plugins (WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache, and more). Changes appear immediately without needing to re-save pages.

== Screenshots ==

1. Admin settings page showing configured graphs
2. Graph configuration form
3. Gutenberg block in the editor
4. Frontend display of water level chart

== Changelog ==

= 1.0.0 =
* Initial production release
* Feature: Three chart types - Line, Area, and Bar charts
* Feature: Rolling date windows for auto-updating date ranges
* Feature: Unlimited historical data display
* Feature: Comprehensive cache clearing for all major caching plugins
* Feature: Automatic database migration system
* Feature: Gutenberg block with full customization
* Feature: Classic Editor shortcode support
* Security: SQL injection and XSS protection
* Performance: Instant frontend updates after editing graphs
* Compatibility: MySQL 8.0.20+ support
* API: Uses modern USGS OGC API

== Upgrade Notice ==

= 1.0.0 =
Production-ready release with all features working correctly. Includes automatic database migration and comprehensive caching support.
