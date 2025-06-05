<?php
/**
 * Repeater Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Repeater Field Class
 */
class RepeaterField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'               => 'repeater',
				'fields'             => array(),
				'min'                => 0,
				'max'                => 0, // 0 = unlimited
				'add_button_text'    => __( 'Add Item', 'spider-boxes' ),
				'remove_button_text' => __( 'Remove', 'spider-boxes' ),
				'collapsed'          => false,
			)
		);
	}

	/**
	 * Render field
	 *
	 * @param mixed $value Current value
	 * @return string
	 */
	public function render( $value = null ) {
		$value              = $value !== null ? $value : $this->config['value'];
		$fields             = $this->config['fields'];
		$min                = $this->config['min'];
		$max                = $this->config['max'];
		$add_button_text    = $this->config['add_button_text'];
		$remove_button_text = $this->config['remove_button_text'];
		$collapsed          = $this->config['collapsed'];

		// Ensure value is array
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		// Ensure minimum items
		while ( count( $value ) < $min ) {
			$value[] = array();
		}

		$content  = '<div class="spider-boxes-repeater" data-field-id="' . esc_attr( $this->id ) . '" data-min="' . esc_attr( $min ) . '" data-max="' . esc_attr( $max ) . '">';
		$content .= '<div class="spider-boxes-repeater-items" data-sortable="true">';

		// Render existing items
		foreach ( $value as $index => $item_value ) {
			$content .= $this->render_repeater_item( $index, $item_value, $fields, $collapsed, $remove_button_text );
		}

		$content .= '</div>';

		// Add button
		$add_button_class = 'spider-boxes-repeater-add';
		if ( $max > 0 && count( $value ) >= $max ) {
			$add_button_class .= ' hidden';
		}

		$content .= '<button type="button" class="' . $add_button_class . '">';
		$content .= '<span class="dashicons dashicons-plus-alt"></span> ' . esc_html( $add_button_text );
		$content .= '</button>';

		$content .= '</div>';

		// Add repeater script
		$this->enqueue_repeater_script();

		return $this->wrap_field( $content );
	}

	/**
	 * Render repeater item
	 *
	 * @param int    $index Item index
	 * @param array  $item_value Item values
	 * @param array  $fields Field configurations
	 * @param bool   $collapsed Whether item is collapsed
	 * @param string $remove_button_text Remove button text
	 * @return string
	 */
	private function render_repeater_item( $index, $item_value, $fields, $collapsed, $remove_button_text ) {
		$content = '<div class="spider-boxes-repeater-item' . ( $collapsed ? ' collapsed' : '' ) . '" data-index="' . esc_attr( $index ) . '">';

		// Item header
		$content .= '<div class="spider-boxes-repeater-header">';
		$content .= '<div class="spider-boxes-repeater-handle"><span class="dashicons dashicons-menu"></span></div>';
		$content .= '<div class="spider-boxes-repeater-title">Item #' . ( $index + 1 ) . '</div>';
		$content .= '<div class="spider-boxes-repeater-controls">';
		$content .= '<button type="button" class="spider-boxes-repeater-toggle" title="' . esc_attr__( 'Toggle', 'spider-boxes' ) . '">';
		$content .= '<span class="dashicons dashicons-arrow-' . ( $collapsed ? 'down' : 'up' ) . '"></span>';
		$content .= '</button>';
		$content .= '<button type="button" class="spider-boxes-repeater-remove" title="' . esc_attr( $remove_button_text ) . '">';
		$content .= '<span class="dashicons dashicons-no-alt"></span>';
		$content .= '</button>';
		$content .= '</div>';
		$content .= '</div>';

		// Item content
		$content .= '<div class="spider-boxes-repeater-content"' . ( $collapsed ? ' style="display: none;"' : '' ) . '>';

		foreach ( $fields as $field_id => $field_config ) {
			$field_name  = $this->id . '[' . $index . '][' . $field_id . ']';
			$field_value = isset( $item_value[ $field_id ] ) ? $item_value[ $field_id ] : '';

			$content .= $this->render_sub_field( $field_id, $field_config, $field_name, $field_value );
		}

		$content .= '</div>';
		$content .= '</div>';

		return $content;
	}

	/**
	 * Render sub field
	 *
	 * @param string $field_id Field ID
	 * @param array  $field_config Field configuration
	 * @param string $field_name Field name
	 * @param mixed  $field_value Field value
	 * @return string
	 */
	private function render_sub_field( $field_id, $field_config, $field_name, $field_value ) {
		$field_registry = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\FieldRegistry' );

		// Create field instance
		$field_type  = $field_config['type'] ?? 'text';
		$field_class = $field_registry->get_field_type( $field_type );

		if ( ! $field_class || ! class_exists( $field_class['class'] ) ) {
			return '<p>' . sprintf( __( 'Field type "%s" not found', 'spider-boxes' ), $field_type ) . '</p>';
		}

		$field_config['name'] = $field_name;
		$field_instance       = new $field_class['class']( $field_id, $field_config );

		return $field_instance->render( $field_value );
	}

	/**
	 * Enqueue repeater script
	 */
	private function enqueue_repeater_script() {
		wp_enqueue_script( 'jquery-ui-sortable' );

		add_action(
			'admin_footer',
			function () {
				?>
			<script>
			(function($) {
				// Initialize sortable
				$('.spider-boxes-repeater-items[data-sortable="true"]').sortable({
					handle: '.spider-boxes-repeater-handle',
					placeholder: 'spider-boxes-repeater-placeholder',
					update: function(event, ui) {
						// Update indices after sorting
						$(this).find('.spider-boxes-repeater-item').each(function(index) {
							$(this).attr('data-index', index);
							$(this).find('.spider-boxes-repeater-title').text('Item #' + (index + 1));
							
							// Update field names
							$(this).find('[name]').each(function() {
								const name = $(this).attr('name');
								const newName = name.replace(/\[\d+\]/, '[' + index + ']');
								$(this).attr('name', newName);
							});
						});
					}
				});

				// Add item
				$(document).on('click', '.spider-boxes-repeater-add', function(e) {
					e.preventDefault();
					
					const repeater = $(this).closest('.spider-boxes-repeater');
					const items = repeater.find('.spider-boxes-repeater-items');
					const fieldId = repeater.data('field-id');
					const max = repeater.data('max');
					const currentCount = items.find('.spider-boxes-repeater-item').length;
					
					if (max > 0 && currentCount >= max) {
						return;
					}
					
					// Clone template or create new item
					const template = repeater.find('.spider-boxes-repeater-template');
					let newItem;
					
					if (template.length) {
						newItem = template.clone().removeClass('spider-boxes-repeater-template').addClass('spider-boxes-repeater-item');
					} else {
						// Create basic item structure
						newItem = $('<div class="spider-boxes-repeater-item" data-index="' + currentCount + '">');
						// Add basic structure - this would need to be expanded based on field configuration
					}
					
					// Update indices
					newItem.attr('data-index', currentCount);
					newItem.find('.spider-boxes-repeater-title').text('Item #' + (currentCount + 1));
					
					// Update field names
					newItem.find('[name]').each(function() {
						const name = $(this).attr('name');
						const newName = name.replace(/\[(\d+|x)\]/, '[' + currentCount + ']');
						$(this).attr('name', newName);
					});
					
					items.append(newItem);
					
					// Check max limit
					if (max > 0 && items.find('.spider-boxes-repeater-item').length >= max) {
						$(this).addClass('hidden');
					}
				});

				// Remove item
				$(document).on('click', '.spider-boxes-repeater-remove', function(e) {
					e.preventDefault();
					
					const item = $(this).closest('.spider-boxes-repeater-item');
					const repeater = item.closest('.spider-boxes-repeater');
					const items = repeater.find('.spider-boxes-repeater-items');
					const min = repeater.data('min');
					const currentCount = items.find('.spider-boxes-repeater-item').length;
					
					if (currentCount <= min) {
						return;
					}
					
					item.remove();
					
					// Update indices
					items.find('.spider-boxes-repeater-item').each(function(index) {
						$(this).attr('data-index', index);
						$(this).find('.spider-boxes-repeater-title').text('Item #' + (index + 1));
						
						// Update field names
						$(this).find('[name]').each(function() {
							const name = $(this).attr('name');
							const newName = name.replace(/\[\d+\]/, '[' + index + ']');
							$(this).attr('name', newName);
						});
					});
					
					// Show add button if was hidden
					repeater.find('.spider-boxes-repeater-add').removeClass('hidden');
				});

				// Toggle item
				$(document).on('click', '.spider-boxes-repeater-toggle', function(e) {
					e.preventDefault();
					
					const item = $(this).closest('.spider-boxes-repeater-item');
					const content = item.find('.spider-boxes-repeater-content');
					const icon = $(this).find('.dashicons');
					
					content.slideToggle();
					item.toggleClass('collapsed');
					icon.toggleClass('dashicons-arrow-down dashicons-arrow-up');
				});
			})(jQuery);
			</script>
				<?php
			}
		);
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return array
	 */
	public function sanitize( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$fields    = $this->config['fields'];
		$sanitized = array();

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitized_item = array();
			foreach ( $fields as $field_id => $field_config ) {
				if ( isset( $item[ $field_id ] ) ) {
					// Get field class for sanitization
					$field_registry     = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\FieldRegistry' );
					$field_type         = $field_config['type'] ?? 'text';
					$field_class_config = $field_registry->get_field_type( $field_type );

					if ( $field_class_config && class_exists( $field_class_config['class'] ) ) {
						$field_instance              = new $field_class_config['class']( $field_id, $field_config );
						$sanitized_item[ $field_id ] = $field_instance->sanitize( $item[ $field_id ] );
					} else {
						$sanitized_item[ $field_id ] = sanitize_text_field( $item[ $field_id ] );
					}
				}
			}
			$sanitized[] = $sanitized_item;
		}

		return $sanitized;
	}

	/**
	 * Validate field value
	 *
	 * @param mixed $value Value to validate
	 * @return bool|\WP_Error
	 */
	public function validate( $value ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error( 'invalid_repeater_format', __( 'Repeater value must be an array', 'spider-boxes' ) );
		}

		$min   = $this->config['min'];
		$max   = $this->config['max'];
		$count = count( $value );

		if ( $count < $min ) {
			return new \WP_Error(
				'repeater_min_items',
				sprintf(
					__( 'Repeater must have at least %d items', 'spider-boxes' ),
					$min
				)
			);
		}

		if ( $max > 0 && $count > $max ) {
			return new \WP_Error(
				'repeater_max_items',
				sprintf(
					__( 'Repeater cannot have more than %d items', 'spider-boxes' ),
					$max
				)
			);
		}

		// Validate individual items
		$fields = $this->config['fields'];
		foreach ( $value as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			foreach ( $fields as $field_id => $field_config ) {
				if ( isset( $item[ $field_id ] ) ) {
					// Validate using field class
					$field_registry     = spider_boxes()->get_container()->get( 'SpiderBoxes\\Core\\FieldRegistry' );
					$field_type         = $field_config['type'] ?? 'text';
					$field_class_config = $field_registry->get_field_type( $field_type );

					if ( $field_class_config && class_exists( $field_class_config['class'] ) ) {
						$field_instance = new $field_class_config['class']( $field_id, $field_config );
						$validation     = $field_instance->validate( $item[ $field_id ] );

						if ( is_wp_error( $validation ) ) {
							return new \WP_Error(
								'repeater_item_validation_failed',
								sprintf(
									__( 'Item %1$d, field "%2$s": %3$s', 'spider-boxes' ),
									$index + 1,
									$field_id,
									$validation->get_error_message()
								)
							);
						}
					}
				}
			}
		}

		return true;
	}
}
