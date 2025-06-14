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
class FieldTypeController extends BaseController {

	/**
	 * Register routes
	 */
	public function register_routes() {
			// Field types endpoint.
		register_rest_route(
			$this->namespace,
			'/field-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_field_types' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_field_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Single field type endpoint
		register_rest_route(
			$this->namespace,
			'/field-types/(?P<id>[\\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_field_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_field_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_field_type' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Field type configuration endpoint.
		register_rest_route(
			$this->namespace,
			'/field-types/(?P<type>[\\w-]+)/config',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_field_type_config' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Get field types
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_field_types( $request ) {
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$field_types    = $field_registry->get_all_field_types();

		return rest_ensure_response(
			apply_filters( 'spider_boxes_rest_field_types', $field_types, $request )
		);
	}

	/**
	 * Get single field type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_field_type( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return $this->error_response( 'missing_id', __( 'Field type ID is required.', 'spider-boxes' ) );
		}

		$field_types = DatabaseManager::get_field_types();
		$field_type  = null;

		foreach ( $field_types as $type ) {
			if ( $type['id'] === $id || $type['type'] === $id ) {
				$field_type = $type;
				break;
			}
		}

		if ( ! $field_type ) {
			return $this->error_response( 'field_type_not_found', __( 'Field type not found.', 'spider-boxes' ), 404 );
		}

		return rest_ensure_response(
			apply_filters( 'spider_boxes_rest_field_type', $field_type, $request )
		);
	}

	/**
	 * Create field type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_field_type( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		if ( empty( $params ) ) {
			return $this->error_response( 'missing_data', __( 'Request data is required.', 'spider-boxes' ) );
		}

		$validation_error = $this->validate_required_fields( $params, array( 'type' ) );
		if ( $validation_error ) {
			return $validation_error;
		}

		// Check if field type already exists
		$existing_types       = DatabaseManager::get_field_types();
		$existing_field_types = wp_list_pluck( $existing_types, 'type' );

		if ( in_array( $params['type'], $existing_field_types, true ) ) {
			return $this->error_response( 'field_type_exists', __( 'Field type already exists.', 'spider-boxes' ), 409 );
		}

		$field_type_data = $this->sanitize_field_type_data( $params );

		do_action( 'spider_boxes_before_create_field_type', $field_type_data, $request );

		$success = DatabaseManager::register_field_type( $field_type_data );

		if ( ! $success ) {
			return $this->error_response( 'creation_failed', __( 'Failed to create field type.', 'spider-boxes' ), 500 );
		}

		do_action( 'spider_boxes_after_create_field_type', $field_type_data, $request );

		return $this->success_response( $field_type_data, __( 'Field type created successfully.', 'spider-boxes' ) );
	}

	/**
	 * Update field type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_field_type( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		if ( empty( $params ) ) {
			return $this->error_response( 'missing_data', __( 'Request data is required.', 'spider-boxes' ) );
		}

		if ( empty( $id ) ) {
			return $this->error_response( 'missing_field_type_id', __( 'Field type ID is required.', 'spider-boxes' ) );
		}

		$existing_types = DatabaseManager::get_field_types();
		$existing_type  = null;

		foreach ( $existing_types as $type ) {
			if ( $type['id'] === $id || $type['type'] === $id ) {
				$existing_type = $type;
				break;
			}
		}

		if ( ! $existing_type ) {
			return $this->error_response( 'field_type_not_found', __( 'Field type not found.', 'spider-boxes' ), 404 );
		}

		$field_type_data = array_merge( $existing_type, $this->sanitize_field_type_data( $params ) );

		do_action( 'spider_boxes_before_update_field_type', $field_type_data, $existing_type, $request );

		$success = DatabaseManager::register_field_type( $field_type_data );

		if ( ! $success ) {
			return $this->error_response( 'update_failed', __( 'Failed to update field type.', 'spider-boxes' ), 500 );
		}

		do_action( 'spider_boxes_after_update_field_type', $field_type_data, $existing_type, $request );

		return $this->success_response( $field_type_data, __( 'Field type updated successfully.', 'spider-boxes' ) );
	}

	/**
	 * Delete field type
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_field_type( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return $this->error_response( 'missing_id', __( 'Field type ID is required.', 'spider-boxes' ) );
		}

		do_action( 'spider_boxes_before_delete_field_type', $id, $request );

		$success = DatabaseManager::delete_field_type( $id );

		if ( ! $success ) {
			return $this->error_response( 'deletion_failed', __( 'Failed to delete field type.', 'spider-boxes' ), 500 );
		}

		do_action( 'spider_boxes_after_delete_field_type', $id, $request );

		return $this->success_response( null, __( 'Field type deleted successfully.', 'spider-boxes' ) );
	}

	/**
	 * Get field type configuration
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_field_type_config( $request ) {
		$field_type = $request->get_param( 'type' );

		if ( empty( $field_type ) ) {
			return $this->error_response( 'missing_type', __( 'Field type is required.', 'spider-boxes' ) );
		}

		$field_registry       = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$registry_field_types = $field_registry->get_field_types();
		$db_field_types       = DatabaseManager::get_db_field_types();

		$field_type_config = null;

		// First check registry.
		if ( isset( $registry_field_types[ $field_type ] ) ) {
			$config            = $registry_field_types[ $field_type ];
			$field_type_config = array(
				'id'          => $field_type,
				'name'        => ucwords( str_replace( array( '_', '-' ), ' ', $field_type ) ),
				'type'        => $field_type,
				'class_name'  => $config['class_name'] ?? '',
				'description' => $config['description'] ?? '',
				'supports'    => $config['supports'] ?? array(),
			);
		}

		// Override with database config if exists
		foreach ( $db_field_types as $db_type ) {
			if ( $db_type['type'] === $field_type ) {
				$field_type_config = array_merge( $field_type_config ?? array(), $db_type );
				break;
			}
		}

		if ( ! $field_type_config ) {
			return $this->error_response( 'field_type_not_found', __( 'Field type not found.', 'spider-boxes' ), 404 );
		}

		$config_generator = spider_boxes()->get_container()->get( 'fieldConfigGenerator' );
		$config_fields    = $config_generator->generate_config_fields( $field_type_config );

		$response = array(
			'type'   => $field_type_config,
			'fields' => $config_fields,
		);

		return rest_ensure_response(
			apply_filters( 'spider_boxes_rest_field_type_config', $response, $request )
		);
	}

	/**
	 * Sanitize field type data
	 *
	 * @param array $params Raw parameters
	 * @return array Sanitized data
	 */
	private function sanitize_field_type_data( $params ) {
		return apply_filters(
			'spider_boxes_sanitize_field_type_data',
			array(
				'type'        => sanitize_key( $params['type'] ?? '' ),
				'class_name'  => sanitize_text_field( $params['class_name'] ?? '' ),
				'icon'        => sanitize_text_field( $params['icon'] ?? 'field' ),
				'description' => sanitize_textarea_field( $params['description'] ?? '' ),
				'supports'    => $params['supports'] ?? array(),
				'is_active'   => (bool) ( $params['is_active'] ?? true ),
			),
			$params
		);
	}
}
