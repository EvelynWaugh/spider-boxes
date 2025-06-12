<?php
/**
 * Test Database Integration
 *
 * This file can be run to test the database functionality.
 * Access via: /wp-content/plugins/spider-boxes/test-database.php
 */

// Load WordPress
require_once '../../../wp-load.php';

// Load our DatabaseManager
require_once 'includes/Database/DatabaseManager.php';

use SpiderBoxes\Database\DatabaseManager;

echo '<h1>Spider Boxes Database Test</h1>';

// Test 1: Get field types
echo '<h2>Test 1: Get Field Types</h2>';
$field_types = DatabaseManager::get_field_types();
echo '<pre>';
print_r( $field_types );
echo '</pre>';

// Test 2: Register a new field type
echo '<h2>Test 2: Register New Field Type</h2>';
$new_field_type = array(
	'id'          => 'test_field',
	'name'        => 'Test Field',
	'class_name'  => 'SpiderBoxes\\Fields\\TestField',
	'category'    => 'test',
	'icon'        => 'test-icon',
	'description' => 'A test field type for testing purposes.',
	'supports'    => array( 'validation', 'default_value' ),
);

$result = DatabaseManager::register_field_type( $new_field_type );
echo 'Register field type result: ' . ( $result ? 'Success' : 'Failed' ) . '<br>';

// Test 3: Save field configuration
echo '<h2>Test 3: Save Field Configuration</h2>';
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

$result = DatabaseManager::save_field_config( 'test_field_config', $field_config );
echo 'Save field config result: ' . ( $result ? 'Success' : 'Failed' ) . '<br>';

// Test 4: Get field configuration
echo '<h2>Test 4: Get Field Configuration</h2>';
$retrieved_config = DatabaseManager::get_field_config( 'test_field_config' );
echo '<pre>';
print_r( $retrieved_config );
echo '</pre>';

// Test 5: Save meta value
echo '<h2>Test 5: Save Meta Value</h2>';
$meta_result = DatabaseManager::save_meta( 1, 'post', 'test_meta_key', 'test meta value', 'default' );
echo 'Save meta result: ' . ( $meta_result ? 'Success' : 'Failed' ) . '<br>';

// Test 6: Get meta value
echo '<h2>Test 6: Get Meta Value</h2>';
$meta_value = DatabaseManager::get_meta( 1, 'post', 'test_meta_key', 'default' );
echo 'Retrieved meta value: ' . $meta_value . '<br>';

echo '<h2>Database Test Complete</h2>';
echo "<p>If all tests show 'Success', the database integration is working correctly.</p>";
