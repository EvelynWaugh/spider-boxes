<?php
/**
 * Button Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Button Field Class
 */
class ButtonField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'        => 'button',
				'button_type' => 'button', // button, submit, reset
				'onclick'     => '',
				'variant'     => 'primary', // primary, secondary, danger
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
		$label       = ! empty( $this->config['label'] ) ? $this->config['label'] : $this->config['title'];
		$button_type = $this->config['button_type'];
		$onclick     = $this->config['onclick'];
		$variant     = $this->config['variant'];

		$button_class = 'spider-boxes-button';
		switch ( $variant ) {
			case 'secondary':
				$button_class .= ' spider-boxes-button-secondary';
				break;
			case 'danger':
				$button_class .= ' spider-boxes-button-danger';
				break;
			default:
				$button_class .= ' spider-boxes-button-primary';
				break;
		}

		$attrs = array(
			'type'  => $button_type,
			'class' => $button_class,
			'name'  => $this->id,
		);

		if ( ! empty( $onclick ) ) {
			$attrs['onclick'] = $onclick;
		}

		$content = '<button' . $this->get_attributes( $attrs ) . '>' . esc_html( $label ) . '</button>';

		return $this->wrap_field( $content );
	}

	/**
	 * Sanitize field value (buttons don't typically have values)
	 *
	 * @param mixed $value Raw value
	 * @return string
	 */
	public function sanitize( $value ) {
		return sanitize_text_field( $value );
	}
}
