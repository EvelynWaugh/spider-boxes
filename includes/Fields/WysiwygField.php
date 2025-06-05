<?php
/**
 * WYSIWYG Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * WYSIWYG Field Class
 */
class WysiwygField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'     => 'wysiwyg',
				'settings' => array(
					'textarea_name' => '',
					'textarea_rows' => 10,
					'teeny'         => false,
					'media_buttons' => true,
					'tinymce'       => true,
					'quicktags'     => true,
				),
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
		$settings = $this->config['settings'];

		// Set textarea name if not provided
		if ( empty( $settings['textarea_name'] ) ) {
			$settings['textarea_name'] = $this->id;
		}

		// Generate unique editor ID
		$editor_id = str_replace( array( '[', ']', '-' ), '_', $this->id );
		$editor_id = preg_replace( '/[^a-zA-Z0-9_]/', '', $editor_id );

		ob_start();
		wp_editor( $value, $editor_id, $settings );
		$content = ob_get_clean();

		return $this->wrap_field( $content );
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return string
	 */
	public function sanitize( $value ) {
		return wp_kses_post( $value );
	}

	/**
	 * Validate field value
	 *
	 * @param mixed $value Value to validate
	 * @return bool|\WP_Error
	 */
	public function validate( $value ) {
		// Allow HTML content, wp_kses_post will handle sanitization
		return true;
	}
}
