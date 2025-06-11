<?php
/**
 * Pane Component
 *
 * @package SpiderBoxes\Components
 */

namespace SpiderBoxes\Components;

/**
 * Pane Component Class
 */
class PaneComponent {

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
			'type'        => 'pane',
			'title'       => '',
			'description' => '',
			'fields'      => array(),
			'collapsed'   => false,
			'class'       => '',
			'icon'        => '',
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
		$fields      = $this->config['fields'];
		$collapsed   = $this->config['collapsed'];
		$class       = $this->config['class'];
		$icon        = $this->config['icon'];

		$pane_class = 'spider-boxes-accordion-pane';
		if ( $collapsed ) {
			$pane_class .= ' spider-boxes-accordion-pane-collapsed';
		}
		if ( ! empty( $class ) ) {
			$pane_class .= ' ' . $class;
		}

		$content = '<div class="' . esc_attr( $pane_class ) . '" data-component-id="' . esc_attr( $this->id ) . '">';

		// Pane header
		if ( ! empty( $title ) ) {
			$content .= '<button type="button" class="spider-boxes-accordion-pane-header" data-toggle="collapse" aria-expanded="' . ( $collapsed ? 'false' : 'true' ) . '">';

			if ( ! empty( $icon ) ) {
				$content .= '<span class="spider-boxes-accordion-pane-icon">' . $icon . '</span>';
			}

			$content .= '<span class="spider-boxes-accordion-pane-title">' . esc_html( $title ) . '</span>';
			$content .= '<span class="spider-boxes-accordion-pane-toggle">';
			$content .= '<span class="dashicons dashicons-arrow-' . ( $collapsed ? 'down' : 'up' ) . '"></span>';
			$content .= '</span>';
			$content .= '</button>';
		}

		// Pane content
		$content_style = $collapsed ? 'display: none;' : '';
		$content      .= '<div class="spider-boxes-accordion-pane-content" style="' . esc_attr( $content_style ) . '">';

		if ( ! empty( $description ) ) {
			$content .= '<div class="spider-boxes-accordion-pane-description">' . wpautop( esc_html( $description ) ) . '</div>';
		}

		// Render fields
		if ( ! empty( $fields ) ) {
			$content .= '<div class="spider-boxes-accordion-pane-fields">';
			$content .= $this->render_fields( $fields, $values );
			$content .= '</div>';
		}

		$content .= '</div>';
		$content .= '</div>';

		return apply_filters( 'spider_boxes_accordion_pane_render', $content, $this->id, $this->config, $values );
	}

	/**
	 * Render fields
	 *
	 * @param array $fields Field configurations.
	 * @param array $values Field values.
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
	 * Check if pane is collapsed
	 *
	 * @return bool
	 */
	public function is_collapsed() {
		return $this->config['collapsed'];
	}

	/**
	 * Set collapsed state
	 *
	 * @param bool $collapsed Collapsed state.
	 */
	public function set_collapsed( $collapsed ) {
		$this->config['collapsed'] = $collapsed;
	}
}
