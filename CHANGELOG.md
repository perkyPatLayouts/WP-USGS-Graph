# Changelog

All notable changes to the USGS Water Levels plugin will be documented in this file.

## [2.3.4] - 2026-04-09

### Fixed
- **Critical: Database Update Debugging**: Added comprehensive error handling and logging
  - Validate graph exists before attempting update
  - Return WP_Error with detailed messages instead of silent failures
  - Add debug logging to track save operations (when WP_DEBUG enabled)
  - Show detailed error messages in admin notices
  - Log graph ID, data, and database errors for troubleshooting
  - Helps diagnose why graph edits are not persisting

### Added
- Detailed error reporting in admin interface
- Debug logging for save operations (requires WP_DEBUG)
- Database error messages shown to user
- Graph existence validation before updates

## [2.3.3] - 2026-04-09

### Fixed
- **Date Editing Critical Fix**: Completely rewrote date handling to ensure proper saving
  - Changed from regex validation to strtotime() for flexible date parsing
  - Use array_key_exists() instead of isset() to properly handle NULL values
  - Dates now use NULL instead of empty string for better database handling
  - Fixed issue where start date would not save when changed

## [2.3.2] - 2026-04-09

### Fixed
- **Date Field Persistence**: Added missing default values for date_start, date_end, and auto_update_dates in form defaults
  - Ensures date changes are properly saved and displayed when editing graphs
  - Fixes issue where start date would revert to previous value after saving

### Improved
- **Graphs Page Layout**: Redesigned status messages and documentation display
  - Scrape success/fail messages now display in prominent custom section at top of page (green for success, red for failure)
  - Shortcode documentation moved from admin notice to custom styled section with better formatting
  - Added emoji icons for visual clarity (📊 for documentation, ✓ for success, ✗ for errors)
  - Dark-themed code examples with better contrast
  - Improved table styling with alternating row backgrounds
  - Box shadows and rounded corners for modern appearance

## [2.3.1] - 2026-04-09

### Fixed
- **Cache Clearing**: Comprehensive cache clearing now works with all major WordPress caching plugins
  - Added support for WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache
  - Added support for WP Fastest Cache, Autoptimize, Cache Enabler, Comet Cache
  - Added support for SiteGround Optimizer, WP-Optimize, Hummingbird
  - Frontend now updates immediately after editing graphs without manual page re-saves

### Improved
- **Shortcode Documentation**: Enhanced visibility and formatting on Graphs page
  - More prominent styling with blue background and border
  - Clearer table layout with proper headers
  - Larger, more readable code examples
  - Better organized sections for easier reading

## [2.3.0] - 2026-04-09

### Added
- **Enhanced Shortcode Documentation**: Detailed parameter table with examples on Graphs page
  - Shows all available parameters (id, chart_type, width, line_color, class)
  - Includes usage examples for each parameter
  - Better formatted with visual examples

- **Rolling Date Window Feature**: Auto-update date range option
  - New "Auto-update date range (rolling window)" checkbox in graph settings
  - Automatically moves end date to today
  - Moves start date forward by the same amount (maintains consistent time window)
  - Perfect for keeping graphs current without manual updates
  - New `auto_update_dates` database column

- **Automatic Cache Clearing**: Frontend now updates immediately
  - Graph changes reflect instantly without re-saving pages
  - Clears WordPress object cache when graph is updated
  - Clears cache when new measurements are saved
  - Works with common caching plugins

### Database
- Added `auto_update_dates` column to graphs table
- Cache invalidation on data updates

### Fixed
- Frontend display now updates immediately after editing graphs
- No longer need to re-save pages containing shortcodes to see changes

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
