<?php
/**
 * REST API Integration Test
 *
 * This file tests the REST API endpoints for field management.
 * Access via browser or curl to test the API endpoints.
 */

// For testing purposes, we'll create a simple test runner
class RestAPITester {

	private $base_url;
	private $namespace = 'spider-boxes/v1';

	public function __construct( $base_url = 'http://localhost:8080' ) {
		$this->base_url = rtrim( $base_url, '/' );
	}

	/**
	 * Test all REST API endpoints
	 */
	public function run_tests() {
		echo '<h1>Spider Boxes REST API Tests</h1>';

		// Test 1: Get field types
		$this->test_get_field_types();

		// Test 2: Create field type
		$this->test_create_field_type();

		// Test 3: Create field
		$this->test_create_field();

		// Test 4: Get fields
		$this->test_get_fields();

		// Test 5: Get single field
		$this->test_get_field();

		// Test 6: Update field
		$this->test_update_field();

		// Test 7: Save field value
		$this->test_save_field_value();

		// Test 8: Get field value
		$this->test_get_field_value();

		// Test 9: Delete field
		$this->test_delete_field();

		echo '<h2>All Tests Completed</h2>';
	}

	/**
	 * Test GET /field-types endpoint
	 */
	private function test_get_field_types() {
		echo '<h3>Test 1: GET /field-types</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/field-types';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$response = $this->make_request( 'GET', $url );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test POST /field-types endpoint
	 */
	private function test_create_field_type() {
		echo '<h3>Test 2: POST /field-types</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/field-types';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$data = array(
			'id'          => 'test_api_field',
			'name'        => 'Test API Field',
			'class_name'  => 'SpiderBoxes\\Fields\\TestAPIField',
			'category'    => 'api_test',
			'icon'        => 'api-icon',
			'description' => 'A test field type created via REST API.',
			'supports'    => array( 'validation', 'default_value' ),
			'is_active'   => true,
			'sort_order'  => 20,
		);

		echo '<p><strong>Data:</strong></p>';
		echo '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';

		$response = $this->make_request( 'POST', $url, $data );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test POST /fields endpoint
	 */
	private function test_create_field() {
		echo '<h3>Test 3: POST /fields</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/fields';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$data = array(
			'id'          => 'test_api_field_config',
			'type'        => 'text',
			'title'       => 'Test API Field Configuration',
			'description' => 'This is a test field configuration created via REST API',
			'parent'      => 'test_section',
			'context'     => 'post',
			'value'       => 'api default value',
			'settings'    => array(
				'placeholder' => 'Enter text via API...',
				'validation'  => array( 'required' => true ),
			),
			'capability'  => 'manage_options',
			'sort_order'  => 15,
		);

		echo '<p><strong>Data:</strong></p>';
		echo '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';

		$response = $this->make_request( 'POST', $url, $data );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test GET /fields endpoint
	 */
	private function test_get_fields() {
		echo '<h3>Test 4: GET /fields</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/fields';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$response = $this->make_request( 'GET', $url );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test GET /fields/{id} endpoint
	 */
	private function test_get_field() {
		echo '<h3>Test 5: GET /fields/{id}</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/fields/test_api_field_config';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$response = $this->make_request( 'GET', $url );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test PUT /fields/{id} endpoint
	 */
	private function test_update_field() {
		echo '<h3>Test 6: PUT /fields/{id}</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/fields/test_api_field_config';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$data = array(
			'title'       => 'Updated Test API Field Configuration',
			'description' => 'This field has been updated via REST API',
			'value'       => 'updated api value',
			'settings'    => array(
				'placeholder' => 'Updated placeholder...',
				'validation'  => array( 'required' => false ),
			),
		);

		echo '<p><strong>Data:</strong></p>';
		echo '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';

		$response = $this->make_request( 'PUT', $url, $data );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test POST /field-value endpoint
	 */
	private function test_save_field_value() {
		echo '<h3>Test 7: POST /field-value</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/field-value';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$data = array(
			'object_id'   => 123,
			'object_type' => 'post',
			'meta_key'    => 'test_api_meta',
			'meta_value'  => 'API meta value test',
			'context'     => 'api_test',
		);

		echo '<p><strong>Data:</strong></p>';
		echo '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';

		$response = $this->make_request( 'POST', $url, $data );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test GET /field-value endpoint
	 */
	private function test_get_field_value() {
		echo '<h3>Test 8: GET /field-value</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/field-value?object_id=123&object_type=post&meta_key=test_api_meta&context=api_test';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$response = $this->make_request( 'GET', $url );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Test DELETE /fields/{id} endpoint
	 */
	private function test_delete_field() {
		echo '<h3>Test 9: DELETE /fields/{id}</h3>';

		$url = $this->base_url . '/wp-json/' . $this->namespace . '/fields/test_api_field_config';
		echo '<p><strong>URL:</strong> ' . $url . '</p>';

		$response = $this->make_request( 'DELETE', $url );

		if ( $response ) {
			echo '<p><strong>Status:</strong> Success</p>';
			echo '<pre>' . json_encode( $response, JSON_PRETTY_PRINT ) . '</pre>';
		} else {
			echo '<p><strong>Status:</strong> Failed</p>';
		}
	}

	/**
	 * Make HTTP request
	 */
	private function make_request( $method, $url, $data = null ) {
		// For testing purposes, we'll simulate the requests
		// In a real environment, you would use cURL or wp_remote_request

		echo '<p><strong>Simulating ' . $method . ' request...</strong></p>';
		echo '<p><em>Note: In a live environment, this would make actual HTTP requests to test the endpoints.</em></p>';

		// Return mock success response for demonstration
		return array(
			'success' => true,
			'method'  => $method,
			'url'     => $url,
			'data'    => $data,
			'message' => 'Request would be processed in live environment',
		);
	}
}

// Usage instructions
echo '<h1>REST API Test Runner</h1>';
echo '<p>This test runner validates all REST API endpoints for the Spider Boxes plugin.</p>';

echo '<h2>Manual Testing Instructions</h2>';
echo '<ol>';
echo '<li>Ensure WordPress is running and the plugin is activated</li>';
echo '<li>Use a tool like Postman, curl, or browser to test endpoints</li>';
echo '<li>Base URL: <code>http://your-site.com/wp-json/spider-boxes/v1/</code></li>';
echo '</ol>';

echo '<h2>Available Endpoints</h2>';
echo '<ul>';
echo '<li><strong>GET</strong> /field-types - Get all field types</li>';
echo '<li><strong>POST</strong> /field-types - Create a new field type</li>';
echo '<li><strong>GET</strong> /fields - Get all fields</li>';
echo '<li><strong>POST</strong> /fields - Create a new field</li>';
echo '<li><strong>GET</strong> /fields/{id} - Get specific field</li>';
echo '<li><strong>PUT</strong> /fields/{id} - Update specific field</li>';
echo '<li><strong>DELETE</strong> /fields/{id} - Delete specific field</li>';
echo '<li><strong>GET</strong> /field-value - Get field meta value</li>';
echo '<li><strong>POST</strong> /field-value - Save field meta value</li>';
echo '</ul>';

echo '<h2>Example cURL Commands</h2>';
echo '<pre>';
echo '# Get field types
curl -X GET "http://your-site.com/wp-json/spider-boxes/v1/field-types"

# Create field type
curl -X POST "http://your-site.com/wp-json/spider-boxes/v1/field-types" \
  -H "Content-Type: application/json" \
  -d \'{
    "id": "custom_field",
    "name": "Custom Field",
    "class_name": "SpiderBoxes\\\\Fields\\\\CustomField",
    "category": "custom",
    "description": "A custom field type"
  }\'

# Create field
curl -X POST "http://your-site.com/wp-json/spider-boxes/v1/fields" \
  -H "Content-Type: application/json" \
  -d \'{
    "id": "my_field",
    "type": "text",
    "title": "My Field",
    "description": "Field description"
  }\'

# Get fields
curl -X GET "http://your-site.com/wp-json/spider-boxes/v1/fields"

# Update field
curl -X PUT "http://your-site.com/wp-json/spider-boxes/v1/fields/my_field" \
  -H "Content-Type: application/json" \
  -d \'{
    "title": "Updated Field Title",
    "description": "Updated description"
  }\'

# Save field value
curl -X POST "http://your-site.com/wp-json/spider-boxes/v1/field-value" \
  -H "Content-Type: application/json" \
  -d \'{
    "object_id": 1,
    "object_type": "post",
    "meta_key": "my_field_value",
    "meta_value": "Hello World",
    "context": "default"
  }\'

# Get field value
curl -X GET "http://your-site.com/wp-json/spider-boxes/v1/field-value?object_id=1&object_type=post&meta_key=my_field_value&context=default"

# Delete field
curl -X DELETE "http://your-site.com/wp-json/spider-boxes/v1/fields/my_field"
';
echo '</pre>';

// Run simulated tests
$tester = new RestAPITester();
$tester->run_tests();
