```php

// Direct repository usage
$field_repo = DatabaseManager::get_repository('field');
$fields = $field_repo->search('contact');

// Legacy DatabaseManager usage (still works)
$fields = DatabaseManager::get_all_fields('contact');

// Advanced filtering
$recent_fields = $field_repo->all(array(
    'context' => 'contact',
    '_order_by' => 'created_at',
    '_order_direction' => 'DESC',
    '_limit' => 10
));