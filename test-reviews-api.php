<?php
/**
 * Test file for Reviews API endpoints
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	// Load WordPress
	require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/wp-load.php';
}

// Check if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<h1>Error: WooCommerce is not active</h1>';
	echo '<p>This test requires WooCommerce to be installed and activated.</p>';
	exit;
}

echo '<h1>Reviews API Test</h1>';

// Test 1: Check if plugin is active
if ( ! function_exists( 'spider_boxes' ) ) {
	echo '<h2>Error: Spider Boxes plugin is not active</h2>';
	exit;
}

echo '<h2>Plugin Status: Active ✓</h2>';

// Test 2: Check if WooCommerce reviews exist
global $wpdb;

$reviews_count = $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->comments} 
	WHERE comment_type = 'review' 
	AND comment_approved IN ('1', 'approve', 'approved')"
);

echo "<h2>WooCommerce Reviews Found: {$reviews_count}</h2>";

if ( $reviews_count == 0 ) {
	echo '<p>No WooCommerce reviews found. Let\'s create some test reviews...</p>';

	// Create a test product first
	$product = new WC_Product_Simple();
	$product->set_name( 'Test Product for Reviews' );
	$product->set_regular_price( 19.99 );
	$product->set_description( 'This is a test product for reviews API testing.' );
	$product->set_status( 'publish' );
	$product_id = $product->save();

	echo "<p>Created test product ID: {$product_id}</p>";

	// Create test reviews
	$test_reviews = array(
		array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => 'John Doe',
			'comment_author_email' => 'john@example.com',
			'comment_content'      => 'Great product! I really love it.',
			'comment_type'         => 'review',
			'comment_approved'     => 1,
			'comment_date'         => current_time( 'mysql' ),
		),
		array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => 'Jane Smith',
			'comment_author_email' => 'jane@example.com',
			'comment_content'      => 'Good quality, fast shipping.',
			'comment_type'         => 'review',
			'comment_approved'     => 1,
			'comment_date'         => current_time( 'mysql' ),
		),
		array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => 'Bob Wilson',
			'comment_author_email' => 'bob@example.com',
			'comment_content'      => 'Average product, could be better.',
			'comment_type'         => 'review',
			'comment_approved'     => 0, // Pending
			'comment_date'         => current_time( 'mysql' ),
		),
	);

	foreach ( $test_reviews as $index => $review ) {
		$comment_id = wp_insert_comment( $review );
		if ( $comment_id ) {
			// Add rating meta
			$rating = 5 - $index; // 5, 4, 3 stars
			update_comment_meta( $comment_id, 'rating', $rating );
			echo "<p>Created test review ID: {$comment_id} with {$rating} stars</p>";
		}
	}
}

// Test 3: Test the Reviews API directly
echo '<h2>Testing Reviews API Endpoint</h2>';

try {
	// Get the reviews manager
	$reviews_manager = spider_boxes()->get_container()->get( 'reviewsManager' );

	// Test get_reviews method
	$reviews_data = $reviews_manager->get_reviews();

	echo '<h3>Reviews Data Structure:</h3>';
	echo '<pre>' . print_r( $reviews_data, true ) . '</pre>';

	if ( ! empty( $reviews_data['reviews'] ) ) {
		echo '<h3>Sample Review Data:</h3>';
		echo '<pre>' . print_r( $reviews_data['reviews'][0], true ) . '</pre>';
	}
} catch ( Exception $e ) {
	echo '<h3>Error testing Reviews Manager:</h3>';
	echo '<p>' . $e->getMessage() . '</p>';
}

// Test 4: Test REST API endpoint simulation
echo '<h2>REST API Endpoint Test</h2>';

// Simulate a REST request
$request = new WP_REST_Request( 'GET', '/spider-boxes/v1/reviews' );

// Get the REST routes handler
try {
	$rest_routes = new SpiderBoxes\API\RestRoutes();
	$response    = $rest_routes->get_reviews( $request );

	if ( is_wp_error( $response ) ) {
		echo '<h3>API Error:</h3>';
		echo '<p>' . $response->get_error_message() . '</p>';
	} else {
		echo '<h3>API Response:</h3>';
		$data = $response->get_data();
		echo '<pre>' . print_r( $data, true ) . '</pre>';
	}
} catch ( Exception $e ) {
	echo '<h3>Error testing REST API:</h3>';
	echo '<p>' . $e->getMessage() . '</p>';
}

echo '<h2>Test completed!</h2>';
echo '<p>You can now test the frontend ReviewsApp component to see if it loads the reviews correctly.</p>';
echo '<p>API Endpoint: <code>/wp-json/spider-boxes/v1/reviews</code></p>';
