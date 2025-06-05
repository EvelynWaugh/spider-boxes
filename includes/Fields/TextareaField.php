<?php
/**
 * Textarea Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Textarea Field Class
 */
class TextareaField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'        => 'textarea',
				'placeholder' => '',
				'rows'        => 4,
				'cols'        => 50,
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
		$placeholder = $this->config['placeholder'];
		$rows        = $this->config['rows'];
		$cols        = $this->config['cols'];

		$attrs = array(
			'class' => 'spider-boxes-textarea-field',
			'name'  => $this->id,
			'rows'  => $rows,
			'cols'  => $cols,
		);

		if ( ! empty( $placeholder ) ) {
			$attrs['placeholder'] = $placeholder;
		}

		$content = '<textarea' . $this->get_attributes( $attrs ) . '>' . esc_textarea( $value ) . '</textarea>';

		return $this->wrap_field( $content );
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return string
	 */
	public function sanitize( $value ) {
		return sanitize_textarea_field( $value );
	}
}
