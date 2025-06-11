<?php
/**
 * Single Tab Component
 *
 * @package SpiderBoxes\Components
 */

namespace SpiderBoxes\Components;

/**
 * Tab Component Class
 */
class TabComponent {

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
			'type'   => 'tab',
			'title'  => '',
			'icon'   => '',
			'fields' => array(),
			'active' => false,
			'class'  => '',
		);
	}

	/**
	 * Render component (individual tab content)
	 *
	 * @param array $values Field values.
	 * @return string
	 */
	public function render( $values = array() ) {
		$title  = $this->config['title'];
		$fields = $this->config['fields'];
		$active = $this->config['active'];
		$class  = $this->config['class'];

		$tab_class = 'spider-boxes-tab-content';
		if ( $active ) {
			$tab_class .= ' spider-boxes-tab-active';
		}
		if ( ! empty( $class ) ) {
			$tab_class .= ' ' . $class;
		}

		$content = '<div class="' . esc_attr( $tab_class ) . '" data-tab-id="' . esc_attr( $this->id ) . '">';

		// Render fields
		if ( ! empty( $fields ) ) {
			$content .= '<div class="spider-boxes-tab-fields">';
			$content .= $this->render_fields( $fields, $values );
			$content .= '</div>';
		}

		$content .= '</div>';

		return apply_filters( 'spider_boxes_tab_render', $content, $this->id, $this->config, $values );
	}

	/**
	 * Render tab navigation item
	 *
	 * @return string
	 */
	public function render_nav() {
		$title  = $this->config['title'];
		$icon   = $this->config['icon'];
		$active = $this->config['active'];

		$nav_class = 'spider-boxes-tab-nav-item';
		if ( $active ) {
			$nav_class .= ' spider-boxes-tab-nav-active';
		}

		$content = '<button type="button" class="' . esc_attr( $nav_class ) . '" data-tab-target="' . esc_attr( $this->id ) . '">';

		if ( ! empty( $icon ) ) {
			$content .= '<span class="spider-boxes-tab-icon">' . $icon . '</span>';
		}

		$content .= '<span class="spider-boxes-tab-title">' . esc_html( $title ) . '</span>';
		$content .= '</button>';

		return $content;
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
	 * Check if tab is active
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->config['active'];
	}

	/**
	 * Set active state
	 *
	 * @param bool $active Active state.
	 */
	public function set_active( $active ) {
		$this->config['active'] = $active;
	}
}
