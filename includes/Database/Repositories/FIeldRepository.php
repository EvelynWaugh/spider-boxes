<?php
/**
 * Field Repository
 *
 * @package SpiderBoxes\Database\Repositories
 */

namespace SpiderBoxes\Database\Repositories;

use SpiderBoxes\Database\Repositories\BaseRepository;
use StellarWP\DB\DB;

/**
 * Field Repository Class
 */
class FieldRepository extends BaseRepository {

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table = 'spider_boxes_fields';

	/**
	 * Serializable fields
	 *
	 * @var array
	 */
	protected $serializable = array( 'value', 'settings' );

	/**
	 * Fillable fields
	 *
	 * @var array
	 */
	protected $fillable = array(
		'type',
		'name',
		'title',
		'description',
		'context',
		'value',
		'settings',
	);

	/**
	 * Get field by name
	 *
	 * @param string $name Field name.
	 * @return array|null
	 */
	public function find_by_name( $name ) {
		return $this->find_by( 'name', $name );
	}

	/**
	 * Get fields by context
	 *
	 * @param string $context Context value.
	 * @return array
	 */
	public function get_by_context( $context ) {
		return $this->all( array( 'context' => $context ) );
	}

	/**
	 * Get fields by type
	 *
	 * @param string $type Field type.
	 * @return array
	 */
	public function get_by_type( $type ) {
		return $this->all( array( 'type' => $type ) );
	}

	/**
	 * Search fields by title or description
	 *
	 * @param string $search Search term.
	 * @return array
	 */
	public function search( $search ) {
		try {
			$results = DB::table( $this->table )
				->where( 'title', 'LIKE', '%' . $search . '%' )
				->orWhere( 'description', 'LIKE', '%' . $search . '%' )
				->orderBy( 'title', 'ASC' )
				->getAll( ARRAY_A );

			if ( ! $results ) {
				return array();
			}

			$processed_results = array();
			foreach ( $results as $result ) {
				$processed_results[] = $this->unserialize_data( $result );
			}

			return apply_filters( 'spider_boxes_search_fields', $processed_results, $search );

		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to search fields: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Get field configuration with validation
	 *
	 * @param string $field_id Field ID.
	 * @return array|null
	 */
	public function get_config( $field_id ) {
		$field = $this->find( $field_id );

		if ( ! $field ) {
			return null;
		}

		// Ensure settings is an array
		if ( ! is_array( $field['settings'] ) ) {
			$field['settings'] = array();
		}

		// Ensure value is properly handled
		if ( empty( $field['value'] ) ) {
			$field['value'] = '';
		}

		return apply_filters( 'spider_boxes_field_config', $field, $field_id );
	}

	/**
	 * Save field configuration
	 *
	 * @param string $field_id Field ID.
	 * @param array  $config Field configuration.
	 * @return bool
	 */
	public function save_config( $field_id, $config ) {
		// Validate required fields
		$required = array( 'name', 'type', 'title' );
		foreach ( $required as $field ) {
			if ( empty( $config[ $field ] ) ) {
				return false;
			}
		}

		// Set defaults
		$config = wp_parse_args(
			$config,
			array(
				'description' => '',
				'context'     => 'default',
				'value'       => '',
				'settings'    => array(),
			)
		);

		if ( $field_id === 'new' ) {
			return $this->create( $config );
		} else {
			return $this->update( $field_id, $config );
		}
	}

	/**
	 * Delete field and related meta
	 *
	 * @param string $field_id Field ID.
	 * @return bool
	 */
	public function delete_with_meta( $field_id ) {
		$field = $this->find( $field_id );
		if ( ! $field ) {
			return false;
		}

		// Delete field meta first
		$meta_repository = new MetaRepository();
		$meta_repository->delete_by_meta_key( $field['name'] );

		// Then delete the field configuration
		return $this->delete( $field_id );
	}
}
