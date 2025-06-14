<?php
/**
 * Field Model
 *
 * @package SpiderBoxes\Database\Models
 */

namespace SpiderBoxes\Database\Models;

use SpiderBoxes\Database\Traits\HasTimestamps;
use SpiderBoxes\Database\Traits\Serializable;
use SpiderBoxes\Database\Traits\Validatable;
use SpiderBoxes\Database\Contracts\ModelInterface;
use Illuminate\Support\Collection;

/**
 * Field Model Class
 */
class Field implements ModelInterface {

	use HasTimestamps;
	use Serializable;
	use Validatable;

	/**
	 * Model attributes
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Original attributes (for dirty checking)
	 *
	 * @var array
	 */
	protected $original = array();

	/**
	 * Fillable attributes (matching your FieldRegistry structure)
	 *
	 * @var array
	 */
	protected $fillable = array(
		'type',
		'parent',
		'title',
		'description',
		'show_tip',
		'value',
		'class',
		'label',
		'context',
		'placeholder',
		'required',
		'multiple',
		'options',
		'settings',
		'supports',
		'fields',
		'min',
		'max',
		'step',
		'rows',
		'mime_types',
		'onclick',
		'format',
		'async',
		'ajax_action',
		'is_active',
	);

	/**
	 * Serializable attributes
	 *
	 * @var array
	 */
	protected $serializable = array(
		'value',
		'options',
		'settings',
		'supports',
		'fields',
		'mime_types',
	);

	/**
	 * Boolean attributes
	 *
	 * @var array
	 */
	protected $boolean = array(
		'show_tip',
		'required',
		'multiple',
		'async',
		'is_active',
	);

	/**
	 * Numeric attributes
	 *
	 * @var array
	 */
	protected $numeric = array(
		'min',
		'max',
		'step',
		'rows',
	);

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	protected $validation_rules = array(
		'type'  => 'required|string|field_type_exists',
		'title' => 'required|string|max:255',
	);

	/**
	 * Default values (matching your FieldRegistry defaults)
	 *
	 * @var array
	 */
	protected $defaults = array(
		'type'        => 'text',
		'parent'      => '',
		'title'       => '',
		'description' => '',
		'show_tip'    => true,
		'value'       => '',
		'class'       => '',
		'label'       => '',
		'context'     => 'default',
		'placeholder' => '',
		'required'    => false,
		'multiple'    => false,
		'options'     => array(),
		'settings'    => array(),
		'supports'    => array(),
		'fields'      => array(),
		'min'         => null,
		'max'         => null,
		'step'        => null,
		'rows'        => null,
		'mime_types'  => array(),
		'onclick'     => '',
		'format'      => '',
		'async'       => false,
		'ajax_action' => '',
		'is_active'   => true,
	);

	/**
	 * Constructor
	 *
	 * @param array $attributes Initial attributes.
	 */
	public function __construct( array $attributes = array() ) {
		$this->fill( $attributes );
		$this->sync_original();
	}

	/**
	 * Create new field instance
	 *
	 * @param array $attributes Field attributes.
	 * @return static
	 */
	public static function create( array $attributes = array() ) {
		$field = new static( $attributes );
		$field->apply_defaults();
		return $field;
	}

	/**
	 * Create field from database row
	 *
	 * @param array $row Database row.
	 * @return static
	 */
	public static function from_database( array $row ) {
		$field = new static();
		$field->fill( $row );
		$field->unserialize_attributes();
		$field->sync_original();
		return $field;
	}

	/**
	 * Create field from registry configuration
	 *
	 * @param string $field_id Field ID from registry.
	 * @param array  $config Field configuration from registry.
	 * @return static
	 */
	public static function from_registry( $field_id, array $config ) {
		$config['id'] = $field_id;
		$field        = new static( $config );
		$field->apply_defaults();
		$field->sync_original();
		return $field;
	}

	/**
	 * Fill model with attributes
	 *
	 * @param array $attributes Attributes to fill.
	 * @return $this
	 */
	public function fill( array $attributes ) {
		foreach ( $attributes as $key => $value ) {
			if ( $this->is_fillable( $key ) ) {
				$this->set_attribute( $key, $value );
			}
		}

		return $this;
	}

	/**
	 * Set attribute value
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $value Attribute value.
	 * @return $this
	 */
	public function set_attribute( $key, $value ) {
		// Cast boolean attributes
		if ( in_array( $key, $this->boolean, true ) ) {
			$value = (bool) $value;
		}

		// Cast numeric attributes
		if ( in_array( $key, $this->numeric, true ) && $value !== null ) {
			$value = is_numeric( $value ) ? (float) $value : null;
		}

		// Ensure arrays for array attributes
		if ( in_array( $key, $this->serializable, true ) && ! is_array( $value ) ) {
			$value = array();
		}

		$this->attributes[ $key ] = apply_filters( "spider_boxes_field_set_{$key}", $value, $this );

		return $this;
	}

