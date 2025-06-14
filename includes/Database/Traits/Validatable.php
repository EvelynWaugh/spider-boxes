<?php
/**
 * Validatable Trait
 *
 * @package SpiderBoxes\Database\Traits
 */

namespace SpiderBoxes\Database\Traits;

/**
 * Validatable Trait
 */
trait Validatable {

	/**
	 * Validation errors
	 *
	 * @var array
	 */
	protected $validation_errors = array();

	/**
	 * Validate model data
	 *
	 * @param array $data Optional data to validate (defaults to current attributes).
	 * @return bool
	 */
	public function is_valid( array $data = null ) {
		if ( $data === null ) {
			$data = $this->attributes;
		}

		$this->validation_errors = array();

		$rules        = $this->get_validation_rules();
		$custom_rules = $this->get_custom_validation_rules();

		// Merge custom rules
		if ( ! empty( $custom_rules ) ) {
			$rules = array_merge( $rules, $custom_rules );
		}

		foreach ( $rules as $field => $field_rules ) {
			$value = $data[ $field ] ?? null;
			$this->validate_field( $field, $value, $field_rules );
		}

		$is_valid = empty( $this->validation_errors );

		return apply_filters( 'spider_boxes_model_is_valid', $is_valid, $data, $this );
	}

	/**
	 * Validate a single field
	 *
	 * @param string       $field Field name.
	 * @param mixed        $value Field value.
	 * @param string|array $rules Validation rules.
	 * @return bool
	 */
	protected function validate_field( $field, $value, $rules ) {
		if ( is_string( $rules ) ) {
			$rules = explode( '|', $rules );
		}

		if ( ! is_array( $rules ) ) {
			return true;
		}

		foreach ( $rules as $rule ) {
			if ( ! $this->validate_rule( $field, $value, $rule ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate a single rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @param string $rule Rule to validate.
	 * @return bool
	 */
	protected function validate_rule( $field, $value, $rule ) {
		// Parse rule parameters
		$parameters = array();
		if ( strpos( $rule, ':' ) !== false ) {
			list( $rule, $parameter_string ) = explode( ':', $rule, 2 );
			$parameters                      = explode( ',', $parameter_string );
		}

		$method_name = 'validate_' . $rule;

		// Check if custom validation method exists
		if ( method_exists( $this, $method_name ) ) {
			return $this->$method_name( $field, $value, $parameters );
		}

		// Built-in validation rules
		switch ( $rule ) {
			case 'required':
				return $this->validate_required( $field, $value );

			case 'string':
				return $this->validate_string( $field, $value );

			case 'numeric':
				return $this->validate_numeric( $field, $value );

			case 'email':
				return $this->validate_email( $field, $value );

			case 'url':
				return $this->validate_url( $field, $value );

			case 'min':
				return $this->validate_min( $field, $value, $parameters );

			case 'max':
				return $this->validate_max( $field, $value, $parameters );

			case 'in':
				return $this->validate_in( $field, $value, $parameters );

			case 'not_in':
				return $this->validate_not_in( $field, $value, $parameters );

			case 'array':
				return $this->validate_array( $field, $value );

			case 'boolean':
				return $this->validate_boolean( $field, $value );

			case 'field_type_exists':
				return $this->validate_field_type_exists( $field, $value );

			default:
				return apply_filters( "spider_boxes_validate_rule_{$rule}", true, $field, $value, $parameters, $this );
		}
	}

	/**
	 * Add validation error
	 *
	 * @param string $field Field name.
	 * @param string $message Error message.
	 * @return void
	 */
	protected function add_validation_error( $field, $message ) {
		if ( ! isset( $this->validation_errors[ $field ] ) ) {
			$this->validation_errors[ $field ] = array();
		}

		$this->validation_errors[ $field ][] = $message;
	}

	/**
	 * Get validation errors
	 *
	 * @return array
	 */
	public function get_validation_errors() {
		return $this->validation_errors;
	}

	/**
	 * Get validation rules
	 *
	 * @return array
	 */
	protected function get_validation_rules() {
		if ( property_exists( $this, 'validation_rules' ) && is_array( $this->validation_rules ) ) {
			return $this->validation_rules;
		}

		return array();
	}

	/**
	 * Get custom validation rules (to be overridden by models)
	 *
	 * @return array
	 */
	protected function get_custom_validation_rules() {
		return array();
	}

	/**
	 * Validate required rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_required( $field, $value ) {
		$is_valid = ! empty( $value ) || $value === '0' || $value === 0;

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field is required.', 'spider-boxes' ), $field ) );
		}

		return $is_valid;
	}

	/**
	 * Validate string rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_string( $field, $value ) {
		$is_valid = is_string( $value );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field must be a string.', 'spider-boxes' ), $field ) );
		}

		return $is_valid;
	}

	/**
	 * Validate numeric rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_numeric( $field, $value ) {
		$is_valid = is_numeric( $value );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field must be numeric.', 'spider-boxes' ), $field ) );
		}

		return $is_valid;
	}

	/**
	 * Validate email rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_email( $field, $value ) {
		$is_valid = empty( $value ) || is_email( $value );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field must be a valid email address.', 'spider-boxes' ), $field ) );
		}

		return $is_valid;
	}

	/**
	 * Validate URL rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_url( $field, $value ) {
		$is_valid = empty( $value ) || filter_var( $value, FILTER_VALIDATE_URL );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field must be a valid URL.', 'spider-boxes' ), $field ) );
		}

		return $is_valid;
	}

	/**
	 * Validate min rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @param array  $parameters Rule parameters.
	 * @return bool
	 */
	protected function validate_min( $field, $value, $parameters ) {
		if ( empty( $parameters ) ) {
			return true;
		}

		$min = (float) $parameters[0];

		if ( is_string( $value ) ) {
			$is_valid = strlen( $value ) >= $min;
			$message  = sprintf( __( 'The %1$s field must be at least %2$d characters.', 'spider-boxes' ), $field, $min );
		} elseif ( is_numeric( $value ) ) {
			$is_valid = (float) $value >= $min;
			$message  = sprintf( __( 'The %1$s field must be at least %2$s.', 'spider-boxes' ), $field, $min );
		} elseif ( is_array( $value ) ) {
			$is_valid = count( $value ) >= $min;
			$message  = sprintf( __( 'The %1$s field must have at least %2$d items.', 'spider-boxes' ), $field, $min );
		} else {
			return true;
		}

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, $message );
		}

		return $is_valid;
	}

	/**
	 * Validate max rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @param array  $parameters Rule parameters.
	 * @return bool
	 */
	protected function validate_max( $field, $value, $parameters ) {
		if ( empty( $parameters ) ) {
			return true;
		}

		$max = (float) $parameters[0];

		if ( is_string( $value ) ) {
			$is_valid = strlen( $value ) <= $max;
			$message  = sprintf( __( 'The %1$s field must not exceed %2$d characters.', 'spider-boxes' ), $field, $max );
		} elseif ( is_numeric( $value ) ) {
			$is_valid = (float) $value <= $max;
			$message  = sprintf( __( 'The %1$s field must not exceed %2$s.', 'spider-boxes' ), $field, $max );
		} elseif ( is_array( $value ) ) {
			$is_valid = count( $value ) <= $max;
			$message  = sprintf( __( 'The %1$s field must not have more than %2$d items.', 'spider-boxes' ), $field, $max );
		} else {
			return true;
		}

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, $message );
		}

		return $is_valid;
	}

