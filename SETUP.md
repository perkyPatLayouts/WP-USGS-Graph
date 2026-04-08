# Setup Instructions for USGS Water Levels Plugin

## Quick Start

This plugin requires **no build process** and works out-of-the-box on shared hosting!

### 1. Upload Plugin

**Option A: Using WP-CLI**
```bash
# Copy plugin to WordPress plugins directory
cp -r usgs-water-levels /path/to/wordpress/wp-content/plugins/

# Activate
wp plugin activate usgs-water-levels
```

**Option B: Via WordPress Admin**
1. Create a zip file of the `usgs-water-levels` folder
2. Log into WordPress admin
3. Go to Plugins → Add New → Upload Plugin
4. Select the zip file and click Install Now
5. Click Activate Plugin

**Option C: FTP/File Manager (Shared Hosting)**
1. Upload the entire `usgs-water-levels` folder to `wp-content/plugins/`
2. Go to WordPress admin → Plugins
3. Find "USGS Water Levels" and click Activate

### 2. Verify Chart.js is Present

The plugin includes Chart.js. Verify it's there:

```bash
ls -lh wp-content/plugins/usgs-water-levels/assets/js/chart.min.js
```

You should see a ~200KB file. If it's missing, download it:

```bash
curl -o wp-content/plugins/usgs-water-levels/assets/js/chart.min.js \
  https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
```

### 3. Configure Your First Graph

1. Go to **USGS Water Levels** in WordPress admin menu
2. Click **Add New Graph**
3. Enter:
   - **Title**: e.g., "My Well Water Level"
   - **USGS URL**: e.g., `https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/`
   - **Scrape Interval**: 24 (hours)
   - Check **Enable scraping**
4. Click **Add Graph**

### 4. Test Manual Scraping

Click the **Scrape Now** button next to your graph to fetch data immediately. This helps verify:
- The USGS URL is accessible
- The data can be parsed correctly
- Database is working

Check the status indicator - it should show green if successful.

### 5. Insert Block in a Post/Page

1. Create or edit a post/page
2. Click the (+) button to add a block
3. Search for "USGS Water Level Graph"
4. Select your graph from the dropdown
5. Customize:
   - Width (100%, 800px, 50vw, etc.)
   - Line color
   - Background color
   - Axis colors
6. Publish!

### 6. View on Frontend

Visit the published post/page to see your interactive water level chart.

## No Build Process Required!

Unlike many modern WordPress plugins, this one uses vanilla JavaScript and requires:
- ❌ No Node.js
- ❌ No npm
- ❌ No build step
- ❌ No compilation
- ✅ Works immediately on shared hosting
- ✅ Easy to customize

## File Verification Checklist

After installation, verify these files exist:

```bash
# Core plugin files
usgs-water-levels/usgs-water-levels.php          ✓
usgs-water-levels/includes/class-database.php    ✓
usgs-water-levels/includes/class-scraper.php     ✓
usgs-water-levels/includes/class-cron.php        ✓
usgs-water-levels/includes/class-settings.php    ✓
usgs-water-levels/includes/class-rest-api.php    ✓

# Block files
usgs-water-levels/blocks/water-level-graph/block.json  ✓
usgs-water-levels/blocks/water-level-graph/index.js    ✓
usgs-water-levels/blocks/water-level-graph/view.js     ✓
usgs-water-levels/blocks/water-level-graph/style.css   ✓

# Assets
usgs-water-levels/assets/js/chart.min.js               ✓
```

## Testing

### Test Scraping

```bash
# View scheduled cron events
wp cron event list | grep usgs

# Run the scraping cron manually
wp cron event run usgs_water_levels_scrape_cron

# Check scraping logs
wp transient get usgs_wl_scrape_log_1
```

### Test Database

```bash
# View graphs
wp db query "SELECT * FROM wp_usgs_wl_graphs"

# View measurements
wp db query "SELECT * FROM wp_usgs_wl_measurements LIMIT 10"

# Count measurements per graph
wp db query "SELECT graph_id, COUNT(*) as count FROM wp_usgs_wl_measurements GROUP BY graph_id"
```