	/**
	 * Get attribute value
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_attribute( $key, $default = null ) {
		$value = $this->attributes[ $key ] ?? $default;

		return apply_filters( "spider_boxes_field_get_{$key}", $value, $this );
	}

	/**
	 * Check if attribute is fillable
	 *
	 * @param string $key Attribute key.
	 * @return bool
	 */
	public function is_fillable( $key ) {
		return in_array( $key, $this->fillable, true );
	}

	/**
	 * Apply default values
	 *
	 * @return $this
	 */
	public function apply_defaults() {
		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->attributes[ $key ] ) ) {
				$this->set_attribute( $key, $value );
			}
		}

		return $this;
	}

	/**
	 * Get field configuration array (compatible with your FieldRegistry)
	 *
	 * @return array
	 */
	public function to_config() {
		$config = $this->to_array();

		// Add computed properties
		$config['field_type_config']  = $this->get_field_type_config();
		$config['validation_errors']  = $this->get_validation_errors();
		$config['is_valid']           = $this->is_valid();
		$config['supported_features'] = $this->get_supported_features();

		return apply_filters( 'spider_boxes_field_to_config', $config, $this );
	}

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array() {
		$array = $this->attributes;

		// Ensure serializable fields are arrays
		foreach ( $this->serializable as $field ) {
			if ( isset( $array[ $field ] ) && ! is_array( $array[ $field ] ) ) {
				$array[ $field ] = array();
			}
		}

		return apply_filters( 'spider_boxes_field_to_array', $array, $this );
	}

	/**
	 * Convert to JSON
	 *
	 * @return string
	 */
	public function to_json() {
		return wp_json_encode( $this->to_array() );
	}

	/**
	 * Prepare for database storage
	 *
	 * @return array
	 */
	public function to_database() {
		$data = $this->to_array();

		// Serialize array fields
		foreach ( $this->serializable as $field ) {
			if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
				$data[ $field ] = maybe_serialize( $data[ $field ] );
			}
		}

		// Add timestamps
		$data = $this->add_timestamps( $data );

		return apply_filters( 'spider_boxes_field_to_database', $data, $this );
	}

	/**
	 * Get field type configuration from FieldRegistry
	 *
	 * @return array|null
	 */
	public function get_field_type_config() {
		if ( empty( $this->get_attribute( 'type' ) ) ) {
			return null;
		}

		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$type           = $this->get_attribute( 'type' );

		return $field_registry->get_field_type( $type );
	}

	/**
	 * Get field instance for rendering (using your FieldRegistry classes)
	 *
	 * @return object|null
	 */
	public function get_field_instance() {
		$field_type_config = $this->get_field_type_config();

		if ( ! $field_type_config || empty( $field_type_config['class_name'] ) ) {
			return null;
		}

		$class_name = $field_type_config['class_name'];

		if ( ! class_exists( $class_name ) ) {
			return null;
		}

		try {
			$instance = new $class_name( $this->to_config() );
			return apply_filters( 'spider_boxes_field_instance', $instance, $this );
		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to create field instance: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Render field HTML
	 *
	 * @param mixed $value Field value.
	 * @param array $context Render context.
	 * @return string
	 */
	public function render( $value = null, $context = array() ) {
		$field_instance = $this->get_field_instance();

		if ( ! $field_instance ) {
			return sprintf(
				'<div class="spider-boxes-field-error">%s</div>',
				esc_html__( 'Field type not found.', 'spider-boxes' )
			);
		}

		// Use provided value or field's default value
		if ( $value === null ) {
			$value = $this->get_attribute( 'value' );
		}

		$render_context = array_merge(
			array(
				'value'   => $value,
				'context' => $this->get_attribute( 'context', 'default' ),
			),
			$context
		);

		$html = $field_instance->render( $render_context );

		return apply_filters( 'spider_boxes_field_render', $html, $value, $context, $this );
	}

	/**
	 * Get field options for select/radio fields
	 *
	 * @return array
	 */
	public function get_options() {
		$options = $this->get_attribute( 'options', array() );

		// If options is a callable string (function name)
		if ( is_string( $options ) && function_exists( $options ) ) {
			$options = call_user_func( $options, $this );
		}

		// If options has a callback
		if ( is_array( $options ) && isset( $options['callback'] ) ) {
			$callback = $options['callback'];
			if ( is_callable( $callback ) ) {
				$options = call_user_func( $callback, $this );
			}
		}

		return apply_filters( 'spider_boxes_field_options', $options, $this );
	}

	/**
	 * Get field value with validation based on field type
	 *
	 * @param mixed $value Raw value.
	 * @return mixed Validated value.
	 */
	public function get_validated_value( $value ) {
		$field_type = $this->get_attribute( 'type' );

		switch ( $field_type ) {
			case 'text':
				return sanitize_text_field( $value );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'email':
				return is_email( $value ) ? $value : '';

			case 'url':
				return esc_url_raw( $value );

			case 'number':
			case 'range':
				$numeric_value = is_numeric( $value ) ? (float) $value : 0;

				// Check min/max constraints
				$min = $this->get_attribute( 'min' );
				$max = $this->get_attribute( 'max' );

				if ( $min !== null && $numeric_value < $min ) {
					$numeric_value = $min;
				}

				if ( $max !== null && $numeric_value > $max ) {
					$numeric_value = $max;
				}

				return $numeric_value;

			case 'checkbox':
			case 'switcher':
				return (bool) $value;

			case 'select':
			case 'radio':
			case 'react-select':
				$options = $this->get_options();
				if ( is_array( $options ) && ! empty( $options ) ) {
					if ( $this->get_attribute( 'multiple' ) && is_array( $value ) ) {
						// For multiple select, validate each value
						$allowed_values = array_keys( $options );
						return array_intersect( $value, $allowed_values );
					} else {
						// For single select
						$allowed_values = array_keys( $options );
						return in_array( $value, $allowed_values, true ) ? $value : '';
					}
				}
				return sanitize_text_field( $value );

			case 'media':
				$mime_types = $this->get_attribute( 'mime_types', array() );
				if ( is_array( $value ) ) {
					$validated_ids = array();
					foreach ( $value as $attachment_id ) {
						$id = absint( $attachment_id );
						if ( $id && $this->validate_media_type( $id, $mime_types ) ) {
							$validated_ids[] = $id;
						}
					}
					return $validated_ids;
				} else {
					$id = absint( $value );
					return ( $id && $this->validate_media_type( $id, $mime_types ) ) ? $id : 0;
				}

			case 'datetime':
				// Validate datetime format
				$format   = $this->get_attribute( 'format', 'Y-m-d H:i:s' );
				$datetime = \DateTime::createFromFormat( $format, $value );
				return $datetime ? $datetime->format( $format ) : '';

			case 'wysiwyg':
				return wp_kses_post( $value );

			case 'repeater':
				if ( is_array( $value ) ) {
					$min = $this->get_attribute( 'min' );
					$max = $this->get_attribute( 'max' );

					// Validate min/max constraints
					if ( $min !== null && count( $value ) < $min ) {
						// Pad with empty arrays if below minimum
						while ( count( $value ) < $min ) {
							$value[] = array();
						}
					}

					if ( $max !== null && count( $value ) > $max ) {
						// Trim to maximum
						$value = array_slice( $value, 0, $max );
					}

					return array_map(
						function ( $item ) {
							return is_array( $item ) ? $item : array();
						},
						$value
					);
				}
				return array();

			default:
				return apply_filters( "spider_boxes_validate_field_value_{$field_type}", sanitize_text_field( $value ), $this );
		}
	}

	/**
	 * Check if field supports a feature (using FieldRegistry supports)
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	public function supports( $feature ) {
		// Check field-specific supports first
		$field_supports = $this->get_attribute( 'supports', array() );
		if ( in_array( $feature, $field_supports, true ) ) {
			return true;
		}

		// Check field type supports from registry
		$field_type_config = $this->get_field_type_config();
		if ( $field_type_config && isset( $field_type_config['supports'] ) ) {
			return in_array( $feature, $field_type_config['supports'], true );
		}

		return apply_filters( 'spider_boxes_field_supports', false, $feature, $this );
	}

	/**
	 * Get all supported features
	 *
	 * @return array
	 */
	public function get_supported_features() {
		$field_supports    = $this->get_attribute( 'supports', array() );
		$field_type_config = $this->get_field_type_config();

		$type_supports = array();
		if ( $field_type_config && isset( $field_type_config['supports'] ) ) {
			$type_supports = $field_type_config['supports'];
		}

		$all_supports = array_unique( array_merge( $field_supports, $type_supports ) );

		return apply_filters( 'spider_boxes_field_supported_features', $all_supports, $this );
	}

	/**
	 * Get child fields (for repeater fields using your FieldRegistry structure)
	 *
	 * @return Collection
	 */
	public function get_child_fields() {
		$fields_data = $this->get_attribute( 'fields', array() );

		if ( empty( $fields_data ) ) {
			return collect();
		}

		$child_fields = collect();

		foreach ( $fields_data as $field_id => $field_data ) {
			if ( is_array( $field_data ) ) {
				// If numeric key, treat as direct field config
				if ( is_numeric( $field_id ) ) {
					$child_field = static::create( $field_data );
				} else {
					// If string key, use as field ID
					$field_data['id'] = $field_id;
					$child_field      = static::create( $field_data );
				}
				$child_fields->push( $child_field );
			}
		}

		return apply_filters( 'spider_boxes_field_child_fields', $child_fields, $this );
	}

	/**
	 * Register this field with FieldRegistry
	 *
	 * @param string $field_id Field ID.
	 * @return bool
	 */
	public function register_with_registry( $field_id ) {
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );

		$config = $this->to_array();
		unset( $config['id'] ); // Remove ID from config as it's passed separately

		return $field_registry->register_field( $field_id, $config );
	}

	/**
	 * Validate media type against allowed mime types
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $allowed_types Allowed mime types.
	 * @return bool
	 */
	private function validate_media_type( $attachment_id, $allowed_types ) {
		if ( empty( $allowed_types ) ) {
			return true; // No restrictions
		}

		$mime_type = get_post_mime_type( $attachment_id );
		return in_array( $mime_type, $allowed_types, true );
	}

	/**
	 * Clone field with new attributes
	 *
	 * @param array $overrides Attribute overrides.
	 * @return static
	 */
	public function clone( array $overrides = array() ) {
		$attributes = array_merge( $this->to_array(), $overrides );
		return static::create( $attributes );
	}

	/**
	 * Check if field has been modified
	 *
	 * @return bool
	 */
	public function is_dirty() {
		return $this->attributes !== $this->original;
	}

	/**
	 * Get changed attributes
	 *
	 * @return array
	 */
	public function get_dirty() {
		$dirty = array();

		foreach ( $this->attributes as $key => $value ) {
			if ( ! isset( $this->original[ $key ] ) || $this->original[ $key ] !== $value ) {
				$dirty[ $key ] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Sync original attributes
	 *
	 * @return $this
	 */
	public function sync_original() {
		$this->original = $this->attributes;
		return $this;
	}

	/**
	 * Validate field configuration using FieldRegistry
	 *
	 * @return bool
	 */
	public function is_valid() {
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );

		// Check if field type exists
		if ( ! $field_registry->field_type_exists( $this->get_attribute( 'type' ) ) ) {
			return false;
		}

		// Check required attributes
		if ( empty( $this->get_attribute( 'type' ) ) || empty( $this->get_attribute( 'title' ) ) ) {
			return false;
		}

		return apply_filters( 'spider_boxes_field_is_valid', true, $this );
	}

	/**
	 * Custom validation rules based on field type
	 *
	 * @return array
	 */
	protected function get_custom_validation_rules() {
		$rules = array();
		$type  = $this->get_attribute( 'type' );

		// Add min/max validation for numeric fields
		if ( in_array( $type, array( 'number', 'range' ), true ) ) {
			$min = $this->get_attribute( 'min' );
			$max = $this->get_attribute( 'max' );

			if ( $min !== null ) {
				$rules['value'][] = "min:{$min}";
			}

			if ( $max !== null ) {
				$rules['value'][] = "max:{$max}";
			}
		}

		// Add options validation for select fields
		if ( in_array( $type, array( 'select', 'radio', 'react-select' ), true ) ) {
			$options = $this->get_options();
			if ( ! empty( $options ) ) {
				$allowed_values   = implode( ',', array_keys( $options ) );
				$rules['value'][] = "in:{$allowed_values}";
			}
		}

		// Add mime type validation for media fields
		if ( $type === 'media' ) {
			$mime_types = $this->get_attribute( 'mime_types' );
			if ( ! empty( $mime_types ) ) {
				$rules['value'][] = 'mime_types:' . implode( ',', $mime_types );
			}
		}

		return apply_filters( 'spider_boxes_field_validation_rules', $rules, $this );
	}

	/**
	 * Magic getter
	 *
	 * @param string $key Attribute key.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get_attribute( $key );
	}

	/**
	 * Magic setter
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $value Attribute value.
	 */
	public function __set( $key, $value ) {
		$this->set_attribute( $key, $value );
	}

	/**
	 * Magic isset
	 *
	 * @param string $key Attribute key.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->attributes[ $key ] );
	}

	/**
	 * Convert to string (JSON representation)
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to_json();
	}
}
