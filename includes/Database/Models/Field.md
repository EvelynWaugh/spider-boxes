```php
// Registry Integration Methods
// Create from registry
$field = Field::from_registry('contact_email', $registry_config);

// Register with registry
$field->register_with_registry('contact_email');

// Get field type config from registry
$type_config = $field->get_field_type_config();

// Check support using registry
if ($field->supports('multiple')) {
    // Handle multiple values
}

//Usage Examples with Your Registry:

// Create a field that matches your registry structure
$field = Field::create([
    'type' => 'react-select',
    'title' => 'Categories',
    'multiple' => true,
    'async' => true,
    'ajax_action' => 'get_categories',
    'context' => 'product_form'
]);

// Validate a value using field type rules
$validated_value = $field->get_validated_value($_POST['categories']);

// Check if field supports a feature from registry
if ($field->supports('ajax_action')) {
    $ajax_action = $field->ajax_action;
}

// Get child fields for repeater
$repeater_field = Field::create([
    'type' => 'repeater',
    'fields' => [
        [
            'type' => 'text',
            'title' => 'Item Title'
        ],
        [
            'type' => 'media',
            'title' => 'Item Image',
            'mime_types' => ['image/jpeg', 'image/png']
        ]
    ]
]);

$child_fields = $repeater_field->get_child_fields();

// Registry-Aware Validation

// Validation works with your field types
$media_field = Field::create([
    'type' => 'media',
    'mime_types' => ['image/jpeg', 'image/png'],
    'multiple' => true
]);

// This will validate against allowed mime types
$validated_ids = $media_field->get_validated_value([123, 456, 789]);

// React-select with options validation
$select_field = Field::create([
    'type' => 'react-select',
    'options' => [
        'red' => 'Red Color',
        'blue' => 'Blue Color'
    ],
    'multiple' => true
]);

$validated_colors = $select_field->get_validated_value(['red', 'invalid', 'blue']);
// Returns: ['red', 'blue'] - filters out invalid values

```
The Field::from_registry and Field::from_database methods serve different purposes based on your dual-source architecture:
Purpose: Convert database row data into a Field model instance
```php
// Raw database data (already unserialized by repository)
$db_row = [
    'id' => 123,
    'type' => 'text',
    'name' => 'admin_created_field',
    'title' => 'Admin Field',
    'value' => '',
    'settings' => ['placeholder' => 'Enter text...'], // Already unserialized
    'created_at' => '2024-06-14 10:30:00',
    'updated_at' => '2024-06-14 10:30:00'
];

$field = Field::from_database($db_row);
// Now $field has full model capabilities: save(), delete(), validation, etc.
//Purpose: Convert registry configuration into a Field model instance (without database persistence)

// Registry configuration (lightweight array from PHP code)
$registry_config = [
	'id' => 'contact_email',
    'type' => 'email',
    'title' => 'Contact Email',
    'placeholder' => 'Enter email...',
    'required' => true,
    'context' => 'contact_form'
    // no timestamps - this is code-defined
];

$field = Field::from_registry('contact_email', $registry_config);
// Now $field has model capabilities but won't save to database


// Get a field (could be from either source)
$field = $field_registry->get_field('contact_email');

if ($field->is_registry_field()) {
    echo "This field was defined in PHP code";
    echo $field->render(); // Can still render
    
    // Cannot save to database
    // $field->save(); // Would throw exception
    
    // But can create a database copy
    $db_copy = $field->create_database_copy();
    $db_copy->save(); // This works
    
} else {
    echo "This field was created in admin";
    echo $field->render();
    
    // Can save changes
    $field->title = "Updated Title";
    $field->save();
    
    // Can delete
    $field->delete();
}

// Render works for both types
$html = $field->render($current_value);