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
		);      // Component types endpoint.
		register_rest_route(
			$this->namespace,
			'/component-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_component_types' ),
				'permission_callback' => array( $this, 'check_reviews_permissions' ),
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
					'permission_callback' => array( $this, 'check_reviews_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_component' ),
					'permission_callback' => array( $this, 'check_reviews_permissions' ),
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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_section_types' ),
				'permission_callback' => array( $this, 'check_permissions' ),
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
						'permission_callback' => array( $this, 'check_reviews_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_review' ),
						'permission_callback' => array( $this, 'check_reviews_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_review' ),
						'permission_callback' => array( $this, 'check_reviews_permissions' ),
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
						'permission_callback' => array( $this, 'check_reviews_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_single_review' ),
						'permission_callback' => array( $this, 'check_reviews_permissions' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_review' ),
						'permission_callback' => array( $this, 'check_reviews_permissions' ),
					),
				)
			);          // Review fields endpoint
			register_rest_route(
				$this->namespace,
				'/reviews/fields',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_review_fields' ),
					'permission_callback' => array( $this, 'check_reviews_permissions' ),
				)
			);

			// Products endpoint for review creation
			register_rest_route(
				$this->namespace,
				'/products',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'check_reviews_permissions' ),
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

		// Component children management endpoints
		// register_rest_route(
		// $this->namespace,
		// '/components/(?P<parent_id>[\\w-]+)/tabs',
		// array(
		// 'methods'             => WP_REST_Server::CREATABLE,
		// 'callback'            => array( $this, 'add_tab_to_component' ),
		// 'permission_callback' => array( $this, 'check_permissions' ),
		// )
		// );

		// register_rest_route(
		// $this->namespace,
		// '/components/(?P<parent_id>[\\w-]+)/panes',
		// array(
		// 'methods'             => WP_REST_Server::CREATABLE,
		// 'callback'            => array( $this, 'add_pane_to_component' ),
		// 'permission_callback' => array( $this, 'check_permissions' ),
		// )
		// );

		// register_rest_route(
		// $this->namespace,
		// '/components/(?P<parent_id>[\\w-]+)/columns',
		// array(
		// 'methods'             => WP_REST_Server::CREATABLE,
		// 'callback'            => array( $this, 'add_column_to_component' ),
		// 'permission_callback' => array( $this, 'check_permissions' ),
		// )
		// );

		// register_rest_route(
		// $this->namespace,
		// '/components/(?P<parent_id>[\\w-]+)/children/(?P<child_id>[\\w-]+)',
		// array(
		// 'methods'             => WP_REST_Server::DELETABLE,
		// 'callback'            => array( $this, 'remove_child_from_component' ),
		// 'permission_callback' => array( $this, 'check_permissions' ),
		// )
		// );

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
	 * Check permissions for reviews API access
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool
	 */
	public function check_reviews_permissions( $request ) {
		// For now, allow any logged-in user to access reviews
		// TODO: Implement proper WooCommerce capability checks.
		return is_user_logged_in();
	}

	/**
	 * Get field types
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_field_types( $request ) {
		// Get field types from database instead of registry.
		$field_types = DatabaseManager::get_field_types();

		return rest_ensure_response( $field_types );
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
		$required_fields = array( 'id', 'name', 'class_name' );
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

		// Validate field type ID format.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $params['id'] ) ) {
			return new WP_Error(
				'invalid_field_type_id',
				__( 'Field type ID can only contain letters, numbers, underscores, and hyphens', 'spider-boxes' ),
				array( 'status' => 400 )
			);
		}

		// Check if field type already exists.
		$existing_types = DatabaseManager::get_field_types();
		$existing_ids   = wp_list_pluck( $existing_types, 'id' );
		if ( in_array( $params['id'], $existing_ids, true ) ) {
			return new WP_Error(
				'field_type_exists',
				__( 'Field type already exists', 'spider-boxes' ),
				array( 'status' => 409 )
			);
		}

		// Sanitize input data.
		$field_type_data = array(
			'id'          => sanitize_key( $params['id'] ),
			'name'        => sanitize_text_field( $params['name'] ),
			'class_name'  => sanitize_text_field( $params['class_name'] ),
			'category'    => sanitize_text_field( $params['category'] ?? 'general' ),
			'icon'        => sanitize_text_field( $params['icon'] ?? 'component' ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'supports'    => is_array( $params['supports'] ?? array() ) ? $params['supports'] : array(),
			'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : true,
			'sort_order'  => absint( $params['sort_order'] ?? 0 ),
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
			$field_type_data['id'],
			array(
				'class'    => $field_type_data['class_name'],
				'supports' => $field_type_data['supports'],
				'category' => $field_type_data['category'],
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
	 * Get fields
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_fields( $request ) {
		$parent  = $request->get_param( 'parent' );
		$context = $request->get_param( 'context' );

		// Get fields from database instead of registry.
		$fields = DatabaseManager::get_all_fields( $parent, $context );

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

		// Check if field already exists.
		$existing_config = DatabaseManager::get_field_config( $validated_config['id'] );
		if ( $existing_config ) {
			return new WP_Error( 'field_exists', __( 'Field already exists', 'spider-boxes' ), array( 'status' => 409 ) );
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
		$component_registry = spider_boxes()->get_container()->get( ComponentRegistry::class );
		$component_types    = $component_registry->get_component_types();

		return rest_ensure_response( array( 'component_types' => $component_types->toArray() ) );
	}
	/**
	 * Get components.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_components( $request ) {
		$parent_id  = $request->get_param( 'parent_id' );
		$section_id = $request->get_param( 'section_id' );
		$context    = $request->get_param( 'context' );

		// Get components from database instead of registry.
		$components = DatabaseManager::get_all_components( $parent_id, $section_id, $context );

		return rest_ensure_response( $components );
	}
	/**
	 * Get single component.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_component( $request ) {
		$id = $request->get_param( 'id' );

		// Get component configuration from database.
		$component = DatabaseManager::get_component_config( $id );

		if ( ! $component ) {
			return new WP_Error( 'component_not_found', __( 'Component not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $component );
	}
	/**
	 * Create component.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_component( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'no_data', __( 'No data provided', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Validate required fields.
		$required_fields = array( 'id', 'type', 'title' );
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

		// Check if component already exists.
		$existing_component = DatabaseManager::get_component_config( $params['id'] );
		if ( $existing_component ) {
			return new WP_Error( 'component_exists', __( 'Component with this ID already exists', 'spider-boxes' ), array( 'status' => 409 ) );
		}

		// Sanitize input data.
		$component_data = array(
			'id'          => sanitize_key( $params['id'] ),
			'type'        => sanitize_text_field( $params['type'] ),
			'title'       => sanitize_text_field( $params['title'] ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'parent_id'   => sanitize_key( $params['parent_id'] ?? '' ),
			'section_id'  => sanitize_key( $params['section_id'] ?? '' ),
			'context'     => sanitize_text_field( $params['context'] ?? 'default' ),
			'settings'    => is_array( $params['settings'] ?? array() ) ? $params['settings'] : array(),
			'children'    => is_array( $params['children'] ?? array() ) ? $params['children'] : array(),
			'sort_order'  => absint( $params['sort_order'] ?? 0 ),
			'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : true,
			'capability'  => sanitize_text_field( $params['capability'] ?? 'manage_options' ),
		);

		// Save component configuration to database.
		$success = DatabaseManager::save_component_config( $component_data['id'], $component_data );

		if ( ! $success ) {
			return new WP_Error( 'component_create_failed', __( 'Failed to create component', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also register with component registry for runtime usage.
		$component_registry = spider_boxes()->get_container()->get( ComponentRegistry::class );
		$component_registry->register_component( $component_data['id'], $component_data );

		return rest_ensure_response(
			array(
				'success'   => true,
				'id'        => $component_data['id'],
				'component' => $component_data,
			)
		);
	}
	/**
	 * Update component.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_component( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'no_data', __( 'No data provided', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Check if component exists in database.
		$existing_component = DatabaseManager::get_component_config( $id );
		if ( ! $existing_component ) {
			return new WP_Error( 'component_not_found', __( 'Component not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Merge with existing data.
		$component_data = array_merge(
			$existing_component,
			array(
				'type'        => sanitize_text_field( $params['type'] ?? $existing_component['type'] ),
				'title'       => sanitize_text_field( $params['title'] ?? $existing_component['title'] ),
				'description' => sanitize_textarea_field( $params['description'] ?? $existing_component['description'] ),
				'parent_id'   => sanitize_key( $params['parent_id'] ?? $existing_component['parent_id'] ),
				'section_id'  => sanitize_key( $params['section_id'] ?? $existing_component['section_id'] ),
				'context'     => sanitize_text_field( $params['context'] ?? $existing_component['context'] ),
				'settings'    => is_array( $params['settings'] ?? $existing_component['settings'] ) ? $params['settings'] ?? $existing_component['settings'] : $existing_component['settings'],
				'children'    => is_array( $params['children'] ?? $existing_component['children'] ) ? $params['children'] ?? $existing_component['children'] : $existing_component['children'],
				'sort_order'  => isset( $params['sort_order'] ) ? absint( $params['sort_order'] ) : $existing_component['sort_order'],
				'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : $existing_component['is_active'],
				'capability'  => sanitize_text_field( $params['capability'] ?? $existing_component['capability'] ),
			)
		);

		// Update component configuration in database.
		$success = DatabaseManager::save_component_config( $id, $component_data );

		if ( ! $success ) {
			return new WP_Error( 'component_update_failed', __( 'Failed to update component', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also update in component registry for runtime usage.
		$component_registry = spider_boxes()->get_container()->get( ComponentRegistry::class );
		$component_registry->register_component( $id, $component_data );

		return rest_ensure_response(
			array(
				'success'   => true,
				'component' => $component_data,
			)
		);
	}
	/**
	 * Delete component.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_component( $request ) {
		$id = $request->get_param( 'id' );

		// Check if component exists in database.
		$existing_component = DatabaseManager::get_component_config( $id );
		if ( ! $existing_component ) {
			return new WP_Error( 'component_not_found', __( 'Component not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Delete component configuration from database.
		$success = DatabaseManager::delete_component_config( $id );

		if ( ! $success ) {
			return new WP_Error( 'component_delete_failed', __( 'Failed to delete component', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also remove from component registry for runtime usage.
		$component_registry = spider_boxes()->get_container()->get( ComponentRegistry::class );
		$component_registry->remove_component( $id );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get section types.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_section_types( $request ) {
		$section_registry = spider_boxes()->get_container()->get( SectionRegistry::class );
		$section_types    = $section_registry->get_section_types();

		return rest_ensure_response( array( 'section_types' => $section_types->toArray() ) );
	}
	/**
	 * Get sections.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_sections( $request ) {
		$context = $request->get_param( 'context' );
		$screen  = $request->get_param( 'screen' );

		// Get sections from database instead of registry.
		$sections = DatabaseManager::get_all_sections( $context, $screen );

		return rest_ensure_response( $sections );
	}
	/**
	 * Get single section.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_section( $request ) {
		$id = $request->get_param( 'id' );

		// Get section configuration from database.
		$section = DatabaseManager::get_section_config( $id );

		if ( ! $section ) {
			return new WP_Error( 'section_not_found', __( 'Section not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $section );
	}
	/**
	 * Create section.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_section( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'no_data', __( 'No data provided', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Validate required fields.
		$required_fields = array( 'id', 'type', 'title' );
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

		// Check if section already exists.
		$existing_section = DatabaseManager::get_section_config( $params['id'] );
		if ( $existing_section ) {
			return new WP_Error( 'section_exists', __( 'Section with this ID already exists', 'spider-boxes' ), array( 'status' => 409 ) );
		}

		// Sanitize input data.
		$section_data = array(
			'id'          => sanitize_key( $params['id'] ),
			'type'        => sanitize_text_field( $params['type'] ),
			'title'       => sanitize_text_field( $params['title'] ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'context'     => sanitize_text_field( $params['context'] ?? 'default' ),
			'screen'      => sanitize_text_field( $params['screen'] ?? '' ),
			'settings'    => is_array( $params['settings'] ?? array() ) ? $params['settings'] : array(),
			'components'  => is_array( $params['components'] ?? array() ) ? $params['components'] : array(),
			'sort_order'  => absint( $params['sort_order'] ?? 0 ),
			'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : true,
			'capability'  => sanitize_text_field( $params['capability'] ?? 'manage_options' ),
		);

		// Save section configuration to database.
		$success = DatabaseManager::save_section_config( $section_data['id'], $section_data );

		if ( ! $success ) {
			return new WP_Error( 'section_create_failed', __( 'Failed to create section', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also register with section registry for runtime usage.
		$section_registry = spider_boxes()->get_container()->get( SectionRegistry::class );
		$section_registry->register_section( $section_data['id'], $section_data );

		return rest_ensure_response(
			array(
				'success' => true,
				'id'      => $section_data['id'],
				'section' => $section_data,
			)
		);
	}
	/**
	 * Update section.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_section( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'no_data', __( 'No data provided', 'spider-boxes' ), array( 'status' => 400 ) );
		}

		// Check if section exists in database.
		$existing_section = DatabaseManager::get_section_config( $id );
		if ( ! $existing_section ) {
			return new WP_Error( 'section_not_found', __( 'Section not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Merge with existing data.
		$section_data = array_merge(
			$existing_section,
			array(
				'type'        => sanitize_text_field( $params['type'] ?? $existing_section['type'] ),
				'title'       => sanitize_text_field( $params['title'] ?? $existing_section['title'] ),
				'description' => sanitize_textarea_field( $params['description'] ?? $existing_section['description'] ),
				'context'     => sanitize_text_field( $params['context'] ?? $existing_section['context'] ),
				'screen'      => sanitize_text_field( $params['screen'] ?? $existing_section['screen'] ),
				'settings'    => is_array( $params['settings'] ?? $existing_section['settings'] ) ? $params['settings'] ?? $existing_section['settings'] : $existing_section['settings'],
				'components'  => is_array( $params['components'] ?? $existing_section['components'] ) ? $params['components'] ?? $existing_section['components'] : $existing_section['components'],
				'sort_order'  => isset( $params['sort_order'] ) ? absint( $params['sort_order'] ) : $existing_section['sort_order'],
				'is_active'   => isset( $params['is_active'] ) ? (bool) $params['is_active'] : $existing_section['is_active'],
				'capability'  => sanitize_text_field( $params['capability'] ?? $existing_section['capability'] ),
			)
		);

		// Update section configuration in database.
		$success = DatabaseManager::save_section_config( $id, $section_data );

		if ( ! $success ) {
			return new WP_Error( 'section_update_failed', __( 'Failed to update section', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also update in section registry for runtime usage.
		$section_registry = spider_boxes()->get_container()->get( SectionRegistry::class );
		$section_registry->register_section( $id, $section_data );

		return rest_ensure_response(
			array(
				'success' => true,
				'section' => $section_data,
			)
		);
	}
	/**
	 * Delete section.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_section( $request ) {
		$id = $request->get_param( 'id' );

		// Check if section exists in database.
		$existing_section = DatabaseManager::get_section_config( $id );
		if ( ! $existing_section ) {
			return new WP_Error( 'section_not_found', __( 'Section not found', 'spider-boxes' ), array( 'status' => 404 ) );
		}

		// Delete section configuration from database.
		$success = DatabaseManager::delete_section_config( $id );

		if ( ! $success ) {
			return new WP_Error( 'section_delete_failed', __( 'Failed to delete section', 'spider-boxes' ), array( 'status' => 500 ) );
		}

		// Also remove from section registry for runtime usage.
		$section_registry = spider_boxes()->get_container()->get( SectionRegistry::class );
		$section_registry->remove_section( $id );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get all fields from database
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_fields_from_database( $request ) {
		// Get all fields from the database using the DatabaseManager.
		$fields = DatabaseManager::get_all_fields();

		return rest_ensure_response( $fields );
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

	/**
	 * Create component with default children
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_component_with_defaults( $request ) {
		$component_data     = $request->get_json_params();
		$component_registry = spider_boxes()->get_container()->get( ComponentRegistry::class );
		$component_factory  = spider_boxes()->get_container()->get( 'componentFactory' );

		// Validate required fields
		if ( empty( $component_data['id'] ) || empty( $component_data['type'] ) ) {
			return new WP_Error( 'missing_required_fields', 'Component ID and type are required', array( 'status' => 400 ) );
		}

		$component_type = $component_registry->get_component_type( $component_data['type'] );
		if ( ! $component_type ) {
			return new WP_Error( 'invalid_component_type', 'Invalid component type', array( 'status' => 400 ) );
		}

		// Create component with proper structure using factory
		$config = $component_factory->get_component_config( $component_data['type'], $component_data['id'], $component_data );

		$result = $component_registry->register_component( $component_data['id'], $config );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'component' => $config,
			)
		);
	}

	/**
	 * Add tab to tabs component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_tab_to_component( $request ) {
		$parent_id         = $request->get_param( 'parent_id' );
		$tab_data          = $request->get_json_params();
		$component_factory = spider_boxes()->get_container()->get( 'componentFactory' );

		$tab_id = $component_factory->add_tab_to_tabs( $parent_id, $tab_data );

		if ( empty( $tab_id ) ) {
			return new WP_Error( 'tab_creation_failed', 'Failed to create tab', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'tab_id'  => $tab_id,
			)
		);
	}

	/**
	 * Add pane to accordion component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_pane_to_component( $request ) {
		$parent_id         = $request->get_param( 'parent_id' );
		$pane_data         = $request->get_json_params();
		$component_factory = spider_boxes()->get_container()->get( 'componentFactory' );

		$pane_id = $component_factory->add_pane_to_accordion( $parent_id, $pane_data );

		if ( empty( $pane_id ) ) {
			return new WP_Error( 'pane_creation_failed', 'Failed to create pane', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'pane_id' => $pane_id,
			)
		);
	}

	/**
	 * Add column to row component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_column_to_component( $request ) {
		$parent_id         = $request->get_param( 'parent_id' );
		$column_data       = $request->get_json_params();
		$component_factory = spider_boxes()->get_container()->get( 'componentFactory' );

		$column_id = $component_factory->add_column_to_row( $parent_id, $column_data );

		if ( empty( $column_id ) ) {
			return new WP_Error( 'column_creation_failed', 'Failed to create column', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'column_id' => $column_id,
			)
		);
	}

	/**
	 * Remove child from parent component
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_child_from_component( $request ) {
		$parent_id         = $request->get_param( 'parent_id' );
		$child_id          = $request->get_param( 'child_id' );
		$component_factory = spider_boxes()->get_container()->get( 'componentFactory' );

		$result = $component_factory->remove_child_from_parent( $parent_id, $child_id );

		if ( ! $result ) {
			return new WP_Error( 'child_removal_failed', 'Failed to remove child from parent', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}
}
