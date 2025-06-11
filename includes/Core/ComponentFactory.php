<?php
/**
 * Component Factory
 *
 * @package SpiderBoxes\Core
 */

namespace SpiderBoxes\Core;

/**
 * Component Factory Class
 */
class ComponentFactory {

	/**
	 * Component Registry instance
	 *
	 * @var ComponentRegistry
	 */
	private $component_registry;

	/**
	 * Constructor
	 *
	 * @param ComponentRegistry $component_registry Component registry instance.
	 */
	public function __construct( ComponentRegistry $component_registry ) {
		$this->component_registry = $component_registry;
	}

	/**
	 * Create a tabs component with default tab
	 *
	 * @param string $id Component ID.
	 * @param array  $args Component arguments.
	 * @return array Component configuration.
	 */
	public function create_tabs( $id, $args = array() ) {
		$defaults = array(
			'type'  => 'tabs',
			'title' => __( 'Tabs', 'spider-boxes' ),
			'tabs'  => array(),
		);

		$config = wp_parse_args( $args, $defaults );

		// Add default tab if no tabs provided
		if ( empty( $config['tabs'] ) ) {
			$tab_id                    = $id . '_tab_1';
			$config['tabs'][ $tab_id ] = array(
				'type'   => 'tab',
				'title'  => __( 'Tab 1', 'spider-boxes' ),
				'icon'   => '',
				'fields' => array(),
				'active' => true,
			);
		}

		return $config;
	}

	/**
	 * Create an accordion component with default pane
	 *
	 * @param string $id Component ID.
	 * @param array  $args Component arguments.
	 * @return array Component configuration.
	 */
	public function create_accordion( $id, $args = array() ) {
		$defaults = array(
			'type'        => 'accordion',
			'title'       => __( 'Accordion', 'spider-boxes' ),
			'description' => '',
			'panes'       => array(),
			'multiple'    => false,
		);

		$config = wp_parse_args( $args, $defaults );

		// Add default pane if no panes provided
		if ( empty( $config['panes'] ) ) {
			$pane_id                     = $id . '_pane_1';
			$config['panes'][ $pane_id ] = array(
				'type'        => 'pane',
				'title'       => __( 'Pane 1', 'spider-boxes' ),
				'description' => '',
				'fields'      => array(),
				'collapsed'   => false,
			);
		}

		return $config;
	}

	/**
	 * Create a row component with default column
	 *
	 * @param string $id Component ID.
	 * @param array  $args Component arguments.
	 * @return array Component configuration.
	 */
	public function create_row( $id, $args = array() ) {
		$defaults = array(
			'type'    => 'row',
			'title'   => __( 'Row', 'spider-boxes' ),
			'columns' => array(),
			'gap'     => 'md',
			'align'   => 'stretch',
		);

		$config = wp_parse_args( $args, $defaults );

		// Add default column if no columns provided
		if ( empty( $config['columns'] ) ) {
			$column_id                       = $id . '_column_1';
			$config['columns'][ $column_id ] = array(
				'type'   => 'column',
				'width'  => 'auto',
				'fields' => array(),
				'align'  => 'start',
			);
		}

		return $config;
	}

	/**
	 * Add a new tab to a tabs component
	 *
	 * @param string $tabs_id Tabs component ID.
	 * @param array  $tab_config Tab configuration.
	 * @return string New tab ID.
	 */
	public function add_tab_to_tabs( $tabs_id, $tab_config = array() ) {
		$tabs_component = $this->component_registry->get_component( $tabs_id );
		if ( ! $tabs_component ) {
			return '';
		}

		$existing_tabs = $tabs_component['tabs'] ?? array();
		$tab_count     = count( $existing_tabs ) + 1;
		$tab_id        = $tabs_id . '_tab_' . $tab_count;

		$defaults = array(
			'type'   => 'tab',
			'title'  => sprintf( __( 'Tab %d', 'spider-boxes' ), $tab_count ),
			'icon'   => '',
			'fields' => array(),
			'active' => false,
		);

		$tab_config = wp_parse_args( $tab_config, $defaults );

		// Deactivate other tabs if this one should be active
		if ( $tab_config['active'] ) {
			foreach ( $existing_tabs as $existing_tab_id => $existing_tab_config ) {
				$existing_tabs[ $existing_tab_id ]['active'] = false;
			}
		}

		$existing_tabs[ $tab_id ] = $tab_config;

		// Update the tabs component
		$tabs_component['tabs'] = $existing_tabs;
		$this->component_registry->register_component( $tabs_id, $tabs_component );

		return $tab_id;
	}

