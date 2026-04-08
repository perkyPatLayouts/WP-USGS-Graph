# Changelog

All notable changes to the USGS Water Levels plugin will be documented in this file.

## [1.1.2] - 2026-04-08

### Fixed

- **Fixed:** SQLite compatibility in `save_measurements()` function
- Changed from bulk INSERT with ON DUPLICATE KEY UPDATE to individual INSERT/UPDATE operations
- Now works with WordPress SQLite Database Integration plugin
- Uses WordPress database abstraction layer (wpdb->insert/update) for compatibility

### Technical Details

**Problem:** The bulk INSERT with ON DUPLICATE KEY UPDATE syntax wasn't translating properly through the WordPress SQLite integration layer, causing "Failed to save measurements to database" errors.

**Solution:** Rewrote `save_measurements()` to:
- Insert measurements individually using `wpdb->insert()`
- Fall back to `wpdb->update()` if INSERT fails due to duplicate key
- Track success/error counts for better reliability
- Return true if at least some measurements were saved

**Performance:** Slightly slower than bulk insert, but ensures compatibility with both MySQL and SQLite.

**Affected file:** `includes/class-database.php`

## [1.1.1] - 2026-04-08

### Fixed

- **Fixed:** MySQL 8.0.20+ compatibility issue in `save_measurements()`
- Changed deprecated `VALUES()` function to table alias syntax in ON DUPLICATE KEY UPDATE
- Resolves "Failed to save measurements to database" error on modern MySQL/MariaDB versions

### Technical Details

**Problem:** MySQL deprecated the `VALUES()` function in version 8.0.20 for use in ON DUPLICATE KEY UPDATE clauses.

**Solution:** Changed from:
```sql
ON DUPLICATE KEY UPDATE water_level = VALUES(water_level)
```
To:
```sql
VALUES $values_string AS new_vals
ON DUPLICATE KEY UPDATE water_level = new_vals.water_level
```

**Affected file:** `includes/class-database.php` line 303

## [1.1.0] - 2026-04-08

### 🔥 CRITICAL UPDATE - USGS API Migration

**BREAKING CHANGE:** USGS decommissioned their legacy HTML-based data delivery in Fall 2025. This update migrates to the new OGC API.

### Changed

- **Complete rewrite of scraper** to use new USGS OGC API (`api.waterdata.usgs.gov`)
- Switched from HTML parsing to JSON API consumption
- API endpoint: `https://api.waterdata.usgs.gov/ogcapi/v0/collections/field-measurements/items`
- Improved error messages to explain API changes
- Increased timeout from 30 to 60 seconds for API requests
- Better handling of monitoring location IDs (with or without USGS- prefix)

### Added

- Support for multiple groundwater parameter codes (62610, 62611, 72019, 72020, 72150)
- Automatic deduplication of measurements by date
- Better URL parsing to handle fragments (#) and query strings (?)
- TROUBLESHOOTING-SCRAPE-FAILURE.md - comprehensive debugging guide
- CHANGELOG.md - this file

### Fixed

- **Fixed:** Scraping now works with current USGS infrastructure
- **Fixed:** URL parsing handles various USGS URL formats
- **Fixed:** Better error messages when no data is available

### Migration Notes

**If you're experiencing "Last scrape failed" errors:**

1. **Update to version 1.1.0** - The old scraper no longer works
2. **Your URLs are still valid** - No need to change monitoring location URLs
3. **Test scraping** - Click "Scrape Now" to verify it works
4. **Check logs** - Enable WP_DEBUG if issues persist

**Technical Details:**

- Old method: HTML scraping from waterdata.usgs.gov
- New method: JSON API from api.waterdata.usgs.gov
- Data format: GeoJSON FeatureCollection
- Authentication: None required (public API)

## [1.0.0] - 2026-04-08

### Initial Release

- Complete WordPress plugin for USGS water level monitoring
- Gutenberg block with Chart.js visualization
- WP-Cron scheduled data scraping
- Admin interface for graph management
- REST API for block editor
- Comprehensive documentation
- No build process required - shared hosting compatible
- 22 files, 6,012+ lines of code
