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
class SectionTypeController extends BaseController {

	/**
	 * Register routes
	 */
	public function register_routes() {

		// Section types endpoint
		register_rest_route(
			$this->namespace,
			'/section-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_section_types' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_section_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Single section type endpoint
		register_rest_route(
			$this->namespace,
			'/section-types/(?P<id>[\\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_section_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_section_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_section_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Section type configuration endpoint.
		register_rest_route(
			$this->namespace,
			'/section-types/(?P<type>[\\w-]+)/config',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_section_type_config' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Get section types.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_section_types( $request ) {
		// Get section types from database instead of registry.
		$section_types = DatabaseManager::get_section_types();

		// Convert to nested format to match expected structure
		$formatted_types = array();
		foreach ( $section_types as $type ) {
			$formatted_types[ $type['id'] ] = $type;
		}

		return rest_ensure_response( array( 'section_types' => $formatted_types ) );
	}

	/**
	 * Get single section type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_section_type( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Section type ID is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Get section types from database
		$section_types = DatabaseManager::get_section_types();
		$section_type  = null;

		// Find the section type by ID or type
		foreach ( $section_types as $type ) {
			if ( $type['id'] === $id || $type['type'] === $id ) {
				$section_type = $type;
				break;
			}
		}

		if ( ! $section_type ) {
			return new WP_Error( 'section_type_not_found', __( 'Section type not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $section_type );
	}

	/**
	 * Create section type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_section_type( $request ) {
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
		$section_type_data = array(
			'type'        => sanitize_key( $params['type'] ),
			'name'        => sanitize_text_field( $params['name'] ),
			'class_name'  => sanitize_text_field( $params['class_name'] ?? '' ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'icon'        => sanitize_text_field( $params['icon'] ?? 'section' ),
			'supports'    => is_array( $params['supports'] ?? array() ) ? $params['supports'] : array(),
			'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : true,
		);

		// Register section type in database.
		$success = DatabaseManager::register_section_type( $section_type_data );

		if ( ! $success ) {
			return new WP_Error( 'section_type_create_failed', __( 'Failed to create section type', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success'      => true,
				'section_type' => $section_type_data,
			)
		);
	}

	/**
	 * Update section type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_section_type( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'no_data', __( 'No data provided', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Section type ID is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Check if section type exists
		$existing_types        = DatabaseManager::get_section_types();
		$existing_section_type = null;

		foreach ( $existing_types as $type ) {
			if ( $type['id'] === $id || $type['type'] === $id ) {
				$existing_section_type = $type;
				break;
			}
		}

		if ( ! $existing_section_type ) {
			return new WP_Error( 'section_type_not_found', __( 'Section type not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Merge with existing data and validate required fields
		$section_type_data = array_merge(
			$existing_section_type,
			array(
				'name'        => sanitize_text_field( $params['name'] ?? $existing_section_type['name'] ),
				'class_name'  => sanitize_text_field( $params['class_name'] ?? $existing_type['class_name'] ),
				'description' => sanitize_textarea_field( $params['description'] ?? $existing_section_type['description'] ),
				'icon'        => sanitize_text_field( $params['icon'] ?? $existing_section_type['icon'] ),
				'supports'    => is_array( $params['supports'] ?? $existing_section_type['supports'] ) ? $params['supports'] ?? $existing_section_type['supports'] : $existing_section_type['supports'],
				'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : $existing_section_type['is_active'],
			)
		);

		// Update section type in database
		$success = DatabaseManager::register_section_type( $section_type_data );

		if ( ! $success ) {
			return new WP_Error( 'section_type_update_failed', __( 'Failed to update section type', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success'      => true,
				'section_type' => $section_type_data,
			)
		);
	}

	/**
	 * Delete section type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_section_type( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Section type ID is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$success = DatabaseManager::delete_section_type( $id );

		if ( ! $success ) {
			return new WP_Error( 'section_type_delete_failed', __( 'Failed to delete section type', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Section type deleted successfully', 'spider-boxes' ),
			)
		);
	}

		/**
		 * Get section type configuration
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return WP_REST_Response|WP_Error
		 */
	public function get_section_type_config( $request ) {
		$section_type = $request->get_param( 'type' );

		if ( empty( $section_type ) ) {
			return new WP_Error( 'missing_type', __( 'Section type is required', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Get section type from registry and database.
		$section_registry       = spider_boxes()->get_container()->get( 'sectionRegistry' );
		$registry_section_types = $section_registry->get_section_types();
		$db_section_types       = DatabaseManager::get_section_types();

		// Find the section type.
		$section_type_config = null;

		// First check registry.
		if ( isset( $registry_section_types[ $section_type ] ) ) {
			$section_type_config         = $registry_section_types[ $section_type ];
			$section_type_config['type'] = $section_type;
		}

		// Override with database config if exists.
		foreach ( $db_section_types as $db_type ) {
			if ( $db_type['type'] === $section_type ) {
				$section_type_config = array_merge( $section_type_config ?? array(), $db_type );
				break;
			}
		}

		if ( ! $section_type_config ) {
			return new WP_Error( 'section_type_not_found', __( 'Section type not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		$config_generator = spider_boxes()->get_container()->get( 'fieldConfigGenerator' );
		$config_fields    = $config_generator->generate_config_fields( $section_type_config );

		return rest_ensure_response(
			array(
				'section_type'  => $section_type_config,
				'config_fields' => $config_fields,
			)
		);
	}
}
