<?php
/**
 * Database Integration Unit Tests
 *
 * @package SpiderBoxes\Tests
 */

namespace SpiderBoxes\Tests;

use PHPUnit\Framework\TestCase;
use SpiderBoxes\Database\DatabaseManager;

/**
 * Database Test Class
 */
class DatabaseTest extends TestCase {

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock WordPress functions if needed
		if ( ! function_exists( 'sanitize_key' ) ) {
			function sanitize_key( $key ) {
				return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) );
			}
		}

		if ( ! function_exists( 'sanitize_text_field' ) ) {
			function sanitize_text_field( $str ) {
				return trim( strip_tags( $str ) );
			}
		}

		if ( ! function_exists( 'sanitize_textarea_field' ) ) {
			function sanitize_textarea_field( $str ) {
				return trim( strip_tags( $str ) );
			}
		}

		if ( ! function_exists( 'absint' ) ) {
			function absint( $maybeint ) {
				return abs( intval( $maybeint ) );
			}
		}
	}

	/**
	 * Test field type registration
	 */
	public function test_register_field_type() {
		$field_type_data = array(
			'id'          => 'test_field_type',
			'name'        => 'Test Field Type',
			'class_name'  => 'SpiderBoxes\\Fields\\TestField',

			'icon'        => 'test-icon',
			'description' => 'A test field type for unit testing.',
			'supports'    => array( 'validation', 'default_value' ),
			'is_active'   => true,

		);

		// Test registration
		$result = DatabaseManager::register_field_type( $field_type_data );
		$this->assertTrue( $result );

		// Test retrieval
		$field_types = DatabaseManager::get_field_types();
		$this->assertIsArray( $field_types );

		// Find our test field type
		$test_field_type = null;
		foreach ( $field_types as $type ) {
			if ( $type['id'] === 'test_field_type' ) {
				$test_field_type = $type;
				break;
			}
		}

		$this->assertNotNull( $test_field_type );
		$this->assertEquals( 'Test Field Type', $test_field_type['name'] );
		$this->assertEquals( 'SpiderBoxes\\Fields\\TestField', $test_field_type['class_name'] );
	}

	/**
	 * Test field configuration management
	 */
	public function test_field_configuration() {
		$field_config = array(
			'type'        => 'text',
			'title'       => 'Test Field Configuration',
			'description' => 'This is a test field configuration',
			'parent'      => 'test_section',
			'context'     => 'post',
			'value'       => 'default value',
			'settings'    => array(
				'placeholder' => 'Enter text here...',
				'validation'  => array( 'required' => true ),
			),

		);

		// Test saving configuration
		$result = DatabaseManager::save_field_config( 'test_field_config', $field_config );
		$this->assertTrue( $result );

		// Test retrieving configuration
		$retrieved_config = DatabaseManager::get_field_config( 'test_field_config' );
		$this->assertIsArray( $retrieved_config );
		$this->assertEquals( 'text', $retrieved_config['type'] );
		$this->assertEquals( 'Test Field Configuration', $retrieved_config['title'] );

		// Test getting all fields
		$all_fields = DatabaseManager::get_all_fields();
		$this->assertIsArray( $all_fields );

		// Find our test field
		$test_field = null;
		foreach ( $all_fields as $field ) {
			if ( $field['id'] === 'test_field_config' ) {
				$test_field = $field;
				break;
			}
		}

		$this->assertNotNull( $test_field );
	}

	/**
	 * Test meta value management
	 */
	public function test_meta_management() {
		$object_id   = 1;
		$object_type = 'post';
		$meta_key    = 'test_meta_key';
		$meta_value  = 'test meta value';
		$context     = 'default';

		// Test saving meta
		$result = DatabaseManager::save_meta( $object_id, $object_type, $meta_key, $meta_value, $context );
		$this->assertTrue( $result );

		// Test retrieving meta
		$retrieved_value = DatabaseManager::get_meta( $object_id, $object_type, $meta_key, $context );
		$this->assertEquals( $meta_value, $retrieved_value );
	}

	/**
	 * Test field validation
	 */
	public function test_field_validation() {
		// Test valid field configuration
		$valid_config = array(
			'id'    => 'valid_field',
			'type'  => 'text',
			'title' => 'Valid Field',
		);

		$result = DatabaseManager::validate_field_config( $valid_config );
		$this->assertTrue( $result );

		// Test invalid field configuration (missing required fields)
		$invalid_config = array(
			'id' => 'invalid_field',
			// Missing type and title
		);

		$result = DatabaseManager::validate_field_config( $invalid_config );
		$this->assertFalse( $result );

		// Test invalid field ID format
		$invalid_id_config = array(
			'id'    => 'invalid field!',
			'type'  => 'text',
			'title' => 'Invalid Field',
		);

		$result = DatabaseManager::validate_field_config( $invalid_id_config );
		$this->assertFalse( $result );
	}

	/**
	 * Test field sanitization
	 */
	public function test_field_sanitization() {
		$dirty_config = array(
			'id'          => 'Test Field ID',
			'type'        => '<script>alert("xss")</script>text',
			'title'       => '<h1>Malicious Title</h1>',
			'description' => '<script>alert("xss")</script>Description',

			'context'     => 'post<script>',
			'value'       => 'safe value',

		);

		$sanitized = DatabaseManager::sanitize_field_config( $dirty_config );

		$this->assertEquals( 'testfieldid', $sanitized['id'] );
		$this->assertEquals( 'text', $sanitized['type'] );
		$this->assertEquals( 'Malicious Title', $sanitized['title'] );
		$this->assertEquals( 'Description', $sanitized['description'] );
	}

	/**
	 * Test field deletion
	 */
	public function test_field_deletion() {
		// First create a field
		$field_config = array(
			'type'  => 'text',
			'title' => 'Field to Delete',
		);

		$save_result = DatabaseManager::save_field_config( 'field_to_delete', $field_config );
		$this->assertTrue( $save_result );

		// Verify it exists
		$retrieved = DatabaseManager::get_field_config( 'field_to_delete' );
		$this->assertIsArray( $retrieved );

		// Delete it
		$delete_result = DatabaseManager::delete_field_config( 'field_to_delete' );
		$this->assertTrue( $delete_result );

		// Verify it's gone
		$retrieved_after_delete = DatabaseManager::get_field_config( 'field_to_delete' );
		$this->assertNull( $retrieved_after_delete );
	}

	/**
	 * Test meta deletion
	 */
	public function test_meta_deletion() {
		// First create meta
		$save_result = DatabaseManager::save_meta( 999, 'post', 'meta_to_delete', 'test value', 'default' );
		$this->assertTrue( $save_result );

		// Verify it exists
		$retrieved = DatabaseManager::get_meta( 999, 'post', 'meta_to_delete', 'default' );
		$this->assertEquals( 'test value', $retrieved );

		// Delete it
		$delete_result = DatabaseManager::delete_field_meta( 999, 'post', 'meta_to_delete', 'default' );
		$this->assertTrue( $delete_result );

		// Verify it's gone
		$retrieved_after_delete = DatabaseManager::get_meta( 999, 'post', 'meta_to_delete', 'default' );
		$this->assertNull( $retrieved_after_delete );
	}
}
