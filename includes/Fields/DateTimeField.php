<?php
/**
 * DateTime Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * DateTime Field Class
 */
class DateTimeField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'   => 'datetime-local',
				'format' => 'Y-m-d\TH:i',
				'min'    => '',
				'max'    => '',
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
		$value  = $value !== null ? $value : $this->config['value'];
		$format = $this->config['format'];
		$min    = $this->config['min'];
		$max    = $this->config['max'];

		// Format value for HTML datetime-local input
		if ( ! empty( $value ) ) {
			$timestamp = is_numeric( $value ) ? $value : strtotime( $value );
			if ( $timestamp ) {
				$value = date( 'Y-m-d\TH:i', $timestamp );
			}
		}

		$attrs = array(
			'type'  => 'datetime-local',
			'value' => $value,
			'class' => 'spider-boxes-datetime-field',
			'name'  => $this->id,
		);

		if ( ! empty( $min ) ) {
			$min_timestamp = is_numeric( $min ) ? $min : strtotime( $min );
			if ( $min_timestamp ) {
				$attrs['min'] = date( 'Y-m-d\TH:i', $min_timestamp );
			}
		}

		if ( ! empty( $max ) ) {
			$max_timestamp = is_numeric( $max ) ? $max : strtotime( $max );
			if ( $max_timestamp ) {
				$attrs['max'] = date( 'Y-m-d\TH:i', $max_timestamp );
			}
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
		if ( empty( $value ) ) {
			return '';
		}

		$timestamp = is_numeric( $value ) ? $value : strtotime( $value );
		if ( ! $timestamp ) {
			return '';
		}

		$format = $this->config['format'];
		return date( $format, $timestamp );
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

		$timestamp = is_numeric( $value ) ? $value : strtotime( $value );
		if ( ! $timestamp ) {
			return new \WP_Error( 'invalid_datetime', __( 'Invalid date/time format', 'spider-boxes' ) );
		}

		$min = $this->config['min'];
		$max = $this->config['max'];

		if ( ! empty( $min ) ) {
			$min_timestamp = is_numeric( $min ) ? $min : strtotime( $min );
			if ( $min_timestamp && $timestamp < $min_timestamp ) {
				return new \WP_Error( 'datetime_too_early', __( 'Date/time is too early', 'spider-boxes' ) );
			}
		}

		if ( ! empty( $max ) ) {
			$max_timestamp = is_numeric( $max ) ? $max : strtotime( $max );
			if ( $max_timestamp && $timestamp > $max_timestamp ) {
				return new \WP_Error( 'datetime_too_late', __( 'Date/time is too late', 'spider-boxes' ) );
			}
		}

		return true;
	}
}
