<?php
/**
 * Model Interface
 *
 * @package SpiderBoxes\Database\Contracts
 */

namespace SpiderBoxes\Database\Contracts;

/**
 * Model Interface
 */
interface ModelInterface {

	/**
	 * Create new model instance
	 *
	 * @param array $attributes Model attributes.
	 * @return static
	 */
	public static function create( array $attributes = array() );

	/**
	 * Create model from database row
	 *
	 * @param array $row Database row.
	 * @return static
	 */
	public static function from_database( array $row );

	/**
	 * Fill model with attributes
	 *
	 * @param array $attributes Attributes to fill.
	 * @return $this
	 */
	public function fill( array $attributes );

	/**
	 * Set attribute value
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $value Attribute value.
	 * @return $this
	 */
	public function set_attribute( $key, $value );

	/**
	 * Get attribute value
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_attribute( $key, $default = null );

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array();

	/**
	 * Convert to JSON
	 *
	 * @return string
	 */
	public function to_json();

	/**
	 * Prepare for database storage
	 *
	 * @return array
	 */
	public function to_database();

	/**
	 * Check if model has been modified
	 *
	 * @return bool
	 */
	public function is_dirty();

	/**
	 * Validate model data
	 *
	 * @return bool
	 */
	public function is_valid();
}
