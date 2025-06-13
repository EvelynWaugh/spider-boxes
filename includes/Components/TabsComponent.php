<?php
/**
 * Tabs Container Component
 *
 * @package SpiderBoxes\Components
 */

namespace SpiderBoxes\Components;

/**
 * Tabs Container Component Class
 */
class TabsComponent {

	/**
	 * Component ID
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Component configuration
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param string $id Component ID.
	 * @param array  $config Component configuration.
	 */
	public function __construct( $id, $config = array() ) {
		$this->id     = $id;
		$this->config = wp_parse_args( $config, $this->get_defaults() );
	}

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	private function get_defaults() {
		return array(
			'type'  => 'tabs',
			'title' => '',
			'tabs'  => array(),
			'class' => '',
		);
	}
	/**
	 * Render component (tabs container)
	 *
	 * @param array $values Field values.
	 * @return string
	 */
	public function render( $values = array() ) {
		$title = $this->config['title'];
		$tabs  = $this->config['tabs'];
		$class = $this->config['class'];

		$tabs_class = 'spider-boxes-tabs';
		if ( ! empty( $class ) ) {
			$tabs_class .= ' ' . $class;
		}

		$content = '<div class="' . esc_attr( $tabs_class ) . '" data-component-id="' . esc_attr( $this->id ) . '">';

		// Container title
		if ( ! empty( $title ) ) {
			$content .= '<h3 class="spider-boxes-tabs-title">' . esc_html( $title ) . '</h3>';
		}

		// Tab navigation
		if ( ! empty( $tabs ) ) {
			$content .= '<div class="spider-boxes-tabs-nav">';
			foreach ( $tabs as $tab_id => $tab_config ) {
				$content .= $this->render_tab_nav( $tab_id, $tab_config );
			}
			$content .= '</div>';

			// Tab content
			$content .= '<div class="spider-boxes-tabs-content">';
			foreach ( $tabs as $tab_id => $tab_config ) {
				$content .= $this->render_tab_content( $tab_id, $tab_config, $values );
			}
			$content .= '</div>';
		}

		$content .= '</div>';

		// Enqueue tabs script
		$this->enqueue_tabs_script();

		return apply_filters( 'spider_boxes_tabs_render', $content, $this->id, $this->config, $values );
	}

	/**
	 * Render tab navigation
	 *
	 * @param string $tab_id Tab ID.
	 * @param array  $tab_config Tab configuration.
	 * @return string
	 */
	private function render_tab_nav( $tab_id, $tab_config ) {
		$component_registry = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\ComponentRegistry' );
		$tab_class_config   = $component_registry->get_component_type( 'tab' );

		if ( ! $tab_class_config || ! class_exists( $tab_class_config['class_name'] ) ) {
			return '<p>' . __( 'Tab component not found', 'spider-boxes' ) . '</p>';
		}

		$tab_instance = new $tab_class_config['class_name']( $tab_id, $tab_config );
		return $tab_instance->render_nav();
	}

	/**
	 * Render tab content
	 *
	 * @param string $tab_id Tab ID.
	 * @param array  $tab_config Tab configuration.
	 * @param array  $values Field values.
	 * @return string
	 */
	private function render_tab_content( $tab_id, $tab_config, $values ) {
		$component_registry = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\ComponentRegistry' );
		$tab_class_config   = $component_registry->get_component_type( 'tab' );

		if ( ! $tab_class_config || ! class_exists( $tab_class_config['class_name'] ) ) {
			return '<p>' . __( 'Tab component not found', 'spider-boxes' ) . '</p>';
		}

		$tab_instance = new $tab_class_config['class_name']( $tab_id, $tab_config );
		return $tab_instance->render( $values );
	}

	/**
	 * Enqueue tabs script
	 */
	private function enqueue_tabs_script() {
		add_action(
			'admin_footer',
			function () {
				?>
			<script>
			(function($) {
				$(document).on('click', '.spider-boxes-tab-nav-item', function(e) {
					e.preventDefault();
					
					const navItem = $(this);
					const tabsContainer = navItem.closest('.spider-boxes-tabs');
					const targetId = navItem.data('tab-target');
					
					// Remove active from all nav items
					tabsContainer.find('.spider-boxes-tab-nav-item').removeClass('spider-boxes-tab-nav-active');
					// Remove active from all tab content
					tabsContainer.find('.spider-boxes-tab-content').removeClass('spider-boxes-tab-active');
					
					// Add active to clicked nav item
					navItem.addClass('spider-boxes-tab-nav-active');
					// Add active to target tab content
					tabsContainer.find('.spider-boxes-tab-content[data-tab-id="' + targetId + '"]').addClass('spider-boxes-tab-active');
				});
			})(jQuery);
			</script>
				<?php
			}
		);
	}
	/**
	 * Get component ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get component configuration
	 *
	 * @return array
	 */
	public function get_config() {
		return $this->config;
	}

	/**
	 * Get component type
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->config['type'];
	}

	/**
	 * Add a tab to the container
	 *
	 * @param string $tab_id Tab ID.
	 * @param array  $tab_config Tab configuration.
	 */
	public function add_tab( $tab_id, $tab_config ) {
		$this->config['tabs'][ $tab_id ] = $tab_config;
	}

	/**
	 * Remove a tab from the container
	 *
	 * @param string $tab_id Tab ID.
	 */
	public function remove_tab( $tab_id ) {
		unset( $this->config['tabs'][ $tab_id ] );
	}

	/**
	 * Get tabs
	 *
	 * @return array
	 */
	public function get_tabs() {
		return $this->config['tabs'];
	}
}
