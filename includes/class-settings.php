<?php
/**
 * Settings page for USGS Water Levels plugin.
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class for managing admin interface.
 */
class USGS_Water_Levels_Settings {

	/**
	 * Single instance of the class.
	 *
	 * @var USGS_Water_Levels_Settings
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return USGS_Water_Levels_Settings
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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_usgs_wl_save_graph', array( $this, 'handle_save_graph' ) );
		add_action( 'admin_post_usgs_wl_delete_graph', array( $this, 'handle_delete_graph' ) );
		add_action( 'admin_post_usgs_wl_scrape_now', array( $this, 'handle_scrape_now' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'USGS Water Levels', 'usgs-water-levels' ),
			__( 'USGS Water Levels', 'usgs-water-levels' ),
			'manage_options',
			'usgs-water-levels',
			array( $this, 'render_settings_page' ),
			'dashicons-chart-line',
			30
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'usgs-water-levels' ) );
		}

		$action    = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$graph_id  = isset( $_GET['graph_id'] ) ? absint( $_GET['graph_id'] ) : 0;
		$message   = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $message ) : ?>
				<?php $this->render_notice( $message ); ?>
			<?php endif; ?>

			<?php
			switch ( $action ) {
				case 'add':
					$this->render_add_form();
					break;
				case 'edit':
					$this->render_edit_form( $graph_id );
					break;
				default:
					$this->render_graphs_list();
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render notice message.
	 *
	 * @param string $message Message key.
	 */
	private function render_notice( $message ) {
		$messages = array(
			'graph_saved'   => array(
				'type' => 'success',
				'text' => __( 'Graph saved successfully.', 'usgs-water-levels' ),
			),
			'graph_deleted' => array(
				'type' => 'success',
				'text' => __( 'Graph deleted successfully.', 'usgs-water-levels' ),
			),
			'scrape_success' => array(
				'type' => 'success',
				'text' => __( 'Data scraped successfully.', 'usgs-water-levels' ),
			),
			'scrape_error'  => array(
				'type' => 'error',
				'text' => __( 'Failed to scrape data. Please check the URL and try again.', 'usgs-water-levels' ),
			),
			'error'         => array(
				'type' => 'error',
				'text' => __( 'An error occurred. Please try again.', 'usgs-water-levels' ),
			),
		);

		$notice = isset( $messages[ $message ] ) ? $messages[ $message ] : $messages['error'];

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $notice['type'] ),
			esc_html( $notice['text'] )
		);
	}

	/**
	 * Render graphs list.
	 */
	private function render_graphs_list() {
		$graphs = USGS_Water_Levels_Database::get_all_graphs();

		// Check for scrape status messages in URL.
		$scrape_message = isset( $_GET['scrape_status'] ) ? sanitize_text_field( wp_unslash( $_GET['scrape_status'] ) ) : '';
		$graph_id       = isset( $_GET['graph_id'] ) ? absint( $_GET['graph_id'] ) : 0;

		?>

		<?php if ( $scrape_message && $graph_id ) : ?>
			<?php
			$scrape_log = USGS_Water_Levels_Scraper::get_scrape_log( $graph_id );
			$graph      = USGS_Water_Levels_Database::get_graph_config( $graph_id );
			?>
			<div style="margin: 20px 0 30px 0; padding: 15px 20px; border-left: 4px solid <?php echo 'success' === $scrape_message ? '#46b450' : '#dc3232'; ?>; background: <?php echo 'success' === $scrape_message ? '#ecf7ed' : '#fcf0f1'; ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h3 style="margin: 0 0 10px 0; color: <?php echo 'success' === $scrape_message ? '#1e4620' : '#761919'; ?>;">
					<?php
					if ( 'success' === $scrape_message ) {
						echo '✓ ' . esc_html__( 'Scrape Successful', 'usgs-water-levels' );
					} else {
						echo '✗ ' . esc_html__( 'Scrape Failed', 'usgs-water-levels' );
					}
					?>
				</h3>
				<p style="margin: 0;">
					<strong><?php esc_html_e( 'Graph:', 'usgs-water-levels' ); ?></strong> <?php echo esc_html( $graph['title'] ?? "ID: $graph_id" ); ?>
				</p>
				<?php if ( $scrape_log && ! empty( $scrape_log['message'] ) ) : ?>
					<p style="margin: 8px 0 0 0;">
						<strong><?php esc_html_e( 'Details:', 'usgs-water-levels' ); ?></strong> <?php echo esc_html( $scrape_log['message'] ); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div style="margin: 20px 0 30px 0; padding: 20px; border-left: 4px solid #72aee6; background: #f0f6fc; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<h2 style="margin-top: 0; color: #1d2327;"><?php esc_html_e( '📊 How to Display Graphs', 'usgs-water-levels' ); ?></h2>

			<div style="background: white; padding: 15px; margin: 15px 0; border-radius: 4px;">
				<h3 style="margin-top: 0; color: #2271b1;"><?php esc_html_e( 'Option 1: Gutenberg Block', 'usgs-water-levels' ); ?></h3>
				<p style="margin: 0;"><?php esc_html_e( 'Insert the "USGS Water Level Graph" block in the block editor and select your graph from the dropdown.', 'usgs-water-levels' ); ?></p>
			</div>

			<div style="background: white; padding: 15px; margin: 15px 0; border-radius: 4px;">
				<h3 style="margin-top: 0; color: #2271b1;"><?php esc_html_e( 'Option 2: Shortcode', 'usgs-water-levels' ); ?></h3>
				<p><?php esc_html_e( 'Copy the shortcode from the table below, or build your own using these parameters:', 'usgs-water-levels' ); ?></p>

				<table style="width: 100%; margin: 15px 0; border-collapse: collapse; border: 1px solid #ddd;">
					<thead>
						<tr style="background: #f6f7f7;">
							<th style="padding: 12px; text-align: left; font-family: monospace; color: #2271b1; border-bottom: 2px solid #ddd; width: 150px;"><?php esc_html_e( 'Parameter', 'usgs-water-levels' ); ?></th>
							<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Description', 'usgs-water-levels' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="padding: 10px 12px; font-family: monospace; color: #2271b1; border-bottom: 1px solid #eee; background: #f9f9f9;"><strong>id</strong></td>
							<td style="padding: 10px 12px; border-bottom: 1px solid #eee;"><?php esc_html_e( '(required) Graph ID from table below', 'usgs-water-levels' ); ?></td>
						</tr>
						<tr>
							<td style="padding: 10px 12px; font-family: monospace; color: #2271b1; border-bottom: 1px solid #eee; background: #f9f9f9;"><strong>chart_type</strong></td>
							<td style="padding: 10px 12px; border-bottom: 1px solid #eee;"><?php esc_html_e( '(optional) "line", "area", or "bar" - default: "line"', 'usgs-water-levels' ); ?></td>
						</tr>
						<tr>
							<td style="padding: 10px 12px; font-family: monospace; color: #2271b1; border-bottom: 1px solid #eee; background: #f9f9f9;"><strong>width</strong></td>
							<td style="padding: 10px 12px; border-bottom: 1px solid #eee;"><?php esc_html_e( '(optional) "100%", "600px", "80vw" - default: "100%"', 'usgs-water-levels' ); ?></td>
						</tr>
						<tr>
							<td style="padding: 10px 12px; font-family: monospace; color: #2271b1; border-bottom: 1px solid #eee; background: #f9f9f9;"><strong>line_color</strong></td>
							<td style="padding: 10px 12px; border-bottom: 1px solid #eee;"><?php esc_html_e( '(optional) Hex color code - default: "#0073aa"', 'usgs-water-levels' ); ?></td>
						</tr>
						<tr>
							<td style="padding: 10px 12px; font-family: monospace; color: #2271b1; background: #f9f9f9;"><strong>class</strong></td>
							<td style="padding: 10px 12px;"><?php esc_html_e( '(optional) Custom CSS classes', 'usgs-water-levels' ); ?></td>
						</tr>
					</tbody>
				</table>

				<p style="margin: 20px 0 10px 0;"><strong><?php esc_html_e( '📝 Shortcode Examples:', 'usgs-water-levels' ); ?></strong></p>
				<div style="margin: 0;">
					<code style="display: block; background: #2c3338; color: #f0f0f1; padding: 15px; margin: 8px 0; border-left: 4px solid #2271b1; font-size: 13px; border-radius: 3px;">[usgs_water_level id="1"]</code>
					<code style="display: block; background: #2c3338; color: #f0f0f1; padding: 15px; margin: 8px 0; border-left: 4px solid #2271b1; font-size: 13px; border-radius: 3px;">[usgs_water_level id="1" chart_type="area"]</code>
					<code style="display: block; background: #2c3338; color: #f0f0f1; padding: 15px; margin: 8px 0; border-left: 4px solid #2271b1; font-size: 13px; border-radius: 3px;">[usgs_water_level id="1" chart_type="bar" width="600px" line_color="#dc3545"]</code>
				</div>
			</div>
		</div>

		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=usgs-water-levels&action=add' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add New Graph', 'usgs-water-levels' ); ?>
			</a>
		</p>

		<?php if ( empty( $graphs ) ) : ?>
			<p><?php esc_html_e( 'No graphs configured yet. Click "Add New Graph" to get started.', 'usgs-water-levels' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e( 'ID', 'usgs-water-levels' ); ?></th>
						<th><?php esc_html_e( 'Title', 'usgs-water-levels' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'usgs-water-levels' ); ?></th>
						<th><?php esc_html_e( 'USGS URL', 'usgs-water-levels' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Scrape Interval', 'usgs-water-levels' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Status', 'usgs-water-levels' ); ?></th>
						<th style="width: 120px;"><?php esc_html_e( 'Last Scrape', 'usgs-water-levels' ); ?></th>
						<th style="width: 220px;"><?php esc_html_e( 'Actions', 'usgs-water-levels' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $graphs as $graph ) : ?>
						<?php
						$last_scrape = USGS_Water_Levels_Cron::get_last_scrape_time( $graph['id'] );
						$scrape_log  = USGS_Water_Levels_Scraper::get_scrape_log( $graph['id'] );
						?>
						<tr>
							<td><?php echo absint( $graph['id'] ); ?></td>
							<td><strong><?php echo esc_html( $graph['title'] ); ?></strong></td>
							<td>
								<code style="font-size: 11px;">[usgs_water_level id="<?php echo absint( $graph['id'] ); ?>"]</code>
							</td>
							<td>
								<a href="<?php echo esc_url( $graph['usgs_url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( wp_trim_words( $graph['usgs_url'], 6, '...' ) ); ?>
								</a>
							</td>
							<td>
								<?php
								printf(
									/* translators: %d: number of hours */
									esc_html__( '%d hours', 'usgs-water-levels' ),
									absint( $graph['scrape_interval'] )
								);
								?>
							</td>
							<td>
								<?php if ( $graph['is_enabled'] ) : ?>
									<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
									<?php esc_html_e( 'Enabled', 'usgs-water-levels' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
									<?php esc_html_e( 'Disabled', 'usgs-water-levels' ); ?>
								<?php endif; ?>
								<?php if ( $scrape_log && 'error' === $scrape_log['status'] ) : ?>
									<br><small style="color: #dc3232;" title="<?php echo esc_attr( $scrape_log['message'] ); ?>">
										<?php esc_html_e( 'Last scrape failed', 'usgs-water-levels' ); ?>
									</small>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $last_scrape ) : ?>
									<?php echo esc_html( human_time_diff( $last_scrape, time() ) . ' ago' ); ?>
								<?php else : ?>
									<?php esc_html_e( 'Never', 'usgs-water-levels' ); ?>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=usgs-water-levels&action=edit&graph_id=' . $graph['id'] ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'usgs-water-levels' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=usgs_wl_scrape_now&graph_id=' . $graph['id'] ), 'usgs_wl_scrape_now_' . $graph['id'] ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Scrape Now', 'usgs-water-levels' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=usgs_wl_delete_graph&graph_id=' . $graph['id'] ), 'usgs_wl_delete_graph_' . $graph['id'] ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this graph and all its data?', 'usgs-water-levels' ); ?>');">
									<?php esc_html_e( 'Delete', 'usgs-water-levels' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render add graph form.
	 */
	private function render_add_form() {
		$this->render_graph_form( null );
	}

	/**
	 * Render edit graph form.
	 *
	 * @param int $graph_id Graph ID.
	 */
	private function render_edit_form( $graph_id ) {
		$graph = USGS_Water_Levels_Database::get_graph_config( $graph_id );

		if ( ! $graph ) {
			echo '<p>' . esc_html__( 'Graph not found.', 'usgs-water-levels' ) . '</p>';
			return;
		}

		$this->render_graph_form( $graph );
	}

	/**
	 * Render graph form (add/edit).
	 *
	 * @param array|null $graph Graph data or null for new graph.
	 */
	private function render_graph_form( $graph ) {
		$is_edit = ! empty( $graph );
		$title   = $is_edit ? __( 'Edit Graph', 'usgs-water-levels' ) : __( 'Add New Graph', 'usgs-water-levels' );

		$graph_data = wp_parse_args(
			$graph,
			array(
				'id'                => 0,
				'title'             => '',
				'usgs_url'          => '',
				'scrape_interval'   => 24,
				'is_enabled'        => 1,
				'custom_css'        => '',
				'date_start'        => '',
				'date_end'          => '',
				'auto_update_dates' => 0,
			)
		);

		?>
		<h2><?php echo esc_html( $title ); ?></h2>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=usgs-water-levels' ) ); ?>">
				&larr; <?php esc_html_e( 'Back to Graphs', 'usgs-water-levels' ); ?>
			</a>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'usgs_wl_save_graph', 'usgs_wl_nonce' ); ?>
			<input type="hidden" name="action" value="usgs_wl_save_graph">
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="graph_id" value="<?php echo absint( $graph_data['id'] ); ?>">
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="title"><?php esc_html_e( 'Graph Title', 'usgs-water-levels' ); ?></label>
					</th>
					<td>
						<input type="text" name="title" id="title" class="regular-text" value="<?php echo esc_attr( $graph_data['title'] ); ?>" required>
						<p class="description"><?php esc_html_e( 'A descriptive title for this graph.', 'usgs-water-levels' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="usgs_url"><?php esc_html_e( 'USGS URL', 'usgs-water-levels' ); ?></label>
					</th>
					<td>
						<input type="url" name="usgs_url" id="usgs_url" class="large-text" value="<?php echo esc_attr( $graph_data['usgs_url'] ); ?>" required>
						<p class="description">
							<?php esc_html_e( 'Full URL of the USGS monitoring location page.', 'usgs-water-levels' ); ?>
							<br>
							<?php esc_html_e( 'Example: https://waterdata.usgs.gov/monitoring-location/USGS-410858072171501/', 'usgs-water-levels' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="date_start"><?php esc_html_e( 'Date Range', 'usgs-water-levels' ); ?></label>
					</th>
					<td>
						<label for="date_start"><?php esc_html_e( 'Start Date:', 'usgs-water-levels' ); ?></label>
						<input type="date" name="date_start" id="date_start" value="<?php echo esc_attr( $graph_data['date_start'] ?? '' ); ?>">
						&nbsp;&nbsp;
						<label for="date_end"><?php esc_html_e( 'End Date:', 'usgs-water-levels' ); ?></label>
						<input type="date" name="date_end" id="date_end" value="<?php echo esc_attr( $graph_data['date_end'] ?? '' ); ?>">
						<p class="description"><?php esc_html_e( 'Optional: Limit scraped data to this date range. Leave blank to scrape all available data.', 'usgs-water-levels' ); ?></p>
						<p style="margin-top: 10px;">
							<label>
								<input type="checkbox" name="auto_update_dates" id="auto_update_dates" value="1" <?php checked( ! empty( $graph_data['auto_update_dates'] ) ); ?>>
								<?php esc_html_e( 'Auto-update date range (rolling window)', 'usgs-water-levels' ); ?>
							</label>
						</p>
						<p class="description"><?php esc_html_e( 'When enabled, the end date will automatically update to today, and the start date will move forward by the same amount, maintaining a consistent time window.', 'usgs-water-levels' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="scrape_interval"><?php esc_html_e( 'Scrape Interval', 'usgs-water-levels' ); ?></label>
					</th>
					<td>
						<input type="number" name="scrape_interval" id="scrape_interval" class="small-text" value="<?php echo absint( $graph_data['scrape_interval'] ); ?>" min="1" max="168" required>
						<?php esc_html_e( 'hours', 'usgs-water-levels' ); ?>
						<p class="description"><?php esc_html_e( 'How often to scrape data from the USGS website (1-168 hours).', 'usgs-water-levels' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Status', 'usgs-water-levels' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="is_enabled" value="1" <?php checked( $graph_data['is_enabled'], 1 ); ?>>
							<?php esc_html_e( 'Enable scraping for this graph', 'usgs-water-levels' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="custom_css"><?php esc_html_e( 'Custom CSS', 'usgs-water-levels' ); ?></label>
					</th>
					<td>
						<textarea name="custom_css" id="custom_css" class="large-text code" rows="8"><?php echo esc_textarea( $graph_data['custom_css'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Optional CSS to apply to this graph only.', 'usgs-water-levels' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( $is_edit ? __( 'Update Graph', 'usgs-water-levels' ) : __( 'Add Graph', 'usgs-water-levels' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Handle save graph request.
	 */
	public function handle_save_graph() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'usgs-water-levels' ) );
		}

		check_admin_referer( 'usgs_wl_save_graph', 'usgs_wl_nonce' );

		$graph_id = isset( $_POST['graph_id'] ) ? absint( $_POST['graph_id'] ) : 0;

		// Validate and sanitize date fields.
		$date_start = '';
		if ( ! empty( $_POST['date_start'] ) ) {
			$date_start = sanitize_text_field( wp_unslash( $_POST['date_start'] ) );
			// Validate date format (Y-m-d).
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_start ) ) {
				$date_start = '';
			}
		}

		$date_end = '';
		if ( ! empty( $_POST['date_end'] ) ) {
			$date_end = sanitize_text_field( wp_unslash( $_POST['date_end'] ) );
			// Validate date format (Y-m-d).
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_end ) ) {
				$date_end = '';
			}
		}

		$data = array(
			'title'             => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'usgs_url'          => isset( $_POST['usgs_url'] ) ? esc_url_raw( wp_unslash( $_POST['usgs_url'] ) ) : '',
			'scrape_interval'   => isset( $_POST['scrape_interval'] ) ? absint( $_POST['scrape_interval'] ) : 24,
			'is_enabled'        => isset( $_POST['is_enabled'] ) ? 1 : 0,
			'date_start'        => $date_start,
			'date_end'          => $date_end,
			'auto_update_dates' => isset( $_POST['auto_update_dates'] ) ? 1 : 0,
			'custom_css'        => isset( $_POST['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) ) : '',
		);

		if ( $graph_id ) {
			// Update existing graph.
			$result = USGS_Water_Levels_Database::update_graph( $graph_id, $data );
		} else {
			// Create new graph.
			$result = USGS_Water_Levels_Database::create_graph( $data );
		}

		$message = $result ? 'graph_saved' : 'error';

		wp_safe_redirect(
			add_query_arg(
				array( 'message' => $message ),
				admin_url( 'admin.php?page=usgs-water-levels' )
			)
		);
		exit;
	}

	/**
	 * Handle delete graph request.
	 */
	public function handle_delete_graph() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'usgs-water-levels' ) );
		}

		$graph_id = isset( $_GET['graph_id'] ) ? absint( $_GET['graph_id'] ) : 0;

		check_admin_referer( 'usgs_wl_delete_graph_' . $graph_id );

		$result  = USGS_Water_Levels_Database::delete_graph( $graph_id );
		$message = $result ? 'graph_deleted' : 'error';

		wp_safe_redirect(
			add_query_arg(
				array( 'message' => $message ),
				admin_url( 'admin.php?page=usgs-water-levels' )
			)
		);
		exit;
	}

	/**
	 * Handle manual scrape request.
	 */
	public function handle_scrape_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'usgs-water-levels' ) );
		}

		$graph_id = isset( $_GET['graph_id'] ) ? absint( $_GET['graph_id'] ) : 0;

		check_admin_referer( 'usgs_wl_scrape_now_' . $graph_id );

		$result = USGS_Water_Levels_Cron::manual_scrape( $graph_id );
		$status = is_wp_error( $result ) ? 'error' : 'success';

		wp_safe_redirect(
			add_query_arg(
				array(
					'scrape_status' => $status,
					'graph_id'      => $graph_id,
				),
				admin_url( 'admin.php?page=usgs-water-levels' )
			)
		);
		exit;
	}
}
