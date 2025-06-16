<?php
/**
 * Field Registry
 *
 * @package SpiderBoxes\Core
 */

namespace SpiderBoxes\Core;

use Illuminate\Support\Collection;
use SpiderBoxes\Database\Repositories\FieldRepository;
use SpiderBoxes\Database\Models\Field;

/**
 * Field Registry Class
 */
class FieldRegistry {

	/**
	 * Registered field types
	 *
	 * @var Collection
	 */
	private $field_types;

	/**
	 * Registered fields
	 *
	 * @var Collection
	 */
	private $fields;


	/**
	 * Field Repository.
	 */
	private $field_repository;

	/**
	 * Constructor
	 */
	public function __construct( FieldRepository $field_repository ) {
		$this->field_types      = new Collection();
		$this->fields           = new Collection();
		$this->field_repository = $field_repository;

		$this->register_default_field_types();
		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		// Allow developers to register custom field types
		do_action( 'spider_boxes_register_field_types', $this );

		// Allow developers to register fields
		do_action( 'spider_boxes_register_fields', $this );
	}

	/**
	 * Register default field types
	 */
	private function register_default_field_types() {
		$default_types = array(
			'button'       => array(
				'class_name' => 'SpiderBoxes\\Fields\\ButtonField',
				'supports'   => array( 'label', 'description', 'class', 'onclick' ),
			),
			'checkbox'     => array(
				'class_name' => 'SpiderBoxes\\Fields\\CheckboxField',
				'supports'   => array( 'label', 'description', 'options', 'multiple', 'value' ),
			),
			'media'        => array(
				'class_name' => 'SpiderBoxes\\Fields\\MediaField',
				'supports'   => array( 'label', 'description', 'multiple', 'mime_types', 'value' ),
			),
			'radio'        => array(
				'class_name' => 'SpiderBoxes\\Fields\\RadioField',
				'supports'   => array( 'label', 'description', 'options', 'value' ),
			),
			'repeater'     => array(
				'class_name' => 'SpiderBoxes\\Fields\\RepeaterField',
				'supports'   => array( 'label', 'description', 'fields', 'min', 'max', 'value' ),
			),
			'select'       => array(
				'class_name' => 'SpiderBoxes\\Fields\\SelectField',
				'supports'   => array( 'label', 'description', 'options', 'multiple', 'value' ),
			),
			'react-select' => array(
				'class_name' => 'SpiderBoxes\\Fields\\ReactSelectField',
				'supports'   => array( 'label', 'description', 'options', 'multiple', 'async', 'ajax_action', 'value' ),
			),
			'range'        => array(
				'class_name' => 'SpiderBoxes\\Fields\\RangeField',
				'supports'   => array( 'label', 'description', 'min', 'max', 'step', 'value' ),
			),
			'switcher'     => array(
				'class_name' => 'SpiderBoxes\\Fields\\SwitcherField',
				'supports'   => array( 'label', 'description', 'value' ),
			),
			'text'         => array(
				'class_name' => 'SpiderBoxes\\Fields\\TextField',
				'supports'   => array( 'label', 'description', 'placeholder', 'value' ),
			),
			'datetime'     => array(
				'class_name' => 'SpiderBoxes\\Fields\\DateTimeField',
				'supports'   => array( 'label', 'description', 'format', 'value' ),
			),
			'textarea'     => array(
				'class_name' => 'SpiderBoxes\\Fields\\TextareaField',
				'supports'   => array( 'label', 'description', 'placeholder', 'rows', 'value' ),
			),
			'wysiwyg'      => array(
				'class_name' => 'SpiderBoxes\\Fields\\WysiwygField',
				'supports'   => array( 'label', 'description', 'settings', 'value' ),
			),
		);

		foreach ( $default_types as $type => $config ) {
			$this->register_field_type( $type, $config );
		}
	}

	/**
	 * Register a field type
	 *
	 * @param string $type Field type name
	 * @param array  $config Field type configuration
	 * @return bool
	 */
	public function register_field_type( $type, $config ) {
		if ( $this->field_types->has( $type ) ) {
			return false;
		}

		$config = wp_parse_args(
			$config,
			array(
				'class'    => '',
				'supports' => array(),

			)
		);

		$this->field_types->put( $type, $config );

		/**
		 * Fires after a field type is registered
		 *
		 * @param string $type Field type name
		 * @param array $config Field type configuration
		 */
		do_action( 'spider_boxes_field_type_registered', $type, $config );

		return true;
	}

	/**
	 * Register a field
	 *
	 * @param string $id Field ID
	 * @param array  $args Field arguments
	 * @return bool
	 */
	public function register_field( $id, $args ) {
		if ( $this->fields->has( $id ) ) {
			return false;
		}

		$defaults = array(
			'type'        => 'text',
			'parent'      => '',
			'title'       => '',
			'description' => '',
			'show_tip'    => true,
			'value'       => '',
			'class'       => '',
			'label'       => '',
			'context'     => 'default',

		);

		$args = wp_parse_args( $args, $defaults );

		// Validate field type
		if ( ! $this->field_types->has( $args['type'] ) ) {
			return false;
		}

		$this->fields->put( $id, $args );

		/**
		 * Fires after a field is registered
		 *
		 * @param string $id Field ID
		 * @param array $args Field arguments
		 */
		do_action( 'spider_boxes_field_registered', $id, $args );

		return true;
	}

