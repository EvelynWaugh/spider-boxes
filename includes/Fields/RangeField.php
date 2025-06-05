<?php
/**
 * Range Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Range Field Class
 */
class RangeField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'       => 'range',
				'min'        => 0,
				'max'        => 100,
				'step'       => 1,
				'show_value' => true,
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
		$value      = $value !== null ? $value : $this->config['value'];
		$min        = $this->config['min'];
		$max        = $this->config['max'];
		$step       = $this->config['step'];
		$show_value = $this->config['show_value'];

		// Ensure value is within range
		$value = max( $min, min( $max, floatval( $value ) ) );

		$content = '<div class="spider-boxes-range-container">';

		$attrs = array(
			'type'  => 'range',
			'value' => $value,
			'min'   => $min,
			'max'   => $max,
			'step'  => $step,
			'class' => 'spider-boxes-range-field',
			'name'  => $this->id,
		);

		$content .= '<input' . $this->get_attributes( $attrs ) . ' />';

		// Show current value
		if ( $show_value ) {
			$content .= '<div class="spider-boxes-range-value">';
			$content .= '<span class="spider-boxes-range-current">' . esc_html( $value ) . '</span>';
			$content .= '<span class="spider-boxes-range-bounds">(' . esc_html( $min ) . ' - ' . esc_html( $max ) . ')</span>';
			$content .= '</div>';
		}

		$content .= '</div>';

		// Add range slider script
		$this->enqueue_range_script();

		return $this->wrap_field( $content );
	}

	/**
	 * Enqueue range slider script
	 */
	private function enqueue_range_script() {
		add_action(
			'admin_footer',
			function () {
				?>
			<script>
			(function($) {
				$('.spider-boxes-range-field').on('input', function() {
					const $this = $(this);
					const value = $this.val();
					const container = $this.closest('.spider-boxes-range-container');
					container.find('.spider-boxes-range-current').text(value);
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
	 * @return float
	 */
	public function sanitize( $value ) {
		$min  = $this->config['min'];
		$max  = $this->config['max'];
		$step = $this->config['step'];

		$value = floatval( $value );
		$value = max( $min, min( $max, $value ) );

		// Round to step
		if ( $step > 0 ) {
			$value = round( $value / $step ) * $step;
		}

		return $value;
	}

	/**
	 * Validate field value
	 *
	 * @param mixed $value Value to validate
	 * @return bool|\WP_Error
	 */
	public function validate( $value ) {
		if ( empty( $value ) && $value !== '0' && $value !== 0 ) {
			return true; // Allow empty values
		}

		$min   = $this->config['min'];
		$max   = $this->config['max'];
		$value = floatval( $value );

		if ( $value < $min || $value > $max ) {
			return new \WP_Error(
				'out_of_range',
				sprintf(
					__( 'Value must be between %1$s and %2$s', 'spider-boxes' ),
					$min,
					$max
				)
			);
		}

		return true;
	}
}
