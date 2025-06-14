<?php
/**
 * Meta Repository
 *
 * @package SpiderBoxes\Database\Repositories
 */

namespace SpiderBoxes\Database\Repositories;

use SpiderBoxes\Database\Repositories\BaseRepository;
use StellarWP\DB\DB;

/**
 * Meta Repository Class
 */
class MetaRepository extends BaseRepository {

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table = 'spider_boxes_meta';

	/**
	 * Serializable fields
	 *
	 * @var array
	 */
	protected $serializable = array( 'meta_value' );

	/**
	 * Fillable fields
	 *
	 * @var array
	 */
	protected $fillable = array(
		'object_id',
		'object_type',
		'meta_key',
		'meta_value',
		'context',
	);

	/**
	 * Get meta value
	 *
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type.
	 * @param string $meta_key Meta key.
	 * @param string $context Context.
	 * @return mixed
	 */
	public function get_meta( $object_id, $object_type, $meta_key, $context = 'default' ) {
		try {
			$result = DB::table( $this->table )
				->select( 'meta_value' )
				->where( 'object_id', $object_id )
				->where( 'object_type', $object_type )
				->where( 'meta_key', $meta_key )
				->where( 'context', $context )
				->get( ARRAY_A );

			if ( ! $result ) {
				return null;
			}

			return maybe_unserialize( $result['meta_value'] );

		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to get meta: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Save meta value
	 *
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param string $context Context.
	 * @return bool
	 */
	public function save_meta( $object_id, $object_type, $meta_key, $meta_value, $context = 'default' ) {
		$data = array(
			'object_id'   => $object_id,
			'object_type' => $object_type,
			'meta_key'    => $meta_key,
			'meta_value'  => $meta_value,
			'context'     => $context,
		);

		// Check if meta exists
		$existing = $this->find_meta_record( $object_id, $object_type, $meta_key, $context );

		if ( $existing ) {
			return $this->update( $existing['id'], array( 'meta_value' => $meta_value ) );
		} else {
			return $this->create( $data ) !== false;
		}
	}

	/**
	 * Delete meta by meta key
	 *
	 * @param string $meta_key Meta key.
	 * @param string $context Optional context.
	 * @return bool
	 */
	public function delete_by_meta_key( $meta_key, $context = '' ) {
		try {
			$query = DB::table( $this->table )
				->where( 'meta_key', $meta_key );

			if ( ! empty( $context ) ) {
				$query->where( 'context', $context );
			}

			$result = $query->delete();

			return $result !== false;

		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to delete meta by key: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get all meta for an object
	 *
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type.
	 * @param string $context Context.
	 * @return array
	 */
	public function get_object_meta( $object_id, $object_type, $context = 'default' ) {
		try {
			$results = DB::table( $this->table )
				->where( 'object_id', $object_id )
				->where( 'object_type', $object_type )
				->where( 'context', $context )
				->getAll( ARRAY_A );

			if ( ! $results ) {
				return array();
			}

			$meta = array();
			foreach ( $results as $result ) {
				$meta[ $result['meta_key'] ] = maybe_unserialize( $result['meta_value'] );
			}

			return $meta;

		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to get object meta: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Find meta record
	 *
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type.
	 * @param string $meta_key Meta key.
	 * @param string $context Context.
	 * @return array|null
	 */
	private function find_meta_record( $object_id, $object_type, $meta_key, $context ) {
		try {
			return DB::table( $this->table )
				->where( 'object_id', $object_id )
				->where( 'object_type', $object_type )
				->where( 'meta_key', $meta_key )
				->where( 'context', $context )
				->get( ARRAY_A );

		} catch ( \Exception $e ) {
			return null;
		}
	}
}