	/**
	 * Get all fields (registry + database)
	 */
	public function get_all_fields( $parent = '', $context = '' ) {
		// Get registry fields
		$registry_fields = $this->get_fields( $parent );

		// Get database fields
		$criteria = array();

		if ( ! empty( $parent ) ) {
			$criteria['parent'] = $parent;
		}

		if ( ! empty( $context ) ) {
			$criteria['context'] = $context;
		}

		$db_fields = $this->field_repository->all_as_models( $criteria );

		// Merge them - database fields take precedence
		$all_fields = new Collection();

		// Add registry fields first
		foreach ( $registry_fields as $id => $field_config ) {
			$all_fields->put( $id, Field::from_registry( $id, $field_config ) );
		}

		// Add/override with database fields
		foreach ( $db_fields as $db_field ) {
			$all_fields->put( $db_field->get_attribute( 'id' ), $db_field );
		}

		return apply_filters( 'spider_boxes_get_all_fields', $all_fields, $parent, $context );
	}

	/**
	 * Create field in database from visual builder
	 */
	public function create_database_field( $field_data ) {
		$field = Field::create( $field_data );

		if ( ! $field->is_valid() ) {
			return new \WP_Error( 'validation_failed', 'Field validation failed', $field->get_validation_errors() );
		}

		if ( $field->save() ) {
			do_action( 'spider_boxes_database_field_created', $field );
			return $field;
		}

		return new \WP_Error( 'save_failed', 'Failed to save field to database' );
	}

		/**
		 * Check if field exists in either registry or database
		 */
	public function field_exists_anywhere( $id ) {
		return $this->field_exists( $id ) || $this->field_repository->find( $id ) !== null;
	}

	/**
	 * Get registered field types
	 *
	 * @return Collection
	 */
	public function get_field_types() {
		return apply_filters( 'spider_boxes_get_field_types', $this->field_types );
	}

	/**
	 * Get all field types (combined from registry and database)
	 *
	 * @return array Array of field types with database overrides
	 */
	public function get_all_field_types() {
		$registry_field_types = $this->field_types;
		$db_field_types       = \SpiderBoxes\Database\DatabaseManager::get_db_field_types();

		// Combine both sources - registry types with database overrides
		$combined_field_types = array();

		// First add all registry types (these represent available field classes)
		foreach ( $registry_field_types as $type => $config ) {
			$combined_field_types[] = array(
				'id'          => $type,
				'name'        => ucfirst( str_replace( '_', ' ', $type ) ),
				'type'        => $type,
				'class_name'  => $config['class_name'] ?? '',

				'description' => $config['description'] ?? '',
				'supports'    => $config['supports'] ?? array(),
				'meta_field'  => $config['meta_field'] ?? false,
				'is_active'   => true,

			);
		}

		// Then merge/override with database types (these can override or add custom types)
		$db_types_by_id = array();
		foreach ( $db_field_types as $db_type ) {
			$db_types_by_id[ $db_type['type'] ] = array_merge(
				$db_type,
				array(
					'id'         => $db_type['type'],
					'type'       => $db_type['type'],
					'name'       => ucfirst( str_replace( '_', ' ', $db_type['type'] ) ),
					'meta_field' => false,
				)
			);
		}

		// Update registry types with database data if exists
		foreach ( $combined_field_types as &$type ) {
			if ( isset( $db_types_by_id[ $type['id'] ] ) ) {
				$db_type = $db_types_by_id[ $type['id'] ];
				$type    = array_merge( $type, $db_type );
				unset( $db_types_by_id[ $type['id'] ] );
			}
		}

		// Add any remaining database-only types
		foreach ( $db_types_by_id as $db_type ) {
			$combined_field_types[] = $db_type;
		}

		// Filter only active types and sort
		$active_field_types = array_filter(
			$combined_field_types,
			function ( $type ) {
				return $type['is_active'] ?? true;
			}
		);

		// Sort by sort_order then by name
		usort(
			$active_field_types,
			function ( $a, $b ) {
				$sort_a = $a['sort_order'] ?? 0;
				$sort_b = $b['sort_order'] ?? 0;
				if ( $sort_a === $sort_b ) {
					return strcmp( $a['name'] ?? '', $b['name'] ?? '' );
				}
				return $sort_a <=> $sort_b;
			}
		);

		return apply_filters( 'spider_boxes_get_all_field_types', $active_field_types );
	}

	/**
	 * Get a specific field type
	 *
	 * @param string $type Field type name
	 * @return array|null
	 */
	public function get_field_type( $type ) {
		return $this->field_types->get( $type );
	}

	/**
	 * Get registered fields
	 *
	 * @return Collection
	 */
	public function get_fields() {
		$fields = $this->fields;

		return apply_filters( 'spider_boxes_get_fields', $fields );
	}

	/**
	 * Get a specific field
	 *
	 * @param string $id Field ID
	 * @return array|null
	 */
	public function get_field( $id ) {
		return $this->fields->get( $id );
	}

	/**
	 * Remove a field
	 *
	 * @param string $id Field ID
	 * @return bool
	 */
	public function remove_field( $id ) {
		if ( ! $this->fields->has( $id ) ) {
			return false;
		}

		$this->fields->forget( $id );

		/**
		 * Fires after a field is removed
		 *
		 * @param string $id Field ID
		 */
		do_action( 'spider_boxes_field_removed', $id );

		return true;
	}

	/**
	 * Check if field type exists
	 *
	 * @param string $type Field type name
	 * @return bool
	 */
	public function field_type_exists( $type ) {
		return $this->field_types->has( $type );
	}

	/**
	 * Check if field exists
	 *
	 * @param string $id Field ID
	 * @return bool
	 */
	public function field_exists( $id ) {
		return $this->fields->has( $id );
	}
}
