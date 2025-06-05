<?php
/**
 * Main Spider Boxes Plugin Class
 *
 * @package SpiderBoxes
 */

namespace SpiderBoxes;

use DI\Container;
use DI\ContainerBuilder;
use SpiderBoxes\Admin\AdminPages;
use SpiderBoxes\Core\FieldRegistry;
use SpiderBoxes\Core\ComponentRegistry;
use SpiderBoxes\Core\SectionRegistry;
use SpiderBoxes\API\RestRoutes;
use SpiderBoxes\WooCommerce\ReviewsManager;
use SpiderBoxes\Database\DatabaseManager;

/**
 * Main plugin class
 */
class SpiderBoxes {

	/**
	 * Single instance of the class
	 *
	 * @var SpiderBoxes|null
	 */
	private static $instance = null;

	/**
	 * DI Container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Get single instance
	 *
	 * @return SpiderBoxes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->setup_container();
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Setup DI Container
	 */
	private function setup_container() {
		$builder = new ContainerBuilder();

		// Add definitions with both full class names and simple aliases
		$builder->addDefinitions(
			array(
				// Full class names
				FieldRegistry::class     => \DI\autowire(),
				ComponentRegistry::class => \DI\autowire(),
				SectionRegistry::class   => \DI\autowire(),
				AdminPages::class        => \DI\autowire(),
				RestRoutes::class        => \DI\autowire(),
				ReviewsManager::class    => \DI\autowire(),

				// Simple aliases for easier access
				'fieldRegistry'          => \DI\get( FieldRegistry::class ),
				'componentRegistry'      => \DI\get( ComponentRegistry::class ),
				'sectionRegistry'        => \DI\get( SectionRegistry::class ),
				'adminPages'             => \DI\get( AdminPages::class ),
				'restRoutes'             => \DI\get( RestRoutes::class ),
				'reviewsManager'         => \DI\get( ReviewsManager::class ),
			)
		);

		$this->container = $builder->build();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );

		// Plugin activation/deactivation hooks
		add_action( 'spider_boxes_activation', array( $this, 'on_activation' ) );
		add_action( 'spider_boxes_deactivation', array( $this, 'on_deactivation' ) );
	}

	/**
	 * Initialize components
	 */
	private function init_components() {
		try {
			// Initialize core registries
			$this->container->get( FieldRegistry::class );
			$this->container->get( ComponentRegistry::class );
			$this->container->get( SectionRegistry::class );

			// Initialize admin pages
			$this->container->get( AdminPages::class );

			// Initialize WooCommerce integration if WooCommerce is active
			if ( class_exists( 'WooCommerce' ) ) {
				$this->container->get( ReviewsManager::class );
			}
		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes initialization error: ' . $e->getMessage() );
		}
	}

	/**
	 * Plugin initialization
	 */
	public function init() {
		// Load text domain
		load_plugin_textdomain( 'spider-boxes', false, dirname( SPIDER_BOXES_PLUGIN_BASENAME ) . '/languages' );

		// Allow developers to hook into plugin initialization
		do_action( 'spider_boxes_init', $this );
	}
	/**
	 * Initialize REST API routes
	 */
	public function init_rest_api() {
		try {
			$rest_routes = $this->container->get( RestRoutes::class );
			error_log( 'Spider Boxes: RestRoutes initialized successfully' );
		} catch ( \Exception $e ) {
			error_log( 'Spider Boxes: Failed to initialize RestRoutes - ' . $e->getMessage() );
		}
	}
	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_frontend_scripts() {
		$script_asset_path = SPIDER_BOXES_PLUGIN_DIR . 'assets/dist/frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path ) ? require $script_asset_path : array(
			'dependencies' => array(),
			'version'      => SPIDER_BOXES_VERSION,
		);

		wp_enqueue_script(
			'spider-boxes-frontend',
			SPIDER_BOXES_PLUGIN_URL . 'assets/dist/frontend.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Add module attribute for ES modules
		add_filter( 'script_loader_tag', array( $this, 'add_module_to_script' ), 10, 3 );

		wp_enqueue_style(
			'spider-boxes-frontend',
			SPIDER_BOXES_PLUGIN_URL . 'assets/dist/frontend.css',
			array(),
			$script_asset['version']
		);

		// Localize script with REST API data
		wp_localize_script(
			'spider-boxes-frontend',
			'spiderBoxes',
			array(
				'restUrl' => rest_url( 'spider-boxes/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}
	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Load on Spider Boxes admin pages and WooCommerce reviews page.
		$allowed_pages = array( 'spider-boxes', 'wc-reviews', 'product_page_wc-reviews' );
		$should_load   = false;

		foreach ( $allowed_pages as $page ) {
			if ( strpos( $hook_suffix, $page ) !== false ) {
				$should_load = true;
				break;
			}
		}

		if ( ! $should_load ) {
			return;
		}

		$script_asset_path = SPIDER_BOXES_PLUGIN_DIR . 'assets/dist/admin.asset.php';
		$script_asset      = file_exists( $script_asset_path ) ? require $script_asset_path : array(
			'dependencies' => array(),
			'version'      => SPIDER_BOXES_VERSION,
		);

		wp_enqueue_script(
			'spider-boxes-admin',
			SPIDER_BOXES_PLUGIN_URL . 'assets/dist/admin.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		// Add module attribute for ES modules.
		add_filter( 'script_loader_tag', array( $this, 'add_module_to_script' ), 10, 3 );

		wp_enqueue_style(
			'spider-boxes-admin',
			SPIDER_BOXES_PLUGIN_URL . 'assets/dist/admin.css',
			array(),
			$script_asset['version']
		);

		// Localize script with admin data.
		wp_localize_script(
			'spider-boxes-admin',
			'spiderBoxesAdmin',
			array(
				'restUrl'   => rest_url( 'spider-boxes/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'pluginUrl' => SPIDER_BOXES_PLUGIN_URL,
			)
		);
	}

	/**
	 * Get DI Container
	 *
	 * @return Container
	 */
	public function get_container() {
		return $this->container;
	}
	/**
	 * Plugin activation callback
	 */
	public function on_activation() {
		// Create custom database tables if needed.
		DatabaseManager::create_tables();

		// Set default options.
		$this->set_default_options();

		// Clear rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback
	 */
	public function on_deactivation() {
		// Clear rewrite rules
		flush_rewrite_rules();
	}




	/**
	 * Set default options
	 */
	private function set_default_options() {
		$default_options = array(
			'spider_boxes_version'    => SPIDER_BOXES_VERSION,
			'spider_boxes_db_version' => '1.0.0',
		);
		foreach ( $default_options as $option_name => $option_value ) {
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $option_value );
			}
		}
	}

	/**
	 * Add module attribute to ES module scripts
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle The script handle.
	 * @param string $src    The script source URL.
	 * @return string Modified script tag.
	 */
	public function add_module_to_script( $tag, $handle, $src ) {
		// Add type="module" for our ES module scripts.
		if ( 'spider-boxes-admin' === $handle || 'spider-boxes-frontend' === $handle ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}       return $tag;
	}
}
