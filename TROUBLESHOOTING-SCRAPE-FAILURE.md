# Troubleshooting: "Last Scrape Failed"

## Quick Diagnosis Steps

### 1. Check the Error Message

In WordPress Admin → USGS Water Levels:
- Look for the error message next to "Last scrape failed"
- This will tell you what went wrong

### 2. Common Causes & Solutions

#### ❌ Invalid USGS URL

**Error:** "No data found in USGS page" or "Empty response"

**Check:**
1. Visit the USGS URL in your browser
2. Verify it loads correctly
3. Make sure it shows a data table

**Correct URL format:**
```
https://waterdata.usgs.gov/monitoring-location/USGS-XXXXXXXXX/
```

**Example working URL:**
```
https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/
```

**Fix:** Edit the graph and update the URL

---

#### ❌ USGS Site Structure Changed

**Error:** "No measurement data found"

**Problem:** The plugin looks for tables with class `usa-table paginated-table`. If USGS changed their HTML structure, scraping fails.

**Quick Test:**
```bash
# Test if the URL loads
curl -I "YOUR_USGS_URL"

# Check for the data table
curl "YOUR_USGS_URL" | grep -i "usa-table"
```

**Temporary Fix:** The USGS site may be temporarily down. Try again later.

---

#### ❌ PHP cURL/allow_url_fopen Disabled

**Error:** "HTTP request failed" or "Failed to fetch URL"

**Check PHP configuration:**
```bash
php -i | grep -i curl
php -i | grep allow_url_fopen
```

**Fix:** Enable in php.ini:
```ini
allow_url_fopen = On
; OR install cURL extension
extension=curl
```

---

#### ❌ Firewall/Server Blocking Outbound Requests

**Error:** "Connection timeout" or "Could not resolve host"

**Check:**
```bash
# Test from server
curl -v "https://waterdata.usgs.gov/"
wget "https://waterdata.usgs.gov/"
```

**Fix:** Contact hosting provider to allow outbound HTTPS requests to usgs.gov

---

#### ❌ SSL Certificate Issues

**Error:** "SSL certificate problem"

**Fix:** Update PHP or disable SSL verification (not recommended for production):
```php
// In class-scraper.php, modify wp_remote_get():
wp_remote_get($url, array(
    'timeout' => 30,
    'sslverify' => false  // Only for testing!
));
```

---

## 🛠️ WP-CLI Debugging Commands

### Test Scraping Manually

```bash
# Test scraping for graph ID 1
wp eval "
\$result = USGS_Water_Levels_Scraper::scrape_and_save(1);
if (is_wp_error(\$result)) {
    echo 'ERROR: ' . \$result->get_error_message() . \"\\n\";
} else {
    echo 'SUCCESS: Data scraped successfully' . \"\\n\";
}
"
```

### Check Scrape Log

```bash
wp transient get usgs_wl_scrape_log_1
# Replace 1 with your graph ID
```

### Test USGS URL Directly

```bash
# Test raw scraping function
wp eval "
\$url = 'YOUR_USGS_URL_HERE';
\$result = USGS_Water_Levels_Scraper::scrape_usgs_data(\$url);
if (is_wp_error(\$result)) {
    echo \$result->get_error_message() . \"\\n\";
} else {
    echo 'Found ' . count(\$result) . ' measurements' . \"\\n\";
    print_r(array_slice(\$result, 0, 3));
}
"
```

### Enable Debug Logging

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check:
```bash
tail -f wp-content/debug.log
```

---

## 🔧 Manual Testing Steps

### 1. Verify USGS URL in Browser

1. Copy the USGS URL from your graph settings
2. Paste it in a browser
3. Look for a data table on the page
4. The table should have columns like "Date" and "Water Level"

### 2. Inspect the HTML

In browser:
1. Right-click on the data table
2. Select "Inspect Element"
3. Check if table has classes: `usa-table` and `paginated-table`
4. If not, the USGS site structure changed

### 3. Test from Server

SSH into your server and test:
```bash
# Can server reach USGS?
curl -I https://waterdata.usgs.gov/

# Can it fetch your specific URL?
curl "YOUR_USGS_URL" | head -100
```

---

## 📋 Information to Collect

If still failing, collect this info:

```bash
# 1. Graph configuration
wp db query "SELECT * FROM wp_usgs_wl_graphs WHERE id = 1"

# 2. PHP version
php -v

# 3. WordPress version
wp core version

# 4. Plugin status
wp plugin status usgs-water-levels

# 5. Recent error log
tail -20 wp-content/debug.log

# 6. Test URL accessibility
curl -v "YOUR_USGS_URL" 2>&1 | head -50
```

---

## 🎯 Most Common Issues

### Issue 1: Wrong URL Format

❌ Bad:
```
waterdata.usgs.gov/monitoring-location/USGS-123456789
https://waterdata.usgs.gov/nwis/uv?site_no=123456789
```

✅ Good:
```
https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/
```

### Issue 2: Monitoring Location Has No Water Level Data

Some USGS sites only have:
- Stream discharge (not water level)
- Water temperature
- Other parameters

**Fix:** Find a monitoring location that specifically measures water levels (groundwater wells, reservoir levels, etc.)

### Issue 3: Table is Hidden with display:none

The scraper handles this, but if it's deeply nested, it might fail.

**Check:** View page source and search for "usa-table"

---

## 🚨 Emergency Workarounds

### Workaround 1: Try a Different USGS Site

Test with a known working site:
```
https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/
```

If this works, your original URL has an issue.

### Workaround 2: Increase Timeout

Edit `includes/class-scraper.php`:
```php
$response = wp_remote_get(
    $url,
    array(
        'timeout' => 60,  // Increase from 30 to 60 seconds
        'user-agent' => 'WordPress USGS Water Levels Plugin/' . USGS_WATER_LEVELS_VERSION,
    )
);
```

### Workaround 3: Disable SSL Verification (Testing Only!)

```php
'sslverify' => false,  // Add to wp_remote_get() args
```

**WARNING:** Only use for testing! Re-enable for production!

---

## 📞 Get Help

If still stuck, provide:

1. **USGS URL** you're trying to scrape
2. **Error message** from admin page
3. **PHP version**: `php -v`
4. **WordPress version**: `wp core version`
5. **Debug log excerpt**: `tail -20 wp-content/debug.log`
6. **cURL test result**: `curl -I "YOUR_USGS_URL"`

---

## ✅ Success Checklist

Once scraping works, you should see:

- ✅ Green checkmark on admin page
- ✅ "Last Scrape: X minutes ago"
- ✅ Data in database: `wp db query "SELECT COUNT(*) FROM wp_usgs_wl_measurements WHERE graph_id = 1"`
- ✅ Chart displays on frontend

---

*Last Updated: April 2026*
