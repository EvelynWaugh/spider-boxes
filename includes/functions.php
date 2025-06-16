<?php
/**
 * Helper Functions
 */

if ( ! function_exists( 'spider_boxes' ) ) {
	/**
	 * Get plugin instance
	 */
	function spider_boxes(): SpiderBoxes\Plugin {
		return SpiderBoxes\Plugin::get_instance();
	}
}

if ( ! function_exists( 'spider_boxes_container' ) ) {
	/**
	 * Get DI container
	 */
	function spider_boxes_container(): DI\Container {
		return spider_boxes()->get_container();
	}
}

if ( ! function_exists( 'spider_boxes_get_field_registry' ) ) {
	/**
	 * Get field registry
	 */
	function spider_boxes_get_field_registry(): SpiderBoxes\Core\FieldRegistry {
		return spider_boxes_container()->get( 'fieldRegistry' );
	}
}

if ( ! function_exists( 'spider_boxes_render_field' ) ) {
	/**
	 * Render a field
	 */
	function spider_boxes_render_field( string $field_id, $value = null, array $context = array() ): string {
		$field_registry = spider_boxes_get_field_registry();
		$field          = $field_registry->get_field( $field_id );

		if ( ! $field ) {
			return '';
		}

		return $field->render( $value, $context );
	}
}
