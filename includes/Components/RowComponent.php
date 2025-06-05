<?php
/**
 * Row Component
 *
 * @package SpiderBoxes\Components
 */

namespace SpiderBoxes\Components;

/**
 * Row Component Class
 */
class RowComponent {

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
			'type'    => 'row',
			'columns' => array(),
			'gap'     => 'md',
			'align'   => 'stretch',
			'class'   => '',
		);
	}

	/**
	 * Render component
	 *
	 * @param array $values Field values
	 * @return string
	 */
	public function render( $values = array() ) {
		$columns = $this->config['columns'];
		$gap     = $this->config['gap'];
		$align   = $this->config['align'];
		$class   = $this->config['class'];

		$row_class  = 'spider-boxes-row';
		$row_class .= ' spider-boxes-row-gap-' . $gap;
		$row_class .= ' spider-boxes-row-align-' . $align;

		if ( ! empty( $class ) ) {
			$row_class .= ' ' . $class;
		}

		$content = '<div class="' . esc_attr( $row_class ) . '" data-component-id="' . esc_attr( $this->id ) . '">';

		// Render columns
		if ( ! empty( $columns ) ) {
			foreach ( $columns as $column_id => $column_config ) {
				$content .= $this->render_column( $column_id, $column_config, $values );
			}
		}

		$content .= '</div>';

		return apply_filters( 'spider_boxes_row_render', $content, $this->id, $this->config, $values );
	}

	/**
	 * Render column
	 *
	 * @param string $column_id Column ID
	 * @param array  $column_config Column configuration
	 * @param array  $values Field values
	 * @return string
	 */
	private function render_column( $column_id, $column_config, $values ) {
		$component_registry  = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\ComponentRegistry' );
		$column_class_config = $component_registry->get_component_type( 'column' );

		if ( ! $column_class_config || ! class_exists( $column_class_config['class'] ) ) {
			return '<p>' . __( 'Column component not found', 'spider-boxes' ) . '</p>';
		}

		$column_instance = new $column_class_config['class']( $column_id, $column_config );
		return $column_instance->render( $values );
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
