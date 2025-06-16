## Usage
```php
// Get all fields as models with criteria
$field_repository = spider_boxes()->get_container()->get('fieldRepository');

// Basic usage (what your FieldRegistry needs)
$db_fields = $field_repository->all_as_models([
    'context' => 'post',
    'parent' => 'contact_section'
]);

// Advanced usage
$search_results = $field_repository->search_as_models('contact');
$text_fields = $field_repository->get_by_type_as_models('text');
$post_fields = $field_repository->get_by_context_as_models('post');

// Pagination
$paginated = $field_repository->paginate_as_models([
    'context' => 'post',
    'search' => 'email'
], $page = 1, $per_page = 10);

// Working with individual models
foreach ($db_fields as $field) {
    echo $field->title; // Access as properties
    echo $field->render(); // Render the field
    
    if ($field->supports('multiple')) {
        // Handle multiple values
    }
}

// Create and save
$field = Field::create([
    'type' => 'text',
    'title' => 'Sample Field'
]);

$field_repository->create_from_model($field);

// Update
$field->title = 'Updated Title';
$field_repository->update_from_model($field);