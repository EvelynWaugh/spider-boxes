<?php
/**
 * Field Repository
 *
 * @package SpiderBoxes\Database\Repositories
 */

namespace SpiderBoxes\Database\Repositories;

use SpiderBoxes\Database\Repositories\BaseRepository;
use SpiderBoxes\Database\DatabaseManager;
use SpiderBoxes\Database\Models\Field;
use StellarWP\DB\DB;
use Illuminate\Support\Collection;

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
		'created_at',
		'updated_at',
	);



	/**
	 * Get database columns (what actually exists in DB)
	 *
	 * @return array
	 */
	public function get_database_columns() {
		return $this->fillable;
	}


	/**
	 * Get all fields as raw data (using DatabaseManager for compatibility)
	 *
	 * @param array $criteria Query criteria.
	 * @return array
	 */
	public function all( $criteria = array() ) {
		$context = $criteria['context'] ?? '';
		$parent  = $criteria['parent'] ?? '';

		// Use existing DatabaseManager method for consistency
		try {
			$query = DB::table( $this->table )
			->select( '*' );

			foreach ( $criteria as $key => $value ) {
				if ( in_array( $key, $this->fillable, true ) && ! is_null( $value ) ) {
					$query->where( $key, $value );
				}
			}

			$results = $query
			->orderBy( 'created_at', 'ASC' )
			->getAll( ARRAY_A );

			if ( ! $results ) {
				return array();
			}

			$fields = array();
			foreach ( $results as $result ) {
				$fields[] = $this->unserialize_data( $result );
			}
		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to get all fields: ' . $e->getMessage() );
			return array();
		}

		// Filter by parent if specified
		if ( ! empty( $parent ) ) {
			$fields = array_filter(
				$fields,
				function ( $field ) use ( $parent ) {
					return ( $field['parent'] ?? '' ) === $parent;
				}
			);
		}

		return apply_filters( 'spider_boxes_get_db_fields', $fields, $criteria );
	}

	/**
	 * Get all fields as Field model instances
	 *
	 * @param array $criteria Query criteria.
	 * @return Collection<Field>
	 */
	public function all_as_models( $criteria = array() ) {
		$raw_fields = $this->all( $criteria );

		$models = collect();

		foreach ( $raw_fields as $field_data ) {
			try {
				// Ensure we have required fields
				if ( empty( $field_data['type'] ) ) {
					continue;
				}

				$field = Field::from_database( $field_data );
				$models->push( $field );
			} catch ( \Exception $e ) {
				error_log( 'Spider Boxes: Failed to create field model: ' . $e->getMessage() );
				continue;
			}
		}

		return apply_filters( 'spider_boxes_fields_as_models', $models, $criteria );
	}


	/**
	 * Prepare data for database storage
	 * Moves non-database fields into settings
	 *
	 * @param array $data Raw field data.
	 * @return array Prepared data for database.
	 */
	public function prepare_for_database( array $data ) {
		$db_data  = array();
		$settings = $data['settings'] ?? array();

		// Separate database fields from settings fields
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $this->get_database_columns(), true ) ) {
				// This is a database column
				$db_data[ $key ] = $value;
			} else {
				// This goes into settings
				$settings[ $key ] = $value;
			}
		}

		// Store settings as serialized data
		$db_data['settings'] = $settings;

		// Serialize fields that need serialization
		$db_data = $this->serialize_data( $db_data );

		return apply_filters( 'spider_boxes_prepare_field_for_database', $db_data, $data );
	}


		/**
		 * Prepare data from database for model
		 * Extracts settings into individual fields
		 *
		 * @param array $db_data Raw database data.
		 * @return array Prepared data for model.
		 */
	public function prepare_from_database( array $db_data ) {
		// Unserialize serializable fields
		foreach ( $this->serializable as $field ) {
			if ( isset( $db_data[ $field ] ) ) {
				$db_data[ $field ] = maybe_unserialize( $db_data[ $field ] );
			}
		}

		// Extract settings into individual fields
		$settings = $db_data['settings'] ?? array();
		if ( is_array( $settings ) ) {
			$db_data = array_merge( $db_data, $settings );
		}

		// Clean up - keep settings as original for reference
		$db_data['_original_settings'] = $settings;

		return apply_filters( 'spider_boxes_prepare_field_from_database', $db_data, $settings );
	}

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
	 * Find field by ID (using DatabaseManager for compatibility)
	 *
	 * @param int|string $id Field ID.
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
			// Unserialize data for the field
			$result = $this->unserialize_data( $result );
			return apply_filters( 'spider_boxes_find_field', $result, $id );
		} catch ( \Exception $e ) {
			error_log( sprintf( 'Spider Boxes: Failed to find field with ID %s: %s', $id, $e->getMessage() ) );
			return null;
		}
	}

		/**
		 * Find field as model instance
		 *
		 * @param int|string $id Field ID.
		 * @return Field|null
		 */
	public function find_as_model( $id ) {
		$field_data = $this->find( $id );

		if ( ! $field_data ) {
			return null;
		}

		try {
			return Field::from_database( $field_data );
		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to create field model from ID ' . $id . ': ' . $e->getMessage() );
			return null;
		}
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
		 * Get fields by context as models
		 *
		 * @param string $context Context value.
		 * @return Collection<Field>
		 */
	public function get_by_context_as_models( $context ) {
		return $this->all_as_models( array( 'context' => $context ) );
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
	 * Get fields by type as models
	 *
	 * @param string $type Field type.
	 * @return Collection<Field>
	 */
	public function get_by_type_as_models( $type ) {
		$raw_fields = $this->get_by_type( $type );

		$models = collect();

		foreach ( $raw_fields as $field_data ) {
			try {
				$field = Field::from_database( $field_data );
				$models->push( $field );
			} catch ( \Exception $e ) {
				error_log( 'Spider Boxes: Failed to create field model: ' . $e->getMessage() );
				continue;
			}
		}

		return apply_filters( 'spider_boxes_fields_by_type_as_models', $models, $type );
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
		 * Search fields as models
		 *
		 * @param string $search Search term.
		 * @return Collection<Field>
		 */
	public function search_as_models( $search ) {
		$raw_fields = $this->search( $search );

		$models = collect();

		foreach ( $raw_fields as $field_data ) {
			try {
				$field = Field::from_database( $field_data );
				$models->push( $field );
			} catch ( \Exception $e ) {
				error_log( 'Spider Boxes: Failed to create field model: ' . $e->getMessage() );
				continue;
			}
		}

		return apply_filters( 'spider_boxes_search_fields_as_models', $models, $search );
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
	 * Create field from model instance
	 *
	 * @param Field $field Field model.
	 * @return int|false Field ID on success, false on failure.
	 */
	public function create_from_model( Field $field ) {
		if ( ! $field->is_valid() ) {
			return false;
		}

		$data   = $field->to_database();
		$result = $this->create( $data );

		if ( $result ) {
			// Set the ID on the model if successful
			$field->set_attribute( 'id', $result );
			$field->sync_original();
		}

		return $result;
	}

		/**
		 * Update field from model instance
		 *
		 * @param Field $field Field model.
		 * @return bool
		 */
	public function update_from_model( Field $field ) {
		$id = $field->get_attribute( 'id' );

		if ( ! $id ) {
			return false;
		}

		if ( ! $field->is_valid() ) {
			return false;
		}

		$data   = $field->to_database();
		$result = $this->update( $id, $data );

		if ( $result ) {
			$field->sync_original();
		}

		return $result;
	}


	/**
	 * Get fields with pagination
	 *
	 * @param array $criteria Query criteria.
	 * @param int   $page Page number.
	 * @param int   $per_page Items per page.
	 * @return array
	 */
	public function paginate( $criteria = array(), $page = 1, $per_page = 20 ) {
		try {
			$query = DB::table( $this->table );

			// Apply criteria filters
			if ( ! empty( $criteria['context'] ) ) {
				$query->where( 'context', $criteria['context'] );
			}

			if ( ! empty( $criteria['type'] ) ) {
				$query->where( 'type', $criteria['type'] );
			}

			if ( ! empty( $criteria['parent'] ) ) {
				$query->where( 'parent', $criteria['parent'] );
			}

			if ( ! empty( $criteria['search'] ) ) {
				$query->where(
					function ( $q ) use ( $criteria ) {
						$q->where( 'title', 'LIKE', '%' . $criteria['search'] . '%' )
						->orWhere( 'description', 'LIKE', '%' . $criteria['search'] . '%' );
					}
				);
			}

			// Get total count
			$total = $query->count();

			// Apply pagination
			$offset  = ( $page - 1 ) * $per_page;
			$results = $query->orderBy( 'title', 'ASC' )
				->limit( $per_page )
				->offset( $offset )
				->getAll( ARRAY_A );

			$processed_results = array();
			foreach ( $results as $result ) {
				$processed_results[] = $this->unserialize_data( $result );
			}

			return array(
				'data'        => $processed_results,
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total / $per_page ),
			);

		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to paginate fields: ' . $e->getMessage() );
			return array(
				'data'        => array(),
				'total'       => 0,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => 0,
			);
		}
	}

	/**
	 * Get fields with pagination as models
	 *
	 * @param array $criteria Query criteria.
	 * @param int   $page Page number.
	 * @param int   $per_page Items per page.
	 * @return array
	 */
	public function paginate_as_models( $criteria = array(), $page = 1, $per_page = 20 ) {
		$result = $this->paginate( $criteria, $page, $per_page );

		$models = collect();
		foreach ( $result['data'] as $field_data ) {
			try {
				$field = Field::from_database( $field_data );
				$models->push( $field );
			} catch ( \Exception $e ) {
				error_log( 'Spider Boxes: Failed to create field model: ' . $e->getMessage() );
				continue;
			}
		}

		$result['data'] = $models;

		return apply_filters( 'spider_boxes_paginate_fields_as_models', $result, $criteria, $page, $per_page );
	}
}
