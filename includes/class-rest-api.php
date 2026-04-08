<?php
/**
 * REST API endpoints for USGS Water Levels plugin.
 *
 * @package USGS_Water_Levels
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API class for providing block editor data.
 */
class USGS_Water_Levels_REST_API {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'usgs-water-levels/v1';

	/**
	 * Initialize REST API routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_routes() {
		// Get all graphs endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/graphs',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_graphs' ),
				'permission_callback' => array( __CLASS__, 'permissions_check' ),
			)
		);

		// Get single graph endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/graphs/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_graph' ),
				'permission_callback' => array( __CLASS__, 'permissions_check' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Permission check for REST API endpoints.
	 *
	 * @return bool True if user can edit posts.
	 */
	public static function permissions_check() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Get all graphs.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response REST response.
	 */
	public static function get_graphs( $request ) {
		$graphs = USGS_Water_Levels_Database::get_all_graphs();

		$response_data = array_map(
			function( $graph ) {
				return array(
					'id'              => absint( $graph['id'] ),
					'title'           => sanitize_text_field( $graph['title'] ),
					'usgs_url'        => esc_url_raw( $graph['usgs_url'] ),
					'scrape_interval' => absint( $graph['scrape_interval'] ),
					'is_enabled'      => (bool) $graph['is_enabled'],
				);
			},
			$graphs
		);

		return rest_ensure_response( $response_data );
	}

	/**
	 * Get single graph.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error REST response or error.
	 */
	public static function get_graph( $request ) {
		$graph_id = absint( $request['id'] );
		$graph    = USGS_Water_Levels_Database::get_graph_config( $graph_id );

		if ( ! $graph ) {
			return new WP_Error(
				'graph_not_found',
				__( 'Graph not found.', 'usgs-water-levels' ),
				array( 'status' => 404 )
			);
		}

		$response_data = array(
			'id'              => absint( $graph['id'] ),
			'title'           => sanitize_text_field( $graph['title'] ),
			'usgs_url'        => esc_url_raw( $graph['usgs_url'] ),
			'scrape_interval' => absint( $graph['scrape_interval'] ),
			'is_enabled'      => (bool) $graph['is_enabled'],
		);

		return rest_ensure_response( $response_data );
	}
}

// Initialize REST API.
USGS_Water_Levels_REST_API::init();
