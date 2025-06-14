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
class SectionController extends BaseController {

	/**
	 * Register routes
	 */
	public function register_routes() {

			// Sections endpoint
		register_rest_route(
			$this->namespace,
			'/sections',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sections' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_section' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Single section endpoint
		register_rest_route(
			$this->namespace,
			'/sections/(?P<id>[\\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_section' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_section' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_section' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}


	/**
	 * Get sections
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_sections( $request ) {
		$context = $request->get_param( 'context' );
		$screen  = $request->get_param( 'screen' );

		$sections = DatabaseManager::get_all_sections( $context, $screen );

		return rest_ensure_response( $sections );
	}

	/**
	 * Get single section
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_section( $request ) {
		$id = $request->get_param( 'id' );

		$section = DatabaseManager::get_section_config( $id );

		if ( ! $section ) {
			return new WP_Error( 'section_not_found', __( 'Section not found.', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $section );
	}

	/**
	 * Create section
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_section( $request ) {
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

		$section_data = array(
			'id'          => sanitize_key( $params['id'] ),
			'type'        => sanitize_key( $params['type'] ),
			'title'       => sanitize_text_field( $params['title'] ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'context'     => sanitize_key( $params['context'] ?? 'default' ),
			'screen'      => sanitize_key( $params['screen'] ?? '' ),
			'settings'    => $params['settings'] ?? array(),
			'is_active'   => (bool) ( $params['is_active'] ?? true ),
		);

		$success = DatabaseManager::save_section_config( $section_data['id'], $section_data );

		if ( ! $success ) {
			return new WP_Error( 'creation_failed', __( 'Failed to create section.', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $section_data,
			)
		);
	}

	/**
	 * Update section
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_section( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'missing_data', __( 'Request data is required.', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$existing_config = DatabaseManager::get_section_config( $id );
		if ( ! $existing_config ) {
			return new WP_Error( 'section_not_found', __( 'Section not found.', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		$params['id']   = $id;
		$updated_config = array_merge( $existing_config, $params );

		$section_data = array(
			'id'          => $id,
			'type'        => sanitize_key( $updated_config['type'] ),
			'title'       => sanitize_text_field( $updated_config['title'] ),
			'description' => sanitize_textarea_field( $updated_config['description'] ?? '' ),
			'context'     => sanitize_key( $updated_config['context'] ?? 'default' ),
			'screen'      => sanitize_key( $updated_config['screen'] ?? '' ),
			'settings'    => $updated_config['settings'] ?? array(),
			'is_active'   => (bool) ( $updated_config['is_active'] ?? true ),
		);

		$success = DatabaseManager::save_section_config( $id, $section_data );

		if ( ! $success ) {
			return new WP_Error( 'update_failed', __( 'Failed to update section.', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $section_data,
			)
		);
	}

	/**
	 * Delete section
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_section( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', __( 'Section ID is required.', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		$success = DatabaseManager::delete_section_config( $id );

		if ( ! $success ) {
			return new WP_Error( 'deletion_failed', __( 'Failed to delete section.', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Section deleted successfully.', 'spider-boxes' ),
			)
		);
	}
}
