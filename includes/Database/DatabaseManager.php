<?php
/**
 * Database Manager Class
 *
 * @package SpiderBoxes\Database
 */

namespace SpiderBoxes\Database;

use StellarWP\DB\DB;

/**
 * Handles database operations for Spider Boxes
 */
class DatabaseManager {
	/**
	 * DB Version for custom tables
	 *
	 * @var string
	 */
	protected static $db_table_version = '1.1.4';

	/**
	 * Create custom database tables
	 */
	public static function create_tables() {
		global $wpdb;

		$db_version = get_option( 'spider_boxes_db_version', '1.0.0' );

		if ( version_compare( $db_version, self::$db_table_version, '>=' ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// parent can be string or integer. Integer in database as reference to parent. In fieldRegistry we can use string.

		// Field configurations table.
		$fields_table = $wpdb->prefix . 'spider_boxes_fields';
		$fields_sql   = "CREATE TABLE $fields_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(50) NOT NULL,
			name varchar(255) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			context varchar(50) DEFAULT 'default',
			value longtext,
			settings longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY context (context)
		) $charset_collate;";

		// Field values/meta table for storing field data values.
		$meta_table = $wpdb->prefix . 'spider_boxes_meta';
		$meta_sql   = "CREATE TABLE $meta_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			object_id bigint(20) unsigned NOT NULL,
			object_type varchar(50) NOT NULL,
			meta_key varchar(255) NOT NULL,
			meta_value longtext,
			context varchar(50) DEFAULT 'default',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY object_id (object_id),
			KEY object_type (object_type),
			KEY meta_key (meta_key),
			KEY context (context)
		) $charset_collate;";
		// Field types table for registered field types.
		$field_types_table = $wpdb->prefix . 'spider_boxes_field_types';
		$field_types_sql   = "CREATE TABLE $field_types_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			class_name varchar(255) DEFAULT NULL,
			icon varchar(100) DEFAULT 'component',
			description text,
			supports longtext,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_active (is_active)
		) $charset_collate;";