	/**
	 * Add a new pane to an accordion component
	 *
	 * @param string $accordion_id Accordion component ID.
	 * @param array  $pane_config Pane configuration.
	 * @return string New pane ID.
	 */
	public function add_pane_to_accordion( $accordion_id, $pane_config = array() ) {
		$accordion_component = $this->component_registry->get_component( $accordion_id );
		if ( ! $accordion_component ) {
			return '';
		}

		$existing_panes = $accordion_component['panes'] ?? array();
		$pane_count     = count( $existing_panes ) + 1;
		$pane_id        = $accordion_id . '_pane_' . $pane_count;

		$defaults = array(
			'type'        => 'pane',
			'title'       => sprintf( __( 'Pane %d', 'spider-boxes' ), $pane_count ),
			'description' => '',
			'fields'      => array(),
			'collapsed'   => true,
		);

		$pane_config = wp_parse_args( $pane_config, $defaults );

		$existing_panes[ $pane_id ] = $pane_config;

		// Update the accordion component
		$accordion_component['panes'] = $existing_panes;
		$this->component_registry->register_component( $accordion_id, $accordion_component );

		return $pane_id;
	}

	/**
	 * Add a new column to a row component
	 *
	 * @param string $row_id Row component ID.
	 * @param array  $column_config Column configuration.
	 * @return string New column ID.
	 */
	public function add_column_to_row( $row_id, $column_config = array() ) {
		$row_component = $this->component_registry->get_component( $row_id );
		if ( ! $row_component ) {
			return '';
		}

		$existing_columns = $row_component['columns'] ?? array();
		$column_count     = count( $existing_columns ) + 1;
		$column_id        = $row_id . '_column_' . $column_count;

		$defaults = array(
			'type'   => 'column',
			'width'  => 'auto',
			'fields' => array(),
			'align'  => 'start',
		);

		$column_config = wp_parse_args( $column_config, $defaults );

		$existing_columns[ $column_id ] = $column_config;

		// Update the row component
		$row_component['columns'] = $existing_columns;
		$this->component_registry->register_component( $row_id, $row_component );

		return $column_id;
	}

	/**
	 * Remove a child component from its parent
	 *
	 * @param string $parent_id Parent component ID.
	 * @param string $child_id Child component ID.
	 * @return bool Success status.
	 */
	public function remove_child_from_parent( $parent_id, $child_id ) {
		$parent_component = $this->component_registry->get_component( $parent_id );
		if ( ! $parent_component ) {
			return false;
		}

		$parent_type = $parent_component['type'];

		switch ( $parent_type ) {
			case 'tabs':
				if ( isset( $parent_component['tabs'][ $child_id ] ) ) {
					unset( $parent_component['tabs'][ $child_id ] );
					$this->component_registry->register_component( $parent_id, $parent_component );
					return true;
				}
				break;

			case 'accordion':
				if ( isset( $parent_component['panes'][ $child_id ] ) ) {
					unset( $parent_component['panes'][ $child_id ] );
					$this->component_registry->register_component( $parent_id, $parent_component );
					return true;
				}
				break;

			case 'row':
				if ( isset( $parent_component['columns'][ $child_id ] ) ) {
					unset( $parent_component['columns'][ $child_id ] );
					$this->component_registry->register_component( $parent_id, $parent_component );
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Get component configuration with proper structure
	 *
	 * @param string $type Component type.
	 * @param string $id Component ID.
	 * @param array  $args Component arguments.
	 * @return array Component configuration.
	 */
	public function get_component_config( $type, $id, $args = array() ) {
		switch ( $type ) {
			case 'tabs':
				return $this->create_tabs( $id, $args );

			case 'accordion':
				return $this->create_accordion( $id, $args );

			case 'row':
				return $this->create_row( $id, $args );

			default:
				return wp_parse_args( $args, array( 'type' => $type ) );
		}
	}
}
