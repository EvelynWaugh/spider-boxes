<?php
/**
 * REST API Routes Manager
 *
 * @package SpiderBoxes\API
 */

namespace SpiderBoxes\API;

use SpiderBoxes\API\Controllers\FieldTypeController;
use SpiderBoxes\API\Controllers\FieldController;
use SpiderBoxes\API\Controllers\ComponentTypeController;
use SpiderBoxes\API\Controllers\ComponentController;
use SpiderBoxes\API\Controllers\SectionTypeController;
use SpiderBoxes\API\Controllers\SectionController;
use SpiderBoxes\API\Controllers\ReviewController;

/**
 * REST Routes Manager Class
 */
class RestRoutes {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = 'spider-boxes/v1';

	/**
	 * Controllers
	 *
	 * @var array
	 */
	private $controllers = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_controllers();
		$this->register_routes();
	}

	/**
	 * Initialize controllers
	 */
	private function init_controllers() {
		$this->controllers = apply_filters(
			'spider_boxes_rest_controllers',
			array(
				'field_type'     => new FieldTypeController(),
				'field'          => new FieldController(),
				'component_type' => new ComponentTypeController(),
				'component'      => new ComponentController(),
				'section_type'   => new SectionTypeController(),
				'section'        => new SectionController(),
				'review'         => new ReviewController(),
			)
		);
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		do_action( 'spider_boxes_before_register_rest_routes', $this->namespace );

		// Register routes for each controller
		foreach ( $this->controllers as $name => $controller ) {
			if ( method_exists( $controller, 'register_routes' ) ) {
				$controller->register_routes();
				do_action( "spider_boxes_registered_{$name}_routes", $controller );
			}
		}

		do_action( 'spider_boxes_after_register_rest_routes', $this->namespace );
	}

	/**
	 * Get controller by name
	 *
	 * @param string $name Controller name
	 * @return object|null
	 */
	public function get_controller( $name ) {
		return $this->controllers[ $name ] ?? null;
	}

	/**
	 * Get all controllers
	 *
	 * @return array
	 */
	public function get_controllers() {
		return $this->controllers;
	}
}