### Test REST API

The block uses REST API to fetch graphs. Test it:

```bash
# Get all graphs (requires authentication)
wp rest get /usgs-water-levels/v1/graphs --user=admin

# Get specific graph
wp rest get /usgs-water-levels/v1/graphs/1 --user=admin
```

## Common Issues

### Issue: Block doesn't appear in editor

**Solution:**
1. Clear WordPress cache: `wp cache flush`
2. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
3. Check if plugin is activated

### Issue: Charts not rendering on frontend

**Solutions:**
1. Verify Chart.js exists:
   ```bash
   ls -lh wp-content/plugins/usgs-water-levels/assets/js/chart.min.js
   ```

2. Check browser console for errors (F12 → Console tab)

3. Ensure the block has a graph selected and the graph has data

### Issue: No data after scraping

**Solutions:**
1. Check if the USGS URL loads in a browser
2. View scrape log on admin page for error messages
3. Enable debugging in wp-config.php:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
4. Check `wp-content/debug.log` for errors

### Issue: "Permission denied" errors

**Solutions:**
1. Check file permissions:
   ```bash
   chmod -R 755 wp-content/plugins/usgs-water-levels
   chown -R www-data:www-data wp-content/plugins/usgs-water-levels
   ```

2. Ensure PHP can write to the database

### Issue: WP-Cron not running

Some shared hosts disable WP-Cron. Solutions:

**Option 1: Use server cron**
Add to crontab:
```bash
*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

**Option 2: Manual scraping**
Click "Scrape Now" in the admin when you want to update data.

**Option 3: Plugin**
Install a cron management plugin like "WP Crontrol" to verify cron is working.

## Customization

### Change Default Colors

Edit `blocks/water-level-graph/block.json` and change the default values:

```json
"lineColor": {
	"type": "string",
	"default": "#0073aa"  // Change this
}
```

### Customize Chart Appearance

Edit `blocks/water-level-graph/view.js` to modify Chart.js options:

```javascript
options: {
	responsive: true,
	maintainAspectRatio: true,
	aspectRatio: 2,  // Change aspect ratio
	// ... more options
}
```

### Add Custom Styling Per Graph

In the admin, when editing a graph, add custom CSS:

```css
.usgs-water-levels-chart-wrapper {
	background: linear-gradient(to bottom, #e3f2fd, #ffffff);
	padding: 30px;
	border-radius: 12px;
	box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
```

## Performance Tips

1. **Adjust scrape intervals** - Don't scrape more frequently than needed
2. **Limit displayed data** - The plugin shows up to 1000 measurements per graph
3. **Regular pruning** - Old data (>2 years) is automatically deleted
4. **Caching** - Uses WordPress transients for scrape logs
5. **Conditional loading** - Chart.js only loads on pages with the block

## Security Checklist

- ✅ All user inputs are sanitized
- ✅ Database queries use prepared statements
- ✅ Nonces protect forms from CSRF
- ✅ Capabilities checked (only admins can manage)
- ✅ Output is escaped to prevent XSS
- ✅ Follows WordPress Coding Standards

## Deployment to Production

1. **Test locally first**
2. **Backup your database** before activation
3. **Upload via FTP or WordPress admin**
4. **Activate plugin**
5. **Configure at least one graph**
6. **Test scraping manually**
7. **Insert block on a test page**
8. **Verify charts display correctly**
9. **Monitor for 24 hours** to ensure cron is working

## Uninstalling

The plugin cleans up after itself:

1. Deactivate the plugin
2. Delete the plugin
3. All database tables are automatically removed
4. All options and transients are deleted

## Getting Help

- **WordPress Support**: https://wordpress.org/support/
- **WP-CLI Docs**: https://wp-cli.org/
- **USGS Water Data**: https://waterdata.usgs.gov/
- **Chart.js Docs**: https://www.chartjs.org/docs/

## Next Steps

- Add multiple monitoring locations
- Customize colors to match your theme
- Experiment with different scrape intervals
- Add custom CSS for unique styling
- Monitor cron execution in admin

Enjoy your USGS water level graphs! 🌊📊
