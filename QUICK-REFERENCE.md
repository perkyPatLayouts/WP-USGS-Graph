# USGS Water Levels - Quick Reference

One-page reference for common tasks and commands.

---

## Installation

```bash
# Upload and activate
cp -r usgs-water-levels /path/to/wp-content/plugins/
wp plugin activate usgs-water-levels
```

---

## Adding a Graph (Admin)

1. Go to **USGS Water Levels** → **Add New Graph**
2. Fill in:
   - Title: "My Well"
   - USGS URL: `https://waterdata.usgs.gov/monitoring-location/USGS-XXXXXXXXX/`
   - Interval: 24 hours
   - Enable scraping: ✓
3. Click **Add Graph**
4. Click **Scrape Now** to test

---

## Using the Block

1. Edit post/page → Add block
2. Search "USGS Water Level Graph"
3. Select graph from dropdown
4. Customize colors/width in sidebar
5. Publish

---

## WP-CLI Commands

```bash
# Plugin management
wp plugin status usgs-water-levels
wp plugin deactivate usgs-water-levels
wp plugin activate usgs-water-levels

# Database
wp db query "SELECT * FROM wp_usgs_wl_graphs"
wp db query "SELECT COUNT(*) FROM wp_usgs_wl_measurements"
wp db export

# Cron
wp cron event list | grep usgs
wp cron event run usgs_water_levels_scrape_cron
wp cron test

# Cache
wp cache flush
wp rewrite flush

# Transients
wp transient get usgs_wl_scrape_log_1
wp transient list | grep usgs_wl

# REST API
wp rest get /usgs-water-levels/v1/graphs --user=admin
wp rest get /usgs-water-levels/v1/graphs/1 --user=admin
```

---

## File Locations

```
Plugin Directory:
/wp-content/plugins/usgs-water-levels/

Main File:
/wp-content/plugins/usgs-water-levels/usgs-water-levels.php

Chart.js:
/wp-content/plugins/usgs-water-levels/assets/js/chart.min.js

Debug Log:
/wp-content/debug.log

Database Tables:
wp_usgs_wl_graphs
wp_usgs_wl_measurements
```

---

## Troubleshooting

### No Data Showing

```bash
# Check if graph exists
wp db query "SELECT * FROM wp_usgs_wl_graphs WHERE id = 1"

# Check if measurements exist
wp db query "SELECT COUNT(*) FROM wp_usgs_wl_measurements WHERE graph_id = 1"

# Manually scrape
wp eval "print_r(USGS_Water_Levels_Scraper::scrape_and_save(1));"
```

### Charts Not Rendering

```bash
# Verify Chart.js exists
ls -lh wp-content/plugins/usgs-water-levels/assets/js/chart.min.js

# Download if missing
curl -o wp-content/plugins/usgs-water-levels/assets/js/chart.min.js \
  https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js

# Clear cache
wp cache flush

# Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
```

### Cron Not Running

```bash
# Check if event scheduled
wp cron event list | grep usgs

# Run manually
wp cron event run usgs_water_levels_scrape_cron

# Test cron system
wp cron test

# If disabled, use server cron
crontab -e
# Add: */15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron
```

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// View log
tail -f wp-content/debug.log
```

---

## Database Queries

```sql
-- List all graphs
SELECT id, title, is_enabled FROM wp_usgs_wl_graphs;

-- Count measurements per graph
SELECT graph_id, COUNT(*) as count
FROM wp_usgs_wl_measurements
GROUP BY graph_id;

-- Recent measurements
SELECT * FROM wp_usgs_wl_measurements
WHERE graph_id = 1
ORDER BY measurement_date DESC
LIMIT 10;

-- Delete old measurements (manual pruning)
DELETE FROM wp_usgs_wl_measurements
WHERE measurement_date < '2023-01-01';

-- Optimize tables
OPTIMIZE TABLE wp_usgs_wl_graphs;
OPTIMIZE TABLE wp_usgs_wl_measurements;
```

---

## REST API

```bash
# Get all graphs
curl -u admin:password \
  https://yoursite.com/wp-json/usgs-water-levels/v1/graphs

