<?php
/**
 * Component Registry
 *
 * @package SpiderBoxes\Core
 */

namespace SpiderBoxes\Core;

use Illuminate\Support\Collection;

/**
 * Component Registry Class
 */
class ComponentRegistry {

	/**
	 * Registered component types
	 *
	 * @var Collection
	 */
	private $component_types;

	/**
	 * Registered components
	 *
	 * @var Collection
	 */
	private $components;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->component_types = new Collection();
		$this->components      = new Collection();

		$this->register_default_component_types();
		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		// Allow developers to register custom component types
		do_action( 'spider_boxes_register_component_types', $this );

		// Allow developers to register components
		do_action( 'spider_boxes_register_components', $this );
	}

	/**
	 * Register default component types
	 */
	private function register_default_component_types() {
		$default_types = array(
			'accordion' => array(
				'class'    => 'SpiderBoxes\\Components\\AccordionComponent',
				'supports' => array( 'title', 'description', 'fields', 'collapsed' ),
			),
			'tab'       => array(
				'class'    => 'SpiderBoxes\\Components\\TabComponent',
				'supports' => array( 'title', 'icon', 'fields', 'active' ),
			),
			'row'       => array(
				'class'    => 'SpiderBoxes\\Components\\RowComponent',
				'supports' => array( 'columns', 'gap', 'align' ),
			),
			'column'    => array(
				'class'    => 'SpiderBoxes\\Components\\ColumnComponent',
				'supports' => array( 'width', 'fields', 'align' ),
			),
		);

		foreach ( $default_types as $type => $config ) {
			$this->register_component_type( $type, $config );
		}
	}

	/**
	 * Register a component type
	 *
	 * @param string $type Component type name
	 * @param array  $config Component type configuration
	 * @return bool
	 */
	public function register_component_type( $type, $config ) {
		if ( $this->component_types->has( $type ) ) {
			return false;
		}

		$config = wp_parse_args(
			$config,
			array(
				'class'    => '',
				'supports' => array(),
				'category' => 'layout',
			)
		);

		$this->component_types->put( $type, $config );

		/**
		 * Fires after a component type is registered
		 *
		 * @param string $type Component type name
		 * @param array $config Component type configuration
		 */
		do_action( 'spider_boxes_component_type_registered', $type, $config );

		return true;
	}

	/**
	 * Register a component
	 *
	 * @param string $id Component ID
	 * @param array  $args Component arguments
	 * @return bool
	 */
	public function register_component( $id, $args ) {
		if ( $this->components->has( $id ) ) {
			return false;
		}

		$defaults = array(
			'type'        => 'accordion',
			'parent'      => '',
			'title'       => '',
			'description' => '',
			'fields'      => array(),
			'class'       => '',
			'context'     => 'default',
			'capability'  => 'manage_options',
			'order'       => 10,
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate component type
		if ( ! $this->component_types->has( $args['type'] ) ) {
			return false;
		}

		$this->components->put( $id, $args );

		/**
		 * Fires after a component is registered
		 *
		 * @param string $id Component ID
		 * @param array $args Component arguments
		 */
		do_action( 'spider_boxes_component_registered', $id, $args );

		return true;
	}

	/**
	 * Get registered component types
	 *
	 * @return Collection
	 */
	public function get_component_types() {
		return apply_filters( 'spider_boxes_get_component_types', $this->component_types );
	}

	/**
	 * Get a specific component type
	 *
	 * @param string $type Component type name
	 * @return array|null
	 */
	public function get_component_type( $type ) {
		return $this->component_types->get( $type );
	}

	/**
	 * Get registered components
	 *
	 * @param string $parent Optional. Parent section ID to filter by
	 * @return Collection
	 */
	public function get_components( $parent = '' ) {
		$components = $this->components;

		if ( ! empty( $parent ) ) {
			$components = $components->filter(
				function ( $component ) use ( $parent ) {
					return $component['parent'] === $parent;
				}
			);
		}

		// Sort by order
		$components = $components->sortBy( 'order' );

		return apply_filters( 'spider_boxes_get_components', $components, $parent );
	}

	/**
	 * Get a specific component
	 *
	 * @param string $id Component ID
	 * @return array|null
	 */
	public function get_component( $id ) {
		return $this->components->get( $id );
	}

	/**
	 * Remove a component
	 *
	 * @param string $id Component ID
	 * @return bool
	 */
	public function remove_component( $id ) {
		if ( ! $this->components->has( $id ) ) {
			return false;
		}

		$this->components->forget( $id );

		/**
		 * Fires after a component is removed
		 *
		 * @param string $id Component ID
		 */
		do_action( 'spider_boxes_component_removed', $id );

		return true;
	}

	/**
	 * Check if component type exists
	 *
	 * @param string $type Component type name
	 * @return bool
	 */
	public function component_type_exists( $type ) {
		return $this->component_types->has( $type );
	}

	/**
	 * Check if component exists
	 *
	 * @param string $id Component ID
	 * @return bool
	 */
	public function component_exists( $id ) {
		return $this->components->has( $id );
	}
}
