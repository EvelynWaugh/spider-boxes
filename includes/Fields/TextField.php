<?php
/**
 * Text Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Text Field Class
 */
class TextField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'        => 'text',
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
		$value = $value !== null ? $value : $this->config['value'];

		$attrs = array(
			'type'  => 'text',
			'value' => $value,
			'class' => 'spider-boxes-text-field',
		);

		if ( ! empty( $this->config['placeholder'] ) ) {
			$attrs['placeholder'] = $this->config['placeholder'];
		}

		$content = '<input' . $this->get_attributes( $attrs ) . ' />';

		return $this->wrap_field( $content );
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return string
	 */
	public function sanitize( $value ) {
		return sanitize_text_field( $value );
	}
}
