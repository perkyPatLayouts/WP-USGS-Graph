# Changelog

All notable changes to the USGS Water Levels plugin will be documented in this file.

## [2.2.1] - 2026-04-09

### Changed
- **Removed 30 measurement limit**: Plugin now fetches and displays all available measurements
  - Previously limited to most recent 30 measurements
  - Now limited only by API response (up to 1000 measurements per request)
  - Better for historical data analysis and long-term trends
  - Date range filtering still available to limit data if needed

## [2.2.0] - 2026-04-09

### Added
- **Chart Type Selection**: Choose between Line, Area, or Bar charts
  - Added `chartType` attribute to Gutenberg block with dropdown selector
  - Added `chart_type` parameter to shortcode (values: "line", "area", "bar")
  - Line chart: Clean line with points, no fill
  - Area chart: Filled area under line with gradient effect
  - Bar chart: Vertical bars for each measurement
- Updated frontend Chart.js rendering to support all three chart types
- Added chart type to block editor preview display

### Changed
- Default chart type is "line" (maintains backward compatibility)
- Updated shortcode documentation with chart type examples
- Updated admin settings page with chart type parameter info

## [2.1.3] - 2026-04-09

### Fixed
- **Critical**: Fixed date range filtering causing HTTP 400 errors
  - Changed from invalid `timeBegin`/`timeEnd` parameters to OGC API standard `datetime` parameter
  - Now supports proper date range format: `datetime=START/END`
  - Supports open-ended ranges: `START/..` or `../END`

## [2.1.2] - 2026-04-09

### Fixed
- **Data Accuracy**: Excluded parameter code 72019 (NGVD29 datum) to prevent mixing incompatible measurements
  - Only accepts 62610 (standard depth) and 62611 (NAVD88 depth) for consistent values
  - Prevents graphs from showing mixed values (e.g., 3 ft and 19 ft in same dataset)

## [2.1.1] - 2026-04-09

### Fixed
- **Scraping**: Expanded parameter code acceptance to include 62611 (depth below surface, NAVD88)
  - Previously only accepted 62610, causing "no data" errors for many sites

## [2.1.0] - 2026-04-09

### Added
- **Shortcode Support**: Added `[usgs_water_level]` shortcode for Classic Editor compatibility
  - Supports parameters: `id`, `width`, `line_color`, `class`
- **Admin UI**: Added shortcode column to graphs table for easy copy/paste
- **Documentation**: Added usage instructions in admin settings page

## [2.0.2] - 2026-04-09

### Security
- Fixed SQL injection vulnerability in `drop_tables()` function
- Fixed XSS vulnerability in custom CSS output
- Added date validation with regex pattern matching
- Fixed date range fields not being saved in graph settings

### Changed
- Updated minimum WordPress version to 6.2

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
