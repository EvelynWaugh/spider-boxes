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
class ReviewController extends BaseController {

	/**
	 * Register routes
	 */
	public function register_routes() {

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
