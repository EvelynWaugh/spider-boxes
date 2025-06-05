<?php
/**
 * Select Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Select Field Class
 */
class SelectField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'        => 'select',
				'options'     => array(),
				'multiple'    => false,
				'placeholder' => '',
			)
		);
	}

	/**
	 * Render field
	 *
	 * @param mixed $value Current value
	 * @return string
	 */
	public function render( $value = null ) {
		$value       = $value !== null ? $value : $this->config['value'];
		$options     = $this->config['options'];
		$multiple    = $this->config['multiple'];
		$placeholder = $this->config['placeholder'];

		// Ensure value is array for multiple select
		if ( $multiple && ! is_array( $value ) ) {
			$value = ! empty( $value ) ? array( $value ) : array();
		}

		$attrs = array(
			'class' => 'spider-boxes-select-field',
			'name'  => $multiple ? $this->id . '[]' : $this->id,
		);

		if ( $multiple ) {
			$attrs['multiple'] = true;
		}

		$content = '<select' . $this->get_attributes( $attrs ) . '>';

		// Add placeholder option
		if ( ! empty( $placeholder ) && ! $multiple ) {
			$content .= '<option value="">' . esc_html( $placeholder ) . '</option>';
		}

		// Add options
		foreach ( $options as $option_value => $option_config ) {
			$option_label = is_array( $option_config ) ? $option_config['label'] : $option_config;
			$option_attrs = array( 'value' => $option_value );

			if ( $multiple ) {
				if ( is_array( $value ) && in_array( $option_value, $value ) ) {
					$option_attrs['selected'] = true;
				}
			} elseif ( $value === $option_value ) {
					$option_attrs['selected'] = true;
			}

			$content .= '<option' . $this->get_attributes( $option_attrs ) . '>' . esc_html( $option_label ) . '</option>';
		}

		$content .= '</select>';

		return $this->wrap_field( $content );
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return mixed
	 */
	public function sanitize( $value ) {
		$options = array_keys( $this->config['options'] );

		if ( $this->config['multiple'] ) {
			if ( ! is_array( $value ) ) {
				return array();
			}
			return array_filter(
				$value,
				function ( $val ) use ( $options ) {
					return in_array( $val, $options );
				}
			);
		} else {
			return in_array( $value, $options ) ? $value : '';
		}
	}

	/**
	 * Validate field value
	 *
	 * @param mixed $value Value to validate
	 * @return bool|\WP_Error
	 */
	public function validate( $value ) {
		if ( empty( $value ) ) {
			return true; // Allow empty values
		}

		$options = array_keys( $this->config['options'] );

		if ( $this->config['multiple'] ) {
			if ( ! is_array( $value ) ) {
				return new \WP_Error( 'invalid_format', __( 'Multiple select value must be an array', 'spider-boxes' ) );
			}

			foreach ( $value as $val ) {
				if ( ! in_array( $val, $options ) ) {
					return new \WP_Error( 'invalid_option', __( 'Invalid option selected', 'spider-boxes' ) );
				}
			}
		} elseif ( ! in_array( $value, $options ) ) {
				return new \WP_Error( 'invalid_option', __( 'Invalid option selected', 'spider-boxes' ) );
		}

		return true;
	}
}
