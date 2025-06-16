<?php
/**
 * Field Type REST Controller
 *
 * @package SpiderBoxes\API\Controllers
 */

namespace SpiderBoxes\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use SpiderBoxes\Database\DatabaseManager;

/**
 * Field Type Controller Class
 */
class ComponentController extends BaseController {

	/**
	 * Register routes
	 */
	public function register_routes() {

			// Components endpoint.
		register_rest_route(
			$this->namespace,
			'/components',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_components' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_component' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Single component endpoint
		register_rest_route(
			$this->namespace,
			'/components/(?P<id>[\\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_component' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_component' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_component' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

			// Component creation with defaults endpoint
		register_rest_route(
			$this->namespace,
			'/components/create-with-defaults',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_component_with_defaults' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}



	/**
	 * Get components
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_components( $request ) {
		$context    = $request->get_param( 'context' );
		$components = DatabaseManager::get_all_components( $context );

		return rest_ensure_response( $components );
	}

	/**
	 * Get single component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_component( $request ) {
		$id = $request->get_param( 'id' );

		$component = DatabaseManager::get_component_config( $id );

		if ( ! $component ) {
			return new WP_Error( 'component_not_found', __( 'Component not found.', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $component );
	}

	/**
	 * Create component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_component( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'missing_data', __( 'Request data is required.', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$required_fields = array( 'id', 'type', 'title' );
		foreach ( $required_fields as $field ) {
			if ( empty( $params[ $field ] ) ) {
				return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'spider-boxes' ), $field ), array( 'status' => 400 ) );
			}
		}

		$component_data = array(
			'id'          => sanitize_key( $params['id'] ),
			'type'        => sanitize_key( $params['type'] ),
			'title'       => sanitize_text_field( $params['title'] ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'context'     => sanitize_key( $params['context'] ?? 'default' ),
			'settings'    => $params['settings'] ?? array(),
			'is_active'   => (bool) ( $params['is_active'] ?? true ),
		);

		$success = DatabaseManager::save_component_config( $component_data['id'], $component_data );

		if ( ! $success ) {
			return new WP_Error( 'creation_failed', __( 'Failed to create component.', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $component_data,
			)
		);
	}

	/**
	 * Update component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_component( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'missing_data', __( 'Request data is required.', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$existing_config = DatabaseManager::get_component_config( $id );
		if ( ! $existing_config ) {
			return new WP_Error( 'component_not_found', __( 'Component not found.', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		$params['id']   = $id;
		$updated_config = array_merge( $existing_config, $params );

		$component_data = array(
			'id'          => $id,
			'type'        => sanitize_key( $updated_config['type'] ),
			'title'       => sanitize_text_field( $updated_config['title'] ),
			'description' => sanitize_textarea_field( $updated_config['description'] ?? '' ),
			'context'     => sanitize_key( $updated_config['context'] ?? 'default' ),
			'settings'    => $updated_config['settings'] ?? array(),
			'is_active'   => (bool) ( $updated_config['is_active'] ?? true ),
		);

		$success = DatabaseManager::save_component_config( $id, $component_data );

		if ( ! $success ) {
			return new WP_Error( 'update_failed', __( 'Failed to update component.', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $component_data,
			)
		);
	}

	/**
	 * Delete component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_component( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Component ID is required.', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$success = DatabaseManager::delete_component_config( $id );

		if ( ! $success ) {
			return new WP_Error( 'deletion_failed', __( 'Failed to delete component.', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Component deleted successfully.', 'spider-boxes' ),
			)
		);
	}

	/**
	 * Create component with defaults
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_component_with_defaults( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'missing_data', __( 'Request data is required.', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$required_fields = array( 'type', 'id' );
		foreach ( $required_fields as $field ) {
			if ( empty( $params[ $field ] ) ) {
				return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'spider-boxes' ), $field ), array( 'status' => 400 ) );
			}
		}

		$component_registry = spider_boxes()->get_container()->get( 'componentRegistry' );
		$success            = $component_registry->create_component_with_defaults(
			$params['type'],
			$params['id'],
			$params
		);

		if ( ! $success ) {
			return new WP_Error( 'creation_failed', __( 'Failed to create component with defaults.', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Component created with defaults successfully.', 'spider-boxes' ),
			)
		);
	}
}
