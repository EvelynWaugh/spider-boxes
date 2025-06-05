<?php
/**
 * Switcher Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Switcher Field Class
 */
class SwitcherField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'      => 'switcher',
				'on_value'  => '1',
				'off_value' => '0',
				'on_label'  => __( 'On', 'spider-boxes' ),
				'off_label' => __( 'Off', 'spider-boxes' ),
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
		$value     = $value !== null ? $value : $this->config['value'];
		$on_value  = $this->config['on_value'];
		$off_value = $this->config['off_value'];
		$on_label  = $this->config['on_label'];
		$off_label = $this->config['off_label'];

		$is_on = $value === $on_value;

		$content = '<div class="spider-boxes-switcher-container">';

		// Hidden input to store actual value
		$content .= '<input type="hidden" name="' . esc_attr( $this->id ) . '" value="' . esc_attr( $is_on ? $on_value : $off_value ) . '" />';

		// Switcher button
		$switcher_class = 'spider-boxes-switcher' . ( $is_on ? ' spider-boxes-switcher-on' : ' spider-boxes-switcher-off' );
		$content       .= '<button type="button" class="' . $switcher_class . '" data-field-id="' . esc_attr( $this->id ) . '" data-on-value="' . esc_attr( $on_value ) . '" data-off-value="' . esc_attr( $off_value ) . '">';
		$content       .= '<span class="spider-boxes-switcher-thumb"></span>';
		$content       .= '</button>';

		// Labels
		$content .= '<div class="spider-boxes-switcher-labels">';
		$content .= '<span class="spider-boxes-switcher-label-off' . ( ! $is_on ? ' active' : '' ) . '">' . esc_html( $off_label ) . '</span>';
		$content .= '<span class="spider-boxes-switcher-label-on' . ( $is_on ? ' active' : '' ) . '">' . esc_html( $on_label ) . '</span>';
		$content .= '</div>';

		$content .= '</div>';

		// Add switcher script
		$this->enqueue_switcher_script();

		return $this->wrap_field( $content );
	}

	/**
	 * Enqueue switcher script
	 */
	private function enqueue_switcher_script() {
		add_action(
			'admin_footer',
			function () {
				?>
			<script>
			(function($) {
				$(document).on('click', '.spider-boxes-switcher', function(e) {
					e.preventDefault();
					
					const $switcher = $(this);
					const fieldId = $switcher.data('field-id');
					const onValue = $switcher.data('on-value');
					const offValue = $switcher.data('off-value');
					const isOn = $switcher.hasClass('spider-boxes-switcher-on');
					const newValue = isOn ? offValue : onValue;
					
					// Toggle state
					$switcher.toggleClass('spider-boxes-switcher-on spider-boxes-switcher-off');
					
					// Update hidden input
					$switcher.siblings('input[type="hidden"]').val(newValue);
					
					// Update labels
					const container = $switcher.closest('.spider-boxes-switcher-container');
					container.find('.spider-boxes-switcher-label-on, .spider-boxes-switcher-label-off').removeClass('active');
					container.find(isOn ? '.spider-boxes-switcher-label-off' : '.spider-boxes-switcher-label-on').addClass('active');
				});
			})(jQuery);
			</script>
				<?php
			}
		);
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return string
	 */
	public function sanitize( $value ) {
		$on_value  = $this->config['on_value'];
		$off_value = $this->config['off_value'];

		return $value === $on_value ? $on_value : $off_value;
	}

	/**
	 * Validate field value
	 *
	 * @param mixed $value Value to validate
	 * @return bool|\WP_Error
	 */
	public function validate( $value ) {
		$on_value  = $this->config['on_value'];
		$off_value = $this->config['off_value'];

		if ( $value !== $on_value && $value !== $off_value ) {
			return new \WP_Error( 'invalid_switcher_value', __( 'Invalid switcher value', 'spider-boxes' ) );
		}

		return true;
	}
}
