# USGS Water Levels Plugin - User Guide

**Version:** 1.0.0
**For:** WordPress Site Administrators and Content Editors

---

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Managing Graphs](#managing-graphs)
4. [Using the Block Editor](#using-the-block-editor)
5. [Using Shortcodes](#using-shortcodes)
6. [Chart Types](#chart-types)
7. [Customizing Your Graphs](#customizing-your-graphs)
8. [Troubleshooting](#troubleshooting)
9. [Frequently Asked Questions](#frequently-asked-questions)

---

## Introduction

### What is USGS Water Levels?

USGS Water Levels is a WordPress plugin that automatically collects water level measurement data from the United States Geological Survey (USGS) monitoring stations and displays it as beautiful, interactive charts on your website.

### What Can You Do With It?

- **Monitor Water Levels**: Track groundwater, reservoir, or stream levels from any USGS monitoring location
- **Multiple Chart Types**: Display data as Line, Area, or Bar charts
- **Gutenberg & Classic Support**: Use modern blocks or traditional shortcodes
- **Display Data**: Show complete historical water level data
- **Date Range Filtering**: Limit data to specific time periods
- **Automatic Updates**: The plugin automatically fetches new data on your schedule
- **Customize Appearance**: Control colors, sizes, chart types, and styling to match your website

### Who Is This For?

- Environmental organizations
- Municipal water departments
- Research institutions
- Educational websites
- Anyone interested in tracking water resources

---

## Getting Started

### Installation

Your site administrator should have already installed and activated the plugin. Once activated, you'll see **"USGS Water Levels"** in your WordPress admin menu.

### Finding USGS Monitoring Locations

Before you can display water level data, you need to find a USGS monitoring location:

1. Visit **https://waterdata.usgs.gov/**
2. Use the search tools to find a monitoring location:
   - Search by state
   - Search by county
   - Search by site number
   - Use the interactive map
3. Click on a monitoring location that interests you
4. Copy the full URL from your browser's address bar

**Example URL:**
```
https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/
```

---

## Managing Graphs

### Accessing the Settings Page

1. Log into WordPress admin
2. Click **"USGS Water Levels"** in the left sidebar
3. You'll see a list of all configured graphs (or a message to add your first one)

### Adding a New Graph

1. Click the **"Add New Graph"** button
2. Fill in the form:

   **Graph Title**
   - Give your graph a descriptive name
   - Example: "Main Street Well Water Level"
   - This title will appear on your charts

   **USGS URL**
   - Paste the full URL from the USGS website
   - Make sure it's a complete URL starting with `https://`

   **Scrape Interval**
   - Choose how often to check for new data (in hours)
   - Recommended: 24 hours (once per day)
   - Range: 1 hour to 168 hours (7 days)
   - Note: More frequent scraping = more server load

   **Status**
   - Check "Enable scraping" to start automatic data collection
   - Uncheck to pause scraping while keeping the graph configuration

   **Date Range** (Optional)
   - **Start Date**: Beginning of data range (YYYY-MM-DD format)
   - **End Date**: End of data range (YYYY-MM-DD format)
   - Leave both blank to fetch all available historical data
   - Use date ranges to limit data collection to specific time periods

   **Auto-update date range (rolling window)**
   - Check this box to automatically keep your date range current
   - When enabled:
     - End date automatically updates to today
     - Start date moves forward by the same amount
     - Maintains a consistent time window
   - Example: If you set a 2-year range (2024-01-01 to 2026-01-01), the plugin will automatically move it forward to always show the last 2 years
   - Perfect for dashboards that need to stay current without manual updates

   **Custom CSS** (Advanced - Optional)
   - Add custom styling code if you want unique formatting
   - Leave blank if you're not familiar with CSS

3. Click **"Add Graph"**

### Testing Your New Graph

After adding a graph, immediately test it:

1. Find your new graph in the list
2. Click the **"Scrape Now"** button
3. The page will reload
4. Check the status:
   - **Green checkmark** = Success! Data was collected
   - **Red X** = There was a problem (see error message)

**Common Issues:**
- Wrong URL format
- USGS site doesn't have water level data
- Internet connection problem

### Editing a Graph

1. Find the graph in the list
2. Click the **"Edit"** button
3. Make your changes
4. Click **"Update Graph"**

**What You Can Change:**
- Title
- USGS URL (if the monitoring location changes)
- Scrape interval
- Enable/disable status
- Custom CSS

### Deleting a Graph

**Warning:** This permanently deletes the graph configuration and all stored data!

1. Find the graph in the list
2. Click the **"Delete"** button
3. Confirm you want to delete it
4. The graph and all its data will be removed

**Note:** Any pages or posts using this graph's block will show an error message until you select a different graph.

### Understanding the Graph List

The main settings page shows important information about each graph:

**ID** - Unique identifier for the graph

**Title** - Your descriptive name

**USGS URL** - The monitoring location (click to visit)

**Scrape Interval** - How often data is collected

**Status**
- Green checkmark = Enabled and working
- Red X = Disabled or has errors
- Error messages appear if the last scrape failed

**Last Scrape**
- Shows when data was last collected
- "Never" means no data has been collected yet
- Click "Scrape Now" to collect data immediately

**Actions**
- **Edit** - Modify graph settings
- **Scrape Now** - Manually collect data right now
- **Delete** - Remove graph permanently

---

## Using the Block Editor

### Inserting a Water Level Graph

1. Edit the page or post where you want the graph
2. Click the **(+)** button to add a new block
3. Search for **"USGS Water Level Graph"** or just type "water"
4. Click the block to insert it

### Selecting a Graph

1. With the block selected, look at the right sidebar
2. Find the **"Graph Settings"** panel
3. Click the **"Select Graph"** dropdown
4. Choose which monitoring location to display

**Important:** If the dropdown is empty, you need to add a graph in the settings first!

### Customizing Block Appearance

After selecting a graph, customize its appearance using the settings in the right sidebar:

#### Graph Settings Panel

**Chart Type**
- Choose how to visualize your data:
  - **Line Chart** (default) - Clean line with points, best for showing trends
  - **Area Chart** - Filled gradient under line, emphasizes magnitude of change
  - **Bar Chart** - Vertical bars for each measurement, best for discrete data points

**Width**
- Controls how wide the graph appears
- Examples:
  - `100%` - Full width of content area (default)
  - `800px` - Fixed width of 800 pixels
  - `50vw` - Half the viewport (browser window) width
  - `30em` - 30 times the current font size

#### Colors Panel

Click to expand the **"Colors"** section to customize:

**Line Color**
- The color of the data line on the chart
- Default: Blue (#0073aa)

**Background Color**
- The background behind the chart
- Default: White (#ffffff)

**Axis Color**
- The color of the X and Y axis lines
- Default: Gray (#666666)

**Label Color**
- The color of text labels on the chart
- Default: Dark Gray (#333333)

**To change a color:**
1. Click the color picker
2. Choose a color by clicking or entering a hex code
3. The preview updates automatically

### Block Preview

In the editor, you'll see a placeholder preview showing:
- The graph ID being displayed
- The width setting
- A preview of the line color
- A note that the actual chart appears on the frontend

**Note:** The real interactive chart only appears when viewing the published page, not in the editor.

### Publishing

1. Click **"Update"** or **"Publish"** to save your changes
2. Click **"View Page"** to see the live chart
3. The chart should be interactive:
   - Hover over data points to see exact values
   - Pan and zoom (if enabled)
   - Responsive on mobile devices

---

## Using Shortcodes

### What Are Shortcodes?

Shortcodes are a simple way to insert graphs into your content, especially useful for:
- Classic Editor users
- Text widgets
- Pages created before the block editor
- Quick insertions without using blocks

### Basic Shortcode

The simplest shortcode to insert a graph:

```
[usgs_water_level id="1"]
```

Replace `1` with your graph ID from the admin settings page.

### Shortcode Parameters

Customize your graph with these optional parameters:

**chart_type** - Choose visualization type
```
[usgs_water_level id="1" chart_type="line"]
[usgs_water_level id="1" chart_type="area"]
[usgs_water_level id="1" chart_type="bar"]
```

**width** - Set graph width
```
[usgs_water_level id="1" width="100%"]
[usgs_water_level id="1" width="600px"]
[usgs_water_level id="1" width="80vw"]
```

**line_color** - Set line/bar color
```
[usgs_water_level id="1" line_color="#0073aa"]
[usgs_water_level id="1" line_color="#ff6600"]
```

**class** - Add custom CSS classes
```
[usgs_water_level id="1" class="my-custom-class"]
```

### Complete Examples

**Line chart with custom width:**
```
[usgs_water_level id="1" chart_type="line" width="800px"]
```

**Area chart with custom color:**
```
[usgs_water_level id="1" chart_type="area" line_color="#28a745" width="100%"]
```

**Bar chart with all options:**
```
[usgs_water_level id="1" chart_type="bar" width="90%" line_color="#dc3545" class="featured-graph"]
```

### Finding Your Graph ID

1. Go to **USGS Water Levels** in admin menu
2. Look at the **ID** column in the graphs table
3. Use that number in your shortcode
4. The table also shows a ready-to-copy shortcode for each graph!

---

## Chart Types

### Choosing the Right Chart Type

Each chart type serves a different purpose:

**Line Chart** (Default)
- **Best for:** Showing trends over time
- **Appearance:** Clean line connecting data points with visible markers
- **Use when:** You want to emphasize the continuous nature of measurements
- **Example:** Tracking gradual water level changes

**Area Chart**
- **Best for:** Emphasizing magnitude and accumulation
- **Appearance:** Filled gradient area under the line
- **Use when:** You want to highlight the volume or amount of change
- **Example:** Showing significant water level fluctuations

**Bar Chart**
- **Best for:** Discrete measurements and comparisons
- **Appearance:** Vertical bars for each data point
- **Use when:** Each measurement is distinct and you want easy comparison
- **Example:** Monthly or yearly water level summaries

### Switching Between Chart Types

**In Block Editor:**
1. Select your graph block
2. Open **Graph Settings** panel in sidebar
3. Choose from **Chart Type** dropdown
4. Update/Publish to see changes

**In Shortcode:**
```
[usgs_water_level id="1" chart_type="area"]
```

Change `chart_type` value to "line", "area", or "bar"

---

## Customizing Your Graphs

### Matching Your Site's Design

Use the color pickers to match your website's color scheme:

**Professional Look:**
- Line: Dark blue or teal
- Background: White or light gray
- Axes: Medium gray
- Labels: Dark gray

**High Contrast:**
- Line: Bright color (red, blue, green)
- Background: White
- Axes: Black
- Labels: Black

**Dark Theme:**
- Line: Bright cyan or yellow
- Background: Dark gray or black
- Axes: Light gray
- Labels: White

### Adjusting Graph Size

**For Blog Posts:**
- Use `100%` width to fill the content area

**For Sidebars:**
- Use `300px` or `400px` for narrow spaces

**For Wide Layouts:**
- Use `1200px` or `80vw` for full-width sections

**Responsive Design:**
- Using `%` or `vw` units makes graphs resize on mobile devices
- Using `px` keeps graphs at a fixed size

### Advanced: Custom CSS

If you're comfortable with CSS, you can add custom styles in the graph settings:

**Example: Add a Border**
```css
.usgs-water-levels-chart-wrapper {
    border: 3px solid #0073aa;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
```

**Example: Change Font**
```css
.usgs-water-levels-chart-wrapper {
    font-family: 'Arial', sans-serif;
    font-size: 14px;
}
```

**Example: Add Background Pattern**
```css
.usgs-water-levels-chart-wrapper {
    background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
}
```

---

## Troubleshooting

### Graph Shows "No data available"

**Possible Causes:**
1. No data has been scraped yet
2. The USGS URL is incorrect
3. The monitoring location doesn't have water level data

**Solutions:**
1. Go to USGS Water Levels settings
2. Find your graph
3. Click "Scrape Now"
4. Wait for the page to reload
5. Check if status is green or red
6. If red, read the error message

### Graph Shows "Please select a graph"

**Cause:** No graph is selected in the block settings

**Solution:**
1. Edit the page/post
2. Click on the graph block
3. Look in the right sidebar
4. Select a graph from the dropdown

### Chart Doesn't Appear on Frontend

**Possible Causes:**
1. JavaScript error on the page
2. Chart.js library not loading
3. Browser caching old version

**Solutions:**
1. Right-click on the page → "Inspect"
2. Click the "Console" tab
3. Look for red error messages
4. Try hard-refreshing: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
5. Contact your site administrator if errors appear

### Data Seems Old or Not Updating

**Check When Last Updated:**
1. Go to USGS Water Levels settings
2. Look at "Last Scrape" column
3. If it's old, click "Scrape Now"

**Check If Scraping Is Enabled:**
1. Click "Edit" on your graph
2. Make sure "Enable scraping" is checked
3. Update the graph

**Check Scrape Interval:**
- If set to 168 hours (7 days), data only updates weekly
- Consider changing to 24 hours for daily updates

### Colors Not Changing

**Solutions:**
1. Make sure you saved the post/page after changing colors
2. Hard-refresh the page in your browser
3. Clear your browser cache
4. Check if custom CSS is overriding the colors

---

## Frequently Asked Questions

### How often is data updated?

Data is updated based on the "Scrape Interval" you set for each graph. For example:
- 1 hour = Checks for new data every hour
- 24 hours = Checks once per day
- 168 hours = Checks once per week

You can also click "Scrape Now" to update data immediately.

### How much historical data is stored?

The plugin automatically stores up to 2 years of historical data. Older data is automatically deleted to save database space.

### Can I display multiple graphs on one page?

Yes! Simply insert multiple "USGS Water Level Graph" blocks and select different graphs for each one.

### Can I export the data?

Currently, the plugin displays data but doesn't have a built-in export feature. You would need to:
1. Visit the USGS website directly
2. Use the USGS's export tools
3. Or ask your site administrator to export from the database

### Does this work on mobile devices?

Yes! The charts are fully responsive and work on phones and tablets. They're touch-enabled, so users can tap data points to see values.

### What if a USGS monitoring location is discontinued?

If the USGS stops monitoring a location:
1. The scraping will start failing
2. You'll see error messages in the admin
3. You'll need to find a new monitoring location
4. Edit the graph and update the URL

### How much does this plugin cost?

The USGS Water Levels plugin is free. However, you need:
- A WordPress website
- Web hosting
- The USGS data is also free

### Is there a limit to how many graphs I can create?

No hard limit, but practical considerations:
- Each graph takes database space
- Each scraping operation uses server resources
- Keep to a reasonable number (10-20) on shared hosting

### Can I use this for real-time monitoring?

Not quite. The plugin:
- Scrapes data on a schedule (minimum every hour)
- There's a delay between USGS updates and your site updates
- For true real-time needs, contact USGS directly about their APIs

### What happens if USGS changes their website?

If USGS significantly changes their website structure:
1. The scraping might stop working
2. You'll see error messages
3. The plugin would need an update
4. Contact your site administrator or the plugin developer

### Can I change how the chart looks?

Yes! You can:
- Change colors using the block settings
- Adjust width
- Add custom CSS for advanced styling
- The chart itself uses Chart.js, which has many customization options (requires technical knowledge)

### Will this slow down my website?

Not noticeably:
- Scraping happens in the background
- Charts only load on pages where they're used
- Chart.js is relatively lightweight (~200KB)
- Data is cached efficiently

### How do I get support?

1. Check this user guide first
2. Contact your site administrator
3. Check the WordPress.org support forums
4. Review the technical documentation if you're a developer

---

## Tips for Best Results

### Choosing Monitoring Locations

- Pick locations relevant to your audience
- Verify the location has water level data (not just discharge or temperature)
- Check that the USGS site loads properly before adding it

### Setting Scrape Intervals

- **Daily (24 hours):** Good for most uses
- **Every 6 hours:** For locations that change frequently
- **Weekly (168 hours):** For locations that change slowly or to reduce server load

### Organizing Multiple Graphs

Use clear, descriptive titles:
- ✅ "Smith Creek at Highway 50"
- ✅ "Downtown Monitoring Well #3"
- ❌ "Graph 1"
- ❌ "Test"

### Writing Content Around Your Graphs

Help your readers understand the data:
- Explain what the location is
- Describe why water levels matter
- Note any trends you observe
- Link to related information
- Provide context about normal ranges

### Example Post Structure

```markdown
# Water Levels at Smith Creek

Smith Creek is an important tributary that supplies...

[Insert USGS Water Level Graph block here]

As you can see from the chart above, water levels typically...

For more information, visit [link to USGS page].

Last updated: [date]
```

---

## Glossary

**Block:** A content element in the WordPress block editor (Gutenberg)

**USGS:** United States Geological Survey - a scientific agency that monitors natural resources

**Monitoring Location:** A physical site where USGS measures water levels

**Scraping:** Automatically collecting data from a website

**Scrape Interval:** How often the plugin checks for new data

**Water Level:** The elevation of water at a specific location, usually measured in feet

**Frontend:** The public-facing part of your website that visitors see

**Backend/Admin:** The administrative area of WordPress where you manage content

**Widget:** A standalone feature (this plugin provides a block, not a widget)

---

## Quick Reference Card

### Adding a Graph (Admin)
1. USGS Water Levels → Add New Graph
2. Fill in title and URL
3. Set interval
4. Enable scraping
5. Save
6. Click "Scrape Now"

### Inserting Block (Editor)
1. Click (+) button
2. Search "USGS Water Level"
3. Insert block
4. Select graph from dropdown
5. Customize colors/width
6. Publish

### Troubleshooting Checklist
□ Is the graph enabled in settings?
□ Has data been scraped? (Click "Scrape Now")
□ Is a graph selected in the block?
□ Is the page published (not just saved)?
□ Try hard-refresh in browser
□ Check browser console for errors

---

**Need More Help?**

- Technical Documentation: `TECHNICAL-DOCUMENTATION.md`
- Installation Guide: `INSTALL.txt`
- Setup Guide: `SETUP.md`
- WordPress Support: https://wordpress.org/support/

---

*Last Updated: April 2026*
*Plugin Version: 1.0.0*
