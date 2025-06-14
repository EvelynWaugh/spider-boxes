<?php
/**
 * Base REST Controller
 *
 * @package SpiderBoxes\API\Controllers
 */

namespace SpiderBoxes\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Base Controller Class
 */
abstract class BaseController {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	protected $namespace = 'spider-boxes/v1';

	/**
	 * Check permissions for API access
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool
	 */
	public function check_permissions( $request ) {
		return apply_filters( 'spider_boxes_rest_permissions', current_user_can( 'manage_options' ), $request );
	}

	/**
	 * Validate required fields
	 *
	 * @param array $params Request parameters
	 * @param array $required_fields Required field names
	 * @return WP_Error|null
	 */
	protected function validate_required_fields( $params, $required_fields ) {
		foreach ( $required_fields as $field ) {
			if ( empty( $params[ $field ] ) ) {
				return new WP_Error(
					'missing_field',
					sprintf( __( 'Missing required field: %s', 'spider-boxes' ), $field ),
					array( 'status' => 400 )
				);
			}
		}
		return null;
	}

	/**
	 * Create success response
	 *
	 * @param mixed  $data Response data
	 * @param string $message Success message
	 * @return WP_REST_Response
	 */
	protected function success_response( $data = null, $message = '' ) {
		$response = array( 'success' => true );

		if ( $data !== null ) {
			$response['data'] = $data;
		}

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Create error response
	 *
	 * @param string $code Error code
	 * @param string $message Error message
	 * @param int    $status HTTP status code
	 * @return WP_Error
	 */
	protected function error_response( $code, $message, $status = 400 ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
