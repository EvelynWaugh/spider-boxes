<?php
/**
 * Test Field Types CRUD API
 *
 * Direct test of the field types CRUD API endpoints to verify they're working
 */

// Include WordPress
require_once __DIR__ . '/../../../../wp-load.php';

// Check if user is logged in and has appropriate permissions
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied. Please log in as an administrator.' );
}

echo '<h1>Field Types CRUD API Test</h1>';

// Test 1: Get all field types
echo '<h2>Test 1: GET /field-types</h2>';

$request     = new WP_REST_Request( 'GET', '/spider-boxes/v1/field-types' );
$rest_server = rest_get_server();
$response    = $rest_server->dispatch( $request );

if ( $response->is_error() ) {
	echo '<p><strong>Status:</strong> Error</p>';
	echo '<p><strong>Error:</strong> ' . $response->as_error()->get_error_message() . '</p>';
} else {
	echo '<p><strong>Status:</strong> Success</p>';
	$data = $response->get_data();
	echo '<p><strong>Available Field Types:</strong> ' . count( $data ) . '</p>';

	// Store first field type for individual tests
	$test_field_type_id = '';
	if ( ! empty( $data ) ) {
		$test_field_type_id = $data[0]['id'];
		echo '<p><strong>Using field type ID for individual tests:</strong> ' . $test_field_type_id . '</p>';
	}
}

// Test 2: Get individual field type (if we have one)
if ( ! empty( $test_field_type_id ) ) {
	echo '<h2>Test 2: GET /field-types/' . $test_field_type_id . '</h2>';

	$request     = new WP_REST_Request( 'GET', '/spider-boxes/v1/field-types/' . $test_field_type_id );
	$rest_server = rest_get_server();
	$response    = $rest_server->dispatch( $request );

	if ( $response->is_error() ) {
		echo '<p><strong>Status:</strong> Error</p>';
		echo '<p><strong>Error:</strong> ' . $response->as_error()->get_error_message() . '</p>';
	} else {
		echo '<p><strong>Status:</strong> Success</p>';
		$data = $response->get_data();
		echo '<p><strong>Field Type Name:</strong> ' . $data['name'] . '</p>';
		echo '<p><strong>Field Type Type:</strong> ' . $data['type'] . '</p>';
		echo '<p><strong>Supports:</strong> ' . implode( ', ', $data['supports'] ) . '</p>';
	}
}

// Test 3: Create new field type
echo '<h2>Test 3: POST /field-types (Create)</h2>';

$new_field_type = array(
	'name'        => 'Test CRUD Field',
	'type'        => 'test_crud_field',
	'description' => 'A test field type created via CRUD API',
	'supports'    => array( 'label', 'description', 'value' ),
	'category'    => 'test',
	'icon'        => 'test-icon',
	'sort_order'  => 100,
	'is_active'   => true,
);

$request = new WP_REST_Request( 'POST', '/spider-boxes/v1/field-types' );
$request->set_header( 'Content-Type', 'application/json' );
$request->set_body( wp_json_encode( $new_field_type ) );

$rest_server = rest_get_server();
$response    = $rest_server->dispatch( $request );

$created_field_type_id = '';
if ( $response->is_error() ) {
	echo '<p><strong>Status:</strong> Error</p>';
	echo '<p><strong>Error:</strong> ' . $response->as_error()->get_error_message() . '</p>';
} else {
	echo '<p><strong>Status:</strong> Success</p>';
	$data = $response->get_data();
	echo '<p><strong>Created field type ID:</strong> ' . $data['id'] . '</p>';
	$created_field_type_id = $data['id'];
}

// Test 4: Update field type (if creation was successful)
if ( ! empty( $created_field_type_id ) ) {
	echo '<h2>Test 4: PUT /field-types/' . $created_field_type_id . ' (Update)</h2>';

	$updated_data = array(
		'description' => 'Updated description for test field type',
		'supports'    => array( 'label', 'description', 'value', 'placeholder' ),
		'sort_order'  => 200,
	);

	$request = new WP_REST_Request( 'PUT', '/spider-boxes/v1/field-types/' . $created_field_type_id );
	$request->set_header( 'Content-Type', 'application/json' );
	$request->set_body( wp_json_encode( $updated_data ) );

	$rest_server = rest_get_server();
	$response    = $rest_server->dispatch( $request );

	if ( $response->is_error() ) {
		echo '<p><strong>Status:</strong> Error</p>';
		echo '<p><strong>Error:</strong> ' . $response->as_error()->get_error_message() . '</p>';
	} else {
		echo '<p><strong>Status:</strong> Success</p>';
		$data = $response->get_data();
		echo '<p><strong>Updated field type description:</strong> ' . $data['field_type']['description'] . '</p>';
		echo '<p><strong>Updated supports:</strong> ' . implode( ', ', $data['field_type']['supports'] ) . '</p>';
	}
}

// Test 5: Delete field type (if creation was successful)
if ( ! empty( $created_field_type_id ) ) {
	echo '<h2>Test 5: DELETE /field-types/' . $created_field_type_id . ' (Delete)</h2>';

	$request = new WP_REST_Request( 'DELETE', '/spider-boxes/v1/field-types/' . $created_field_type_id );

	$rest_server = rest_get_server();
	$response    = $rest_server->dispatch( $request );

	if ( $response->is_error() ) {
		echo '<p><strong>Status:</strong> Error</p>';
		echo '<p><strong>Error:</strong> ' . $response->as_error()->get_error_message() . '</p>';
	} else {
		echo '<p><strong>Status:</strong> Success</p>';
		$data = $response->get_data();
		echo '<p><strong>Deleted:</strong> ' . ( $data['deleted'] ? 'Yes' : 'No' ) . '</p>';
	}
}

echo '<h2>Tests Completed</h2>';
echo '<p><em>All CRUD operations tested!</em></p>';
