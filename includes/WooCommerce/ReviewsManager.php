<?php
/**
 * WooCommerce Reviews Manager
 *
 * @package SpiderBoxes\WooCommerce
 */

namespace SpiderBoxes\WooCommerce;

use StellarWP\DB\DB;
use Illuminate\Support\Collection;

/**
 * Reviews Manager Class
 */
class ReviewsManager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		// Add custom columns to comments list table
		add_filter( 'manage_edit-comments_columns', array( $this, 'add_comments_columns' ) );
		add_action( 'manage_comments_custom_column', array( $this, 'render_comments_column' ), 10, 2 );

		// Add custom meta box to comment edit screen
		add_action( 'add_meta_boxes_comment', array( $this, 'add_comment_meta_boxes' ) );
		add_action( 'edit_comment', array( $this, 'save_comment_meta' ) );

		// AJAX handlers for review management
		add_action( 'wp_ajax_spider_boxes_approve_review', array( $this, 'ajax_approve_review' ) );
		add_action( 'wp_ajax_spider_boxes_spam_review', array( $this, 'ajax_spam_review' ) );
		add_action( 'wp_ajax_spider_boxes_trash_review', array( $this, 'ajax_trash_review' ) );
		add_action( 'wp_ajax_spider_boxes_reply_review', array( $this, 'ajax_reply_review' ) );

		/**
		 * Allow developers to hook into reviews manager initialization
		 */
		do_action( 'spider_boxes_reviews_manager_init', $this );
	}

	/**
	 * Get WooCommerce product reviews
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_reviews( $args = array() ) {
		$defaults = array(
			'post_type'  => 'product',
			'status'     => 'all',
			'meta_query' => array(
				array(
					'key'     => 'rating',
					'compare' => 'EXISTS',
				),
			),
			'number'     => 20,
			'offset'     => 0,
			'orderby'    => 'comment_date',
			'order'      => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$comments_query = new \WP_Comment_Query( $args );
		$comments       = $comments_query->comments;

		$reviews = array();
		foreach ( $comments as $comment ) {
			$reviews[] = $this->format_review( $comment );
		}

		return array(
			'reviews' => $reviews,
			'total'   => $comments_query->found_comments,
			'pages'   => ceil( $comments_query->found_comments / $args['number'] ),
		);
	}

	/**
	 * Get single review
	 *
	 * @param int $review_id Review ID
	 * @return array|null
	 */
	public function get_review( $review_id ) {
		$comment = get_comment( $review_id );

		if ( ! $comment || $comment->comment_type !== 'review' ) {
			return null;
		}

		return $this->format_review( $comment );
	}

	/**
	 * Format review data
	 *
	 * @param \WP_Comment $comment Comment object
	 * @return array
	 */
	private function format_review( $comment ) {
		$product = wc_get_product( $comment->comment_post_ID );
		$rating  = get_comment_meta( $comment->comment_ID, 'rating', true );

		$formatted = array(
			'id'           => $comment->comment_ID,
			'product_id'   => $comment->comment_post_ID,
			'product_name' => $product ? $product->get_name() : '',
			'author_name'  => $comment->comment_author,
			'author_email' => $comment->comment_author_email,
			'author_url'   => $comment->comment_author_url,
			'content'      => $comment->comment_content,
			'rating'       => intval( $rating ),
			'date'         => $comment->comment_date,
			'date_gmt'     => $comment->comment_date_gmt,
			'status'       => wp_get_comment_status( $comment->comment_ID ),
			'parent'       => $comment->comment_parent,
			'meta'         => array(),
		);

		// Get custom meta fields
		$custom_meta       = $this->get_review_custom_meta( $comment->comment_ID );
		$formatted['meta'] = $custom_meta;

		return apply_filters( 'spider_boxes_format_review', $formatted, $comment );
	}

	/**
	 * Get custom meta fields for review
	 *
	 * @param int $comment_id Comment ID
	 * @return array
	 */
	public function get_review_custom_meta( $comment_id ) {
		$meta = array();

		// Get all meta for the comment
		$comment_meta = get_comment_meta( $comment_id );

		// Filter out default WooCommerce meta
		$default_keys = array( 'rating', 'verified' );

		foreach ( $comment_meta as $key => $values ) {
			if ( ! in_array( $key, $default_keys ) ) {
				$meta[ $key ] = is_array( $values ) && count( $values ) === 1 ? $values[0] : $values;
			}
		}

		return apply_filters( 'spider_boxes_review_custom_meta', $meta, $comment_id );
	}

	/**
	 * Update review
	 *
	 * @param int   $review_id Review ID
	 * @param array $data Review data
	 * @return bool|\WP_Error
	 */
	public function update_review( $review_id, $data ) {
		$comment = get_comment( $review_id );

		if ( ! $comment ) {
			return new \WP_Error( 'review_not_found', __( 'Review not found', 'spider-boxes' ) );
		}

		$comment_data = array(
			'comment_ID' => $review_id,
		);

		// Update comment fields
		if ( isset( $data['author_name'] ) ) {
			$comment_data['comment_author'] = sanitize_text_field( $data['author_name'] );
		}

		if ( isset( $data['author_email'] ) ) {
			$comment_data['comment_author_email'] = sanitize_email( $data['author_email'] );
		}

		if ( isset( $data['author_url'] ) ) {
			$comment_data['comment_author_url'] = esc_url_raw( $data['author_url'] );
		}

		if ( isset( $data['content'] ) ) {
			$comment_data['comment_content'] = wp_kses_post( $data['content'] );
		}

		if ( isset( $data['status'] ) ) {
			$comment_data['comment_approved'] = $data['status'];
		}

		// Update comment
		$result = wp_update_comment( $comment_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update rating
		if ( isset( $data['rating'] ) ) {
			update_comment_meta( $review_id, 'rating', intval( $data['rating'] ) );
		}

		// Update custom meta fields
		if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( $data['meta'] as $key => $value ) {
				update_comment_meta( $review_id, sanitize_key( $key ), $value );
			}
		}

		do_action( 'spider_boxes_review_updated', $review_id, $data );

		return true;
	}

	/**
	 * Approve review
	 *
	 * @param int $review_id Review ID
	 * @return bool
	 */
	public function approve_review( $review_id ) {
		return wp_set_comment_status( $review_id, 'approve' );
	}

	/**
	 * Mark review as spam
	 *
	 * @param int $review_id Review ID
	 * @return bool
	 */
	public function spam_review( $review_id ) {
		return wp_spam_comment( $review_id );
	}

	/**
	 * Trash review
	 *
	 * @param int $review_id Review ID
	 * @return bool
	 */
	public function trash_review( $review_id ) {
		return wp_trash_comment( $review_id );
	}

	/**
	 * Reply to review
	 *
	 * @param int    $review_id Parent review ID
	 * @param string $content Reply content
	 * @param array  $args Additional arguments
	 * @return int|\WP_Error Comment ID on success, WP_Error on failure
	 */
	public function reply_to_review( $review_id, $content, $args = array() ) {
		$parent_comment = get_comment( $review_id );

		if ( ! $parent_comment ) {
			return new \WP_Error( 'parent_not_found', __( 'Parent review not found', 'spider-boxes' ) );
		}

		$defaults = array(
			'comment_post_ID'      => $parent_comment->comment_post_ID,
			'comment_author'       => get_bloginfo( 'name' ),
			'comment_author_email' => get_bloginfo( 'admin_email' ),
			'comment_author_url'   => home_url(),
			'comment_content'      => wp_kses_post( $content ),
			'comment_type'         => 'review',
			'comment_parent'       => $review_id,
			'user_id'              => get_current_user_id(),
			'comment_approved'     => 1,
		);

		$comment_data = wp_parse_args( $args, $defaults );

		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			return new \WP_Error( 'reply_failed', __( 'Failed to create reply', 'spider-boxes' ) );
		}

		do_action( 'spider_boxes_review_reply_created', $comment_id, $review_id, $content );

		return $comment_id;
	}

	/**
	 * Add custom columns to comments list table
	 *
	 * @param array $columns Existing columns
	 * @return array
	 */
	public function add_comments_columns( $columns ) {
		if ( isset( $_GET['comment_type'] ) && $_GET['comment_type'] === 'review' ) {
			$columns['rating']  = __( 'Rating', 'spider-boxes' );
			$columns['product'] = __( 'Product', 'spider-boxes' );
		}

		return $columns;
	}

	/**
	 * Render custom columns content
	 *
	 * @param string $column Column name
	 * @param int    $comment_id Comment ID
	 */
	public function render_comments_column( $column, $comment_id ) {
		switch ( $column ) {
			case 'rating':
				$rating = get_comment_meta( $comment_id, 'rating', true );
				if ( $rating ) {
					echo str_repeat( '★', intval( $rating ) ) . str_repeat( '☆', 5 - intval( $rating ) );
				}
				break;

			case 'product':
				$comment = get_comment( $comment_id );
				$product = wc_get_product( $comment->comment_post_ID );
				if ( $product ) {
					echo '<a href="' . get_edit_post_link( $product->get_id() ) . '">' . esc_html( $product->get_name() ) . '</a>';
				}
				break;
		}
	}

	/**
	 * Add meta boxes to comment edit screen
	 *
	 * @param \WP_Comment $comment Comment object
	 */
	public function add_comment_meta_boxes( $comment ) {
		if ( $comment->comment_type === 'review' ) {
			add_meta_box(
				'spider-boxes-review-details',
				__( 'Review Details', 'spider-boxes' ),
				array( $this, 'render_review_details_meta_box' ),
				'comment',
				'normal',
				'high',
				$comment
			);
		}
	}

	/**
	 * Render review details meta box
	 *
	 * @param \WP_Comment $comment Comment object
	 */
	public function render_review_details_meta_box( $comment ) {
		$rating   = get_comment_meta( $comment->comment_ID, 'rating', true );
		$verified = get_comment_meta( $comment->comment_ID, 'verified', true );

		wp_nonce_field( 'spider_boxes_review_meta', 'spider_boxes_review_meta_nonce' );
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="review_rating"><?php _e( 'Rating', 'spider-boxes' ); ?></label></th>
				<td>
					<select name="review_rating" id="review_rating">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<option value="<?php echo $i; ?>" <?php selected( $rating, $i ); ?>>
								<?php printf( _n( '%d Star', '%d Stars', $i, 'spider-boxes' ), $i ); ?>
							</option>
						<?php endfor; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="review_verified"><?php _e( 'Verified Purchase', 'spider-boxes' ); ?></label></th>
				<td>
					<input type="checkbox" name="review_verified" id="review_verified" value="1" <?php checked( $verified, '1' ); ?> />
					<label for="review_verified"><?php _e( 'This review is from a verified purchase', 'spider-boxes' ); ?></label>
				</td>
			</tr>
		</table>

		<div id="spider-boxes-review-custom-fields"></div>
		<?php
	}

	/**
	 * Save comment meta
	 *
	 * @param int $comment_id Comment ID
	 */
	public function save_comment_meta( $comment_id ) {
		if ( ! isset( $_POST['spider_boxes_review_meta_nonce'] ) ||
			! wp_verify_nonce( $_POST['spider_boxes_review_meta_nonce'], 'spider_boxes_review_meta' ) ) {
			return;
		}

		if ( isset( $_POST['review_rating'] ) ) {
			update_comment_meta( $comment_id, 'rating', intval( $_POST['review_rating'] ) );
		}

		if ( isset( $_POST['review_verified'] ) ) {
			update_comment_meta( $comment_id, 'verified', '1' );
		} else {
			delete_comment_meta( $comment_id, 'verified' );
		}

		do_action( 'spider_boxes_save_review_meta', $comment_id, $_POST );
	}

	/**
	 * AJAX handler for approving review
	 */
	public function ajax_approve_review() {
		check_ajax_referer( 'spider_boxes_nonce', 'nonce' );

		if ( ! current_user_can( 'moderate_comments' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'spider-boxes' ) );
		}

		$review_id = intval( $_POST['review_id'] );
		$result    = $this->approve_review( $review_id );

		wp_send_json_success( array( 'approved' => $result ) );
	}

	/**
	 * AJAX handler for marking review as spam
	 */
	public function ajax_spam_review() {
		check_ajax_referer( 'spider_boxes_nonce', 'nonce' );

		if ( ! current_user_can( 'moderate_comments' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'spider-boxes' ) );
		}

		$review_id = intval( $_POST['review_id'] );
		$result    = $this->spam_review( $review_id );

		wp_send_json_success( array( 'spammed' => $result ) );
	}

	/**
	 * AJAX handler for trashing review
	 */
	public function ajax_trash_review() {
		check_ajax_referer( 'spider_boxes_nonce', 'nonce' );

		if ( ! current_user_can( 'moderate_comments' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'spider-boxes' ) );
		}

		$review_id = intval( $_POST['review_id'] );
		$result    = $this->trash_review( $review_id );

		wp_send_json_success( array( 'trashed' => $result ) );
	}

	/**
	 * AJAX handler for replying to review
	 */
	public function ajax_reply_review() {
		check_ajax_referer( 'spider_boxes_nonce', 'nonce' );

		if ( ! current_user_can( 'moderate_comments' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'spider-boxes' ) );
		}

		$review_id = intval( $_POST['review_id'] );
		$content   = wp_kses_post( $_POST['content'] );

		$result = $this->reply_to_review( $review_id, $content );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'reply_id' => $result ) );
	}
}
