<?php
/**
 * Permission Middleware for REST API
 *
 * @package SpiderBoxes\API\Middleware
 */

namespace SpiderBoxes\API\Middleware;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_User;

/**
 * Permission Middleware Class
 */
class PermissionMiddleware {

	/**
	 * Permission levels
	 */
	const PERMISSION_READ   = 'read';
	const PERMISSION_WRITE  = 'write';
	const PERMISSION_DELETE = 'delete';
	const PERMISSION_ADMIN  = 'admin';

	/**
	 * Default capability mappings
	 *
	 * @var array
	 */
	private static $capability_map = array(
		self::PERMISSION_READ   => 'read',
		self::PERMISSION_WRITE  => 'edit_posts',
		self::PERMISSION_DELETE => 'delete_posts',
		self::PERMISSION_ADMIN  => 'manage_options',
	);

	/**
	 * Context-specific permissions
	 *
	 * @var array
	 */
	private static $context_permissions = array(
		'field_types'     => array(
			'read'   => 'read',
			'create' => 'manage_options',
			'update' => 'manage_options',
			'delete' => 'manage_options',
		),
		'fields'          => array(
			'read'   => 'edit_posts',
			'create' => 'edit_posts',
			'update' => 'edit_posts',
			'delete' => 'delete_posts',
		),
		'components'      => array(
			'read'   => 'edit_posts',
			'create' => 'edit_posts',
			'update' => 'edit_posts',
			'delete' => 'delete_posts',
		),
		'component_types' => array(
			'read'   => 'read',
			'create' => 'manage_options',
			'update' => 'manage_options',
			'delete' => 'manage_options',
		),
		'sections'        => array(
			'read'   => 'edit_posts',
			'create' => 'edit_posts',
			'update' => 'edit_posts',
			'delete' => 'delete_posts',
		),
		'section_types'   => array(
			'read'   => 'read',
			'create' => 'manage_options',
			'update' => 'manage_options',
			'delete' => 'manage_options',
		),
		'reviews'         => array(
			'read'   => 'read',
			'create' => 'edit_posts',
			'update' => 'edit_posts',
			'delete' => 'delete_posts',
		),
		'meta'            => array(
			'read'   => 'read',
			'create' => 'edit_posts',
			'update' => 'edit_posts',
			'delete' => 'delete_posts',
		),
	);

	/**
	 * Initialize middleware
	 */
	public static function init() {
		// Allow developers to modify permission mappings
		self::$capability_map      = apply_filters( 'spider_boxes_capability_map', self::$capability_map );
		self::$context_permissions = apply_filters( 'spider_boxes_context_permissions', self::$context_permissions );

		// Hook into REST API initialization
		add_action( 'rest_api_init', array( __CLASS__, 'register_permission_hooks' ) );
	}

	/**
	 * Register permission hooks
	 */
	public static function register_permission_hooks() {
		do_action( 'spider_boxes_before_register_permission_hooks' );

		// Add custom permission check filters
		add_filter( 'spider_boxes_rest_permissions', array( __CLASS__, 'check_context_permissions' ), 10, 3 );

		do_action( 'spider_boxes_after_register_permission_hooks' );
	}

