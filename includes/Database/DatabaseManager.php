<?php
/**
 * Database Manager Class
 *
 * @package SpiderBoxes\Database
 */

namespace SpiderBoxes\Database;

use SpiderBoxes\Database\Repositories\FieldRepository;
use SpiderBoxes\Database\Repositories\FieldTypeRepository;
use SpiderBoxes\Database\Repositories\ComponentRepository;
use SpiderBoxes\Database\Repositories\ComponentTypeRepository;
use SpiderBoxes\Database\Repositories\SectionRepository;
use SpiderBoxes\Database\Repositories\SectionTypeRepository;


/**
 * Database Manager - Main coordinator for all database operations
 */
class DatabaseManager {

	/**
	 * DB Version for custom tables
	 *
	 * @var string
	 */
	protected static $db_table_version = '1.1.5';

	/**
	 * Repository instances
	 *
	 * @var array
	 */
	private static $repositories = array();

	/**
	 * Get repository instance
	 *
	 * @param string $repository Repository name.
	 * @return object
	 */
	public static function get_repository( $repository ) {
		if ( ! isset( self::$repositories[ $repository ] ) ) {
			$class_map = array(
				'field'      => FieldRepository::class,
				'field_type' => FieldTypeRepository::class,
				// 'component'      => ComponentRepository::class,
				// 'component_type' => ComponentTypeRepository::class,
				// 'section'        => SectionRepository::class,
				// 'section_type'   => SectionTypeRepository::class,
			);

			if ( ! isset( $class_map[ $repository ] ) ) {
				throw new \Exception( "Repository '{$repository}' not found." );
			}

			self::$repositories[ $repository ] = new $class_map[ $repository ]();
		}

		return self::$repositories[ $repository ];
	}

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
			type varchar(255) NOT NULL,
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
			type varchar(255) NOT NULL,
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
			type varchar(255) NOT NULL,
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

	// ========================================
	// Field Operations (delegated to FieldRepository)
	// ========================================

	/**
	 * Get all field types
	 */
	public static function get_field_types() {
		return self::get_repository( 'field_type' )->get_with_registry();
	}

	/**
	 * Get database field types
	 */
	public static function get_db_field_types() {
		return self::get_repository( 'field_type' )->get_active();
	}

	/**
	 * Register field type
	 */
	public static function register_field_type( $field_type ) {
		return self::get_repository( 'field_type' )->register( $field_type );
	}

	/**
	 * Delete field type
	 */
	public static function delete_field_type( $id ) {
		return self::get_repository( 'field_type' )->delete( $id );
	}

	/**
	 * Save field configuration
	 */
	public static function save_field_config( $field_id, $field_config ) {
		return self::get_repository( 'field' )->save_config( $field_id, $field_config );
	}

	/**
	 * Get field configuration
	 */
	public static function get_field_config( $field_id ) {
		return self::get_repository( 'field' )->get_config( $field_id );
	}

	/**
	 * Get field configuration by name
	 */
	public static function get_field_config_by_name( $name ) {
		return self::get_repository( 'field' )->find_by_name( $name );
	}

	/**
	 * Get all fields
	 */
	public static function get_all_fields( $context = '' ) {
		$filters = array();
		if ( ! empty( $context ) ) {
			$filters['context'] = $context;
		}
		return self::get_repository( 'field' )->all( $filters );
	}

	/**
	 * Delete field configuration
	 */
	public static function delete_field_config( $field_id ) {
		return self::get_repository( 'field' )->delete_with_meta( $field_id );
	}

	/**
	 * Delete field meta
	 */
	public static function delete_field_meta( $meta_key, $context = '' ) {
		return self::get_repository( 'meta' )->delete_by_meta_key( $meta_key, $context );
	}


	// ========================================
	// Component Operations (delegated to ComponentRepository)
	// ========================================

	/**
	 * Get component types
	 */
	public static function get_component_types() {
		return self::get_repository( 'component_type' )->get_active();
	}

	/**
	 * Register component type
	 */
	public static function register_component_type( $component_type ) {
		return self::get_repository( 'component_type' )->register( $component_type );
	}

	/**
	 * Delete component type
	 */
	public static function delete_component_type( $id ) {
		return self::get_repository( 'component_type' )->delete( $id );
	}

	/**
	 * Save component configuration
	 */
	public static function save_component_config( $component_id, $component_config ) {
		return self::get_repository( 'component' )->save_config( $component_id, $component_config );
	}

	/**
	 * Get component configuration
	 */
	public static function get_component_config( $component_id ) {
		return self::get_repository( 'component' )->get_config( $component_id );
	}

	/**
	 * Get all components
	 */
	public static function get_all_components( $context = '' ) {
		$filters = array();
		if ( ! empty( $context ) ) {
			$filters['context'] = $context;
		}
		return self::get_repository( 'component' )->all( $filters );
	}

	/**
	 * Delete component configuration
	 */
	public static function delete_component_config( $component_id ) {
		return self::get_repository( 'component' )->delete( $component_id );
	}

	// ========================================
	// Section Operations (delegated to SectionRepository)
	// ========================================

	/**
	 * Get section types
	 */
	public static function get_section_types() {
		return self::get_repository( 'section_type' )->get_active();
	}

	/**
	 * Register section type
	 */
	public static function register_section_type( $section_type ) {
		return self::get_repository( 'section_type' )->register( $section_type );
	}

	/**
	 * Delete section type
	 */
	public static function delete_section_type( $id ) {
		return self::get_repository( 'section_type' )->delete( $id );
	}

	/**
	 * Save section configuration
	 */
	public static function save_section_config( $section_id, $section_config ) {
		return self::get_repository( 'section' )->save_config( $section_id, $section_config );
	}

	/**
	 * Get section configuration
	 */
	public static function get_section_config( $section_id ) {
		return self::get_repository( 'section' )->get_config( $section_id );
	}

	/**
	 * Get all sections
	 */
	public static function get_all_sections( $context = '', $screen = '' ) {
		$filters = array();
		if ( ! empty( $context ) ) {
			$filters['context'] = $context;
		}
		if ( ! empty( $screen ) ) {
			$filters['screen'] = $screen;
		}
		return self::get_repository( 'section' )->all( $filters );
	}

	/**
	 * Delete section configuration
	 */
	public static function delete_section_config( $section_id ) {
		return self::get_repository( 'section' )->delete( $section_id );
	}

	// ========================================
	// Legacy/Compatibility Methods
	// ========================================

	/**
	 * Validate field configuration data
	 */
	public static function validate_field_config( $field_config ) {
		// Keep existing validation logic or move to Field model
		return $field_config;
	}

	/**
	 * Sanitize field configuration data
	 */
	public static function sanitize_field_config( $field_config ) {
		// Keep existing sanitization logic or move to Field model
		return $field_config;
	}
}
