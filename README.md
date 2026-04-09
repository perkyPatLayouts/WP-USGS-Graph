# USGS Water Levels - WordPress Plugin

A WordPress plugin that scrapes USGS water monitoring data and displays it as interactive graphs using Gutenberg blocks and shortcodes.

**No build process required!** This plugin uses vanilla JavaScript and works on shared hosting without Node.js/npm.

## Features

- **Automatic Data Scraping**: Periodically scrapes USGS water level measurements via OGC API
- **Multiple Graphs**: Configure multiple monitoring locations
- **Gutenberg Block**: Easy-to-use block for displaying graphs
- **Shortcode Support**: Classic Editor compatible with `[usgs_water_level]` shortcode
- **Date Range Filtering**: Limit scraped data to specific date ranges
- **Customizable**: Set colors, dimensions, and scrape intervals
- **Responsive Charts**: Beautiful line charts using Chart.js
- **Accurate Data**: Filters for consistent depth measurements (62610/62611 parameter codes)
- **Database Storage**: Efficient data storage for historical measurements
- **No Build Required**: Works out of the box on shared hosting

## Requirements

- WordPress 6.2 or higher
- PHP 8.0 or higher
- MySQL 5.7 or higher
- **No Node.js or npm required**

## Installation

### Quick Install

1. **Upload the plugin folder to WordPress:**
   ```bash
   cp -r usgs-water-levels /path/to/wordpress/wp-content/plugins/
   ```

2. **Activate the plugin:**
   ```bash
   wp plugin activate usgs-water-levels
   ```

   Or activate through WordPress admin: Plugins → Activate "USGS Water Levels"

3. **You're done!** Chart.js is already included in the plugin.

### Manual Upload (Shared Hosting)

1. Download or create a zip file of the `usgs-water-levels` folder
2. Log into WordPress admin
3. Go to Plugins → Add New → Upload Plugin
4. Choose the zip file and click Install Now
5. Click Activate Plugin

## Usage

### 1. Configure Graphs

1. Go to **USGS Water Levels** in WordPress admin
2. Click **Add New Graph**
3. Fill in the form:
   - **Title**: Name for your graph
   - **USGS URL**: Full URL of the monitoring location
   - **Scrape Interval**: How often to fetch data (in hours)
   - **Status**: Enable/disable scraping
   - **Date Range** (Optional): Limit data to specific dates
     - Leave blank to scrape all available historical data
     - Format: YYYY-MM-DD
   - **Custom CSS**: Optional styling

Example USGS URL:
```
https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/
```

### 2. Display Graphs

#### Option A: Gutenberg Block (Modern Editor)

1. Edit any post or page
2. Add the **USGS Water Level Graph** block
3. Select your configured graph from the dropdown
4. Choose chart type: Line, Area, or Bar
5. Customize colors and width in the block settings
6. Publish!

#### Option B: Shortcode (Classic Editor)

Use the shortcode in any post, page, or widget:

```
[usgs_water_level id="1"]
```

**Parameters:**
- `id` (required) - Graph ID from settings page
- `chart_type` (optional) - Chart type: "line", "area", or "bar" (default: "line")
- `width` (optional) - Graph width (default: "100%")
  - Examples: `"600px"`, `"80%"`, `"50vw"`
- `line_color` (optional) - Line/bar color (default: "#0073aa")
- `class` (optional) - Additional CSS classes

**Examples:**
```
[usgs_water_level id="1"]
[usgs_water_level id="1" chart_type="area"]
[usgs_water_level id="1" chart_type="bar" line_color="#ff6600"]
[usgs_water_level id="1" chart_type="line" width="600px"]
[usgs_water_level id="1" chart_type="area" width="80%" line_color="#0073aa"]
```

### 3. Manual Scraping

- Click **Scrape Now** next to any graph to fetch data immediately
- Useful for testing or updating data on demand
- Each graph shows its shortcode in the admin table for easy copy/paste

## File Structure

```
usgs-water-levels/
├── usgs-water-levels.php      # Main plugin file
├── uninstall.php               # Uninstall cleanup
├── readme.txt                  # WordPress.org readme
├── includes/                   # PHP classes
│   ├── class-database.php      # Database operations
│   ├── class-scraper.php       # USGS data scraping
│   ├── class-cron.php          # Scheduled tasks
│   ├── class-settings.php      # Admin settings page
│   └── class-rest-api.php      # REST API endpoints
├── blocks/water-level-graph/   # Gutenberg block
│   ├── block.json              # Block metadata
│   ├── index.js                # Block editor script (vanilla JS)
│   ├── view.js                 # Frontend rendering
│   └── style.css               # Block styles
├── admin/                      # Admin assets
│   └── admin.css               # Admin styles
└── assets/                     # Frontend assets
    └── js/
        └── chart.min.js        # Chart.js library (included)
```

