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
	 */ private function get_defaults() {
		return array(
			'type'    => 'row',
			'title'   => '',
			'columns' => array(),
			'gap'     => 'md',
			'align'   => 'stretch',
			'class'   => '',
		);
}
	/**
	 * Render component
	 *
	 * @param array $values Field values.
	 * @return string
	 */
public function render( $values = array() ) {
	$title   = $this->config['title'];
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

	// Row title
	if ( ! empty( $title ) ) {
		$content .= '<h3 class="spider-boxes-row-title">' . esc_html( $title ) . '</h3>';
	}

	// Render columns
	if ( ! empty( $columns ) ) {
		$content .= '<div class="spider-boxes-row-columns">';
		foreach ( $columns as $column_id => $column_config ) {
			$content .= $this->render_column( $column_id, $column_config, $values );
		}
		$content .= '</div>';
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

	/**
	 * Add a column to the row
	 *
	 * @param string $column_id Column ID.
	 * @param array  $column_config Column configuration.
	 */
public function add_column( $column_id, $column_config ) {
	$this->config['columns'][ $column_id ] = $column_config;
}

	/**
	 * Remove a column from the row
	 *
	 * @param string $column_id Column ID.
	 */
public function remove_column( $column_id ) {
	unset( $this->config['columns'][ $column_id ] );
}

	/**
	 * Get columns
	 *
	 * @return array
	 */
public function get_columns() {
	return $this->config['columns'];
}
}
