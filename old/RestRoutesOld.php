<?php
/**
 * REST API Routes
 *
 * @package SpiderBoxes\API
 */

namespace SpiderBoxes\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use SpiderBoxes\Core\ComponentRegistry;
use SpiderBoxes\Core\SectionRegistry;
use SpiderBoxes\Database\DatabaseManager;

/**
 * REST Routes Class
 */
class RestRoutes {

	/**
	 * API namespace
	 *
	 * @var string
	 * */
	private $namespace = 'spider-boxes/v1';


	/**
	 * Constructor
	 */
	public function __construct() {

		$this->register_routes();
	}

	/**
	 * Register REST API routes
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
		);      // Reviews endpoint (if WooCommerce is active)
		if ( class_exists( 'WooCommerce' ) ) {
			register_rest_route(
				$this->namespace,
				'/reviews',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_reviews' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_review' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_review' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);
			register_rest_route(
				$this->namespace,
				'/reviews/(?P<id>\\d+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_review' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_single_review' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_review' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);          // Review fields endpoint
			register_rest_route(
				$this->namespace,
				'/reviews/fields',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_review_fields' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				)
			);

			// Products endpoint for review creation
			register_rest_route(
				$this->namespace,
				'/products',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				)
			);
		}

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

		/**
		 * Allow developers to register custom REST routes
		 */
		do_action( 'spider_boxes_register_rest_routes', $this->namespace );
	}

	/**
	 * Check permissions for API access
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool
	 */
	public function check_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get field types
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_field_types( $request ) {

		// Get field types from registry (available field classes) and database.
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$field_types    = $field_registry->get_all_field_types();

		return rest_ensure_response( $field_types );
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
			return new WP_Error(
				'missing_field_type',
				__( 'Field type is required', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		// Get field type from registry and database.
		$field_registry       = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$registry_field_types = $field_registry->get_field_types();
		$db_field_types       = DatabaseManager::get_field_types();

		// Find the field type.
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

		// Override with database config if exists.
		foreach ( $db_field_types as $db_type ) {
			if ( $db_type['type'] === $field_type ) {
				$field_type_config = array_merge( $field_type_config ?? array(), $db_type );
				break;
			}
		}

		if ( ! $field_type_config ) {
			return new WP_Error(
				'field_type_not_found',
				__( 'Field type not found', 'spider-boxes' ),
				array( 'status' => 404 )
			);
		}

		// Generate dynamic configuration fields based on supports.
		$config_fields = $this->generate_config_fields( $field_type_config );

		return rest_ensure_response(
			array(
				'field_type'    => $field_type_config,
				'config_fields' => $config_fields,
			)
		);
	}

	/**
	 * Generate configuration fields based on field type supports
	 *
	 * @param array $field_type_config Field type configuration.
	 * @return array
	 */
	private function generate_config_fields( $field_type_config ) {
		$config_generator = spider_boxes()->get_container()->get( 'fieldConfigGenerator' );

		return $config_generator->generate_config_fields( $field_type_config );
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

		// Validate required fields.
		$required_fields = array( 'type' );
		foreach ( $required_fields as $field ) {
			if ( empty( $params[ $field ] ) ) {
				// translators: %s is the field name.
				return new WP_Error(
					'missing_required_field',
					sprintf( __( 'Missing required field: %s', 'spider-boxes' ), $field ),
					array( 'status' => 400 )
				);
			}
		}

		// Check if field type already exists.
		$existing_types       = DatabaseManager::get_field_types();
		$existing_field_types = wp_list_pluck( $existing_types, 'type' );
		if ( in_array( $params['type'], $existing_field_types, true ) ) {
			return new WP_Error(
				'field_type_exists',
				__( 'Field type already exists', 'spider-boxes' ),
				array( 'status' => 409 )
			);
		}
		// Sanitize input data.
		$field_type_data = array(

			'type'        => sanitize_text_field( $params['type'] ),
			'class_name'  => sanitize_text_field( $params['class_name'] ?? '' ),
			'icon'        => sanitize_text_field( $params['icon'] ?? 'component' ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'supports'    => is_array( $params['supports'] ?? array() ) ? $params['supports'] : array(),
			'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : true,
		);

		// Register field type in database.
		$success = DatabaseManager::register_field_type( $field_type_data );

		if ( ! $success ) {
			return new WP_Error(
				'create_failed',
				__( 'Failed to create field type', 'spider-boxes' ),
				array( 'status' => 500 )
			);
		}

		// Also register with field registry for runtime usage.
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$field_registry->register_field_type(
			$field_type_data['type'],
			array(
				'class_name' => $field_type_data['class_name'] ?? '',
				'supports'   => $field_type_data['supports'],

			)
		);
		return rest_ensure_response(
			array(
				'success'    => true,
				'id'         => $field_type_data['id'],
				'field_type' => $field_type_data,
			)
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
			return new WP_Error(
				'missing_field_type_id',
				__( 'Field type ID is required', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		// Get field types from database
		$field_types = DatabaseManager::get_field_types();
		$field_type  = null;

		// Find the field type by ID or type
		foreach ( $field_types as $type ) {
			if ( $type['id'] === $id || $type['type'] === $id || $type['name'] === $id ) {
				$field_type = $type;
				break;
			}
		}

		if ( ! $field_type ) {
			return new WP_Error(
				'field_type_not_found',
				__( 'Field type not found', 'spider-boxes' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $field_type );
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

		if ( empty( $id ) ) {
			return new WP_Error(
				'missing_field_type_id',
				__( 'Field type ID is required', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		// Check if field type exists
		$existing_types      = DatabaseManager::get_field_types();
		$existing_field_type = null;

		foreach ( $existing_types as $type ) {
			if ( $type['id'] === $id || $type['name'] === $id ) {
				$existing_field_type = $type;
				break;
			}
		}

		if ( ! $existing_field_type ) {
			return new WP_Error(
				'field_type_not_found',
				__( 'Field type not found', 'spider-boxes' ),
				array( 'status' => 404 )
			);
		}

		// Merge with existing data and validate required fields
		$field_type_data = array_merge(
			$existing_field_type,
			array(
				'name'        => sanitize_text_field( $params['name'] ?? $existing_field_type['name'] ),
				'class_name'  => sanitize_text_field( $params['class_name'] ?? $existing_field_type['class_name'] ),
				'icon'        => sanitize_text_field( $params['icon'] ?? $existing_field_type['icon'] ),
				'description' => sanitize_textarea_field( $params['description'] ?? $existing_field_type['description'] ),
				'supports'    => is_array( $params['supports'] ?? $existing_field_type['supports'] ) ? ( $params['supports'] ?? $existing_field_type['supports'] ) : array(),
				'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : ( $existing_field_type['is_active'] ?? true ),
			)
		);

		// Update field type in database
		$success = DatabaseManager::register_field_type( $field_type_data );

		if ( ! $success ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update field type', 'spider-boxes' ),
				array( 'status' => 500 )
			);
		}

		// Also update field registry for runtime usage
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$field_registry->register_field_type(
			$field_type_data['type'],
			array(
				'class_name' => $field_type_data['class_name'] ?? '',
				'supports'   => $field_type_data['supports'],
			)
		);

		return rest_ensure_response(
			array(
				'success'    => true,
				'field_type' => $field_type_data,
			)
		);
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
			return new WP_Error(
				'missing_field_type_id',
				__( 'Field type ID is required', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$success = DatabaseManager::delete_field_type( $id );

		if ( ! $success ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete field type', 'spider-boxes' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'deleted' => true,
				'message' => 'Field type deleted successfully',
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

	/**
	 * Get meta value based on object type
	 *
	 * @param int    $object_id Object ID
	 * @param string $object_type Object type (post, user, term, comment)
	 * @param string $meta_key Meta key
	 * @param string $context Context
	 * @return mixed
	 */
	private function get_meta_value( $object_id, $object_type, $meta_key, $context = 'default' ) {
		switch ( $object_type ) {
			case 'post':
				return get_post_meta( $object_id, $meta_key, true );
			case 'user':
				return get_user_meta( $object_id, $meta_key, true );
			case 'term':
				return get_term_meta( $object_id, $meta_key, true );
			case 'comment':
				return get_comment_meta( $object_id, $meta_key, true );
			default:
				return apply_filters( 'spider_boxes_get_meta_value', null, $object_id, $object_type, $meta_key, $context );
		}
	}

	/**
	 * Save meta value based on object type
	 *
	 * @param int    $object_id Object ID
	 * @param string $object_type Object type (post, user, term, comment)
	 * @param string $meta_key Meta key
	 * @param mixed  $meta_value Meta value
	 * @param string $context Context
	 * @return bool
	 */
	private function save_meta_value( $object_id, $object_type, $meta_key, $meta_value, $context = 'default' ) {
		switch ( $object_type ) {
			case 'post':
				return update_post_meta( $object_id, $meta_key, $meta_value ) !== false;
			case 'user':
				return update_user_meta( $object_id, $meta_key, $meta_value ) !== false;
			case 'term':
				return update_term_meta( $object_id, $meta_key, $meta_value ) !== false;
			case 'comment':
				return update_comment_meta( $object_id, $meta_key, $meta_value ) !== false;
			default:
				return apply_filters( 'spider_boxes_save_meta_value', false, $object_id, $object_type, $meta_key, $meta_value, $context );
		}
	}

	/**
	 * Get reviews (WooCommerce integration)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_reviews( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );
		// Get query parameters.
		$page       = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;
		$per_page   = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 20;
		$status     = $request->get_param( 'status' ) ? sanitize_text_field( $request->get_param( 'status' ) ) : 'all';
		$search     = $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';
		$product_id = $request->get_param( 'product_id' ) ? absint( $request->get_param( 'product_id' ) ) : 0;
		$rating     = $request->get_param( 'rating' ) ? absint( $request->get_param( 'rating' ) ) : 0;
		$orderby    = $request->get_param( 'orderby' ) ? sanitize_text_field( $request->get_param( 'orderby' ) ) : 'comment_date';
		$order      = $request->get_param( 'order' ) ? sanitize_text_field( $request->get_param( 'order' ) ) : 'DESC';

		// Build query arguments.
		$args = array(
			'post_type'  => 'product',
			'type'       => 'review',
			'status'     => $status,
			'number'     => $per_page,
			'offset'     => ( $page - 1 ) * $per_page,
			'orderby'    => $orderby,
			'order'      => $order,
			'meta_query' => array(
				array(
					'key'     => 'rating',
					'compare' => 'EXISTS',
				),
			),
		);
		// Add search filter.
		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		// Add product filter.
		if ( $product_id > 0 ) {
			$args['post_id'] = $product_id;
		}

		// Add rating filter.
		if ( $rating > 0 && $rating <= 5 ) {
			$args['meta_query'][] = array(
				'key'     => 'rating',
				'value'   => $rating,
				'compare' => '=',
			);
		}

		// Get reviews.
		$result = $reviews_manager->get_reviews( $args );

		// Apply filters for extensibility.
		$result = apply_filters( 'spider_boxes_rest_reviews_response', $result, $request );

		return rest_ensure_response( $result );
	}

	/**
	 * Update review (WooCommerce integration)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_review( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );
		$params          = $request->get_json_params();

		if ( empty( $params ) || empty( $params['reviews'] ) ) {
			return new WP_Error(
				'no_reviews_data',
				__( 'No review data provided', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$results = array();
		$errors  = array();

		foreach ( $params['reviews'] as $review_data ) {
			if ( empty( $review_data['id'] ) ) {
				$errors[] = __( 'Review ID is required', 'spider-boxes' );
				continue;
			}

			$review_id = absint( $review_data['id'] );
			$action    = sanitize_text_field( $review_data['action'] ?? 'update' );

			switch ( $action ) {
				case 'approve':
					$result = $reviews_manager->approve_review( $review_id );
					break;

				case 'spam':
					$result = $reviews_manager->spam_review( $review_id );
					break;              case 'trash':
					$result = $reviews_manager->trash_review( $review_id );
											break;

					case 'reply':
						if ( empty( $review_data['reply_content'] ) ) {
							// translators: %d is the review ID.
							$errors[] = sprintf( __( 'Reply content is required for review %d', 'spider-boxes' ), $review_id );
							continue 2;
						}
						$result = $reviews_manager->reply_to_review( $review_id, $review_data['reply_content'] );
						break;

					default:
						$result = $reviews_manager->update_review( $review_id, $review_data );
						break;
			}

			if ( is_wp_error( $result ) ) {
				// translators: %1$d is the review ID, %2$s is the error message.
				$errors[] = sprintf( __( 'Error updating review %1$d: %2$s', 'spider-boxes' ), $review_id, $result->get_error_message() );
			} else {
				$results[] = array(
					'id'     => $review_id,
					'action' => $action,
					'result' => $result,
				);
			}
		}

		$response_data = array(
			'success' => empty( $errors ),
			'results' => $results,
		);

		if ( ! empty( $errors ) ) {
			$response_data['errors'] = $errors;
		}
		// Apply filters for extensibility.
		$response_data = apply_filters( 'spider_boxes_rest_update_reviews_response', $response_data, $request );

		return rest_ensure_response( $response_data );
	}

	/**
	 * Get single review (WooCommerce integration)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_review( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$review_id = absint( $request->get_param( 'id' ) );

		if ( empty( $review_id ) ) {
			return new WP_Error(
				'invalid_review_id',
				__( 'Invalid review ID', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );
		$review          = $reviews_manager->get_review( $review_id );

		if ( ! $review ) {
			return new WP_Error(
				'review_not_found',
				__( 'Review not found', 'spider-boxes' ),
				array( 'status' => 404 )
			);
		}

		// Apply filters for extensibility
		$review = apply_filters( 'spider_boxes_rest_single_review_response', $review, $request );

		return rest_ensure_response( array( 'review' => $review ) );
	}

	/**
	 * Update single review (WooCommerce integration)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_single_review( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$review_id = absint( $request->get_param( 'id' ) );
		$params    = $request->get_json_params();

		if ( empty( $review_id ) ) {
			return new WP_Error(
				'invalid_review_id',
				__( 'Invalid review ID', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $params ) ) {
			return new WP_Error(
				'no_review_data',
				__( 'No review data provided', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );
		$result          = $reviews_manager->update_review( $review_id, $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}
		// Get updated review data.
		$updated_review = $reviews_manager->get_review( $review_id );

		$response_data = array(
			'success' => true,
			'review'  => $updated_review,
		);

		// Apply filters for extensibility.
		$response_data = apply_filters( 'spider_boxes_rest_update_single_review_response', $response_data, $request );

		return rest_ensure_response( $response_data );
	}

	/**
	 * Delete review (WooCommerce integration)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_review( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$review_id = absint( $request->get_param( 'id' ) );

		if ( empty( $review_id ) ) {
			return new WP_Error(
				'invalid_review_id',
				__( 'Invalid review ID', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		// Check if review exists
		$comment = get_comment( $review_id );
		if ( ! $comment ) {
			return new WP_Error(
				'review_not_found',
				__( 'Review not found', 'spider-boxes' ),
				array( 'status' => 404 )
			);
		}

		$force_delete = $request->get_param( 'force' ) ? true : false;

		if ( $force_delete ) {
			// Permanently delete the review
			$deleted = wp_delete_comment( $review_id, true );
		} else {
			// Move to trash
			$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );
			$deleted         = $reviews_manager->trash_review( $review_id );
		}

		if ( ! $deleted ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete review', 'spider-boxes' ),
				array( 'status' => 500 )
			);
		}

		$response_data = array(
			'success'      => true,
			'deleted'      => true,
			'force_delete' => $force_delete,
		);

		// Apply filters for extensibility
		$response_data = apply_filters( 'spider_boxes_rest_delete_review_response', $response_data, $request );
		return rest_ensure_response( $response_data );
	}

	/**
	 * Create review (WooCommerce integration)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_review( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error(
				'no_review_data',
				__( 'No review data provided', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		// Validate required fields
		$required_fields = array( 'product_id', 'author_name', 'author_email', 'content', 'rating' );
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

		$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );
		$result          = $reviews_manager->create_review( $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get the created review data
		$created_review = $reviews_manager->get_review( $result );

		$response_data = array(
			'success' => true,
			'review'  => $created_review,
			'id'      => $result,
		);

		// Apply filters for extensibility
		$response_data = apply_filters( 'spider_boxes_rest_create_review_response', $response_data, $request );

		return rest_ensure_response( $response_data );
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
		$component_registry       = spider_boxes()->get_container()->get( ComponentRegistry::class );
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

		// Generate dynamic configuration fields based on supports.
		$config_fields = $this->generate_component_config_fields( $component_type_config );

		return rest_ensure_response(
			array(
				'component_type' => $component_type_config,
				'config_fields'  => $config_fields,
			)
		);
	}

	/**
	 * Generate configuration fields based on component type supports
	 *
	 * @param array $component_type_config Component type configuration.
	 * @return array
	 */
	private function generate_component_config_fields( $component_type_config ) {
		$supports      = $component_type_config['supports'] ?? array();
		$config_fields = array();

		// Basic fields
		$config_fields['title'] = array(
			'type'        => 'text',
			'label'       => __( 'Title', 'spider-boxes' ),
			'description' => __( 'Component title', 'spider-boxes' ),
			'required'    => true,
		);

		$config_fields['description'] = array(
			'type'        => 'textarea',
			'label'       => __( 'Description', 'spider-boxes' ),
			'description' => __( 'Component description', 'spider-boxes' ),
		);

		// Add fields based on what the component type supports
		foreach ( $supports as $support ) {
			switch ( $support ) {
				case 'icon':
					$config_fields['icon'] = array(
						'type'        => 'text',
						'label'       => __( 'Icon', 'spider-boxes' ),
						'description' => __( 'Component icon class or name', 'spider-boxes' ),
					);
					break;

				case 'class':
					$config_fields['class'] = array(
						'type'        => 'text',
						'label'       => __( 'CSS Class', 'spider-boxes' ),
						'description' => __( 'Additional CSS classes', 'spider-boxes' ),
					);
					break;

				case 'collapsed':
					$config_fields['collapsed'] = array(
						'type'        => 'switcher',
						'label'       => __( 'Collapsed', 'spider-boxes' ),
						'description' => __( 'Whether the component should be collapsed by default', 'spider-boxes' ),
					);
					break;

				case 'active':
					$config_fields['active'] = array(
						'type'        => 'switcher',
						'label'       => __( 'Active', 'spider-boxes' ),
						'description' => __( 'Whether the component should be active by default', 'spider-boxes' ),
					);
					break;

				case 'width':
					$config_fields['width'] = array(
						'type'        => 'select',
						'label'       => __( 'Width', 'spider-boxes' ),
						'description' => __( 'Component width', 'spider-boxes' ),
						'options'     => array(
							'auto' => __( 'Auto', 'spider-boxes' ),
							'25%'  => __( '25%', 'spider-boxes' ),
							'50%'  => __( '50%', 'spider-boxes' ),
							'75%'  => __( '75%', 'spider-boxes' ),
							'100%' => __( '100%', 'spider-boxes' ),
						),
					);
					break;

				case 'columns':
					$config_fields['columns'] = array(
						'type'        => 'range',
						'label'       => __( 'Columns', 'spider-boxes' ),
						'description' => __( 'Number of columns', 'spider-boxes' ),
						'min'         => 1,
						'max'         => 12,
						'step'        => 1,
					);
					break;

				case 'gap':
					$config_fields['gap'] = array(
						'type'        => 'select',
						'label'       => __( 'Gap', 'spider-boxes' ),
						'description' => __( 'Space between items', 'spider-boxes' ),
						'options'     => array(
							'none'   => __( 'None', 'spider-boxes' ),
							'small'  => __( 'Small', 'spider-boxes' ),
							'medium' => __( 'Medium', 'spider-boxes' ),
							'large'  => __( 'Large', 'spider-boxes' ),
						),
					);
					break;

				case 'align':
					$config_fields['align'] = array(
						'type'        => 'select',
						'label'       => __( 'Alignment', 'spider-boxes' ),
						'description' => __( 'Content alignment', 'spider-boxes' ),
						'options'     => array(
							'left'   => __( 'Left', 'spider-boxes' ),
							'center' => __( 'Center', 'spider-boxes' ),
							'right'  => __( 'Right', 'spider-boxes' ),
						),
					);
					break;
			}
		}
		return apply_filters( 'spider_boxes_component_config_fields', $config_fields, $component_type_config );
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


		/**
		 * Get review fields (WooCommerce integration)
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
	public function get_review_fields( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );
		$review_fields   = $reviews_manager->get_review_fields();

		// Convert Collection to array for JSON response
		$fields_array = $review_fields->toArray();
		return rest_ensure_response( array( 'fields' => $fields_array ) );
	}


	/**
	 * Get products (WooCommerce integration)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_products( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		// Get query parameters
		$search   = $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';
		$per_page = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 20;
		$page     = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;

		// Build query arguments
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		// Add search filter
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Get products
		$products_query = new \WP_Query( $args );
		$products       = array();

		foreach ( $products_query->posts as $product_post ) {
			$product = wc_get_product( $product_post->ID );

			if ( $product ) {
				$products[] = array(
					'id'    => $product->get_id(),
					'name'  => $product->get_name(),
					'slug'  => $product->get_slug(),
					'type'  => $product->get_type(),
					'price' => $product->get_price(),
					'image' => wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' ),
				);
			}
		}

		$response_data = array(
			'products' => $products,
			'total'    => $products_query->found_posts,
			'pages'    => $products_query->max_num_pages,
		);

		// Apply filters for extensibility
		$response_data = apply_filters( 'spider_boxes_rest_products_response', $response_data, $request );

		return rest_ensure_response( $response_data );
	}
}
