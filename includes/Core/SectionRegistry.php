<?php
/**
 * Section Registry
 *
 * @package SpiderBoxes\Core
 */

namespace SpiderBoxes\Core;

use Illuminate\Support\Collection;

/**
 * Section Registry Class
 */
class SectionRegistry {

	/**
	 * Registered section types
	 *
	 * @var Collection
	 */
	private $section_types;

	/**
	 * Registered sections
	 *
	 * @var Collection
	 */
	private $sections;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->section_types = new Collection();
		$this->sections      = new Collection();

		$this->register_default_section_types();
		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		// Allow developers to register custom section types
		do_action( 'spider_boxes_register_section_types', $this );

		// Allow developers to register sections
		do_action( 'spider_boxes_register_sections', $this );
	}

	/**
	 * Register default section types
	 */
	private function register_default_section_types() {
		$default_types = array(
			'section' => array(
				'class'    => 'SpiderBoxes\\Sections\\SectionType',
				'supports' => array( 'title', 'description', 'components', 'collapsible' ),
			),
			'form'    => array(
				'class'    => 'SpiderBoxes\\Sections\\FormType',
				'supports' => array( 'title', 'description', 'components', 'action', 'method' ),
			),
		);

		foreach ( $default_types as $type => $config ) {
			$this->register_section_type( $type, $config );
		}
	}

	/**
	 * Register a section type
	 *
	 * @param string $type Section type name
	 * @param array  $config Section type configuration
	 * @return bool
	 */
	public function register_section_type( $type, $config ) {
		if ( $this->section_types->has( $type ) ) {
			return false;
		}

		$config = wp_parse_args(
			$config,
			array(
				'class'    => '',
				'supports' => array(),
				'category' => 'general',
			)
		);

		$this->section_types->put( $type, $config );

		/**
		 * Fires after a section type is registered
		 *
		 * @param string $type Section type name
		 * @param array $config Section type configuration
		 */
		do_action( 'spider_boxes_section_type_registered', $type, $config );

		return true;
	}

	/**
	 * Register a section
	 *
	 * @param string $id Section ID
	 * @param array  $args Section arguments
	 * @return bool
	 */
	public function register_section( $id, $args ) {
		if ( $this->sections->has( $id ) ) {
			return false;
		}

		$defaults = array(
			'type'          => 'section',
			'title'         => '',
			'description'   => '',
			'components'    => array(),
			'context'       => 'default',
			'screen'        => '',

			'priority'      => 'default',
			'callback_args' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate section type
		if ( ! $this->section_types->has( $args['type'] ) ) {
			return false;
		}

		$this->sections->put( $id, $args );

		/**
		 * Fires after a section is registered
		 *
		 * @param string $id Section ID
		 * @param array $args Section arguments
		 */
		do_action( 'spider_boxes_section_registered', $id, $args );

		return true;
	}

	/**
	 * Get registered section types
	 *
	 * @return Collection
	 */
	public function get_section_types() {
		return apply_filters( 'spider_boxes_get_section_types', $this->section_types );
	}

	/**
	 * Get a specific section type
	 *
	 * @param string $type Section type name
	 * @return array|null
	 */
	public function get_section_type( $type ) {
		return $this->section_types->get( $type );
	}

	/**
	 * Get registered sections
	 *
	 * @param string $context Optional. Context to filter by
	 * @param string $screen Optional. Screen to filter by
	 * @return Collection
	 */
	public function get_sections( $context = '', $screen = '' ) {
		$sections = $this->sections;

		if ( ! empty( $context ) ) {
			$sections = $sections->filter(
				function ( $section ) use ( $context ) {
					return $section['context'] === $context;
				}
			);
		}

		if ( ! empty( $screen ) ) {
			$sections = $sections->filter(
				function ( $section ) use ( $screen ) {
					return empty( $section['screen'] ) || $section['screen'] === $screen;
				}
			);
		}

		return apply_filters( 'spider_boxes_get_sections', $sections, $context, $screen );
	}

	/**
	 * Get a specific section
	 *
	 * @param string $id Section ID
	 * @return array|null
	 */
	public function get_section( $id ) {
		return $this->sections->get( $id );
	}

	/**
	 * Remove a section
	 *
	 * @param string $id Section ID
	 * @return bool
	 */
	public function remove_section( $id ) {
		if ( ! $this->sections->has( $id ) ) {
			return false;
		}

		$this->sections->forget( $id );

		/**
		 * Fires after a section is removed
		 *
		 * @param string $id Section ID
		 */
		do_action( 'spider_boxes_section_removed', $id );

		return true;
	}

	/**
	 * Check if section type exists
	 *
	 * @param string $type Section type name
	 * @return bool
	 */
	public function section_type_exists( $type ) {
		return $this->section_types->has( $type );
	}

	/**
	 * Check if section exists
	 *
	 * @param string $id Section ID
	 * @return bool
	 */
	public function section_exists( $id ) {
		return $this->sections->has( $id );
	}
}
