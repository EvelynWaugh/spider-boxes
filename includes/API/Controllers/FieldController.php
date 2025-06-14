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
class FieldController extends BaseController {

	/**
	 * Register routes
	 */
	public function register_routes() {

		// Fields endpoint
		register_rest_route(
			$this->namespace,
			'/fields',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fields' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_field' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Single field endpoint
		register_rest_route(
			$this->namespace,
			'/fields/(?P<id>[\\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_field' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_field' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_field' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Field value endpoint
		register_rest_route(
			$this->namespace,
			'/field-value',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_field_value' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_field_value' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}



	/**
	 * Get fields
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_fields( $request ) {

		$context = $request->get_param( 'context' );

		// Get fields from database instead of registry.
		$fields = DatabaseManager::get_all_fields( $context );

		return rest_ensure_response( $fields );
	}
	/**
	 * Get single field
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_field( $request ) {
		$id = $request->get_param( 'id' );

		// Get field configuration from database.
		$field = DatabaseManager::get_field_config( $id );

		if ( ! $field ) {
			return new WP_Error( 'field_not_found', __( 'Field not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $field );
	}

	/**
	 * Create field
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_field( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		// Sanitize and validate input.
		$sanitized_config = DatabaseManager::sanitize_field_config( $params );
		$validated_config = DatabaseManager::validate_field_config( $sanitized_config );

		if ( is_wp_error( $validated_config ) ) {
			return $validated_config;
		}

		// Save field configuration to database.
		$success = DatabaseManager::save_field_config( $validated_config['id'], $validated_config );

		if ( ! $success ) {
			return new WP_Error( 'create_failed', __( 'Failed to create field', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also register with field registry for runtime usage.
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$field_registry->register_field( $validated_config['id'], $validated_config );

		return rest_ensure_response(
			array(
				'success' => true,
				'id'      => $validated_config['id'],
				'field'   => $validated_config,
			)
		);
	}

	/**
	 * Update field
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_field( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		// Check if field exists in database.
		$existing_config = DatabaseManager::get_field_config( $id );
		if ( ! $existing_config ) {
			return new WP_Error( 'field_not_found', __( 'Field not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Merge with existing config and ensure ID is preserved.
		$params['id']   = $id;
		$updated_config = array_merge( $existing_config, $params );

		// Sanitize and validate input.
		$sanitized_config = DatabaseManager::sanitize_field_config( $updated_config );
		$validated_config = DatabaseManager::validate_field_config( $sanitized_config );

		if ( is_wp_error( $validated_config ) ) {
			return $validated_config;
		}

		// Save updated field configuration to database.
		$success = DatabaseManager::save_field_config( $id, $validated_config );

		if ( ! $success ) {
			return new WP_Error( 'update_failed', __( 'Failed to update field', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also update field registry for runtime usage.
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$field_registry->remove_field( $id );
		$field_registry->register_field( $id, $validated_config );

		return rest_ensure_response(
			array(
				'success' => true,
				'field'   => $validated_config,
			)
		);
	}

	/**
	 * Delete field
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_field( $request ) {
		$id = $request->get_param( 'id' );

		// Check if field exists in database.
		$existing_config = DatabaseManager::get_field_config( $id );
		if ( ! $existing_config ) {
			return new WP_Error( 'field_not_found', __( 'Field not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Delete from database first.
		$database_success = DatabaseManager::delete_field_config( $id );
		if ( ! $database_success ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete field from database', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also delete related meta values.
		DatabaseManager::delete_field_meta( $id );

		// Remove from runtime registry as well.
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$field_registry->remove_field( $id );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get field value
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_field_value( $request ) {
		$object_id   = $request->get_param( 'object_id' );
		$object_type = $request->get_param( 'object_type' );
		$meta_key    = $request->get_param( 'meta_key' );
		$context     = $request->get_param( 'context' ) ? $request->get_param( 'context' ) : 'default';

		if ( ! $object_id || ! $object_type || ! $meta_key ) {
			return new WP_Error( 'missing_parameters', __( 'Missing required parameters', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Get value from database using DatabaseManager.
		$value = DatabaseManager::get_meta( $object_id, $object_type, $meta_key, $context );

		return rest_ensure_response( array( 'value' => $value ) );
	}

	/**
	 * Save field value
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_field_value( $request ) {
		$params      = $request->get_json_params();
		$object_id   = $params['object_id'] ?? '';
		$object_type = $params['object_type'] ?? '';
		$meta_key    = $params['meta_key'] ?? '';
		$meta_value  = $params['meta_value'] ?? '';
		$context     = $params['context'] ?? 'default';

		if ( ! $object_id || ! $object_type || ! $meta_key ) {
			return new WP_Error( 'missing_parameters', __( 'Missing required parameters', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$success = DatabaseManager::save_meta( $object_id, $object_type, $meta_key, $meta_value, $context );

		if ( ! $success ) {
			return new WP_Error( 'save_failed', __( 'Failed to save field value', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}
}