## Database Schema

### `wp_usgs_wl_graphs`
Stores graph configurations.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| title | varchar(255) | Graph title |
| usgs_url | text | USGS monitoring URL |
| scrape_interval | int | Hours between scrapes |
| is_enabled | tinyint | Enable/disable flag |
| date_start | date | Optional start date for data range |
| date_end | date | Optional end date for data range |
| custom_css | text | Custom CSS for graph |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |

### `wp_usgs_wl_measurements`
Stores water level measurements.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| graph_id | bigint | Foreign key to graphs |
| measurement_date | date | Date of measurement |
| water_level | decimal(10,2) | Water level in feet |
| created_at | datetime | Creation timestamp |

## WP-CLI Commands

```bash
# View plugin status
wp plugin status usgs-water-levels

# View cron schedule
wp cron event list

# Manually trigger scraping cron
wp cron event run usgs_water_levels_scrape_cron

# Database operations
wp db query "SELECT * FROM wp_usgs_wl_graphs"
wp db query "SELECT COUNT(*) FROM wp_usgs_wl_measurements"

# Check for scraping errors
wp transient get usgs_wl_scrape_log_1
```

## Troubleshooting

### Scraping Not Working

1. **Check if WP-Cron is running:**
   ```bash
   wp cron test
   ```

2. **Verify the USGS URL** is accessible in a browser

3. **Check the scrape log** on the admin page for errors

4. **Enable WP_DEBUG** in wp-config.php to see detailed errors:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

5. **Manually trigger a scrape** using the "Scrape Now" button

6. **Date range issues**: If you get "HTTP 400" errors:
   - Check that your date range is valid (YYYY-MM-DD format)
   - Try removing the date range to test without filtering
   - The plugin uses OGC API `datetime` parameter format

### No Data / Empty Measurements

1. **Check parameter codes**: The plugin only accepts consistent depth measurements:
   - ✅ 62610 (Standard depth below land surface)
   - ✅ 62611 (Depth below land surface, NAVD88)
   - ❌ 72019 (Excluded - uses incompatible NGVD29 datum)
   - ❌ 72020 (Excluded - elevation above datum, not depth)

2. **Verify your monitoring location has groundwater data**:
   - Visit your USGS URL in a browser
   - Look for "Field Measurements" tab
   - Check if parameter code 62610 or 62611 exists

3. **Run debug script** to see what's happening:
   ```bash
   php wp-content/plugins/usgs-water-levels/debug-scrape-now.php
   ```

### Block Not Displaying

1. Verify a graph is selected in block settings

2. Check if the graph has data:
   ```bash
   wp db query "SELECT * FROM wp_usgs_wl_measurements WHERE graph_id = 1"
   ```

3. Clear WordPress cache:
   ```bash
   wp cache flush
   ```

### Charts Not Rendering

1. Verify Chart.js is present:
   ```bash
   ls -lh wp-content/plugins/usgs-water-levels/assets/js/chart.min.js
   ```

2. Check browser console for JavaScript errors

3. Ensure the block is actually on the page (not just saved but not displayed)

### WP-Cron Issues

If WP-Cron is disabled on your server, you can:

1. **Use real cron** - Add to server crontab:
   ```bash
   */15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron
   ```

2. **Manually trigger** when needed:
   ```bash
   wp cron event run usgs_water_levels_scrape_cron
   ```

## Security

- All inputs are sanitized and validated
- Nonces protect admin forms
- Database queries use prepared statements
- User capabilities are checked
- XSS protection with escaping
- Follows WordPress Coding Standards

## Performance

- Data is cached using WordPress transients
- Historical measurements are preserved (no automatic pruning)
- Scraping respects configured intervals to avoid API overload
- Charts load only when block is present on the page
- Minimal database queries using indexed columns
- Supports unlimited measurements (limited only by API response)

## Customization

### Custom Graph Styling

Add custom CSS in the graph settings:

```css
.usgs-water-levels-chart-wrapper {
    border: 2px solid #0073aa;
    padding: 20px;
    border-radius: 8px;
}
```

### Modify Chart Appearance

Edit `blocks/water-level-graph/view.js` to customize Chart.js options.

### Change Color Scheme

Use the block's color pickers in the editor sidebar, or set defaults in `block.json`.

## License

GPL v2 or later

## Credits

- Uses [Chart.js](https://www.chartjs.org/) for rendering charts
- Data from [USGS Water Data](https://waterdata.usgs.gov/)

## Support

For WordPress plugin support:
- WordPress Plugin Handbook: https://developer.wordpress.org/plugins/
- WP-CLI Documentation: https://wp-cli.org/

For USGS data questions:
- USGS Water Data: https://waterdata.usgs.gov/