	/**
	 * Check basic API access permissions
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public static function check_basic_permissions( $request ) {
		// Allow logged-out users for read operations only
		if ( ! is_user_logged_in() ) {
			$method = $request->get_method();
			if ( 'GET' === $method ) {
				return apply_filters( 'spider_boxes_allow_anonymous_read', false, $request );
			}
			return new WP_Error(
				'rest_forbidden',
				__( 'Authentication required.', 'spider-boxes' ),
				array( 'status' => 401 )
			);
		}

		$user = wp_get_current_user();

		// Check if user is active
		if ( ! self::is_user_active( $user ) ) {
			return new WP_Error(
				'rest_user_inactive',
				__( 'User account is inactive.', 'spider-boxes' ),
				array( 'status' => 403 )
			);
		}

		// Check basic capability
		$basic_capability = apply_filters( 'spider_boxes_basic_capability', 'read', $request );
		if ( ! current_user_can( $basic_capability ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access this resource.', 'spider-boxes' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check context-specific permissions
	 *
	 * @param bool            $permission Current permission status.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $context Permission context.
	 * @return bool|WP_Error
	 */
	public static function check_context_permissions( $permission, $request, $context = '' ) {
		// First check basic permissions
		$basic_check = self::check_basic_permissions( $request );
		if ( is_wp_error( $basic_check ) ) {
			return $basic_check;
		}

		if ( ! $basic_check ) {
			return false;
		}

		// If no context provided, try to determine from route
		if ( empty( $context ) ) {
			$context = self::determine_context_from_route( $request );
		}

		// Determine action from HTTP method
		$action = self::determine_action_from_method( $request->get_method() );

		// Get required capability for this context and action
		$required_capability = self::get_required_capability( $context, $action );

		do_action( 'spider_boxes_before_check_context_permissions', $context, $action, $required_capability, $request );

		// Check if user has required capability
		$has_permission = current_user_can( $required_capability );

		// Allow additional checks through filters
		$has_permission = apply_filters( 'spider_boxes_check_specific_permission', $has_permission, $context, $action, $request );

		// Check object-specific permissions if applicable
		if ( $has_permission && in_array( $action, array( 'update', 'delete' ), true ) ) {
			$has_permission = self::check_object_permissions( $request, $context, $action );
		}

		do_action( 'spider_boxes_after_check_context_permissions', $has_permission, $context, $action, $request );

		if ( ! $has_permission ) {
			return new WP_Error(
				'rest_forbidden_context',
				sprintf(
					/* translators: %1$s: action, %2$s: context */
					__( 'Sorry, you are not allowed to %1$s %2$s.', 'spider-boxes' ),
					$action,
					$context
				),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check role-based permissions
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param array           $allowed_roles Allowed user roles.
	 * @return bool|WP_Error
	 */
	public static function check_role_permissions( $request, $allowed_roles = array() ) {
		if ( empty( $allowed_roles ) ) {
			return true;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Authentication required.', 'spider-boxes' ),
				array( 'status' => 401 )
			);
		}

		$user_roles = $user->roles;
		$has_role   = array_intersect( $user_roles, $allowed_roles );

		if ( empty( $has_role ) ) {
			return new WP_Error(
				'rest_forbidden_role',
				sprintf(
					/* translators: %s: comma-separated list of required roles */
					__( 'User must have one of the following roles: %s', 'spider-boxes' ),
					implode( ', ', $allowed_roles )
				),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check ownership permissions
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param string          $context Resource context.
	 * @return bool
	 */
	public static function check_ownership_permissions( $request, $context ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// For super admin, allow everything
		if ( is_super_admin( $user_id ) ) {
			return true;
		}

		$object_id = $request->get_param( 'id' );
		if ( empty( $object_id ) ) {
			return true; // No specific object, allow general operations
		}

		// Check ownership based on context
		switch ( $context ) {
			case 'reviews':
				return self::check_review_ownership( $object_id, $user_id );

			case 'fields':
				return self::check_field_ownership( $object_id, $user_id );

			case 'components':
				return self::check_component_ownership( $object_id, $user_id );

			case 'sections':
				return self::check_section_ownership( $object_id, $user_id );

			default:
				return apply_filters( "spider_boxes_check_{$context}_ownership", true, $object_id, $user_id, $request );
		}
	}

	/**
	 * Check rate limiting
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public static function check_rate_limit( $request ) {
		$user_id    = get_current_user_id();
		$ip_address = self::get_client_ip();

		// Different limits for different actions
		$limits = apply_filters(
			'spider_boxes_rate_limits',
			array(
				'create' => array(
					'requests' => 100,
					'window'   => 3600,
				), // 100 per hour
				'update' => array(
					'requests' => 200,
					'window'   => 3600,
				), // 200 per hour
				'delete' => array(
					'requests' => 50,
					'window'   => 3600,
				),  // 50 per hour
				'read'   => array(
					'requests' => 1000,
					'window'   => 3600,
				), // 1000 per hour
			)
		);

		$action       = self::determine_action_from_method( $request->get_method() );
		$limit_config = $limits[ $action ] ?? $limits['read'];

		$key = sprintf( 'spider_boxes_rate_limit_%s_%s_%s', $action, $user_id ?: 'anonymous', md5( $ip_address ) );

		$current_count = get_transient( $key );
		if ( false === $current_count ) {
			$current_count = 0;
		}

		if ( $current_count >= $limit_config['requests'] ) {
			return new WP_Error(
				'rest_rate_limit_exceeded',
				sprintf(
					/* translators: %1$d: requests, %2$d: time window in seconds */
					__( 'Rate limit exceeded. Maximum %1$d requests per %2$d seconds.', 'spider-boxes' ),
					$limit_config['requests'],
					$limit_config['window']
				),
				array( 'status' => 429 )
			);
		}

		// Increment counter
		set_transient( $key, $current_count + 1, $limit_config['window'] );

		return true;
	}

	/**
	 * Create permission callback for specific context
	 *
	 * @param string $context Permission context.
	 * @param array  $options Additional options.
	 * @return callable
	 */
	public static function create_permission_callback( $context, $options = array() ) {
		return function ( $request ) use ( $context, $options ) {
			// Check rate limiting if enabled
			if ( ! empty( $options['rate_limit'] ) ) {
				$rate_check = self::check_rate_limit( $request );
				if ( is_wp_error( $rate_check ) ) {
					return $rate_check;
				}
			}

			// Check role-based permissions if specified
			if ( ! empty( $options['roles'] ) ) {
				$role_check = self::check_role_permissions( $request, $options['roles'] );
				if ( is_wp_error( $role_check ) ) {
					return $role_check;
				}
			}

			// Check context permissions
			$permission = apply_filters( 'spider_boxes_rest_permissions', true, $request, $context );

			// Check ownership if required
			if ( ! empty( $options['check_ownership'] ) && $permission === true ) {
				$ownership_check = self::check_ownership_permissions( $request, $context );
				if ( ! $ownership_check ) {
					return new WP_Error(
						'rest_forbidden_ownership',
						__( 'You can only modify your own resources.', 'spider-boxes' ),
						array( 'status' => 403 )
					);
				}
			}

			return $permission;
		};
	}

	/**
	 * Determine context from route
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return string
	 */
	private static function determine_context_from_route( $request ) {
		$route = $request->get_route();

		// Extract context from route pattern
		if ( preg_match( '#/spider-boxes/v1/([^/]+)#', $route, $matches ) ) {
			return str_replace( '-', '_', $matches[1] );
		}

		return 'default';
	}

	/**
	 * Determine action from HTTP method
	 *
	 * @param string $method HTTP method.
	 * @return string
	 */
	private static function determine_action_from_method( $method ) {
		$action_map = array(
			'GET'    => 'read',
			'POST'   => 'create',
			'PUT'    => 'update',
			'PATCH'  => 'update',
			'DELETE' => 'delete',
		);

		return $action_map[ $method ] ?? 'read';
	}

	/**
	 * Get required capability for context and action
	 *
	 * @param string $context Permission context.
	 * @param string $action Action being performed.
	 * @return string
	 */
	private static function get_required_capability( $context, $action ) {
		// Get context-specific permissions
		$context_perms = self::$context_permissions[ $context ] ?? array();

		// Get capability for this action
		$capability = $context_perms[ $action ] ?? null;

		// Fallback to general capability mapping
		if ( ! $capability ) {
			$capability = self::$capability_map[ $action ] ?? 'read';
		}

		return apply_filters( 'spider_boxes_required_capability', $capability, $context, $action );
	}

	/**
	 * Check object-specific permissions
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param string          $context Permission context.
	 * @param string          $action Action being performed.
	 * @return bool
	 */
	private static function check_object_permissions( $request, $context, $action ) {
		// Allow super admin to do everything
		if ( is_super_admin() ) {
			return true;
		}

		// For now, allow if user has basic capability
		// This can be extended to check specific object ownership
		return apply_filters( 'spider_boxes_check_object_permissions', true, $request, $context, $action );
	}

	/**
	 * Check if user is active
	 *
	 * @param WP_User $user User object.
	 * @return bool
	 */
	private static function is_user_active( $user ) {
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		// Check if user is blocked/suspended
		$is_active = ! get_user_meta( $user->ID, 'spider_boxes_user_suspended', true );

		return apply_filters( 'spider_boxes_is_user_active', $is_active, $user );
	}

	/**
	 * Check review ownership
	 *
	 * @param string $review_id Review ID.
	 * @param int    $user_id User ID.
	 * @return bool
	 */
	private static function check_review_ownership( $review_id, $user_id ) {
		// This would check if the user owns the review
		// Implementation depends on how reviews are stored
		return apply_filters( 'spider_boxes_check_review_ownership', true, $review_id, $user_id );
	}

	/**
	 * Check field ownership
	 *
	 * @param string $field_id Field ID.
	 * @param int    $user_id User ID.
	 * @return bool
	 */
	private static function check_field_ownership( $field_id, $user_id ) {
		// Fields are generally system-wide, so check if user can edit posts
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check component ownership
	 *
	 * @param string $component_id Component ID.
	 * @param int    $user_id User ID.
	 * @return bool
	 */
	private static function check_component_ownership( $component_id, $user_id ) {
		// Components are generally system-wide, so check if user can edit posts
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check section ownership
	 *
	 * @param string $section_id Section ID.
	 * @param int    $user_id User ID.
	 * @return bool
	 */
	private static function check_section_ownership( $section_id, $user_id ) {
		// Sections are generally system-wide, so check if user can edit posts
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				// Handle comma-separated IPs (from proxies)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
}
