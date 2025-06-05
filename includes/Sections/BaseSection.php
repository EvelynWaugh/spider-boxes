<?php
/**
 * Base Section
 *
 * @package SpiderBoxes
 */

namespace SpiderBoxes\Sections;

use SpiderBoxes\Core\FieldRegistry;
use SpiderBoxes\Core\ComponentRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Section Abstract Class
 */
abstract class BaseSection {

	/**
	 * Section type
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Section configuration
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 * Field registry instance
	 *
	 * @var FieldRegistry
	 */
	protected $field_registry;

	/**
	 * Component registry instance
	 *
	 * @var ComponentRegistry
	 */
	protected $component_registry;

	/**
	 * Constructor
	 *
	 * @param FieldRegistry     $field_registry     Field registry instance.
	 * @param ComponentRegistry $component_registry Component registry instance.
	 */
	public function __construct( FieldRegistry $field_registry, ComponentRegistry $component_registry ) {
		$this->field_registry     = $field_registry;
		$this->component_registry = $component_registry;
	}

	/**
	 * Get section type
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Set section configuration
	 *
	 * @param array $config Configuration array.
	 */
	public function set_config( array $config ) {
		$this->config = $config;
	}

	/**
	 * Get section configuration
	 *
	 * @return array
	 */
	public function get_config() {
		return $this->config;
	}

	/**
	 * Render the section
	 *
	 * @param array $value Current values.
	 * @return string
	 */
	abstract public function render( $value = array() );

	/**
	 * Sanitize section values
	 *
	 * @param mixed $value Value to sanitize.
	 * @return mixed
	 */
	abstract public function sanitize( $value );

	/**
	 * Validate section values
	 *
	 * @param mixed $value Value to validate.
	 * @return bool|WP_Error
	 */
	abstract public function validate( $value );

	/**
	 * Get section schema for REST API
	 *
	 * @return array
	 */
	public function get_schema() {
		return array(
			'type'        => $this->type,
			'description' => $this->config['description'] ?? '',
			'properties'  => $this->get_properties_schema(),
		);
	}

	/**
	 * Get properties schema
	 *
	 * @return array
	 */
	protected function get_properties_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => true,
		);
	}
}