		// Component types table for registered component types.
		$component_types_table = $wpdb->prefix . 'spider_boxes_component_types';
		$component_types_sql   = "CREATE TABLE $component_types_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			class_name varchar(255) DEFAULT NULL,
			icon varchar(100) DEFAULT 'component',
			description text,
			supports longtext,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_active (is_active)
		) $charset_collate;";

		// Section types table for registered section types.
		$section_types_table = $wpdb->prefix . 'spider_boxes_section_types';
		$section_types_sql   = "CREATE TABLE $section_types_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			class_name varchar(255) DEFAULT NULL,
			icon varchar(100) DEFAULT 'section',
			description text,
			supports longtext,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_active (is_active)
		) $charset_collate;";

		// Components table for component configurations.
		$components_table = $wpdb->prefix . 'spider_boxes_components';
		$components_sql   = "CREATE TABLE $components_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			context varchar(50) DEFAULT 'default',
			settings longtext,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY context (context),
			KEY is_active (is_active)
		) $charset_collate;";

		// Sections table for section configurations.
		$sections_table = $wpdb->prefix . 'spider_boxes_sections';
		$sections_sql   = "CREATE TABLE $sections_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			context varchar(50) DEFAULT 'default',
			screen varchar(100) DEFAULT '',
			settings longtext,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY context (context),
			KEY screen (screen),
			KEY is_active (is_active)
		) $charset_collate;";

		if ( version_compare( $db_version, self::$db_table_version, '<' ) ) {
			dbDelta( $fields_sql );
			dbDelta( $meta_sql );
			dbDelta( $field_types_sql );
			dbDelta( $component_types_sql );
			dbDelta( $section_types_sql );
			dbDelta( $components_sql );
			dbDelta( $sections_sql );

			update_option( 'spider_boxes_db_version', self::$db_table_version );
		}
	}


	/**
	 * Get all available field types (combined from registry and database)
	 *
	 * @return array Array of field types.
	 */
	public static function get_field_types() {
		// Use the field registry to get combined field types
		$field_registry = spider_boxes()->get_container()->get( 'fieldRegistry' );
		return $field_registry->get_all_field_types();
	}


	/**
	 * Get all available field types from database
	 *
	 * @return array Array of field types.
	 */
	public static function get_db_field_types() {

		$results = DB::table( 'spider_boxes_field_types' )
		->select( '*' )
		->where( 'is_active', 1 )
		->orderBy( 'name' )
		->getAll();

		if ( ! $results ) {
			return array();
		}

		// Decode supports JSON for each field type.
		foreach ( $results as &$field_type ) {
			$field_type['supports'] = json_decode( $field_type['supports'], true );
		}

		return $results;
	}


	/**
	 * Get field type by type identifier
	 */
	public static function get_field_type_by_type( $type ) {
		return DB::table( 'spider_boxes_field_types' )
			->where( 'type', $type )
			->where( 'is_active', 1 )
			->get();
	}

	/**
	 * Register a new field type
	 *
	 * @param array $field_type Field type configuration.
	 * @return bool Success status.
	 */
	public static function register_field_type( $field_type ) {

		// Validate required fields.
		$required_fields = array( 'name' );
		foreach ( $required_fields as $field ) {
			if ( empty( $field_type[ $field ] ) ) {
				return false;
			}
		}

		// Set defaults.
		$field_type = wp_parse_args(
			$field_type,
			array(

				'icon'        => 'component',
				'description' => '',
				'supports'    => maybe_serialize( array() ),
				'is_active'   => 1,

			)
		);

		// Encode supports if it's an array.
		if ( is_array( $field_type['supports'] ) ) {
			$field_type['supports'] = maybe_serialize( $field_type['supports'] );
		}
		try {
				// Check if field type already exists
				$existing = DB::table( 'spider_boxes_field_types' )
					->where( 'name', $field_type['name'] )
					->get();

			if ( $existing ) {
				// Update existing field type
				$update_data = $field_type;
				unset( $update_data['name'] ); // Don't update the name field

				$result = DB::table( 'spider_boxes_field_types' )
					->where( 'name', $field_type['name'] )
					->update( $update_data );
			} else {
				// Insert new field type
				$result = DB::table( 'spider_boxes_field_types' )
					->insert( $field_type );
			}

				return $result !== false;

		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to register field type: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Save field configuration to database
	 *
	 * @param integer|string $field_id Field ID.
	 * @param array          $field_config Field configuration.
	 * @return bool Success status.
	 */
	public static function save_field_config( $field_id, $field_config ) {

		// Prepare data for insertion.
		$data = array(

			'name'        => $field_config['name'] ?? '',
			'type'        => $field_config['type'] ?? '',
			'title'       => $field_config['title'] ?? '',
			'description' => $field_config['description'] ?? '',

			'context'     => $field_config['context'] ?? 'default',
			'value'       => maybe_serialize( $field_config['value'] ?? '' ),
			'settings'    => maybe_serialize( $field_config['settings'] ?? array() ),

		);

		if ( $field_id === 'new' ) {
			$result = DB::table( 'spider_boxes_fields' )
				->insert( $data );
		} else {

			$result = DB::table( 'spider_boxes_fields' )
				->where( 'id', $field_id )
				->update( $data );
		}

		return false !== $result;
	}

	/**
	 * Get field configuration from database
	 *
	 * @param integer $field_id Field ID.
	 * @return array|null Field configuration or null if not found.
	 */
	public static function get_field_config( $field_id ) {
		global $wpdb;

		$fields_table = $wpdb->prefix . 'spider_boxes_fields';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $fields_table WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$field_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $result ) {
			return null;
		}

		// Decode JSON settings.
		$result['settings'] = maybe_unserialize( $result['settings'] );
		$result['value']    = maybe_unserialize( $result['value'] );

		return $result;
	}


	/**
	 * Get field configuration from database by name
	 *
	 * @param string $name Field Name.
	 * @return array|null Field configuration or null if not found.
	 */
	public static function get_field_config_by_name( $name ) {
		global $wpdb;

		$fields_table = $wpdb->prefix . 'spider_boxes_fields';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $fields_table WHERE name = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$name
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $result ) {
			return null;
		}

		// Decode JSON settings.
		$result['settings'] = maybe_unserialize( $result['settings'] );
		$result['value']    = maybe_unserialize( $result['value'] );

		return $result;
	}

	/**
	 * Save field meta value
	 *
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type (post, term, comment, etc.).
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param string $context Context.
	 * @return bool Success status.
	 */
	public static function save_meta( $object_id, $object_type, $meta_key, $meta_value, $context = 'default' ) {
		global $wpdb;

		$meta_table = $wpdb->prefix . 'spider_boxes_meta';

		$data = array(
			'object_id'   => $object_id,
			'object_type' => $object_type,
			'meta_key'    => $meta_key,
			'meta_value'  => maybe_serialize( $meta_value ),
			'context'     => $context,
		);

		// Check if meta exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $meta_table WHERE object_id = %d AND object_type = %s AND meta_key = %s AND context = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$object_id,
				$object_type,
				$meta_key,
				$context
			)
		);

		if ( $existing ) {
			unset( $data['created_at'] );
			$result = $wpdb->update(
				$meta_table,
				$data,
				array(
					'object_id'   => $object_id,
					'object_type' => $object_type,
					'meta_key'    => $meta_key,
					'context'     => $context,
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->insert( $meta_table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}

	/**
	 * Get field meta value
	 *
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type (post, term, comment, etc.).
	 * @param string $meta_key Meta key.
	 * @param string $context Context.
	 * @return mixed Meta value or null if not found.
	 */
	public static function get_meta( $object_id, $object_type, $meta_key, $context = 'default' ) {
		global $wpdb;

		$meta_table = $wpdb->prefix . 'spider_boxes_meta';

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM $meta_table WHERE object_id = %d AND object_type = %s AND meta_key = %s AND context = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$object_id,
				$object_type,
				$meta_key,
				$context
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $result ? maybe_unserialize( $result ) : null;
	}

	/**
	 * Get all field configurations from database
	 *
	 * @param string $parent Optional parent to filter by.
	 * @param string $context Optional context to filter by.
	 * @return array Array of field configurations.
	 */
	public static function get_all_fields( $parent = '', $context = '' ) {
		try {
			$query = DB::table( 'spider_boxes_fields' )
			->select( '*' );

			// Add conditional where clauses
			if ( ! empty( $parent ) ) {
				$query->where( 'parent', $parent );
			}

			if ( ! empty( $context ) ) {
				$query->where( 'context', $context );
			}

			$results = $query
			->orderBy( 'created_at', 'ASC' )
			->getAll();

			if ( ! $results ) {
				return array();
			}

			// Convert objects to arrays and decode JSON settings and unserialize values
			$fields = array();
			foreach ( $results as $field ) {
				$field_array             = (array) $field;
				$field_array['settings'] = maybe_unserialize( $field_array['settings'] );
				$field_array['value']    = maybe_unserialize( $field_array['value'] );
				$fields[]                = $field_array;
			}

			return apply_filters( 'spider_boxes_get_db_fields', $fields, $parent, $context );

		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to get all fields: ' . $e->getMessage() );
			return array();
		}
	}
	/**
	 * Delete field configuration from database
	 *
	 * @param string $field_id Field ID.
	 * @return bool Success status.
	 */
	public static function delete_field_config( $field_id ) {
		global $wpdb;

		$fields_table = $wpdb->prefix . 'spider_boxes_fields';

		// Check if field exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $fields_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$field_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $existing ) {
			return false;
		}

		// Delete the field configuration.
		$result = $wpdb->delete(
			$fields_table,
			array( 'id' => $field_id ),
			array( '%s' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return false !== $result;
	}
	/**
	 * Delete field meta values for a specific field
	 *
	 * @param string $meta_key Meta key (field ID).
	 * @param string $context Optional context to filter by.
	 * @return bool Success status.
	 */
	public static function delete_field_meta( $meta_key, $context = '' ) {
		global $wpdb;

		$meta_table = $wpdb->prefix . 'spider_boxes_meta';

		if ( ! empty( $context ) ) {
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $meta_table WHERE meta_key = %s AND context = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$meta_key,
					$context
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $meta_table WHERE meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$meta_key
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}

	/**
	 * Validate field configuration data
	 *
	 * @param array $field_config Field configuration.
	 * @return array|WP_Error Validated field config or error object.
	 */
	public static function validate_field_config( $field_config ) {
		$errors = array();

		// Required fields.
		$required_fields = array( 'id', 'name', 'type', 'title' );
		foreach ( $required_fields as $field ) {
			if ( empty( $field_config[ $field ] ) ) {
				$errors[] = sprintf( 'Missing required field: %s', $field );
			}
		}

		// Check if field type is registered.
		if ( ! empty( $field_config['type'] ) ) {
			$field_types      = self::get_field_types();
			$registered_types = wp_list_pluck( $field_types, 'name' );
			if ( ! in_array( $field_config['type'], $registered_types, true ) ) {
				$errors[] = sprintf( 'Invalid field type: %s', $field_config['type'] );
			}
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'validation_failed', 'Field validation failed', array( 'errors' => $errors ) );
		}

		// Set defaults for optional fields.
		$defaults = array(
			'description' => '',

			'context'     => 'default',
			'value'       => '',

		);

		return wp_parse_args( $field_config, $defaults );
	}

	/**
	 * Sanitize field configuration data
	 *
	 * @param array $field_config Field configuration.
	 * @return array Sanitized field config.
	 */
	public static function sanitize_field_config( $field_config ) {
		$sanitized = array();

		// Sanitize field ID.
		if ( isset( $field_config['id'] ) ) {
			$sanitized['id'] = sanitize_key( $field_config['id'] );
		}

		// Sanitize field name.
		if ( isset( $field_config['name'] ) ) {
			$sanitized['name'] = sanitize_key( $field_config['name'] );
		}

		// Sanitize field type.
		if ( isset( $field_config['type'] ) ) {
			$sanitized['type'] = sanitize_key( $field_config['type'] );
		}

		// Sanitize title.
		if ( isset( $field_config['title'] ) ) {
			$sanitized['title'] = sanitize_text_field( $field_config['title'] );
		}

		// Sanitize description.
		if ( isset( $field_config['description'] ) ) {
			$sanitized['description'] = sanitize_textarea_field( $field_config['description'] );
		}

		// Sanitize parent.
		if ( isset( $field_config['parent'] ) ) {
			$sanitized['parent'] = sanitize_key( $field_config['parent'] );
		}

		// Sanitize context.
		if ( isset( $field_config['context'] ) ) {
			$sanitized['context'] = sanitize_key( $field_config['context'] );
		}

		// Value can be mixed, so we'll serialize it.
		if ( isset( $field_config['value'] ) ) {
			$sanitized['value'] = $field_config['value'];
		}

		// Keep other settings as-is for now (they'll be JSON encoded).
		foreach ( $field_config as $key => $value ) {
			if ( ! isset( $sanitized[ $key ] ) ) {
				$sanitized[ $key ] = $value;
			}
		}
		return $sanitized;
	}

	/**
	 * Save component configuration to database
	 *
	 * @param string $component_id Component ID.
	 * @param array  $component_config Component configuration.
	 * @return bool Success status.
	 */
	public static function save_component_config( $component_id, $component_config ) {
		global $wpdb;

		$components_table = $wpdb->prefix . 'spider_boxes_components';

		// Prepare data for insertion.
		$data = array(
			'id'          => $component_id,
			'type'        => $component_config['type'],
			'title'       => $component_config['title'],
			'description' => $component_config['description'] ?? '',

			'context'     => $component_config['context'] ?? 'default',
			'settings'    => maybe_serialize( $component_config['settings'] ?? array() ),

			'is_active'   => $component_config['is_active'] ?? 1,

		);

		// Check if component exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $components_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$component_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $existing ) {
			unset( $data['created_at'] );
			$result = $wpdb->update(
				$components_table,
				$data,
				array( 'id' => $component_id )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->insert( $components_table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}

	/**
	 * Get component configuration from database
	 *
	 * @param string $component_id Component ID.
	 * @return array|null Component configuration or null if not found.
	 */
	public static function get_component_config( $component_id ) {
		global $wpdb;

		$components_table = $wpdb->prefix . 'spider_boxes_components';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $components_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$component_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $result ) {
			return null;
		}

		// Decode JSON settings.
		$result['settings'] = maybe_unserialize( $result['settings'] );

		return $result;
	}

	/**
	 * Get component configuration from database
	 *
	 * @param type $component_id Component ID.
	 * @return array|false|null Component configuration or null if not found.
	 */
	public static function get_component_by_type( $type ) {
		global $wpdb;

		$components_table = $wpdb->prefix . 'spider_boxes_components';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $components_table WHERE type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$type
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $result;
	}

	/**
	 * Get all components from database
	 *
	 * @param string $parent_id Optional parent ID to filter by.
	 * @param string $section_id Optional section ID to filter by.
	 * @param string $context Optional context to filter by.
	 * @return array Array of component configurations.
	 */
	public static function get_all_components( $parent_id = '', $section_id = '', $context = '' ) {
		global $wpdb;

		$components_table = $wpdb->prefix . 'spider_boxes_components';

		$where_conditions = array();
		$prepare_values   = array();

		if ( ! empty( $parent_id ) ) {
			$where_conditions[] = 'parent_id = %s';
			$prepare_values[]   = $parent_id;
		}

		if ( ! empty( $section_id ) ) {
			$where_conditions[] = 'section_id = %s';
			$prepare_values[]   = $section_id;
		}

		if ( ! empty( $context ) ) {
			$where_conditions[] = 'context = %s';
			$prepare_values[]   = $context;
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		$sql = "SELECT * FROM $components_table $where_clause ORDER BY created_at ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $prepare_values ) ) {
			$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $results ) {
			return array();
		}

		// Decode JSON settings for each component.
		foreach ( $results as &$component ) {
			$component['settings'] = maybe_unserialize( $component['settings'] );

		}

		return $results;
	}

	/**
	 * Delete component configuration from database
	 *
	 * @param string $component_id Component ID.
	 * @return bool Success status.
	 */
	public static function delete_component_config( $component_id ) {
		global $wpdb;

		$components_table = $wpdb->prefix . 'spider_boxes_components';

		// Check if component exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $components_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$component_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $existing ) {
			return false;
		}

		// Delete the component configuration.
		$result = $wpdb->delete(
			$components_table,
			array( 'id' => $component_id ),
			array( '%s' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return false !== $result;
	}

	/**
	 * Save section configuration to database
	 *
	 * @param string $section_id Section ID.
	 * @param array  $section_config Section configuration.
	 * @return bool Success status.
	 */
	public static function save_section_config( $section_id, $section_config ) {
		global $wpdb;

		$sections_table = $wpdb->prefix . 'spider_boxes_sections';

		// Prepare data for insertion.
		$data = array(
			'id'          => $section_id,
			'type'        => $section_config['type'],
			'title'       => $section_config['title'],
			'description' => $section_config['description'] ?? '',
			'context'     => $section_config['context'] ?? 'default',
			'screen'      => $section_config['screen'] ?? '',
			'settings'    => maybe_serialize( $section_config['settings'] ?? array() ),
			'is_active'   => $section_config['is_active'] ?? 1,
		);

		// Check if section exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $sections_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$section_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $existing ) {
			unset( $data['created_at'] );
			$result = $wpdb->update(
				$sections_table,
				$data,
				array( 'id' => $section_id )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->insert( $sections_table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}

	/**
	 * Get section configuration from database
	 *
	 * @param string $section_id Section ID.
	 * @return array|null Section configuration or null if not found.
	 */
	public static function get_section_config( $section_id ) {
		global $wpdb;

		$sections_table = $wpdb->prefix . 'spider_boxes_sections';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $sections_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$section_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $result ) {
			return null;
		}

		// Decode JSON settings and components.
		$result['settings'] = maybe_unserialize( $result['settings'] );

		return $result;
	}

	/**
	 * Get all sections from database
	 *
	 * @param string $context Optional context to filter by.
	 * @param string $screen Optional screen to filter by.
	 * @return array Array of section configurations.
	 */
	public static function get_all_sections( $context = '', $screen = '' ) {
		global $wpdb;

		$sections_table = $wpdb->prefix . 'spider_boxes_sections';

		$where_conditions = array();
		$prepare_values   = array();

		if ( ! empty( $context ) ) {
			$where_conditions[] = 'context = %s';
			$prepare_values[]   = $context;
		}

		if ( ! empty( $screen ) ) {
			$where_conditions[] = 'screen = %s';
			$prepare_values[]   = $screen;
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		$sql = "SELECT * FROM $sections_table $where_clause ORDER BY created_at ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $prepare_values ) ) {
			$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $results ) {
			return array();
		}

		// Decode JSON settings and components for each section.
		foreach ( $results as &$section ) {
			$section['settings'] = maybe_unserialize( $section['settings'] );

		}

		return $results;
	}

	/**
	 * Delete section configuration from database
	 *
	 * @param string $section_id Section ID.
	 * @return bool Success status.
	 */
	public static function delete_section_config( $section_id ) {
		global $wpdb;

		$sections_table = $wpdb->prefix . 'spider_boxes_sections';

		// Check if section exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $sections_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$section_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $existing ) {
			return false;
		}

		// Delete the section configuration.
		$result = $wpdb->delete(
			$sections_table,
			array( 'id' => $section_id ),
			array( '%s' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return false !== $result;
	}

	/**
	 * Get all available component types from database
	 *
	 * @return array Array of component types.
	 */
	public static function get_component_types() {
		global $wpdb;

		$component_types_table = $wpdb->prefix . 'spider_boxes_component_types';

		$results = $wpdb->get_results(
			"SELECT * FROM $component_types_table WHERE is_active = 1 ORDER BY  name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		);

		if ( ! $results ) {
			return array();
		}

		// Decode supports JSON for each component type.
		foreach ( $results as &$component_type ) {
			$component_type['supports'] = json_decode( $component_type['supports'], true );

		}

		return $results;
	}

	/**
	 * Register a new component type
	 *
	 * @param array $component_type Component type configuration.
	 * @return bool Success status.
	 */
	public static function register_component_type( $component_type ) {
		global $wpdb;

		$component_types_table = $wpdb->prefix . 'spider_boxes_component_types';

		// Validate required fields.
		$required_fields = array( 'type', 'name' );
		foreach ( $required_fields as $field ) {
			if ( empty( $component_type[ $field ] ) ) {
				return false;
			}
		}

		// Set defaults.
		$component_type = wp_parse_args(
			$component_type,
			array(
				'icon'        => 'component',
				'description' => '',
				'supports'    => maybe_serialize( array() ),
				'is_active'   => 1,
			)
		);

		// Encode supports if they are arrays.
		if ( is_array( $component_type['supports'] ) ) {
			$component_type['supports'] = maybe_serialize( $component_type['supports'] );
		}

		// Insert or update.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $component_types_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$component_type['id']
			)
		);

		if ( $existing ) {
			unset( $component_type['created_at'] );
			$result = $wpdb->update(
				$component_types_table,
				$component_type,
				array( 'id' => $component_type['id'] )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->insert( $component_types_table, $component_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}

	/**
	 * Get all available section types from database
	 *
	 * @return array Array of section types.
	 */
	public static function get_section_types() {
		global $wpdb;

		$section_types_table = $wpdb->prefix . 'spider_boxes_section_types';

		$results = $wpdb->get_results(
			"SELECT * FROM $section_types_table WHERE is_active = 1 ORDER BY  name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		);

		if ( ! $results ) {
			return array();
		}

		// Decode supports JSON for each section type.
		foreach ( $results as &$section_type ) {
			$section_type['supports'] = json_decode( $section_type['supports'], true );
		}

		return $results;
	}

	/**
	 * Register a new section type
	 *
	 * @param array $section_type Section type configuration.
	 * @return bool Success status.
	 */
	public static function register_section_type( $section_type ) {
		global $wpdb;

		$section_types_table = $wpdb->prefix . 'spider_boxes_section_types';

		// Validate required fields.
		$required_fields = array( 'type', 'name' );
		foreach ( $required_fields as $field ) {
			if ( empty( $section_type[ $field ] ) ) {
				return false;
			}
		}

		// Set defaults.
		$section_type = wp_parse_args(
			$section_type,
			array(

				'icon'        => 'section',
				'description' => '',
				'supports'    => maybe_serialize( array() ),
				'is_active'   => 1,

			)
		);

		// Encode supports if it's an array.
		if ( is_array( $section_type['supports'] ) ) {
			$section_type['supports'] = maybe_serialize( $section_type['supports'] );
		}

		// Insert or update.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $section_types_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$section_type['id']
			)
		);

		if ( $existing ) {
			unset( $section_type['created_at'] );
			$result = $wpdb->update(
				$section_types_table,
				$section_type,
				array( 'id' => $section_type['id'] )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->insert( $section_types_table, $section_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}
}
