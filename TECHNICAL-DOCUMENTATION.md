# USGS Water Levels Plugin - Technical Documentation

**Version:** 1.0.0
**For:** Developers, System Administrators, and Technical Users

---

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Installation & Configuration](#installation--configuration)
3. [Database Schema](#database-schema)
4. [PHP Classes & Methods](#php-classes--methods)
5. [JavaScript Components](#javascript-components)
6. [REST API Endpoints](#rest-api-endpoints)
7. [Hooks & Filters](#hooks--filters)
8. [WP-Cron Implementation](#wp-cron-implementation)
9. [Security Measures](#security-measures)
10. [Performance Optimization](#performance-optimization)
11. [Extending the Plugin](#extending-the-plugin)
12. [Debugging & Logging](#debugging--logging)
13. [Testing](#testing)
14. [Deployment](#deployment)
15. [Troubleshooting](#troubleshooting)

---

## System Architecture

### Overview

The USGS Water Levels plugin follows a modular architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────┐
│                    WordPress Core                        │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│              USGS Water Levels Plugin                    │
│                                                          │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐        │
│  │  Database  │  │  Scraper   │  │    Cron    │        │
│  │   Layer    │←→│   Engine   │←→│ Scheduler  │        │
│  └────────────┘  └────────────┘  └────────────┘        │
│        ↑              ↑                                  │
│        │              │                                  │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐        │
│  │  Settings  │  │ REST API   │  │ Gutenberg  │        │
│  │    Page    │  │ Endpoints  │  │   Block    │        │
│  └────────────┘  └────────────┘  └────────────┘        │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│                  Frontend Display                        │
│              (Chart.js Visualization)                    │
└─────────────────────────────────────────────────────────┘
```

### Design Principles

1. **No Build Process**: Pure vanilla JavaScript - works immediately on shared hosting
2. **Separation of Concerns**: Each class handles one responsibility
3. **WordPress Standards**: Follows WordPress coding standards and best practices
4. **Security First**: All inputs sanitized, outputs escaped, SQL prepared
5. **Performance**: Efficient database queries, caching, conditional loading

### Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: Vanilla JavaScript (ES5+), Chart.js 4.4
- **Standards**: WordPress Coding Standards, PSR-12
- **No Dependencies**: No Composer, no npm, no build tools

---

## Installation & Configuration

### System Requirements

**Minimum:**
- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- 50MB disk space
- cURL or allow_url_fopen enabled

**Recommended:**
- WordPress 6.4+
- PHP 8.1+
- MySQL 8.0+
- 100MB disk space
- WP-Cron or server cron
- HTTPS enabled

### Manual Installation

```bash
# 1. Copy plugin to WordPress installation
cp -r usgs-water-levels /path/to/wordpress/wp-content/plugins/

# 2. Set permissions
chmod -R 755 /path/to/wordpress/wp-content/plugins/usgs-water-levels
chown -R www-data:www-data /path/to/wordpress/wp-content/plugins/usgs-water-levels

# 3. Activate plugin
wp plugin activate usgs-water-levels

# 4. Verify installation
wp plugin status usgs-water-levels
```

### PHP Configuration

No special PHP configuration required, but ensure:

```ini
; php.ini
max_execution_time = 300
memory_limit = 256M
allow_url_fopen = On
; OR
extension=curl
```

### WordPress Configuration

**wp-config.php additions (optional):**

```php
// Enable debug mode during development
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Increase memory limit if needed
define('WP_MEMORY_LIMIT', '256M');

// Disable WP-Cron if using server cron
// define('DISABLE_WP_CRON', true);
```

### Server Cron Setup (Optional)

If WP-Cron is unreliable on your host:

```bash
# Add to crontab
*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1

# Or using WP-CLI
*/15 * * * * cd /path/to/wordpress && wp cron event run --due-now >/dev/null 2>&1
```

---

## Database Schema

### Tables Created

The plugin creates two custom tables on activation:

#### `wp_usgs_wl_graphs`

Stores graph configurations.

```sql
CREATE TABLE wp_usgs_wl_graphs (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    usgs_url text NOT NULL,
    scrape_interval int(11) NOT NULL DEFAULT 24,
    is_enabled tinyint(1) NOT NULL DEFAULT 1,
    custom_css text,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY is_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Indexes:**
- `PRIMARY KEY (id)` - Fast lookups by graph ID
- `KEY is_enabled (is_enabled)` - Efficient filtering of enabled graphs

#### `wp_usgs_wl_measurements`

Stores water level measurements.

```sql
CREATE TABLE wp_usgs_wl_measurements (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    graph_id bigint(20) unsigned NOT NULL,
    measurement_date date NOT NULL,
    water_level decimal(10,2) NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY graph_id (graph_id),
    KEY measurement_date (measurement_date),
    UNIQUE KEY unique_measurement (graph_id, measurement_date),
    CONSTRAINT fk_graph_id
        FOREIGN KEY (graph_id)
        REFERENCES wp_usgs_wl_graphs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Indexes:**
- `PRIMARY KEY (id)` - Fast lookups by measurement ID
- `KEY graph_id (graph_id)` - Efficient joins with graphs table
- `KEY measurement_date (measurement_date)` - Fast date range queries
- `UNIQUE KEY unique_measurement (graph_id, measurement_date)` - Prevents duplicates

**Foreign Key:**
- Cascading delete - removing a graph automatically deletes all its measurements

### Database Queries

**Common Queries:**

```sql
-- Get all enabled graphs
SELECT * FROM wp_usgs_wl_graphs WHERE is_enabled = 1;

-- Get measurements for a graph
SELECT measurement_date, water_level
FROM wp_usgs_wl_measurements
WHERE graph_id = %d
ORDER BY measurement_date ASC
LIMIT 1000;

-- Count total measurements
SELECT COUNT(*) FROM wp_usgs_wl_measurements;

-- Get measurements in date range
SELECT * FROM wp_usgs_wl_measurements
WHERE graph_id = %d
AND measurement_date BETWEEN %s AND %s
ORDER BY measurement_date ASC;

-- Delete old measurements (pruning)
DELETE FROM wp_usgs_wl_measurements
WHERE graph_id = %d
AND measurement_date < %s;
```

### Transients Used

The plugin uses WordPress transients for temporary data:

```php
// Scrape logs (expires after 24 hours)
'usgs_wl_scrape_log_{graph_id}'

// Last scrape timestamp (expires after 1 year)
'usgs_wl_last_scrape_{graph_id}'
```

---

## PHP Classes & Methods

### File: `usgs-water-levels.php`

**Main Plugin Class: `USGS_Water_Levels`**

```php
class USGS_Water_Levels {
    // Singleton instance
    private static $instance = null;

    // Methods
    public static function get_instance()
    private function __construct()
    private function load_dependencies()
    private function init_hooks()
    public function activate()
    public function deactivate()
    public function init()
    public function register_block()
    public function render_block($attributes, $content)
    public function admin_enqueue_scripts($hook)
}
```

**Key Methods:**

`activate()` - Runs on plugin activation
- Creates database tables
- Schedules cron events
- Flushes rewrite rules

`deactivate()` - Runs on plugin deactivation
- Clears scheduled cron events
- Does NOT delete data (use uninstall.php)

`render_block($attributes, $content)` - Server-side block rendering
- Validates graph ID
- Fetches graph configuration and measurements
- Enqueues Chart.js and frontend scripts
- Returns HTML canvas element with data attributes

### File: `includes/class-database.php`

**Class: `USGS_Water_Levels_Database`**

Static methods for database operations:

```php
class USGS_Water_Levels_Database {
    // Table management
    public static function create_tables()
    public static function drop_tables()

    // Graph CRUD operations
    public static function get_all_graphs()
    public static function get_graph_config($graph_id)
    public static function get_enabled_graphs()
    public static function create_graph($data)
    public static function update_graph($graph_id, $data)
    public static function delete_graph($graph_id)

    // Measurement operations
    public static function save_measurements($graph_id, $measurements)
    public static function get_measurements($graph_id, $limit = 1000)
    public static function prune_old_measurements($graph_id, $days_to_keep = 365)
}
```

**Usage Examples:**

```php
// Create a new graph
$graph_id = USGS_Water_Levels_Database::create_graph([
    'title' => 'My Well',
    'usgs_url' => 'https://waterdata.usgs.gov/...',
    'scrape_interval' => 24,
    'is_enabled' => 1,
    'custom_css' => ''
]);

// Save measurements
USGS_Water_Levels_Database::save_measurements($graph_id, [
    ['date' => '2024-01-01', 'value' => 125.50],
    ['date' => '2024-01-02', 'value' => 125.75],
]);

// Get measurements
$measurements = USGS_Water_Levels_Database::get_measurements($graph_id);
```

### File: `includes/class-scraper.php`

**Class: `USGS_Water_Levels_Scraper`**

```php
class USGS_Water_Levels_Scraper {
    // Main scraping methods
    public static function scrape_usgs_data($url)
    private static function parse_html($html)
    private static function parse_date($date_string)
    private static function parse_value($value_string)

    // High-level scraping
    public static function scrape_and_save($graph_id)
    public static function scrape_all_enabled()

    // Logging
    private static function log_error($graph_id, $error_message)
    private static function log_success($graph_id, $measurements_count)
    public static function get_scrape_log($graph_id)
}
```

**Scraping Process:**

1. Fetch HTML using `wp_remote_get()`
2. Parse HTML with DOMDocument and DOMXPath
3. Find table with class `usa-table paginated-table`
4. Extract date and value from each table row
5. Normalize dates to Y-m-d format
6. Extract numeric values (handle units)
7. Return array of measurements

**Error Handling:**

Returns `WP_Error` objects on failure:
- Invalid URL
- HTTP request failure
- Empty response
- No data found in HTML
- Graph not found/disabled

**Usage Example:**

```php
// Scrape specific graph
$result = USGS_Water_Levels_Scraper::scrape_and_save(1);
if (is_wp_error($result)) {
    echo $result->get_error_message();
}

// Scrape all enabled graphs
$results = USGS_Water_Levels_Scraper::scrape_all_enabled();
foreach ($results as $result) {
    echo "{$result['title']}: {$result['message']}\n";
}
```

### File: `includes/class-cron.php`

**Class: `USGS_Water_Levels_Cron`**

```php
class USGS_Water_Levels_Cron {
    const CRON_HOOK = 'usgs_water_levels_scrape_cron';

    private static $instance = null;

    public static function get_instance()
    private function __construct()
    private function init_hooks()

    // Cron management
    public function add_cron_schedules($schedules)
    public static function schedule_events()
    public static function clear_scheduled_events()

    // Scraping logic
    public function run_scheduled_scrape()
    private function should_scrape_graph($graph)

    // Manual operations
    public static function manual_scrape($graph_id)
    public static function get_next_scrape_time($graph_id)
    public static function get_last_scrape_time($graph_id)
}
```

**Custom Cron Schedules:**

The plugin registers custom cron intervals:

```php
// Hourly intervals from 1 to 24 hours
usgs_every_1_hours
usgs_every_2_hours
usgs_every_3_hours
// ... up to
usgs_every_24_hours
```

**Cron Logic:**

1. Main cron runs hourly
2. For each enabled graph, check if interval has elapsed
3. Track last scrape time in transients
4. Only scrape if interval has passed
5. Update last scrape time after scraping

**WP-CLI Commands:**

```bash
# List scheduled events
wp cron event list | grep usgs

# Run cron manually
wp cron event run usgs_water_levels_scrape_cron

# Test cron system
wp cron test
```

### File: `includes/class-settings.php`

**Class: `USGS_Water_Levels_Settings`**

```php
class USGS_Water_Levels_Settings {
    private static $instance = null;

    public static function get_instance()
    private function __construct()
    private function init_hooks()

    // Admin page
    public function add_admin_menu()
    public function render_settings_page()
    private function render_notice($message)

    // Views
    private function render_graphs_list()
    private function render_add_form()
    private function render_edit_form($graph_id)
    private function render_graph_form($graph)

    // Form handlers
    public function handle_save_graph()
    public function handle_delete_graph()
    public function handle_scrape_now()
}
```

**Admin Page Structure:**

```
USGS Water Levels
├── List View (default)
│   ├── Table of graphs
│   ├── Status indicators
│   └── Action buttons
├── Add Graph Form
│   └── All fields
└── Edit Graph Form
    └── Pre-filled fields
```

**Security:**

- Capability check: `manage_options`
- Nonce verification on all forms
- Input sanitization with WordPress functions
- URL validation
- SQL injection prevention with prepared statements

### File: `includes/class-rest-api.php`

**Class: `USGS_Water_Levels_REST_API`**

```php
class USGS_Water_Levels_REST_API {
    const NAMESPACE = 'usgs-water-levels/v1';

    public static function init()
    public static function register_routes()
    public static function permissions_check()
    public static function get_graphs($request)
    public static function get_graph($request)
}
```

**Endpoints:**

```
GET /wp-json/usgs-water-levels/v1/graphs
GET /wp-json/usgs-water-levels/v1/graphs/{id}
```

**Permission:** User must have `edit_posts` capability

---

## JavaScript Components

### File: `blocks/water-level-graph/index.js`

**Block Registration (Vanilla JavaScript)**

```javascript
wp.blocks.registerBlockType('usgs-water-levels/water-level-graph', {
    title: 'USGS Water Level Graph',
    description: 'Display a water level graph from USGS monitoring data.',
    category: 'widgets',
    icon: 'chart-line',

    attributes: {
        graphId: { type: 'number', default: 0 },
        width: { type: 'string', default: '100%' },
        lineColor: { type: 'string', default: '#0073aa' },
        backgroundColor: { type: 'string', default: '#ffffff' },
        axisColor: { type: 'string', default: '#666666' },
        labelColor: { type: 'string', default: '#333333' }
    },

    edit: EditComponent,
    save: function() { return null; } // Dynamic block
});
```

**Edit Component Structure:**

```javascript
function EditComponent(props) {
    const { attributes, setAttributes } = props;
    const { graphId, width, lineColor, ... } = attributes;

    // State management
    const [graphs, setGraphs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Fetch graphs from REST API
    useEffect(function() {
        wp.apiFetch({ path: '/usgs-water-levels/v1/graphs' })
            .then(setGraphs)
            .catch(setError);
    }, []);

    // Build UI using wp.element.createElement()
    return el('div', blockProps,
        inspectorControls,
        previewContent
    );
}
```

**No JSX:** Uses `wp.element.createElement()` (aliased as `el`)

**WordPress APIs Used:**
- `wp.blocks` - Block registration
- `wp.element` - React-like API (createElement, useState, useEffect)
- `wp.blockEditor` - Block editor components
- `wp.components` - UI components
- `wp.i18n` - Internationalization
- `wp.apiFetch` - REST API calls

### File: `blocks/water-level-graph/view.js`

**Frontend Chart Rendering**

```javascript
(function() {
    'use strict';

    function initCharts() {
        const canvases = document.querySelectorAll('.usgs-water-levels-chart-wrapper canvas');

        canvases.forEach(function(canvas) {
            const chartData = JSON.parse(canvas.dataset.chartData || '{}');
            const lineColor = canvas.dataset.lineColor || '#0073aa';

            // Create Chart.js instance
            canvas.chart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: { /* ... */ }
            });
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }
})();
```

**Chart.js Configuration:**

```javascript
options: {
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
    plugins: {
        legend: { display: true, position: 'top' },
        tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' +
                           context.parsed.y.toFixed(2) + ' ft';
                }
            }
        }
    },
    scales: {
        x: {
            title: { display: true, text: 'Measurement Date' },
            ticks: { maxRotation: 45, minRotation: 45 }
        },
        y: {
            title: { display: true, text: 'Water Level (feet)' },
            ticks: {
                callback: function(value) {
                    return value.toFixed(2) + ' ft';
                }
            }
        }
    }
}
```

---

## REST API Endpoints

### GET `/wp-json/usgs-water-levels/v1/graphs`

**Description:** Retrieve all graph configurations

**Authentication:** User must have `edit_posts` capability

**Response:**

```json
[
    {
        "id": 1,
        "title": "My Well",
        "usgs_url": "https://waterdata.usgs.gov/...",
        "scrape_interval": 24,
        "is_enabled": true
    },
    {
        "id": 2,
        "title": "River Gauge",
        "usgs_url": "https://waterdata.usgs.gov/...",
        "scrape_interval": 6,
        "is_enabled": true
    }
]
```

**Usage:**

```javascript
// In block editor
wp.apiFetch({ path: '/usgs-water-levels/v1/graphs' })
    .then(graphs => console.log(graphs));
```

```bash
# Via WP-CLI
wp rest get /usgs-water-levels/v1/graphs --user=admin
```

### GET `/wp-json/usgs-water-levels/v1/graphs/{id}`

**Description:** Retrieve a single graph configuration

**Parameters:**
- `id` (required): Graph ID

**Authentication:** User must have `edit_posts` capability

**Response:**

```json
{
    "id": 1,
    "title": "My Well",
    "usgs_url": "https://waterdata.usgs.gov/monitoring-location/USGS-123456789/",
    "scrape_interval": 24,
    "is_enabled": true
}
```

**Error Response (404):**

```json
{
    "code": "graph_not_found",
    "message": "Graph not found.",
    "data": {
        "status": 404
    }
}
```

---

## Hooks & Filters

### Actions

**`plugins_loaded`**
```php
add_action('plugins_loaded', array($this, 'init'), 10, 0);
```
Initializes plugin components after all plugins are loaded.

**`init`**
```php
add_action('init', array($this, 'register_block'), 10, 0);
```
Registers the Gutenberg block.

**`admin_menu`**
```php
add_action('admin_menu', array($this, 'add_admin_menu'), 10, 0);
```
Adds admin menu page.

**`admin_enqueue_scripts`**
```php
add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
```
Enqueues admin styles (only on plugin pages).

**`rest_api_init`**
```php
add_action('rest_api_init', array(__CLASS__, 'register_routes'), 10, 0);
```
Registers REST API endpoints.

**`usgs_water_levels_scrape_cron`**
```php
add_action('usgs_water_levels_scrape_cron', array($this, 'run_scheduled_scrape'), 10, 0);
```
Runs scheduled scraping operation.

### Filters

**`cron_schedules`**
```php
add_filter('cron_schedules', array($this, 'add_cron_schedules'), 10, 1);
```
Adds custom cron intervals (1-24 hours).

### Custom Hooks (for Extension)

**Future hooks that could be added:**

```php
// Before scraping
do_action('usgs_water_levels_before_scrape', $graph_id);

// After successful scrape
do_action('usgs_water_levels_after_scrape', $graph_id, $measurements);

// Filter chart configuration
$chart_config = apply_filters('usgs_water_levels_chart_config', $config, $graph_id);

// Filter measurements before display
$measurements = apply_filters('usgs_water_levels_measurements', $measurements, $graph_id);
```

---

## WP-Cron Implementation

### Cron Event Structure

**Event Name:** `usgs_water_levels_scrape_cron`

**Schedule:** Hourly (uses WordPress built-in `hourly` schedule)

**Callback:** `USGS_Water_Levels_Cron::run_scheduled_scrape()`

### Scheduling Logic

1. **On Activation:**
   ```php
   wp_schedule_event(time(), 'hourly', 'usgs_water_levels_scrape_cron');
   ```

2. **Hourly Execution:**
   - Main cron event runs every hour
   - Checks all enabled graphs
   - For each graph, calculates if interval has elapsed
   - Only scrapes if needed based on graph's interval

3. **On Deactivation:**
   ```php
   wp_clear_scheduled_hook('usgs_water_levels_scrape_cron');
   ```

### Per-Graph Interval Tracking

```php
// Check if graph should be scraped
private function should_scrape_graph($graph) {
    $last_scrape = get_transient('usgs_wl_last_scrape_' . $graph['id']);
    $interval_seconds = $graph['scrape_interval'] * HOUR_IN_SECONDS;

    if (!$last_scrape || (time() - $last_scrape) >= $interval_seconds) {
        set_transient('usgs_wl_last_scrape_' . $graph['id'], time(), YEAR_IN_SECONDS);
        return true;
    }

    return false;
}
```

### Manual Cron Execution

```bash
# Via WP-CLI
wp cron event run usgs_water_levels_scrape_cron

# Via URL (if WP-Cron disabled)
wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron

# Check next scheduled time
wp cron event list | grep usgs_water_levels_scrape_cron
```

### Debugging Cron

```php
// Enable cron debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check if event is scheduled
$timestamp = wp_next_scheduled('usgs_water_levels_scrape_cron');
if ($timestamp) {
    echo 'Next run: ' . date('Y-m-d H:i:s', $timestamp);
}

// List all cron events
$cron = _get_cron_array();
print_r($cron);
```

---

## Security Measures

### Input Sanitization

All user inputs are sanitized:

```php
// Text fields
$title = sanitize_text_field($input);

// URLs
$url = esc_url_raw($input);

// Integers
$interval = absint($input);

// CSS (strip tags)
$css = wp_strip_all_tags($input);
```

### Output Escaping

All outputs are escaped:

```php
// HTML content
echo esc_html($text);

// HTML attributes
echo esc_attr($attribute);

// URLs
echo esc_url($url);

// JavaScript
echo esc_js($js_string);

// SQL (prepared statements)
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);
```

### Nonce Verification

All forms use nonces:

```php
// Create nonce
wp_nonce_field('usgs_wl_save_graph', 'usgs_wl_nonce');

// Verify nonce
check_admin_referer('usgs_wl_save_graph', 'usgs_wl_nonce');
```

### Capability Checks

```php
// Admin pages
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

// REST API
public static function permissions_check() {
    return current_user_can('edit_posts');
}
```

### SQL Injection Prevention

```php
// ALWAYS use prepared statements
$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id);
$wpdb->prepare("INSERT INTO {$table} (name) VALUES (%s)", $name);

// NEVER concatenate user input
// BAD: "SELECT * FROM table WHERE id = " . $_GET['id']
```

### XSS Prevention

```php
// Escape output
echo '<div class="' . esc_attr($class) . '">';
echo esc_html($user_input);

// Sanitize before storing
$safe_value = sanitize_text_field($input);
```

### CSRF Prevention

All state-changing operations use nonces and check referrer.

---

## Performance Optimization

### Database Optimization

**Indexes:**
- Primary keys on all tables
- Index on `graph_id` for fast joins
- Index on `measurement_date` for date queries
- Index on `is_enabled` for filtering

**Query Limits:**
```php
// Limit measurements returned
SELECT * FROM measurements WHERE graph_id = %d
ORDER BY measurement_date ASC LIMIT 1000;
```

**Automatic Pruning:**
```php
// Delete measurements older than 2 years
USGS_Water_Levels_Database::prune_old_measurements($graph_id, 730);
```

### Caching Strategy

**Transients:**
```php
// Scrape logs (24 hour cache)
set_transient('usgs_wl_scrape_log_' . $graph_id, $log, DAY_IN_SECONDS);

// Last scrape time (1 year cache)
set_transient('usgs_wl_last_scrape_' . $graph_id, time(), YEAR_IN_SECONDS);
```

**Object Caching:**
WordPress object cache automatically used for:
- Database query results
- Options
- Transients

### Conditional Loading

**Admin assets only on plugin pages:**
```php
public function admin_enqueue_scripts($hook) {
    if ('toplevel_page_usgs-water-levels' !== $hook) {
        return; // Don't load on other pages
    }
    wp_enqueue_style('usgs-water-levels-admin', ...);
}
```

**Frontend assets only when block present:**
```php
public function render_block($attributes, $content) {
    // Only enqueue if block is actually rendered
    wp_enqueue_script('usgs-water-levels-chart', ...);
    wp_enqueue_script('usgs-water-levels-frontend', ...);
}
```

### HTTP Request Optimization

**Timeout settings:**
```php
wp_remote_get($url, array(
    'timeout' => 30, // 30 second timeout
    'user-agent' => 'WordPress USGS Water Levels Plugin/1.0.0'
));
```

**Error handling:**
```php
$response = wp_remote_get($url);
if (is_wp_error($response)) {
    return $response; // Don't retry immediately
}
```

### Chart Rendering Optimization

**Canvas vs SVG:** Uses HTML5 Canvas (faster for large datasets)

**Data limiting:** Maximum 1000 data points per chart

**Lazy initialization:** Charts init only when DOM is ready

---

## Extending the Plugin

### Adding Custom Block Attributes

```php
// In block.json, add new attribute
"customAttribute": {
    "type": "string",
    "default": "value"
}

// In index.js, add to Edit component
const { customAttribute } = attributes;

// Add control
el(TextControl, {
    label: 'Custom Setting',
    value: customAttribute,
    onChange: function(value) {
        setAttributes({ customAttribute: value });
    }
});

// In render_block(), access attribute
$custom = isset($attributes['customAttribute']) ? $attributes['customAttribute'] : 'default';
```

### Adding Custom Chart Types

Modify `blocks/water-level-graph/view.js`:

```javascript
// Change chart type
canvas.chart = new Chart(ctx, {
    type: 'bar', // or 'line', 'radar', 'pie', etc.
    // ...
});
```

### Adding New REST Endpoints

```php
// In class-rest-api.php
register_rest_route(
    self::NAMESPACE,
    '/custom-endpoint',
    array(
        'methods' => 'GET',
        'callback' => array(__CLASS__, 'custom_callback'),
        'permission_callback' => array(__CLASS__, 'permissions_check'),
    )
);

public static function custom_callback($request) {
    // Your logic here
    return rest_ensure_response($data);
}
```

### Adding Custom Admin Pages

```php
// In class-settings.php or new class
add_submenu_page(
    'usgs-water-levels', // Parent slug
    'Custom Page',       // Page title
    'Custom',            // Menu title
    'manage_options',    // Capability
    'usgs-custom',       // Menu slug
    array($this, 'render_custom_page') // Callback
);
```

### Hooking Into Scraping

```php
// Add action after scrape (requires adding hook to scraper)
add_action('usgs_water_levels_after_scrape', function($graph_id, $measurements) {
    // Send email notification
    // Log to external service
    // Trigger webhook
    // etc.
}, 10, 2);
```

### Custom Data Processing

```php
// Filter measurements before saving (requires adding filter)
add_filter('usgs_water_levels_measurements', function($measurements, $graph_id) {
    // Convert units
    // Filter outliers
    // Add calculated fields
    return $measurements;
}, 10, 2);
```

---

## Debugging & Logging

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

### Debug Log Location

```
/wp-content/debug.log
```

### Plugin Logging

Scraping errors are logged automatically:

```php
// View scrape log
$log = USGS_Water_Levels_Scraper::get_scrape_log($graph_id);
print_r($log);
// Output:
// Array(
//     [timestamp] => 2024-01-01 12:00:00
//     [status] => error|success
//     [message] => Error message or success message
// )
```

### WP-CLI Debugging

```bash
# Check if tables exist
wp db tables --all-tables | grep usgs

# Check cron events
wp cron event list | grep usgs

# Test cron
wp cron test

# Run scrape manually with debug
wp eval "WP_DEBUG=true; echo USGS_Water_Levels_Scraper::scrape_and_save(1);"

# Check transients
wp transient list | grep usgs_wl
```

### JavaScript Debugging

```javascript
// Browser console
console.log('Charts initialized');

// Check if Chart.js loaded
console.log(typeof Chart);

// Check canvas elements
document.querySelectorAll('.usgs-water-levels-chart-wrapper canvas');
```

### Database Debugging

```sql
-- Check table structure
DESCRIBE wp_usgs_wl_graphs;
DESCRIBE wp_usgs_wl_measurements;

-- Check data
SELECT * FROM wp_usgs_wl_graphs;
SELECT COUNT(*) FROM wp_usgs_wl_measurements GROUP BY graph_id;

-- Check indexes
SHOW INDEX FROM wp_usgs_wl_measurements;

-- Query performance
EXPLAIN SELECT * FROM wp_usgs_wl_measurements WHERE graph_id = 1;
```

### Network Request Debugging

```php
// Test USGS URL
$response = wp_remote_get('https://waterdata.usgs.gov/...');
print_r($response);

// Check response code
echo wp_remote_retrieve_response_code($response);

// Check body
echo wp_remote_retrieve_body($response);
```

---

## Testing

### Manual Testing Checklist

**Installation:**
- [ ] Plugin activates without errors
- [ ] Database tables created
- [ ] Cron event scheduled
- [ ] Admin menu appears

**Graph Management:**
- [ ] Can add new graph
- [ ] Can edit existing graph
- [ ] Can delete graph
- [ ] Form validation works
- [ ] Nonce verification prevents CSRF

**Scraping:**
- [ ] Manual scrape works
- [ ] Data is saved to database
- [ ] Errors are logged
- [ ] Success is logged
- [ ] Automatic scraping runs on schedule

**Block Editor:**
- [ ] Block appears in inserter
- [ ] Can select graph from dropdown
- [ ] Color pickers work
- [ ] Width input works
- [ ] Preview displays

**Frontend:**
- [ ] Chart displays on published page
- [ ] Chart is interactive
- [ ] Tooltips show data
- [ ] Responsive on mobile
- [ ] No JavaScript errors

### Unit Testing (Future)

**PHPUnit tests:**

```php
class Test_Database extends WP_UnitTestCase {
    public function test_create_graph() {
        $graph_id = USGS_Water_Levels_Database::create_graph([
            'title' => 'Test Graph',
            'usgs_url' => 'https://waterdata.usgs.gov/test',
            'scrape_interval' => 24,
            'is_enabled' => 1,
        ]);

        $this->assertIsInt($graph_id);
        $this->assertGreaterThan(0, $graph_id);
    }

    public function test_save_measurements() {
        $result = USGS_Water_Levels_Database::save_measurements(1, [
            ['date' => '2024-01-01', 'value' => 100.0],
        ]);

        $this->assertTrue($result);
    }
}
```

### Load Testing

```bash
# Test scraping performance
time wp eval "USGS_Water_Levels_Scraper::scrape_and_save(1);"

# Test database query performance
time wp db query "SELECT * FROM wp_usgs_wl_measurements WHERE graph_id = 1 LIMIT 1000"

# Test frontend rendering
# Use browser dev tools → Network → measure load time
```

---

## Deployment

### Pre-Deployment Checklist

- [ ] All files present
- [ ] Chart.js included (~200KB)
- [ ] No node_modules directory
- [ ] Correct file permissions (755/644)
- [ ] WP_DEBUG disabled in production
- [ ] Database backup completed
- [ ] Tested on staging environment

### Deployment Steps

**1. Prepare Plugin Package**

```bash
# Create clean copy
cp -r usgs-water-levels usgs-water-levels-deploy

# Remove development files
cd usgs-water-levels-deploy
rm -f TECHNICAL-DOCUMENTATION.md
rm -f USER-GUIDE.md
rm -f DEPLOYMENT-CHECKLIST.txt
rm -rf .git

# Verify Chart.js present
ls -lh assets/js/chart.min.js

# Create zip
cd ..
zip -r usgs-water-levels.zip usgs-water-levels-deploy/
```

**2. Upload to Server**

```bash
# Via SCP
scp usgs-water-levels.zip user@server:/path/to/wp-content/plugins/

# On server
cd /path/to/wp-content/plugins/
unzip usgs-water-levels.zip
mv usgs-water-levels-deploy usgs-water-levels
chown -R www-data:www-data usgs-water-levels
chmod -R 755 usgs-water-levels
```

**3. Activate**

```bash
# Via WP-CLI
wp plugin activate usgs-water-levels

# Verify
wp plugin status usgs-water-levels
```

**4. Post-Deployment Verification**

```bash
# Check tables created
wp db tables | grep usgs

# Check cron scheduled
wp cron event list | grep usgs

# Test REST API
wp rest get /usgs-water-levels/v1/graphs --user=admin

# Test scraping
wp eval "print_r(USGS_Water_Levels_Scraper::scrape_all_enabled());"
```

### Production Configuration

```php
// wp-config.php - Production settings
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('WP_CACHE', true); // If using object cache

// Increase limits if needed
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

### Rollback Plan

```bash
# 1. Deactivate plugin
wp plugin deactivate usgs-water-levels

# 2. Restore from backup
cp -r /backup/usgs-water-levels /path/to/wp-content/plugins/

# 3. Restore database
wp db import backup.sql

# 4. Reactivate
wp plugin activate usgs-water-levels
```

---

## Troubleshooting

### Common Issues

#### Issue: Tables Not Created

**Diagnosis:**
```bash
wp db tables | grep usgs
# If empty, tables weren't created
```

**Solution:**
```php
// Run activation manually
wp eval "USGS_Water_Levels_Database::create_tables();"
```

#### Issue: Scraping Fails

**Diagnosis:**
```bash
# Test URL manually
curl -I "https://waterdata.usgs.gov/monitoring-location/USGS-123456/"

# Check PHP error log
tail -f /var/log/php-error.log

# Check WordPress debug log
tail -f wp-content/debug.log
```

**Common Causes:**
- USGS URL changed or invalid
- allow_url_fopen disabled
- cURL not installed
- Firewall blocking requests
- USGS website structure changed

**Solution:**
```php
// Test scraping
wp eval "
\$result = USGS_Water_Levels_Scraper::scrape_usgs_data('https://waterdata.usgs.gov/...');
if (is_wp_error(\$result)) {
    echo \$result->get_error_message();
} else {
    print_r(\$result);
}
"
```

#### Issue: Cron Not Running

**Diagnosis:**
```bash
wp cron test
wp cron event list | grep usgs
```

**Solution:**
```bash
# If WP-Cron disabled, use server cron
crontab -e
# Add:
*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron

# Or run manually
wp cron event run usgs_water_levels_scrape_cron
```

#### Issue: Charts Not Displaying

**Diagnosis:**
- Browser console (F12) → Check for errors
- Network tab → Check if chart.min.js loads
- Elements tab → Check if canvas element exists

**Common Causes:**
- Chart.js not loaded (missing file)
- JavaScript error
- Canvas element missing (block not rendering)
- Data attribute empty

**Solution:**
```bash
# Verify Chart.js exists
ls -lh wp-content/plugins/usgs-water-levels/assets/js/chart.min.js

# If missing
curl -o wp-content/plugins/usgs-water-levels/assets/js/chart.min.js \
https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
```

#### Issue: Block Not Appearing in Editor

**Diagnosis:**
```javascript
// Browser console
wp.blocks.getBlockTypes().filter(b => b.name.includes('usgs'));
```

**Solution:**
```bash
# Clear cache
wp cache flush

# Check if plugin activated
wp plugin status usgs-water-levels

# Hard refresh browser (Ctrl+Shift+R)
```

### Performance Issues

**Slow Queries:**
```sql
-- Add missing indexes
CREATE INDEX idx_graph_id ON wp_usgs_wl_measurements(graph_id);
CREATE INDEX idx_measurement_date ON wp_usgs_wl_measurements(measurement_date);

-- Analyze tables
ANALYZE TABLE wp_usgs_wl_graphs;
ANALYZE TABLE wp_usgs_wl_measurements;
```

**Large Database:**
```php
// Prune old data more aggressively
wp eval "
\$graphs = USGS_Water_Levels_Database::get_all_graphs();
foreach (\$graphs as \$graph) {
    USGS_Water_Levels_Database::prune_old_measurements(\$graph['id'], 365); // Keep 1 year
}
"
```

### Security Issues

**SQL Injection Test:**
- All queries use prepared statements
- No concatenation of user input
- PHPCS checks for SQL issues

**XSS Test:**
- All outputs escaped
- Nonces on all forms
- Capability checks on all admin operations

**File Permission Issues:**
```bash
# Correct permissions
find usgs-water-levels -type d -exec chmod 755 {} \;
find usgs-water-levels -type f -exec chmod 644 {} \;
chown -R www-data:www-data usgs-water-levels
```

---

## Appendix

### File Manifest

```
usgs-water-levels/
├── usgs-water-levels.php          # Main plugin file
├── uninstall.php                   # Uninstall cleanup
├── readme.txt                      # WordPress.org readme
├── .gitignore                      # Git ignore rules
├── README.md                       # Developer readme
├── SETUP.md                        # Setup instructions
├── INSTALL.txt                     # Installation guide
├── USER-GUIDE.md                   # User documentation
├── TECHNICAL-DOCUMENTATION.md      # This file
├── DEPLOYMENT-CHECKLIST.txt        # Deployment checklist
├── includes/
│   ├── class-database.php          # Database operations
│   ├── class-scraper.php           # USGS scraping
│   ├── class-cron.php              # Cron management
│   ├── class-settings.php          # Admin settings
│   └── class-rest-api.php          # REST API endpoints
├── blocks/
│   └── water-level-graph/
│       ├── block.json              # Block metadata
│       ├── index.js                # Block editor (vanilla JS)
│       ├── view.js                 # Frontend rendering
│       └── style.css               # Block styles
├── admin/
│   └── admin.css                   # Admin styles
└── assets/
    └── js/
        └── chart.min.js            # Chart.js library (200KB)
```

### Database Size Estimates

**Per Graph Configuration:** ~1KB

**Per Measurement:** ~50 bytes

**Example:**
- 10 graphs = ~10KB
- 10,000 measurements per graph = ~500KB
- **Total for 10 graphs with 2 years data:** ~5MB

### API Reference Summary

**PHP Classes:**
- `USGS_Water_Levels` - Main plugin class
- `USGS_Water_Levels_Database` - Database operations
- `USGS_Water_Levels_Scraper` - Data scraping
- `USGS_Water_Levels_Cron` - Scheduling
- `USGS_Water_Levels_Settings` - Admin interface
- `USGS_Water_Levels_REST_API` - REST endpoints

**JavaScript Objects:**
- `wp.blocks` - Block registration
- `wp.element` - React-like API
- `wp.blockEditor` - Block editor components
- `wp.components` - UI components
- `wp.i18n` - Internationalization
- `Chart` - Chart.js library

**REST Endpoints:**
- `GET /usgs-water-levels/v1/graphs`
- `GET /usgs-water-levels/v1/graphs/{id}`

**WP-CLI Commands:**
```bash
wp plugin activate usgs-water-levels
wp cron event run usgs_water_levels_scrape_cron
wp db query "SELECT * FROM wp_usgs_wl_graphs"
```

### Version History

**1.0.0 (April 2026)**
- Initial release
- Automatic USGS data scraping
- Gutenberg block for display
- Admin settings interface
- WP-Cron scheduling
- Chart.js visualization
- No build process required

---

## Support & Resources

**WordPress:**
- Plugin Handbook: https://developer.wordpress.org/plugins/
- Coding Standards: https://developer.wordpress.org/coding-standards/
- WP-CLI: https://wp-cli.org/

**USGS:**
- Water Data: https://waterdata.usgs.gov/
- API Documentation: https://waterservices.usgs.gov/

**Chart.js:**
- Documentation: https://www.chartjs.org/docs/
- Samples: https://www.chartjs.org/samples/

**Development Tools:**
- PHPCS: https://github.com/squizlabs/PHP_CodeSniffer
- Query Monitor: https://wordpress.org/plugins/query-monitor/
- WP Crontrol: https://wordpress.org/plugins/wp-crontrol/

---

*Last Updated: April 2026*
*Plugin Version: 1.0.0*
*For WordPress 6.0+*
