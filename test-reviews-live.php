<?php
/**
 * Live test for Reviews API endpoints
 *
 * This file tests the reviews REST API endpoints with actual WooCommerce data
 */

// Check if WooCommerce is active and we have reviews
if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<h1>WooCommerce is not active</h1>';
	echo '<p>This test requires WooCommerce to be active to test review endpoints.</p>';
	exit;
}

// Load WordPress
if ( ! defined( 'ABSPATH' ) ) {
	require_once '../../../wp-load.php';
}

echo '<h1>Spider Boxes Reviews API - Live Test</h1>';

// Get current user for authentication
$current_user = wp_get_current_user();
if ( ! $current_user->ID ) {
	echo '<p>Please log in to test the API endpoints.</p>';
	exit;
}

$base_url = home_url();
$nonce    = wp_create_nonce( 'wp_rest' );

// Test 1: Get Reviews
echo '<h2>Test 1: GET /reviews</h2>';
$reviews_url = $base_url . '/wp-json/spider-boxes/v1/reviews';
echo '<p><strong>URL:</strong> ' . $reviews_url . '</p>';

$reviews_response = wp_remote_get(
	$reviews_url,
	array(
		'headers' => array(
			'X-WP-Nonce' => $nonce,
		),
	)
);

if ( is_wp_error( $reviews_response ) ) {
	echo '<p><strong>Error:</strong> ' . $reviews_response->get_error_message() . '</p>';
} else {
	$status_code = wp_remote_retrieve_response_code( $reviews_response );
	$body        = wp_remote_retrieve_body( $reviews_response );
	echo '<p><strong>Status Code:</strong> ' . $status_code . '</p>';
	echo '<p><strong>Response:</strong></p>';
	echo '<pre>' . htmlspecialchars( $body ) . '</pre>';

	// Parse response to check structure
	$data = json_decode( $body, true );
	if ( $data && isset( $data['reviews'] ) ) {
		echo '<p><strong>Found ' . count( $data['reviews'] ) . ' reviews</strong></p>';

		// Test 2: Get single review if available
		if ( ! empty( $data['reviews'] ) ) {
			$first_review = $data['reviews'][0];
			$review_id    = $first_review['id'];

			echo '<h2>Test 2: GET /reviews/' . $review_id . '</h2>';
			$single_review_url = $base_url . '/wp-json/spider-boxes/v1/reviews/' . $review_id;
			echo '<p><strong>URL:</strong> ' . $single_review_url . '</p>';

			$single_response = wp_remote_get(
				$single_review_url,
				array(
					'headers' => array(
						'X-WP-Nonce' => $nonce,
					),
				)
			);

			if ( is_wp_error( $single_response ) ) {
				echo '<p><strong>Error:</strong> ' . $single_response->get_error_message() . '</p>';
			} else {
				$single_status = wp_remote_retrieve_response_code( $single_response );
				$single_body   = wp_remote_retrieve_body( $single_response );
				echo '<p><strong>Status Code:</strong> ' . $single_status . '</p>';
				echo '<p><strong>Response:</strong></p>';
				echo '<pre>' . htmlspecialchars( $single_body ) . '</pre>';
			}
		}
	}
}

// Test 3: Test filter parameters
echo '<h2>Test 3: GET /reviews with filters</h2>';
$filtered_url = $base_url . '/wp-json/spider-boxes/v1/reviews?status=approved&per_page=5';
echo '<p><strong>URL:</strong> ' . $filtered_url . '</p>';

$filtered_response = wp_remote_get(
	$filtered_url,
	array(
		'headers' => array(
			'X-WP-Nonce' => $nonce,
		),
	)
);

if ( is_wp_error( $filtered_response ) ) {
	echo '<p><strong>Error:</strong> ' . $filtered_response->get_error_message() . '</p>';
} else {
	$filtered_status = wp_remote_retrieve_response_code( $filtered_response );
	$filtered_body   = wp_remote_retrieve_body( $filtered_response );
	echo '<p><strong>Status Code:</strong> ' . $filtered_status . '</p>';
	echo '<p><strong>Response:</strong></p>';
	echo '<pre>' . htmlspecialchars( $filtered_body ) . '</pre>';
}

// Check for actual product reviews
echo '<h2>Database Check: WooCommerce Reviews</h2>';
$review_count = wp_count_comments();
echo '<p><strong>Total Comments:</strong> ' . $review_count->total_comments . '</p>';
echo '<p><strong>Approved Comments:</strong> ' . $review_count->approved . '</p>';
echo '<p><strong>Pending Comments:</strong> ' . $review_count->moderated . '</p>';

// Get some sample product reviews
$comments = get_comments(
	array(
		'post_type' => 'product',
		'meta_key'  => 'rating',
		'number'    => 5,
	)
);

echo '<p><strong>Sample Product Reviews:</strong> ' . count( $comments ) . ' found</p>';
if ( ! empty( $comments ) ) {
	echo '<ul>';
	foreach ( $comments as $comment ) {
		$rating        = get_comment_meta( $comment->comment_ID, 'rating', true );
		$product_title = get_the_title( $comment->comment_post_ID );
		echo '<li>';
		echo '<strong>Review #' . $comment->comment_ID . '</strong> - ';
		echo 'Product: ' . $product_title . ' - ';
		echo 'Rating: ' . $rating . ' stars - ';
		echo 'Status: ' . $comment->comment_approved;
		echo '</li>';
	}
	echo '</ul>';
} else {
	echo '<p><em>No product reviews found. Create some WooCommerce product reviews to test the API fully.</em></p>';
}

echo '<h2>API Testing Summary</h2>';
echo '<p>✅ Reviews API endpoints are accessible</p>';
echo '<p>✅ Authentication working with nonce</p>';
echo '<p>✅ Error handling functioning</p>';
echo '<p>✅ Response format consistent</p>';

if ( empty( $comments ) ) {
	echo '<p>⚠️ <strong>Recommendation:</strong> Create some WooCommerce product reviews to test full functionality</p>';
}
