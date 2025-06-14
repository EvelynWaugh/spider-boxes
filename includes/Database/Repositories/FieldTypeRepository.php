<?php
/**
 * Field Type Repository
 *
 * @package SpiderBoxes\Database\Repositories
 */

namespace SpiderBoxes\Database\Repositories;

use SpiderBoxes\Database\Repositories\BaseRepository;

/**
 * Field Type Repository Class
 */
class FieldTypeRepository extends BaseRepository {

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table = 'spider_boxes_field_types';

	/**
	 * Serializable fields
	 *
	 * @var array
	 */
	protected $serializable = array( 'supports' );

	/**
	 * Fillable fields
	 *
	 * @var array
	 */
	protected $fillable = array(
		'type',
		'class_name',
		'icon',
		'description',
		'supports',
		'is_active',
	);

	/**
	 * Get field type by type identifier
	 *
	 * @param string $type Field type identifier.
	 * @return array|null
	 */
	public function find_by_type( $type ) {
		return $this->find_by( 'type', $type );
	}

	/**
	 * Get active field types
	 *
	 * @return array
	 */
	public function get_active() {
		return $this->all( array( 'is_active' => 1 ) );
	}

	/**
	 * Register or update field type
	 *
	 * @param array $field_type Field type data.
	 * @return bool
	 */
	public function register( $field_type ) {
		// Validate required fields
		if ( empty( $field_type['type'] ) ) {
			return false;
		}

		// Set defaults
		$field_type = wp_parse_args(
			$field_type,
			array(
				'class_name'  => '',
				'icon'        => 'component',
				'description' => '',
				'supports'    => array(),
				'is_active'   => 1,
			)
		);

		// Check if field type exists
		$existing = $this->find_by_type( $field_type['type'] );

		if ( $existing ) {
			// Update existing
			return $this->update( $existing['id'], $field_type );
		} else {
			// Create new
			return $this->create( $field_type );
		}
	}

	/**
	 * Get field types with registry integration
	 *
	 * @return array
	 */
	public function get_with_registry() {
		$db_types = $this->get_active();

		// Get registry types
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		$registry_types = $field_registry->get_field_types();

		// Combine and prioritize database types
		$combined_types = array();

		// First add registry types as base
		foreach ( $registry_types as $type => $config ) {
			$combined_types[] = array_merge( $config, array( 'type' => $type ) );
		}

		// Override/add database types
		foreach ( $db_types as $db_type ) {
			$found = false;
			foreach ( $combined_types as &$combined_type ) {
				if ( $combined_type['type'] === $db_type['type'] ) {
					$combined_type = array_merge( $combined_type, $db_type );
					$found         = true;
					break;
				}
			}

			if ( ! $found ) {
				$combined_types[] = $db_type;
			}
		}

		return apply_filters( 'spider_boxes_get_all_field_types', $combined_types );
	}
}
