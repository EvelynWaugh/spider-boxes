<?php
/**
 * Database Manager Class
 *
 * @package SpiderBoxes\Database
 */

namespace SpiderBoxes\Database;

/**
 * Handles database operations for Spider Boxes
 */
class DatabaseManager {
	/**
	 * DB Version for custom tables
	 *
	 * @var string
	 */
	protected static $db_table_version = '1.1.0';

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
		// Field configurations table.
		$fields_table = $wpdb->prefix . 'spider_boxes_fields';
		$fields_sql   = "CREATE TABLE $fields_table (
			id varchar(255) NOT NULL,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			parent varchar(255) DEFAULT '',
			context varchar(50) DEFAULT 'default',
			value longtext,
			settings longtext,
			capability varchar(100) DEFAULT 'manage_options',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY parent (parent),
			KEY context (context)
		) $charset_collate;";

		// Field values/meta table for storing field data values.
		$meta_table        = $wpdb->prefix . 'spider_boxes_meta';
		$meta_sql          = "CREATE TABLE $meta_table (
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
		) $charset_collate;";       // Field types table for registered field types.
		$field_types_table = $wpdb->prefix . 'spider_boxes_field_types';
		$field_types_sql   = "CREATE TABLE $field_types_table (
			id varchar(255) NOT NULL,
			name varchar(255) NOT NULL,
			class_name varchar(255) NOT NULL,
			category varchar(100) DEFAULT 'general',
			icon varchar(100) DEFAULT 'component',
			description text,
			supports longtext,
			is_active tinyint(1) DEFAULT 1,
			sort_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY class_name (class_name),
			KEY category (category),
			KEY is_active (is_active),
			KEY sort_order (sort_order)
		) $charset_collate;";

		// Components table for component configurations.
		$components_table = $wpdb->prefix . 'spider_boxes_components';
		$components_sql   = "CREATE TABLE $components_table (
			id varchar(255) NOT NULL,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			parent_id varchar(255) DEFAULT '',
			section_id varchar(255) DEFAULT '',
			context varchar(50) DEFAULT 'default',
			settings longtext,
			children longtext,
			sort_order int(11) DEFAULT 0,
			is_active tinyint(1) DEFAULT 1,
			capability varchar(100) DEFAULT 'manage_options',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY parent_id (parent_id),
			KEY section_id (section_id),
			KEY context (context),
			KEY sort_order (sort_order),
			KEY is_active (is_active)
		) $charset_collate;";

		// Sections table for section configurations.
		$sections_table = $wpdb->prefix . 'spider_boxes_sections';
		$sections_sql   = "CREATE TABLE $sections_table (
			id varchar(255) NOT NULL,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			context varchar(50) DEFAULT 'default',
			screen varchar(100) DEFAULT '',
			settings longtext,
			components longtext,
			sort_order int(11) DEFAULT 0,
			is_active tinyint(1) DEFAULT 1,
			capability varchar(100) DEFAULT 'manage_options',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY context (context),
			KEY screen (screen),
			KEY sort_order (sort_order),
			KEY is_active (is_active)
		) $charset_collate;";
		if ( version_compare( $db_version, self::$db_table_version, '<' ) ) {
			dbDelta( $fields_sql );
			dbDelta( $meta_sql );
			dbDelta( $field_types_sql );
			dbDelta( $components_sql );
			dbDelta( $sections_sql );
			// Insert default field types.

			update_option( 'spider_boxes_db_version', self::$db_table_version );
		}
	}

	/**
	 * Get all available field types from database
	 *
	 * @return array Array of field types.
	 */
	public static function get_field_types() {
		global $wpdb;

		$field_types_table = $wpdb->prefix . 'spider_boxes_field_types';

		$results = $wpdb->get_results(
			"SELECT * FROM $field_types_table WHERE is_active = 1 ORDER BY sort_order ASC, name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		);

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
	 * Register a new field type
	 *
	 * @param array $field_type Field type configuration.
	 * @return bool Success status.
	 */
	public static function register_field_type( $field_type ) {
		global $wpdb;

		$field_types_table = $wpdb->prefix . 'spider_boxes_field_types';

		// Validate required fields.
		$required_fields = array( 'id', 'name', 'class_name' );
		foreach ( $required_fields as $field ) {
			if ( empty( $field_type[ $field ] ) ) {
				return false;
			}
		}

		// Set defaults.
		$field_type = wp_parse_args(
			$field_type,
			array(
				'category'    => 'general',
				'icon'        => 'component',
				'description' => '',
				'supports'    => wp_json_encode( array() ),
				'is_active'   => 1,
				'sort_order'  => 0,
			)
		);

		// Encode supports if it's an array.
		if ( is_array( $field_type['supports'] ) ) {
			$field_type['supports'] = wp_json_encode( $field_type['supports'] );
		}

		// Insert or update.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $field_types_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$field_type['id']
			)
		);

		if ( $existing ) {
			unset( $field_type['created_at'] );
			$result = $wpdb->update(
				$field_types_table,
				$field_type,
				array( 'id' => $field_type['id'] )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->insert( $field_types_table, $field_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}

	/**
	 * Save field configuration to database
	 *
	 * @param string $field_id Field ID.
	 * @param array  $field_config Field configuration.
	 * @return bool Success status.
	 */
	public static function save_field_config( $field_id, $field_config ) {
		global $wpdb;

		$fields_table = $wpdb->prefix . 'spider_boxes_fields';

		// Prepare data for insertion.
		$data = array(
			'id'          => $field_id,
			'type'        => $field_config['type'] ?? '',
			'title'       => $field_config['title'] ?? '',
			'description' => $field_config['description'] ?? '',
			'parent'      => $field_config['parent'] ?? '',
			'context'     => $field_config['context'] ?? 'default',
			'value'       => maybe_serialize( $field_config['value'] ?? '' ),
			'settings'    => wp_json_encode( $field_config ),
			'capability'  => $field_config['capability'] ?? 'manage_options',
		);

		// Check if field exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $fields_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$field_id
			)
		);

		if ( $existing ) {
			unset( $data['created_at'] );
			$result = $wpdb->update(
				$fields_table,
				$data,
				array( 'id' => $field_id )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$result = $wpdb->insert( $fields_table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return false !== $result;
	}

	/**
	 * Get field configuration from database
	 *
	 * @param string $field_id Field ID.
	 * @return array|null Field configuration or null if not found.
	 */
	public static function get_field_config( $field_id ) {
		global $wpdb;

		$fields_table = $wpdb->prefix . 'spider_boxes_fields';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $fields_table WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$field_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $result ) {
			return null;
		}

		// Decode JSON settings.
		$result['settings'] = json_decode( $result['settings'], true );
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
		global $wpdb;

		$fields_table = $wpdb->prefix . 'spider_boxes_fields';

		$where_conditions = array();
		$prepare_values   = array();

		if ( ! empty( $parent ) ) {
			$where_conditions[] = 'parent = %s';
			$prepare_values[]   = $parent;
		}

		if ( ! empty( $context ) ) {
			$where_conditions[] = 'context = %s';
			$prepare_values[]   = $context;
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		$sql = "SELECT * FROM $fields_table $where_clause ORDER BY created_at ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $prepare_values ) ) {
			$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $results ) {
			return array();
		}

		// Decode JSON settings and unserialize values for each field.
		foreach ( $results as &$field ) {
			$field['settings'] = json_decode( $field['settings'], true );
			$field['value']    = maybe_unserialize( $field['value'] );
		}

		return $results;
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
		$required_fields = array( 'id', 'type', 'title' );
		foreach ( $required_fields as $field ) {
			if ( empty( $field_config[ $field ] ) ) {
				$errors[] = sprintf( 'Missing required field: %s', $field );
			}
		}

		// Validate field ID format.
		if ( ! empty( $field_config['id'] ) && ! preg_match( '/^[a-zA-Z0-9_-]+$/', $field_config['id'] ) ) {
			$errors[] = 'Field ID can only contain letters, numbers, underscores, and hyphens';
		}
		// Check if field type is registered.
		if ( ! empty( $field_config['type'] ) ) {
			$field_types      = self::get_field_types();
			$registered_types = wp_list_pluck( $field_types, 'id' );
			if ( ! in_array( $field_config['type'], $registered_types, true ) ) {
				$errors[] = sprintf( 'Invalid field type: %s', $field_config['type'] );
			}
		}

		// Validate capability.
		if ( ! empty( $field_config['capability'] ) && ! current_user_can( $field_config['capability'] ) ) {
			$errors[] = 'You do not have permission to create this field';
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', 'Field validation failed', array( 'errors' => $errors ) );
		}

		// Set defaults for optional fields.
		$defaults = array(
			'description' => '',
			'parent'      => '',
			'context'     => 'default',
			'value'       => '',
			'capability'  => 'manage_options',
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

		// Sanitize capability.
		if ( isset( $field_config['capability'] ) ) {
			$sanitized['capability'] = sanitize_key( $field_config['capability'] );
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
			'parent_id'   => $component_config['parent_id'] ?? '',
			'section_id'  => $component_config['section_id'] ?? '',
			'context'     => $component_config['context'] ?? 'default',
			'settings'    => wp_json_encode( $component_config['settings'] ?? array() ),
			'children'    => wp_json_encode( $component_config['children'] ?? array() ),
			'sort_order'  => $component_config['sort_order'] ?? 0,
			'is_active'   => $component_config['is_active'] ?? 1,
			'capability'  => $component_config['capability'] ?? 'manage_options',
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

		// Decode JSON settings and children.
		$result['settings'] = json_decode( $result['settings'], true );
		$result['children'] = json_decode( $result['children'], true );

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

		$sql = "SELECT * FROM $components_table $where_clause ORDER BY sort_order ASC, created_at ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $prepare_values ) ) {
			$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $results ) {
			return array();
		}

		// Decode JSON settings and children for each component.
		foreach ( $results as &$component ) {
			$component['settings'] = json_decode( $component['settings'], true );
			$component['children'] = json_decode( $component['children'], true );
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
			'settings'    => wp_json_encode( $section_config['settings'] ?? array() ),
			'components'  => wp_json_encode( $section_config['components'] ?? array() ),
			'sort_order'  => $section_config['sort_order'] ?? 0,
			'is_active'   => $section_config['is_active'] ?? 1,
			'capability'  => $section_config['capability'] ?? 'manage_options',
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
		$result['settings']   = json_decode( $result['settings'], true );
		$result['components'] = json_decode( $result['components'], true );

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

		$sql = "SELECT * FROM $sections_table $where_clause ORDER BY sort_order ASC, created_at ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $prepare_values ) ) {
			$sql = $wpdb->prepare( $sql, $prepare_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $results ) {
			return array();
		}

		// Decode JSON settings and components for each section.
		foreach ( $results as &$section ) {
			$section['settings']   = json_decode( $section['settings'], true );
			$section['components'] = json_decode( $section['components'], true );
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
}
