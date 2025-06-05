<?php
/**
 * Radio Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Radio Field Class
 */
class RadioField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'    => 'radio',
				'options' => array(),
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
		$value   = $value !== null ? $value : $this->config['value'];
		$options = $this->config['options'];

		if ( empty( $options ) ) {
			return $this->wrap_field( '<p>' . __( 'No options available', 'spider-boxes' ) . '</p>' );
		}

		$content = '<div class="spider-boxes-radio-group">';

		foreach ( $options as $option_value => $option_config ) {
			$option_label = is_array( $option_config ) ? $option_config['label'] : $option_config;
			$option_id    = $this->id . '_' . $option_value;

			$attrs = array(
				'type'  => 'radio',
				'id'    => $option_id,
				'name'  => $this->id,
				'value' => $option_value,
				'class' => 'spider-boxes-radio-option',
			);

			if ( $value === $option_value ) {
				$attrs['checked'] = true;
			}

			$content .= '<label class="spider-boxes-radio-label" for="' . esc_attr( $option_id ) . '">';
			$content .= '<input' . $this->get_attributes( $attrs ) . ' />';
			$content .= '<span class="spider-boxes-radio-text">' . esc_html( $option_label ) . '</span>';
			$content .= '</label>';
		}

		$content .= '</div>';

		return $this->wrap_field( $content );
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return string
	 */
	public function sanitize( $value ) {
		$options = array_keys( $this->config['options'] );
		return in_array( $value, $options ) ? $value : '';
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
		if ( ! in_array( $value, $options ) ) {
			return new \WP_Error( 'invalid_option', __( 'Invalid option selected', 'spider-boxes' ) );
		}

		return true;
	}
}
