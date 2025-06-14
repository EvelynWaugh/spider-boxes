```php
// In your controller:
public function register_routes() {
    register_rest_route(
        $this->namespace,
        '/fields',
        array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_field' ),
            'permission_callback' => PermissionMiddleware::create_permission_callback(
                'fields',
                array(
                    'rate_limit'      => true,
                    'roles'           => array( 'editor', 'administrator' ),
                    'check_ownership' => false,
                )
            ),
        )
    );
}
//Basic usage in BaseController:

public function check_permissions( $request ) {
    return PermissionMiddleware::check_context_permissions( true, $request, 'fields' );
}
//Advanced usage with options:

$permission_callback = PermissionMiddleware::create_permission_callback( 'reviews', array(
    'rate_limit'      => true,
    'roles'           => array( 'contributor', 'author', 'editor', 'administrator' ),
    'check_ownership' => true,
) );
//Custom permission checks:

add_filter( 'spider_boxes_check_specific_permission', function( $has_permission, $context, $action, $request ) {
    if ( $context === 'premium_fields' && ! user_has_premium_access() ) {
        return false;
    }
    return $has_permission;
}, 10, 4 );