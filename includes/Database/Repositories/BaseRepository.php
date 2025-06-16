<?php
/**
 * Base Repository
 *
 * @package SpiderBoxes\Database\Repositories
 */

namespace SpiderBoxes\Database\Repositories;

use SpiderBoxes\Database\Contracts\RepositoryInterface;
use StellarWP\DB\DB;
use SpiderBoxes\Database\Traits\HasTimestamps;

/**
 * Base Repository Class
 */
abstract class BaseRepository implements RepositoryInterface {

	use HasTimestamps;

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Primary key
	 *
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * Serializable fields
	 *
	 * @var array
	 */
	protected $serializable = array();

	/**
	 * Fillable fields
	 *
	 * @var array
	 */
	protected $fillable = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( empty( $this->table ) ) {
			throw new \Exception( 'Table name must be defined in repository.' );
		}
	}

	/**
	 * Find a record by ID
	 *
	 * @param mixed $id Record ID.
	 * @return array|null
	 */
	public function find( $id ) {
		try {
			$result = DB::table( $this->table )
				->where( $this->primary_key, $id )
				->get( ARRAY_A );

			if ( ! $result ) {
				return null;
			}

			return $this->unserialize_data( $result );

		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to find record in %s: %s', $this->table, $e->getMessage() ) );
			return null;
		}
	}

	/**
	 * Get all records
	 *
	 * @param array $filters Optional filters.
	 * @return array
	 */
	public function all( $filters = array() ) {
		try {
			$query = DB::table( $this->table );

			// Apply filters
			if ( ! empty( $filters ) ) {
				$query = $this->apply_filters( $query, $filters );
			}

			$results = $query->getAll( ARRAY_A );

			if ( ! $results ) {
				return array();
			}

			// Unserialize data for each record
			$processed_results = array();
			foreach ( $results as $result ) {
				$processed_results[] = $this->unserialize_data( $result );
			}

			return apply_filters( "spider_boxes_get_all_{$this->table}", $processed_results, $filters );

		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to get all records from %s: %s', $this->table, $e->getMessage() ) );
			return array();
		}
	}

	/**
	 * Create a new record
	 *
	 * @param array $data Record data.
	 * @return bool|int
	 */
	public function create( $data ) {
		try {
			$data = $this->prepare_data( $data );
			$data = $this->serialize_data( $data );
			$data = $this->add_timestamps( $data );

			do_action( "spider_boxes_before_create_{$this->table}", $data );

			$result = DB::table( $this->table )->insert( $data );

			if ( $result !== false ) {
				do_action( "spider_boxes_after_create_{$this->table}", $data, $result );
			}

			return $result;

		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to create record in %s: %s', $this->table, $e->getMessage() ) );
			return false;
		}
	}

	/**
	 * Update a record
	 *
	 * @param mixed $id Record ID.
	 * @param array $data Updated data.
	 * @return bool
	 */
	public function update( $id, $data ) {
		try {
			if ( ! $this->exists( $id ) ) {
				return false;
			}

			$data = $this->prepare_data( $data );
			$data = $this->serialize_data( $data );
			$data = $this->add_timestamps( $data, true );

			do_action( "spider_boxes_before_update_{$this->table}", $id, $data );

			$result = DB::table( $this->table )
				->where( $this->primary_key, $id )
				->update( $data );

			if ( $result !== false ) {
				do_action( "spider_boxes_after_update_{$this->table}", $id, $data );
			}

			return $result !== false;

		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to update record in %s: %s', $this->table, $e->getMessage() ) );
			return false;
		}
	}

	/**
	 * Delete a record
	 *
	 * @param mixed $id Record ID.
	 * @return bool
	 */
	public function delete( $id ) {
		try {
			if ( ! $this->exists( $id ) ) {
				return false;
			}

			do_action( "spider_boxes_before_delete_{$this->table}", $id );

			$result = DB::table( $this->table )
				->where( $this->primary_key, $id )
				->delete();

			if ( $result !== false ) {
				do_action( "spider_boxes_after_delete_{$this->table}", $id );
			}

			return $result !== false;

		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to delete record from %s: %s', $this->table, $e->getMessage() ) );
			return false;
		}
	}

	/**
	 * Check if a record exists
	 *
	 * @param mixed $id Record ID.
	 * @return bool
	 */
	public function exists( $id ) {
		try {
			$result = DB::table( $this->table )
				->where( $this->primary_key, $id )
				->get();

			return $result !== null;

		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Find by field value
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @return array|null
	 */
	public function find_by( $field, $value ) {
		try {
			$result = DB::table( $this->table )
				->where( $field, $value )
				->get( ARRAY_A );

			if ( ! $result ) {
				return null;
			}

			return $this->unserialize_data( $result );

		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to find by %s in %s: %s', $field, $this->table, $e->getMessage() ) );
			return null;
		}
	}

	/**
	 * Get records where field value is in array
	 *
	 * @param string $field Field name.
	 * @param array  $values Array of values.
	 * @return array
	 */
	public function where_in( $field, $values ) {
		try {
			$results = DB::table( $this->table )
				->whereIn( $field, $values )
				->getAll( ARRAY_A );

			if ( ! $results ) {
				return array();
			}

			$processed_results = array();
			foreach ( $results as $result ) {
				$processed_results[] = $this->unserialize_data( $result );
			}

			return $processed_results;

		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to get records where %s in %s: %s', $field, $this->table, $e->getMessage() ) );
			return array();
		}
	}

	/**
	 * Apply filters to query
	 *
	 * @param object $query DB query object.
	 * @param array  $filters Filters to apply.
	 * @return object Modified query.
	 */
	protected function apply_filters( $query, $filters ) {
		foreach ( $filters as $field => $value ) {
			if ( is_array( $value ) ) {
				$query->whereIn( $field, $value );
			} else {
				$query->where( $field, $value );
			}
		}

		// Apply ordering if specified
		if ( isset( $filters['_order_by'] ) ) {
			$direction = $filters['_order_direction'] ?? 'ASC';
			$query->orderBy( $filters['_order_by'], $direction );
		} else {
			$query->orderBy( 'created_at', 'DESC' );
		}

		// Apply limits if specified
		if ( isset( $filters['_limit'] ) ) {
			$query->limit( (int) $filters['_limit'] );
		}

		if ( isset( $filters['_offset'] ) ) {
			$query->offset( (int) $filters['_offset'] );
		}

		return apply_filters( "spider_boxes_apply_filters_{$this->table}", $query, $filters );
	}

	/**
	 * Prepare data before saving
	 *
	 * @param array $data Raw data.
	 * @return array Prepared data.
	 */
	protected function prepare_data( $data ) {
		// Only keep fillable fields if defined
		if ( ! empty( $this->fillable ) ) {
			$prepared = array();
			foreach ( $this->fillable as $field ) {
				if ( isset( $data[ $field ] ) ) {
					$prepared[ $field ] = $data[ $field ];
				}
			}
			$data = $prepared;
		}

		// prepare settings
		$settings = $data['settings'] ?? array();

		// Separate database fields from settings fields
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $this->fillable, true ) ) {
				// This is a database column
				$db_data[ $key ] = $value;
			} elseif ( $key !== '_original_settings' && $key !== 'settings' ) {
				// This goes into settings (skip internal fields)
				$settings[ $key ] = $value;
			}
		}

		// Store settings array
		$db_data['settings'] = $settings;

		return apply_filters( "spider_boxes_prepare_data_{$this->table}", $data );
	}


	/**
	 * Prepare data from database for model
	 * Extracts settings into individual fields
	 *
	 * @param array $db_data Raw database data.
	 * @return array Prepared data for model.
	 */
	public function prepare_from_database( array $db_data ) {
		// Unserialize serializable fields first
		$db_data = $this->unserialize_data( $db_data );

		// Extract settings into individual fields
		$settings = $db_data['settings'] ?? array();
		if ( is_array( $settings ) ) {
			// Merge settings into main data array
			$db_data = array_merge( $db_data, $settings );
		}

		// Keep original settings for reference
		$db_data['_original_settings'] = $settings;

		return apply_filters( 'spider_boxes_prepare_field_from_database', $db_data, $settings );
	}

	/**
	 * Serialize data for storage
	 *
	 * @param array $data Data to serialize.
	 * @return array Serialized data.
	 */
	protected function serialize_data( $data ) {
		foreach ( $this->serializable as $field ) {
			if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
				$data[ $field ] = maybe_serialize( $data[ $field ] );
			}
		}

		return $data;
	}

	/**
	 * Unserialize data after retrieval
	 *
	 * @param array $data Data to unserialize.
	 * @return array Unserialized data.
	 */
	protected function unserialize_data( $data ) {
		foreach ( $this->serializable as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$data[ $field ] = maybe_unserialize( $data[ $field ] );

			}
		}

		return apply_filters( "spider_boxes_unserialize_data_{$this->table}", $data );
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function get_table() {
		return $this->table;
	}

	/**
	 * Get primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return $this->primary_key;
	}
}
