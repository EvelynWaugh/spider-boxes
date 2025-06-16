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
class ComponentTypeController extends BaseController {

	/**
	 * Register routes
	 */
	public function register_routes() {

		// Component types endpoint.
		register_rest_route(
			$this->namespace,
			'/component-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_component_types' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_component_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Single component type endpoint
		register_rest_route(
			$this->namespace,
			'/component-types/(?P<id>[\\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_component_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_component_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_component_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Component type configuration endpoint.
		register_rest_route(
			$this->namespace,
			'/component-types/(?P<type>[\\w-]+)/config',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_component_type_config' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Get component types.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 *
	 * @since 1.0.0
	 */
	public function get_component_types( $request ) {
		// Get component types from database instead of registry.

		$component_registry = spider_boxes()->get_container()->get( 'componentRegistry' );
		$component_types    = $component_registry->get_all_component_types();

		// Convert to nested format to match expected structure
		$formatted_types = array();
		foreach ( $component_types as $type ) {
			$formatted_types[ $type['id'] ] = $type;
		}

		return rest_ensure_response( array( 'component_types' => $formatted_types ) );
	}
	/**
	 * Get single component type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_component_type( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Component type ID is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Get component types from database
		$component_types = DatabaseManager::get_component_types();
		$component_type  = null;

		// Find the component type by ID or type
		foreach ( $component_types as $type ) {
			if ( $type['id'] === $id || $type['type'] === $id ) {
				$component_type = $type;
				break;
			}
		}

		if ( ! $component_type ) {
			return new WP_Error( 'component_type_not_found', __( 'Component type not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $component_type );
	}

	/**
	 * Create component type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_component_type( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'no_data', __( 'No data provided', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Validate required fields.
		$required_fields = array( 'type', 'name' );
		foreach ( $required_fields as $field ) {
			if ( empty( $params[ $field ] ) ) {
				return new WP_Error(
					'missing_required_field',
					// translators: %s is the field name.
					sprintf( __( 'Missing required field: %s', 'spider-boxes' ), $field ),
					array( 'status' => 400 )
				);
			}
		}

		// Sanitize input data.
		$component_type_data = array(
			'type'        => sanitize_key( $params['type'] ),
			'name'        => sanitize_text_field( $params['name'] ),
			'class_name'  => sanitize_text_field( $params['class_name'] ?? '' ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'icon'        => sanitize_text_field( $params['icon'] ?? 'component' ),
			'supports'    => is_array( $params['supports'] ?? array() ) ? $params['supports'] : array(),
			'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : true,
		);

		// Register component type in database.
		$success = DatabaseManager::register_component_type( $component_type_data );

		if ( ! $success ) {
			return new WP_Error( 'component_type_create_failed', __( 'Failed to create component type', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'component_type' => $component_type_data,
			)
		);
	}

	/**
	 * Update component type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_component_type( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'no_data', __( 'No data provided', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Component type ID is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Check if component type exists
		$existing_types          = DatabaseManager::get_component_types();
		$existing_component_type = null;

		foreach ( $existing_types as $type ) {
			if ( $type['id'] === $id || $type['type'] === $id ) {
				$existing_component_type = $type;
				break;
			}
		}

		if ( ! $existing_component_type ) {
			return new WP_Error( 'component_type_not_found', __( 'Component type not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Merge with existing data and validate required fields
		$component_type_data = array_merge(
			$existing_component_type,
			array(
				'name'        => sanitize_text_field( $params['name'] ?? $existing_component_type['name'] ),
				'class_name'  => sanitize_text_field( $params['class_name'] ?? $existing_type['class_name'] ),
				'description' => sanitize_textarea_field( $params['description'] ?? $existing_component_type['description'] ),
				'icon'        => sanitize_text_field( $params['icon'] ?? $existing_component_type['icon'] ),
				'supports'    => is_array( $params['supports'] ?? $existing_component_type['supports'] ) ? $params['supports'] ?? $existing_component_type['supports'] : $existing_component_type['supports'],
				'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : $existing_component_type['is_active'],
			)
		);

		// Update component type in database
		$success = DatabaseManager::register_component_type( $component_type_data );

		if ( ! $success ) {
			return new WP_Error( 'component_type_update_failed', __( 'Failed to update component type', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'component_type' => $component_type_data,
			)
		);
	}

	/**
	 * Delete component type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_component_type( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Component type ID is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$success = DatabaseManager::delete_component_type( $id );

		if ( ! $success ) {
			return new WP_Error( 'component_type_delete_failed', __( 'Failed to delete component type', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Component type deleted successfully', 'spider-boxes' ),
			)
		);
	}

	/**
	 * Get component type configuration
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_component_type_config( $request ) {
		$component_type = $request->get_param( 'type' );

		if ( empty( $component_type ) ) {
			return new WP_Error( 'missing_type', __( 'Component type is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Get component type from registry and database.
		$component_registry       = spider_boxes()->get_container()->get( 'componentRegistry' );
		$registry_component_types = $component_registry->get_component_types();
		$db_component_types       = DatabaseManager::get_component_types();

		// Find the component type.
		$component_type_config = null;

		// First check registry.
		if ( isset( $registry_component_types[ $component_type ] ) ) {
			$component_type_config         = $registry_component_types[ $component_type ];
			$component_type_config['type'] = $component_type;
		}

		// Override with database config if exists.
		foreach ( $db_component_types as $db_type ) {
			if ( $db_type['type'] === $component_type ) {
				$component_type_config = array_merge( $component_type_config ?? array(), $db_type );
				break;
			}
		}

		if ( ! $component_type_config ) {
			return new WP_Error( 'component_type_not_found', __( 'Component type not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		$config_generator = spider_boxes()->get_container()->get( 'fieldConfigGenerator' );
		$config_fields    = $config_generator->generate_config_fields( $component_type_config );

		return rest_ensure_response(
			array(
				'component_type' => $component_type_config,
				'config_fields'  => $config_fields,
			)
		);
	}
}
