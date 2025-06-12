<?php
/**
 * Test Field Types API
 *
 * Direct test of the field types API endpoint to verify it's working
 */

// Include WordPress
require_once __DIR__ . '/../../../../wp-load.php';

// Check if user is logged in and has appropriate permissions
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied. Please log in as an administrator.' );
}

echo '<h1>Field Types API Test</h1>';

// Test 1: Get field types
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
	echo '<p><strong>Response:</strong></p>';
	echo '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';

	echo '<p><strong>Available Field Types:</strong> ' . count( $data ) . '</p>';
	if ( ! empty( $data ) ) {
		echo '<ul>';
		foreach ( $data as $field_type ) {
			echo '<li><strong>' . $field_type['name'] . '</strong> (' . $field_type['type'] . ') - Supports: ' . implode( ', ', $field_type['supports'] ) . '</li>';
		}
		echo '</ul>';
	}
}

// Test 2: Test field type configuration endpoint for text field
echo '<h2>Test 2: GET /field-types/text/config</h2>';

$request     = new WP_REST_Request( 'GET', '/spider-boxes/v1/field-types/text/config' );
$rest_server = rest_get_server();
$response    = $rest_server->dispatch( $request );

if ( $response->is_error() ) {
	echo '<p><strong>Status:</strong> Error</p>';
	echo '<p><strong>Error:</strong> ' . $response->as_error()->get_error_message() . '</p>';
} else {
	echo '<p><strong>Status:</strong> Success</p>';
	$data = $response->get_data();
	echo '<p><strong>Response:</strong></p>';
	echo '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';

	if ( isset( $data['config_fields'] ) ) {
		echo '<p><strong>Configuration Fields:</strong> ' . count( $data['config_fields'] ) . '</p>';
		echo '<ul>';
		foreach ( $data['config_fields'] as $config_field ) {
			echo '<li><strong>' . $config_field['title'] . '</strong> (' . $config_field['type'] . ') - ' . $config_field['description'] . '</li>';
		}
		echo '</ul>';
	}
}

// Test 3: Test field type configuration endpoint for select field
echo '<h2>Test 3: GET /field-types/select/config</h2>';

$request     = new WP_REST_Request( 'GET', '/spider-boxes/v1/field-types/select/config' );
$rest_server = rest_get_server();
$response    = $rest_server->dispatch( $request );

if ( $response->is_error() ) {
	echo '<p><strong>Status:</strong> Error</p>';
	echo '<p><strong>Error:</strong> ' . $response->as_error()->get_error_message() . '</p>';
} else {
	echo '<p><strong>Status:</strong> Success</p>';
	$data = $response->get_data();
	echo '<p><strong>Response:</strong></p>';
	echo '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';
}

echo '<h2>Tests Completed</h2>';
echo '<p><em>If you see data above, the API is working correctly!</em></p>';
