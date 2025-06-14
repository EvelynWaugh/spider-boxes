<?php
/**
 * Validation Trait
 *
 * @package SpiderBoxes\API\Traits
 */

namespace SpiderBoxes\API\Traits;

use WP_Error;

/**
 * Validation Trait
 */
trait ValidationTrait {

	/**
	 * Validate field configuration
	 *
	 * @param array $config Field configuration
	 * @return WP_Error|null
	 */
	protected function validate_field_config( $config ) {
		$errors = array();

		// Required fields
		$required = array( 'type', 'title' );
		foreach ( $required as $field ) {
			if ( empty( $config[ $field ] ) ) {
				$errors[] = sprintf( __( 'Missing required field: %s', 'spider-boxes' ), $field );
			}
		}

		// Type validation
		if ( ! empty( $config['type'] ) ) {
			$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
			if ( ! $field_registry->field_type_exists( $config['type'] ) ) {
				$errors[] = __( 'Invalid field type.', 'spider-boxes' );
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', implode( ', ', $errors ), array( 'status' => 400 ) );
		}

		return null;
	}

	/**
	 * Validate component configuration
	 *
	 * @param array $config Component configuration
	 * @return WP_Error|null
	 */
	protected function validate_component_config( $config ) {
		$errors = array();

		// Required fields
		$required = array( 'type', 'title' );
		foreach ( $required as $field ) {
			if ( empty( $config[ $field ] ) ) {
				$errors[] = sprintf( __( 'Missing required field: %s', 'spider-boxes' ), $field );
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', implode( ', ', $errors ), array( 'status' => 400 ) );
		}

		return null;
	}
}
