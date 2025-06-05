<?php
/**
 * Base Field Class
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Base Field Class
 */
abstract class BaseField {

	/**
	 * Field configuration
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Field ID
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Constructor
	 *
	 * @param string $id Field ID
	 * @param array  $config Field configuration
	 */
	public function __construct( $id, $config ) {
		$this->id     = $id;
		$this->config = wp_parse_args( $config, $this->get_defaults() );
	}

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array(
			'type'        => '',
			'title'       => '',
			'description' => '',
			'value'       => '',
			'class'       => '',
			'label'       => '',
			'show_tip'    => true,
			'context'     => 'default',
			'capability'  => 'manage_options',
		);
	}

	/**
	 * Render field
	 *
	 * @param mixed $value Current value
	 * @return string
	 */
	abstract public function render( $value = null );

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return mixed
	 */
	public function sanitize( $value ) {
		return $value;
	}

	/**
	 * Validate field value
	 *
	 * @param mixed $value Value to validate
	 * @return bool|\WP_Error
	 */
	public function validate( $value ) {
		return true;
	}

	/**
	 * Get field configuration
	 *
	 * @return array
	 */
	public function get_config() {
		return $this->config;
	}

	/**
	 * Get field ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get field type
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->config['type'];
	}

	/**
	 * Check if field supports a feature
	 *
	 * @param string $feature Feature name
	 * @return bool
	 */
	public function supports( $feature ) {
		$field_registry = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\FieldRegistry' );
		$field_type     = $field_registry->get_field_type( $this->get_type() );

		return isset( $field_type['supports'] ) && in_array( $feature, $field_type['supports'] );
	}

	/**
	 * Render field wrapper
	 *
	 * @param string $content Field content
	 * @return string
	 */
	protected function wrap_field( $content ) {
		$wrapper_class = 'spider-boxes-field spider-boxes-field-' . $this->get_type();
		if ( ! empty( $this->config['class'] ) ) {
			$wrapper_class .= ' ' . $this->config['class'];
		}

		$output = '<div class="' . esc_attr( $wrapper_class ) . '" data-field-id="' . esc_attr( $this->id ) . '">';

		if ( ! empty( $this->config['title'] ) ) {
			$output .= '<label class="spider-boxes-field-label">' . esc_html( $this->config['title'] ) . '</label>';
		}

		$output .= '<div class="spider-boxes-field-content">' . $content . '</div>';

		if ( ! empty( $this->config['description'] ) && $this->config['show_tip'] ) {
			$output .= '<p class="spider-boxes-field-description">' . esc_html( $this->config['description'] ) . '</p>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get field attributes as string
	 *
	 * @param array $additional_attrs Additional attributes
	 * @return string
	 */
	protected function get_attributes( $additional_attrs = array() ) {
		$attrs = array_merge(
			array(
				'id'              => $this->id,
				'name'            => $this->id,
				'data-field-type' => $this->get_type(),
			),
			$additional_attrs
		);

		$attr_string = '';
		foreach ( $attrs as $key => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$attr_string .= ' ' . esc_attr( $key );
				}
			} else {
				$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return $attr_string;
	}
}
