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
	 */
	private function get_defaults() {
		return array(
			'type'        => 'accordion',
			'title'       => '',
			'description' => '',
			'fields'      => array(),
			'collapsed'   => false,
			'collapsible' => true,
			'class'       => '',
			'icon'        => '',
		);
	}

	/**
	 * Render component
	 *
	 * @param array $values Field values
	 * @return string
	 */
	public function render( $values = array() ) {
		$title       = $this->config['title'];
		$description = $this->config['description'];
		$fields      = $this->config['fields'];
		$collapsed   = $this->config['collapsed'];
		$collapsible = $this->config['collapsible'];
		$class       = $this->config['class'];
		$icon        = $this->config['icon'];

		$accordion_class = 'spider-boxes-accordion';
		if ( $collapsed ) {
			$accordion_class .= ' spider-boxes-accordion-collapsed';
		}
		if ( ! empty( $class ) ) {
			$accordion_class .= ' ' . $class;
		}

		$content = '<div class="' . esc_attr( $accordion_class ) . '" data-component-id="' . esc_attr( $this->id ) . '">';

		// Accordion header
		if ( ! empty( $title ) ) {
			$header_tag   = $collapsible ? 'button' : 'div';
			$header_attrs = array(
				'class' => 'spider-boxes-accordion-header',
				'type'  => $collapsible ? 'button' : null,
			);

			if ( $collapsible ) {
				$header_attrs['data-toggle']   = 'collapse';
				$header_attrs['aria-expanded'] = $collapsed ? 'false' : 'true';
			}

			$content .= '<' . $header_tag . $this->get_attributes( $header_attrs ) . '>';

			if ( ! empty( $icon ) ) {
				$content .= '<span class="spider-boxes-accordion-icon">' . $icon . '</span>';
			}

			$content .= '<span class="spider-boxes-accordion-title">' . esc_html( $title ) . '</span>';

			if ( $collapsible ) {
				$content .= '<span class="spider-boxes-accordion-toggle">';
				$content .= '<span class="dashicons dashicons-arrow-' . ( $collapsed ? 'down' : 'up' ) . '"></span>';
				$content .= '</span>';
			}

			$content .= '</' . $header_tag . '>';
		}

		// Accordion content
		$content_style = $collapsed && $collapsible ? 'display: none;' : '';
		$content      .= '<div class="spider-boxes-accordion-content" style="' . esc_attr( $content_style ) . '">';

		if ( ! empty( $description ) ) {
			$content .= '<div class="spider-boxes-accordion-description">' . wpautop( esc_html( $description ) ) . '</div>';
		}

		// Render fields
		if ( ! empty( $fields ) ) {
			$content .= '<div class="spider-boxes-accordion-fields">';
			$content .= $this->render_fields( $fields, $values );
			$content .= '</div>';
		}

		$content .= '</div>';
		$content .= '</div>';

		// Add accordion script
		if ( $collapsible ) {
			$this->enqueue_accordion_script();
		}

		return apply_filters( 'spider_boxes_accordion_render', $content, $this->id, $this->config, $values );
	}

	/**
	 * Render fields
	 *
	 * @param array $fields Field configurations
	 * @param array $values Field values
	 * @return string
	 */
	private function render_fields( $fields, $values ) {
		$content        = '';
		$field_registry = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\FieldRegistry' );

		foreach ( $fields as $field_id => $field_config ) {
			$field_type         = $field_config['type'] ?? 'text';
			$field_class_config = $field_registry->get_field_type( $field_type );

			if ( ! $field_class_config || ! class_exists( $field_class_config['class'] ) ) {
				$content .= '<p>' . sprintf( __( 'Field type "%s" not found', 'spider-boxes' ), $field_type ) . '</p>';
				continue;
			}

			$field_value    = isset( $values[ $field_id ] ) ? $values[ $field_id ] : '';
			$field_instance = new $field_class_config['class']( $field_id, $field_config );
			$content       .= $field_instance->render( $field_value );
		}

		return $content;
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
				$(document).on('click', '.spider-boxes-accordion-header[data-toggle="collapse"]', function(e) {
					e.preventDefault();
					
					const header = $(this);
					const accordion = header.closest('.spider-boxes-accordion');
					const content = accordion.find('.spider-boxes-accordion-content');
					const toggle = header.find('.spider-boxes-accordion-toggle .dashicons');
					const isCollapsed = accordion.hasClass('spider-boxes-accordion-collapsed');
					
					if (isCollapsed) {
						content.slideDown();
						accordion.removeClass('spider-boxes-accordion-collapsed');
						toggle.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
						header.attr('aria-expanded', 'true');
					} else {
						content.slideUp();
						accordion.addClass('spider-boxes-accordion-collapsed');
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
	 * Get attributes string
	 *
	 * @param array $attrs Attributes array
	 * @return string
	 */
	private function get_attributes( $attrs ) {
		$attributes = array();
		foreach ( $attrs as $key => $value ) {
			if ( $value !== null ) {
				$attributes[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
			}
		}
		return ! empty( $attributes ) ? ' ' . implode( ' ', $attributes ) : '';
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
}