# Get specific graph
curl -u admin:password \
  https://yoursite.com/wp-json/usgs-water-levels/v1/graphs/1

# Using WP-CLI
wp rest get /usgs-water-levels/v1/graphs --user=admin
```

---

## File Permissions

```bash
# Set correct permissions
find usgs-water-levels -type d -exec chmod 755 {} \;
find usgs-water-levels -type f -exec chmod 644 {} \;
chown -R www-data:www-data usgs-water-levels
```

---

## Backup & Restore

```bash
# Backup database
wp db export usgs-backup.sql

# Backup plugin
tar -czf usgs-water-levels-backup.tar.gz \
  wp-content/plugins/usgs-water-levels

# Restore database
wp db import usgs-backup.sql

# Restore plugin
tar -xzf usgs-water-levels-backup.tar.gz -C /path/to/wordpress/
```

---

## Uninstall

```bash
# Deactivate
wp plugin deactivate usgs-water-levels

# Delete (this removes all data!)
wp plugin delete usgs-water-levels

# Verify tables removed
wp db tables | grep usgs
```

---

## PHP Code Snippets

### Manually Create Graph

```php
$graph_id = USGS_Water_Levels_Database::create_graph([
    'title' => 'Test Graph',
    'usgs_url' => 'https://waterdata.usgs.gov/...',
    'scrape_interval' => 24,
    'is_enabled' => 1,
    'custom_css' => ''
]);
```

### Manually Scrape Data

```php
$result = USGS_Water_Levels_Scraper::scrape_and_save($graph_id);
if (is_wp_error($result)) {
    echo $result->get_error_message();
}
```

### Get Measurements

```php
$measurements = USGS_Water_Levels_Database::get_measurements($graph_id);
foreach ($measurements as $m) {
    echo $m['measurement_date'] . ': ' . $m['water_level'] . " ft\n";
}
```

---

## JavaScript Snippets

### Check if Chart.js Loaded

```javascript
// Browser console
console.log(typeof Chart); // Should output "function"
```

### Find Chart Instances

```javascript
// Browser console
document.querySelectorAll('.usgs-water-levels-chart-wrapper canvas')
```

### Reinitialize Charts

```javascript
// Browser console
// Useful if charts don't display
const script = document.createElement('script');
script.src = '/wp-content/plugins/usgs-water-levels/blocks/water-level-graph/view.js';
document.head.appendChild(script);
```

---

## Common URLs

```
Admin Page:
/wp-admin/admin.php?page=usgs-water-levels

Add Graph:
/wp-admin/admin.php?page=usgs-water-levels&action=add

Edit Graph:
/wp-admin/admin.php?page=usgs-water-levels&action=edit&graph_id=1

REST API:
/wp-json/usgs-water-levels/v1/graphs
/wp-json/usgs-water-levels/v1/graphs/1

USGS Site:
https://waterdata.usgs.gov/
```

---

## Block Attributes

```json
{
  "graphId": 0,
  "width": "100%",
  "lineColor": "#0073aa",
  "backgroundColor": "#ffffff",
  "axisColor": "#666666",
  "labelColor": "#333333"
}
```

---

## Cron Schedules

```
Event: usgs_water_levels_scrape_cron
Frequency: hourly
Custom schedules: usgs_every_1_hours through usgs_every_24_hours
```

---

## Error Messages

```
"No data available for this graph"
→ No measurements in database. Click "Scrape Now"

"Please select a graph to display"
→ No graph selected in block settings

"Graph not found"
→ Graph was deleted. Select different graph

"Failed to scrape data"
→ Check USGS URL, enable debug mode
```

---

## Support Resources

- User Guide: `USER-GUIDE.md`
- Technical Docs: `TECHNICAL-DOCUMENTATION.md`
- Setup Guide: `SETUP.md`
- Installation: `INSTALL.txt`
- WordPress Support: https://wordpress.org/support/
- USGS Water Data: https://waterdata.usgs.gov/

---

*Quick Reference v1.0.0 - April 2026*
