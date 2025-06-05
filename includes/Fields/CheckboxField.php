<?php
/**
 * Checkbox Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Checkbox Field Class
 */
class CheckboxField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'     => 'checkbox',
				'options'  => array(),
				'multiple' => false,
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
		$value    = $value !== null ? $value : $this->config['value'];
		$options  = $this->config['options'];
		$multiple = $this->config['multiple'];

		if ( empty( $options ) ) {
			return $this->wrap_field( '<p>' . __( 'No options available', 'spider-boxes' ) . '</p>' );
		}

		// Ensure value is array for multiple checkboxes
		if ( $multiple && ! is_array( $value ) ) {
			$value = ! empty( $value ) ? array( $value ) : array();
		}

		$content = '<div class="spider-boxes-checkbox-group">';

		foreach ( $options as $option_value => $option_config ) {
			$option_label = is_array( $option_config ) ? $option_config['label'] : $option_config;
			$option_id    = $this->id . '_' . $option_value;

			$attrs = array(
				'type'  => 'checkbox',
				'id'    => $option_id,
				'value' => $option_value,
				'class' => 'spider-boxes-checkbox-option',
			);

			if ( $multiple ) {
				$attrs['name'] = $this->id . '[]';
				if ( is_array( $value ) && in_array( $option_value, $value ) ) {
					$attrs['checked'] = true;
				}
			} else {
				$attrs['name'] = $this->id;
				if ( $value === $option_value ) {
					$attrs['checked'] = true;
				}
			}

			$content .= '<label class="spider-boxes-checkbox-label" for="' . esc_attr( $option_id ) . '">';
			$content .= '<input' . $this->get_attributes( $attrs ) . ' />';
			$content .= '<span class="spider-boxes-checkbox-text">' . esc_html( $option_label ) . '</span>';
			$content .= '</label>';
		}

		$content .= '</div>';

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
				return new \WP_Error( 'invalid_format', __( 'Multiple checkbox value must be an array', 'spider-boxes' ) );
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
