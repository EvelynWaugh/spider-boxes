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