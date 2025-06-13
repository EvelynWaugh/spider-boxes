<?php
/**
 * Accordion Component
 *
 * @package SpiderBoxes\Components
 */

namespace SpiderBoxes\Components;

/**
 * Accordion Component Class
 */
class AccordionComponent {

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
	 * @param string $id Component ID
	 * @param array  $config Component configuration
	 */
	public function __construct( $id, $config = array() ) {
		$this->id     = $id;
		$this->config = wp_parse_args( $config, $this->get_defaults() );
	}

	/**
	 * Get default configuration
	 *
	 * @return array
	 */ private function get_defaults() {
		return array(
			'type'        => 'accordion',
			'title'       => '',
			'description' => '',
			'panes'       => array(),
			'multiple'    => false,
			'class'       => '',
		);
}
	/**
	 * Render component
	 *
	 * @param array $values Field values.
	 * @return string
	 */
public function render( $values = array() ) {
	$title       = $this->config['title'];
	$description = $this->config['description'];
	$panes       = $this->config['panes'];
	$multiple    = $this->config['multiple'];
	$class       = $this->config['class'];

	$accordion_class = 'spider-boxes-accordion';
	if ( ! $multiple ) {
		$accordion_class .= ' spider-boxes-accordion-single';
	}
	if ( ! empty( $class ) ) {
		$accordion_class .= ' ' . $class;
	}

	$content = '<div class="' . esc_attr( $accordion_class ) . '" data-component-id="' . esc_attr( $this->id ) . '">';

	// Accordion title
	if ( ! empty( $title ) ) {
		$content .= '<h3 class="spider-boxes-accordion-title">' . esc_html( $title ) . '</h3>';
	}

	// Accordion description
	if ( ! empty( $description ) ) {
		$content .= '<div class="spider-boxes-accordion-description">' . wpautop( esc_html( $description ) ) . '</div>';
	}

	// Render panes
	if ( ! empty( $panes ) ) {
		$content .= '<div class="spider-boxes-accordion-panes">';
		foreach ( $panes as $pane_id => $pane_config ) {
			$content .= $this->render_pane( $pane_id, $pane_config, $values );
		}
		$content .= '</div>';
	}

	$content .= '</div>';

	// Add accordion script
	$this->enqueue_accordion_script();

	return apply_filters( 'spider_boxes_accordion_render', $content, $this->id, $this->config, $values );
}

	/**
	 * Render pane
	 *
	 * @param string $pane_id Pane ID.
	 * @param array  $pane_config Pane configuration.
	 * @param array  $values Field values.
	 * @return string
	 */
private function render_pane( $pane_id, $pane_config, $values ) {
	$component_registry = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\ComponentRegistry' );
	$pane_class_config  = $component_registry->get_component_type( 'pane' );

	if ( ! $pane_class_config || ! class_exists( $pane_class_config['class_name'] ) ) {
		return '<p>' . __( 'Pane component not found', 'spider-boxes' ) . '</p>';
	}

	$pane_instance = new $pane_class_config['class_name']( $pane_id, $pane_config );
	return $pane_instance->render( $values );
}
	/**
	 * Enqueue accordion script
	 */
private function enqueue_accordion_script() {
	add_action(
		'admin_footer',
		function () {
			?>
			<script>
			(function($) {
				$(document).on('click', '.spider-boxes-accordion-pane-header[data-toggle="collapse"]', function(e) {
					e.preventDefault();
					
					const header = $(this);
					const pane = header.closest('.spider-boxes-accordion-pane');
					const accordion = pane.closest('.spider-boxes-accordion');
					const content = pane.find('.spider-boxes-accordion-pane-content');
					const toggle = header.find('.spider-boxes-accordion-pane-toggle .dashicons');
					const isCollapsed = pane.hasClass('spider-boxes-accordion-pane-collapsed');
					const isSingle = accordion.hasClass('spider-boxes-accordion-single');
					
					if (isSingle) {
						// Close all other panes
						accordion.find('.spider-boxes-accordion-pane').not(pane).each(function() {
							const otherPane = $(this);
							const otherContent = otherPane.find('.spider-boxes-accordion-pane-content');
							const otherToggle = otherPane.find('.spider-boxes-accordion-pane-toggle .dashicons');
							
							otherContent.slideUp();
							otherPane.addClass('spider-boxes-accordion-pane-collapsed');
							otherToggle.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
							otherPane.find('.spider-boxes-accordion-pane-header').attr('aria-expanded', 'false');
						});
					}
					
					if (isCollapsed) {
						content.slideDown();
						pane.removeClass('spider-boxes-accordion-pane-collapsed');
						toggle.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
						header.attr('aria-expanded', 'true');
					} else {
						content.slideUp();
						pane.addClass('spider-boxes-accordion-pane-collapsed');
						toggle.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
						header.attr('aria-expanded', 'false');
					}
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
	 * Add a pane to the accordion
	 *
	 * @param string $pane_id Pane ID.
	 * @param array  $pane_config Pane configuration.
	 */
public function add_pane( $pane_id, $pane_config ) {
	$this->config['panes'][ $pane_id ] = $pane_config;
}

	/**
	 * Remove a pane from the accordion
	 *
	 * @param string $pane_id Pane ID.
	 */
public function remove_pane( $pane_id ) {
	unset( $this->config['panes'][ $pane_id ] );
}

	/**
	 * Get panes
	 *
	 * @return array
	 */
public function get_panes() {
	return $this->config['panes'];
}
}