	/**
	 * Validate in rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @param array  $parameters Rule parameters.
	 * @return bool
	 */
	protected function validate_in( $field, $value, $parameters ) {
		$is_valid = in_array( $value, $parameters, true );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %1$s field must be one of: %2$s.', 'spider-boxes' ), $field, implode( ', ', $parameters ) ) );
		}

		return $is_valid;
	}

	/**
	 * Validate not_in rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @param array  $parameters Rule parameters.
	 * @return bool
	 */
	protected function validate_not_in( $field, $value, $parameters ) {
		$is_valid = ! in_array( $value, $parameters, true );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %1$s field must not be one of: %2$s.', 'spider-boxes' ), $field, implode( ', ', $parameters ) ) );
		}

		return $is_valid;
	}

	/**
	 * Validate array rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_array( $field, $value ) {
		$is_valid = is_array( $value );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field must be an array.', 'spider-boxes' ), $field ) );
		}

		return $is_valid;
	}

	/**
	 * Validate boolean rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_boolean( $field, $value ) {
		$is_valid = is_bool( $value ) || in_array( $value, array( 0, 1, '0', '1', 'true', 'false' ), true );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field must be a boolean value.', 'spider-boxes' ), $field ) );
		}

		return $is_valid;
	}

	/**
	 * Validate field type exists rule
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return bool
	 */
	protected function validate_field_type_exists( $field, $value ) {
		if ( empty( $value ) ) {
			return true; // Let required rule handle empty values
		}

		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$is_valid       = $field_registry->field_type_exists( $value );

		if ( ! $is_valid ) {
			$this->add_validation_error( $field, sprintf( __( 'The %s field type does not exist.', 'spider-boxes' ), $value ) );
		}

		return $is_valid;
	}

	/**
	 * Check if model passes validation
	 *
	 * @return bool
	 */
	public function passes_validation() {
		return $this->is_valid();
	}

	/**
	 * Check if model fails validation
	 *
	 * @return bool
	 */
	public function fails_validation() {
		return ! $this->is_valid();
	}

	/**
	 * Get first validation error for a field
	 *
	 * @param string $field Field name.
	 * @return string|null
	 */
	public function get_first_error( $field ) {
		if ( isset( $this->validation_errors[ $field ] ) && ! empty( $this->validation_errors[ $field ] ) ) {
			return $this->validation_errors[ $field ][0];
		}

		return null;
	}

	/**
	 * Get all validation errors as flat array
	 *
	 * @return array
	 */
	public function get_all_errors() {
		$all_errors = array();

		foreach ( $this->validation_errors as $field => $errors ) {
			$all_errors = array_merge( $all_errors, $errors );
		}

		return $all_errors;
	}

	/**
	 * Check if field has validation errors
	 *
	 * @param string $field Field name.
	 * @return bool
	 */
	public function has_error( $field ) {
		return isset( $this->validation_errors[ $field ] ) && ! empty( $this->validation_errors[ $field ] );
	}

	/**
	 * Clear validation errors
	 *
	 * @param string $field Optional field name to clear specific errors.
	 * @return $this
	 */
	public function clear_errors( $field = null ) {
		if ( $field ) {
			unset( $this->validation_errors[ $field ] );
		} else {
			$this->validation_errors = array();
		}

		return $this;
	}
}
