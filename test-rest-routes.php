<?php
/**
 * Test REST Routes API
 *
 * This file can be run to test the REST API endpoints.
 */

// Load WordPress
require_once '../../../wp-load.php';

echo '<h1>Spider Boxes REST API Test</h1>';

// Test 1: Get field types
echo '<h2>Test 1: Get Field Types (GET)</h2>';
$url = home_url( '/wp-json/spider-boxes/v1/field-types' );
echo "Testing URL: $url<br>";

$response = wp_remote_get(
	$url,
	array(
		'headers' => array(
			'Authorization' => 'Bearer ' . ( wp_get_current_user()->ID ? wp_create_nonce( 'wp_rest' ) : '' ),
		),
	)
);

if ( is_wp_error( $response ) ) {
	echo 'Error: ' . $response->get_error_message() . '<br>';
} else {
	echo 'Status Code: ' . wp_remote_retrieve_response_code( $response ) . '<br>';
	echo 'Response: ' . wp_remote_retrieve_body( $response ) . '<br>';
}

// Test 2: Create field type
echo '<h2>Test 2: Create Field Type (POST)</h2>';
$create_data = array(
	'id'          => 'test_field_type',
	'name'        => 'Test Field Type',
	'class_name'  => 'SpiderBoxes\\Fields\\TestFieldType',
	'category'    => 'test',
	'icon'        => 'test',
	'description' => 'A test field type for API testing',
	'supports'    => array( 'validation', 'default_value' ),
);

$response = wp_remote_post(
	$url,
	array(
		'body'    => json_encode( $create_data ),
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . ( wp_get_current_user()->ID ? wp_create_nonce( 'wp_rest' ) : '' ),
		),
	)
);

if ( is_wp_error( $response ) ) {
	echo 'Error: ' . $response->get_error_message() . '<br>';
} else {
	echo 'Status Code: ' . wp_remote_retrieve_response_code( $response ) . '<br>';
	echo 'Response: ' . wp_remote_retrieve_body( $response ) . '<br>';
}

echo '<h2>REST Route Testing Complete</h2>';
