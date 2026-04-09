<?php
/**
 * Plugin Name: USGS Water Levels
 * Plugin URI: https://github.com/yourusername/usgs-water-levels
 * Description: Scrapes USGS water monitoring data and displays it as interactive graphs via Gutenberg blocks and shortcodes
 * Version: 2.3.5
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: usgs-water-levels
 * Domain Path: /languages
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'USGS_WATER_LEVELS_VERSION', '2.3.5' );
define( 'USGS_WATER_LEVELS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'USGS_WATER_LEVELS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'USGS_WATER_LEVELS_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class.
 */
class USGS_Water_Levels {

	/**
	 * Single instance of the class.
	 *
	 * @var USGS_Water_Levels
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return USGS_Water_Levels
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		require_once USGS_WATER_LEVELS_PLUGIN_DIR . 'includes/class-database.php';
		require_once USGS_WATER_LEVELS_PLUGIN_DIR . 'includes/class-scraper.php';
		require_once USGS_WATER_LEVELS_PLUGIN_DIR . 'includes/class-cron.php';
		require_once USGS_WATER_LEVELS_PLUGIN_DIR . 'includes/class-settings.php';
		require_once USGS_WATER_LEVELS_PLUGIN_DIR . 'includes/class-rest-api.php';
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Activation and deactivation hooks.
		register_activation_hook( USGS_WATER_LEVELS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( USGS_WATER_LEVELS_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Initialize plugin components.
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Register Gutenberg block.
		add_action( 'init', array( $this, 'register_block' ) );

		// Enqueue block editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Plugin activation callback.
	 */
	public function activate() {
		USGS_Water_Levels_Database::create_tables();
		USGS_Water_Levels_Cron::schedule_events();
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public function deactivate() {
		USGS_Water_Levels_Cron::clear_scheduled_events();
		flush_rewrite_rules();
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		// Load text domain for translations.
		load_plugin_textdomain(
			'usgs-water-levels',
			false,
			dirname( plugin_basename( USGS_WATER_LEVELS_PLUGIN_FILE ) ) . '/languages'
		);

		// Initialize settings page.
		if ( is_admin() ) {
			USGS_Water_Levels_Settings::get_instance();
		}

		// Initialize cron handler.
		USGS_Water_Levels_Cron::get_instance();

		// Register shortcode for Classic Editor support.
		add_shortcode( 'usgs_water_level', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register Gutenberg block.
	 */
	public function register_block() {
		// Register block from block.json.
		register_block_type(
			USGS_WATER_LEVELS_PLUGIN_DIR . 'blocks/water-level-graph',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_block_editor_assets() {
		// Enqueue block editor script with proper dependencies.
		wp_enqueue_script(
			'usgs-water-levels-block-editor',
			USGS_WATER_LEVELS_PLUGIN_URL . 'blocks/water-level-graph/index.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-i18n',
				'wp-api-fetch',
			),
			USGS_WATER_LEVELS_VERSION,
			true
		);

		// Enqueue block editor styles.
		wp_enqueue_style(
			'usgs-water-levels-block-editor',
			USGS_WATER_LEVELS_PLUGIN_URL . 'blocks/water-level-graph/style.css',
			array(),
			USGS_WATER_LEVELS_VERSION
		);
	}

	/**
	 * Render block callback.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string Block HTML output.
	 */
	public function render_block( $attributes, $content ) {
		// Get graph ID from attributes.
		$graph_id = isset( $attributes['graphId'] ) ? intval( $attributes['graphId'] ) : 0;

		if ( ! $graph_id ) {
			return '<div class="usgs-water-levels-error">' . esc_html__( 'Please select a graph to display.', 'usgs-water-levels' ) . '</div>';
		}

		// Get graph data.
		$graph_config = USGS_Water_Levels_Database::get_graph_config( $graph_id );
		$measurements = USGS_Water_Levels_Database::get_measurements( $graph_id );

		if ( ! $graph_config || empty( $measurements ) ) {
			return '<div class="usgs-water-levels-error">' . esc_html__( 'No data available for this graph.', 'usgs-water-levels' ) . '</div>';
		}

		// Prepare data for chart.
		$chart_data = array(
			'labels'   => array_column( $measurements, 'measurement_date' ),
			'datasets' => array(
				array(
					'label' => $graph_config['title'],
					'data'  => array_column( $measurements, 'water_level' ),
				),
			),
		);

		// Get block attributes for styling and chart type.
		$chart_type = isset( $attributes['chartType'] ) ? esc_attr( $attributes['chartType'] ) : 'line';
		$width      = isset( $attributes['width'] ) ? esc_attr( $attributes['width'] ) : '100%';
		$line_color = isset( $attributes['lineColor'] ) ? esc_attr( $attributes['lineColor'] ) : '#0073aa';

		// Enqueue frontend script.
		wp_enqueue_script(
			'usgs-water-levels-chart',
			USGS_WATER_LEVELS_PLUGIN_URL . 'assets/js/chart.min.js',
			array(),
			USGS_WATER_LEVELS_VERSION,
			true
		);

		wp_enqueue_script(
			'usgs-water-levels-frontend',
			USGS_WATER_LEVELS_PLUGIN_URL . 'blocks/water-level-graph/view.js',
			array( 'usgs-water-levels-chart' ),
			USGS_WATER_LEVELS_VERSION,
			true
		);

		// Generate unique ID for this chart instance.
		$chart_id = 'usgs-chart-' . $graph_id . '-' . wp_rand();

		// Build wrapper classes.
		$wrapper_classes = 'usgs-water-levels-chart-wrapper';
		if ( isset( $attributes['className'] ) ) {
			$wrapper_classes .= ' ' . esc_attr( $attributes['className'] );
		}

		// Output chart container.
		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_classes ); ?>" style="width: <?php echo esc_attr( $width ); ?>;">
			<?php if ( ! empty( $graph_config['custom_css'] ) ) : ?>
				<style><?php echo wp_strip_all_tags( $graph_config['custom_css'] ); ?></style>
			<?php endif; ?>
			<canvas
				id="<?php echo esc_attr( $chart_id ); ?>"
				data-chart-data="<?php echo esc_attr( wp_json_encode( $chart_data ) ); ?>"
				data-chart-type="<?php echo esc_attr( $chart_type ); ?>"
				data-line-color="<?php echo esc_attr( $line_color ); ?>"
			></canvas>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render shortcode callback for Classic Editor support.
	 *
	 * Usage: [usgs_water_level id="1" chart_type="line" width="100%" line_color="#0073aa"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode HTML output.
	 */
	public function render_shortcode( $atts ) {
		// Parse shortcode attributes with defaults.
		$atts = shortcode_atts(
			array(
				'id'         => 0,
				'chart_type' => 'line',
				'width'      => '100%',
				'line_color' => '#0073aa',
				'class'      => '',
			),
			$atts,
			'usgs_water_level'
		);

		// Validate chart type.
		$valid_types = array( 'line', 'area', 'bar' );
		$chart_type  = in_array( $atts['chart_type'], $valid_types, true ) ? $atts['chart_type'] : 'line';

		// Convert shortcode attributes to block-style attributes.
		$attributes = array(
			'graphId'   => intval( $atts['id'] ),
			'chartType' => $chart_type,
			'width'     => $atts['width'],
			'lineColor' => $atts['line_color'],
			'className' => $atts['class'],
		);

		// Reuse the block rendering logic.
		return $this->render_block( $attributes, '' );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Only load on plugin settings page.
		if ( 'toplevel_page_usgs-water-levels' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'usgs-water-levels-admin',
			USGS_WATER_LEVELS_PLUGIN_URL . 'admin/admin.css',
			array(),
			USGS_WATER_LEVELS_VERSION
		);
	}
}

/**
 * Initialize the plugin.
 */
function usgs_water_levels_init() {
	return USGS_Water_Levels::get_instance();
}

// Start the plugin.
usgs_water_levels_init();
