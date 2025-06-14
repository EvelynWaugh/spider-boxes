<?php
/**
 * Field Configuration Generator
 *
 * @package SpiderBoxes\Core
 */

namespace SpiderBoxes\Core;

/**
 * FieldConfigGenerator Class
 *
 * Generates dynamic configuration fields based on field type supports
 */
class FieldConfigGenerator {

	/**
	 * Generate configuration fields based on field type supports
	 *
	 * @param array $field_type_config Field type configuration
	 * @param array $existing_config Existing field configuration
	 * @return array Generated configuration fields
	 */
	public function generate_config_fields( array $field_type_config, array $existing_config = array() ): array {
		$supports = $field_type_config['supports'] ?? array();
		$fields   = array();

		// Base fields that all types support
		$fields = array_merge( $fields, $this->get_base_config_fields( $existing_config ) );

		// Add supported configuration fields based on supports array
		foreach ( $supports as $support ) {
			$method = "get_{$support}_config_fields";
			if ( method_exists( $this, $method ) ) {
				$fields = array_merge( $fields, $this->$method( $existing_config ) );
			}
		}

		// Apply filters for extensibility
		$fields = apply_filters( 'spider_boxes_field_config_fields', $fields, $field_type_config, $existing_config );
		$fields = apply_filters( "spider_boxes_field_config_fields_{$field_type_config['type']}", $fields, $existing_config );

		return $fields;
	}

	/**
	 * Get base configuration fields that all field types support
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Base configuration fields
	 */
	private function get_base_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'label',
				'type'        => 'text',
				'title'       => __( 'Label', 'spider-boxes' ),
				'description' => __( 'The display label for this field', 'spider-boxes' ),
				'value'       => $existing_config['label'] ?? '',
				'required'    => true,
				'placeholder' => __( 'Enter field label', 'spider-boxes' ),
			),
			array(
				'id'          => 'description',
				'type'        => 'textarea',
				'title'       => __( 'Description', 'spider-boxes' ),
				'description' => __( 'Optional description for this field', 'spider-boxes' ),
				'value'       => $existing_config['description'] ?? '',
				'rows'        => 3,
				'placeholder' => __( 'Enter field description', 'spider-boxes' ),
			),
			array(
				'id'          => 'required',
				'type'        => 'checkbox',
				'title'       => __( 'Required Field', 'spider-boxes' ),
				'description' => __( 'Mark this field as required', 'spider-boxes' ),
				'value'       => $existing_config['required'] ?? false,
			),

			array(
				'id'          => 'context',
				'type'        => 'select',
				'title'       => __( 'Context', 'spider-boxes' ),
				'description' => __( 'Where this field is used', 'spider-boxes' ),
				'value'       => $existing_config['context'] ?? 'default',
				'options'     => array(
					'default'      => __( 'Default', 'spider-boxes' ),
					'review'       => __( 'Review', 'spider-boxes' ),
					'product'      => __( 'Product', 'spider-boxes' ),
					'post'         => __( 'Post', 'spider-boxes' ),
					'page'         => __( 'Page', 'spider-boxes' ),
					'user'         => __( 'User Profile', 'spider-boxes' ),
					'comment'      => __( 'Comment', 'spider-boxes' ),
					'term'         => __( 'Term/Category', 'spider-boxes' ),
					'settings'     => __( 'Settings', 'spider-boxes' ),
					'checkout'     => __( 'Checkout', 'spider-boxes' ),
					'registration' => __( 'Registration', 'spider-boxes' ),
				),
			),
			array(
				'id'          => 'meta_field',
				'type'        => 'checkbox',
				'title'       => __( 'Is Meta Field', 'spider-boxes' ),
				'description' => __( 'Store this field as metadata', 'spider-boxes' ),
				'value'       => $existing_config['meta_field'] ?? false,
			),
		);
	}

	/**
	 * Get placeholder configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Placeholder configuration fields
	 */
	private function get_placeholder_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'placeholder',
				'type'        => 'text',
				'title'       => __( 'Placeholder', 'spider-boxes' ),
				'description' => __( 'Placeholder text for this field', 'spider-boxes' ),
				'value'       => $existing_config['placeholder'] ?? '',
				'placeholder' => __( 'Enter placeholder text', 'spider-boxes' ),
			),
		);
	}

	/**
	 * Get default value configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Default value configuration fields
	 */
	private function get_default_value_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'default_value',
				'type'        => 'text',
				'title'       => __( 'Default Value', 'spider-boxes' ),
				'description' => __( 'Default value for this field', 'spider-boxes' ),
				'value'       => $existing_config['default_value'] ?? '',
				'placeholder' => __( 'Enter default value', 'spider-boxes' ),
			),
		);
	}

	/**
	 * Get value configuration fields (alias for default_value)
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Value configuration fields
	 */
	private function get_value_config_fields( array $existing_config ): array {
		return $this->get_default_value_config_fields( $existing_config );
	}

	/**
	 * Get options configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Options configuration fields
	 */
	private function get_options_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'options',
				'type'        => 'textarea',
				'title'       => __( 'Options', 'spider-boxes' ),
				'description' => __( 'One option per line (value|label format)', 'spider-boxes' ),
				'value'       => $existing_config['options'] ?? '',
				'rows'        => 5,
				'placeholder' => "option1|Option 1\noption2|Option 2\noption3|Option 3",
			),
			array(
				'id'          => 'options_source',
				'type'        => 'select',
				'title'       => __( 'Options Source', 'spider-boxes' ),
				'description' => __( 'Where to load options from', 'spider-boxes' ),
				'value'       => $existing_config['options_source'] ?? 'manual',
				'options'     => array(
					'manual' => __( 'Manual Entry', 'spider-boxes' ),
					'posts'  => __( 'Posts', 'spider-boxes' ),
					'pages'  => __( 'Pages', 'spider-boxes' ),
					'users'  => __( 'Users', 'spider-boxes' ),
					'terms'  => __( 'Terms/Categories', 'spider-boxes' ),
					'custom' => __( 'Custom Function', 'spider-boxes' ),
					'ajax'   => __( 'AJAX Load', 'spider-boxes' ),
				),
			),
		);
	}

	/**
	 * Get multiple selection configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Multiple selection configuration fields
	 */
	private function get_multiple_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'multiple',
				'type'        => 'checkbox',
				'title'       => __( 'Multiple Selection', 'spider-boxes' ),
				'description' => __( 'Allow multiple values to be selected', 'spider-boxes' ),
				'value'       => $existing_config['multiple'] ?? false,
			),
			array(
				'id'          => 'max_selections',
				'type'        => 'number',
				'title'       => __( 'Maximum Selections', 'spider-boxes' ),
				'description' => __( 'Maximum number of items that can be selected (0 for unlimited)', 'spider-boxes' ),
				'value'       => $existing_config['max_selections'] ?? 0,
				'min'         => 0,
				'conditional' => array(
					'field' => 'multiple',
					'value' => true,
				),
			),
		);
	}

	/**
	 * Get rows configuration fields (for textarea)
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Rows configuration fields
	 */
	private function get_rows_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'rows',
				'type'        => 'number',
				'title'       => __( 'Rows', 'spider-boxes' ),
				'description' => __( 'Number of rows for textarea', 'spider-boxes' ),
				'value'       => $existing_config['rows'] ?? 5,
				'min'         => 1,
				'max'         => 20,
			),
			array(
				'id'          => 'cols',
				'type'        => 'number',
				'title'       => __( 'Columns', 'spider-boxes' ),
				'description' => __( 'Number of columns for textarea', 'spider-boxes' ),
				'value'       => $existing_config['cols'] ?? 50,
				'min'         => 10,
				'max'         => 200,
			),
		);
	}

	/**
	 * Get minimum value configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Minimum value configuration fields
	 */
	private function get_min_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'min_value',
				'type'        => 'number',
				'title'       => __( 'Minimum Value', 'spider-boxes' ),
				'description' => __( 'Minimum allowed value', 'spider-boxes' ),
				'value'       => $existing_config['min_value'] ?? '',
				'step'        => 'any',
			),
			array(
				'id'          => 'min_length',
				'type'        => 'number',
				'title'       => __( 'Minimum Length', 'spider-boxes' ),
				'description' => __( 'Minimum number of characters', 'spider-boxes' ),
				'value'       => $existing_config['min_length'] ?? '',
				'min'         => 0,
			),
		);
	}

	/**
	 * Get maximum value configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Maximum value configuration fields
	 */
	private function get_max_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'max_value',
				'type'        => 'number',
				'title'       => __( 'Maximum Value', 'spider-boxes' ),
				'description' => __( 'Maximum allowed value', 'spider-boxes' ),
				'value'       => $existing_config['max_value'] ?? '',
				'step'        => 'any',
			),
			array(
				'id'          => 'max_length',
				'type'        => 'number',
				'title'       => __( 'Maximum Length', 'spider-boxes' ),
				'description' => __( 'Maximum number of characters', 'spider-boxes' ),
				'value'       => $existing_config['max_length'] ?? '',
				'min'         => 1,
			),
		);
	}

	/**
	 * Get step configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Step configuration fields
	 */
	private function get_step_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'step',
				'type'        => 'number',
				'title'       => __( 'Step', 'spider-boxes' ),
				'description' => __( 'Step increment for range/number fields', 'spider-boxes' ),
				'value'       => $existing_config['step'] ?? 1,
				'min'         => 0.01,
				'step'        => 0.01,
			),
		);
	}

	/**
	 * Get format configuration fields (for date/time fields)
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Format configuration fields
	 */
	private function get_format_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'date_format',
				'type'        => 'select',
				'title'       => __( 'Date Format', 'spider-boxes' ),
				'description' => __( 'Date display format', 'spider-boxes' ),
				'value'       => $existing_config['date_format'] ?? 'Y-m-d',
				'options'     => array(
					'Y-m-d'  => 'YYYY-MM-DD (2024-01-15)',
					'm/d/Y'  => 'MM/DD/YYYY (01/15/2024)',
					'd/m/Y'  => 'DD/MM/YYYY (15/01/2024)',
					'F j, Y' => 'Month DD, YYYY (January 15, 2024)',
					'j F Y'  => 'DD Month YYYY (15 January 2024)',
				),
			),
			array(
				'id'          => 'time_format',
				'type'        => 'select',
				'title'       => __( 'Time Format', 'spider-boxes' ),
				'description' => __( 'Time display format', 'spider-boxes' ),
				'value'       => $existing_config['time_format'] ?? 'H:i',
				'options'     => array(
					'H:i'     => '24 Hour (14:30)',
					'h:i A'   => '12 Hour (2:30 PM)',
					'H:i:s'   => '24 Hour with Seconds (14:30:45)',
					'h:i:s A' => '12 Hour with Seconds (2:30:45 PM)',
				),
			),
		);
	}

	/**
	 * Get media type configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Media type configuration fields
	 */
	private function get_media_type_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'media_type',
				'type'        => 'select',
				'title'       => __( 'Media Type', 'spider-boxes' ),
				'description' => __( 'Type of media to allow', 'spider-boxes' ),
				'value'       => $existing_config['media_type'] ?? 'image',
				'options'     => array(
					'image'    => __( 'Image', 'spider-boxes' ),
					'video'    => __( 'Video', 'spider-boxes' ),
					'audio'    => __( 'Audio', 'spider-boxes' ),
					'document' => __( 'Document', 'spider-boxes' ),
					'archive'  => __( 'Archive', 'spider-boxes' ),
					'any'      => __( 'Any File Type', 'spider-boxes' ),
				),
			),
			array(
				'id'          => 'file_extensions',
				'type'        => 'text',
				'title'       => __( 'Allowed Extensions', 'spider-boxes' ),
				'description' => __( 'Comma-separated list of allowed file extensions', 'spider-boxes' ),
				'value'       => $existing_config['file_extensions'] ?? '',
				'placeholder' => __( 'jpg,png,gif,pdf', 'spider-boxes' ),
			),
			array(
				'id'          => 'max_file_size',
				'type'        => 'number',
				'title'       => __( 'Max File Size (MB)', 'spider-boxes' ),
				'description' => __( 'Maximum file size in megabytes', 'spider-boxes' ),
				'value'       => $existing_config['max_file_size'] ?? 10,
				'min'         => 1,
				'max'         => 100,
			),
		);
	}

	/**
	 * Get validation configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Validation configuration fields
	 */
	private function get_validation_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'required',
				'type'        => 'checkbox',
				'label'       => __( 'Required', 'spider-boxes' ),
				'description' => __( 'This component must be completed', 'spider-boxes' ),
				'value'       => $existing_config['required'] ?? false,
			),
			array(
				'id'          => 'validation_rules',
				'type'        => 'repeater',
				'label'       => __( 'Validation Rules', 'spider-boxes' ),
				'description' => __( 'Custom validation rules for this component', 'spider-boxes' ),
				'value'       => $existing_config['validation_rules'] ?? array(),
				'fields'      => array(
					array(
						'id'      => 'rule_type',
						'type'    => 'select',
						'label'   => __( 'Rule Type', 'spider-boxes' ),
						'options' => array(
							'min_length' => __( 'Minimum Length', 'spider-boxes' ),
							'max_length' => __( 'Maximum Length', 'spider-boxes' ),
							'regex'      => __( 'Regular Expression', 'spider-boxes' ),
							'custom'     => __( 'Custom Function', 'spider-boxes' ),
						),
					),
					array(
						'id'    => 'rule_value',
						'type'  => 'text',
						'label' => __( 'Rule Value', 'spider-boxes' ),
					),
					array(
						'id'    => 'error_message',
						'type'  => 'text',
						'label' => __( 'Error Message', 'spider-boxes' ),
					),
				),
			),
		);
	}

	/**
	 * Get AJAX action configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array AJAX action configuration fields
	 */
	private function get_ajax_action_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'ajax_action',
				'type'        => 'text',
				'title'       => __( 'AJAX Action', 'spider-boxes' ),
				'description' => __( 'WordPress AJAX action for loading options', 'spider-boxes' ),
				'value'       => $existing_config['ajax_action'] ?? '',
				'placeholder' => __( 'my_ajax_action', 'spider-boxes' ),
			),
			array(
				'id'          => 'ajax_nonce',
				'type'        => 'text',
				'title'       => __( 'AJAX Nonce Action', 'spider-boxes' ),
				'description' => __( 'Nonce action for security', 'spider-boxes' ),
				'value'       => $existing_config['ajax_nonce'] ?? '',
				'placeholder' => __( 'my_ajax_nonce', 'spider-boxes' ),
			),
		);
	}


	/**
	 * Get settings configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Settings configuration fields
	 */
	private function get_settings_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'additional_settings',
				'type'        => 'textarea',
				'title'       => __( 'Additional Settings', 'spider-boxes' ),
				'description' => __( 'JSON object with additional field settings', 'spider-boxes' ),
				'value'       => $existing_config['additional_settings'] ?? '',
				'rows'        => 5,
				'placeholder' => '{"custom_attribute": "value", "data_source": "api_endpoint"}',
			),
		);
	}

	/**
	 * Get autocomplete configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Autocomplete configuration fields
	 */
	private function get_autocomplete_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'autocomplete_source',
				'type'        => 'select',
				'title'       => __( 'Autocomplete Source', 'spider-boxes' ),
				'description' => __( 'Data source for autocomplete suggestions', 'spider-boxes' ),
				'value'       => $existing_config['autocomplete_source'] ?? 'ajax',
				'options'     => array(
					'ajax'   => __( 'AJAX Endpoint', 'spider-boxes' ),
					'static' => __( 'Static Options', 'spider-boxes' ),
					'posts'  => __( 'WordPress Posts', 'spider-boxes' ),
					'users'  => __( 'WordPress Users', 'spider-boxes' ),
				),
			),
			array(
				'id'          => 'min_characters',
				'type'        => 'number',
				'title'       => __( 'Minimum Characters', 'spider-boxes' ),
				'description' => __( 'Minimum characters before showing suggestions', 'spider-boxes' ),
				'value'       => $existing_config['min_characters'] ?? 2,
				'min'         => 1,
				'max'         => 10,
			),
		);
	}

	/**
	 * Get conditional logic configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Conditional logic configuration fields
	 */
	private function get_conditional_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'conditional_logic',
				'type'        => 'checkbox',
				'title'       => __( 'Enable Conditional Logic', 'spider-boxes' ),
				'description' => __( 'Show/hide this field based on other field values', 'spider-boxes' ),
				'value'       => $existing_config['conditional_logic'] ?? false,
			),
			array(
				'id'          => 'conditional_rules',
				'type'        => 'textarea',
				'title'       => __( 'Conditional Rules', 'spider-boxes' ),
				'description' => __( 'JSON array of conditional rules', 'spider-boxes' ),
				'value'       => $existing_config['conditional_rules'] ?? '',
				'rows'        => 6,
				'placeholder' => '[{"field": "field_id", "operator": "equals", "value": "show_value"}]',
				'conditional' => array(
					'field' => 'conditional_logic',
					'value' => true,
				),
			),
		);
	}


	/**
	 * Get repeater configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Repeater configuration fields
	 */
	private function get_repeater_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'     => 'validation',
				'type'   => 'repeater',
				'title'  => __( 'Example Repeater', 'spider-boxes' ),
				'value'  => $existing_config['repeater'] ?? 0,
				'fields' => array(
					array(
						'id'      => 'field_1',
						'type'    => 'select',
						'label'   => __( 'Field 1', 'spider-boxes' ),
						'options' => array(
							'min_length' => __( 'Minimum Length', 'spider-boxes' ),
							'max_length' => __( 'Maximum Length', 'spider-boxes' ),
							'regex'      => __( 'Regular Expression', 'spider-boxes' ),
							'custom'     => __( 'Custom Function', 'spider-boxes' ),
						),
					),
					array(
						'id'    => 'field_2',
						'type'  => 'text',
						'label' => __( 'Field 2', 'spider-boxes' ),
					),
					array(
						'id'    => 'field_3',
						'type'  => 'text',
						'label' => __( 'Field 3', 'spider-boxes' ),
					),
				),
			),

		);
	}

	/**
	 * Get color picker configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Color configuration fields
	 */
	private function get_color_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'color_format',
				'type'        => 'select',
				'title'       => __( 'Color Format', 'spider-boxes' ),
				'description' => __( 'Format for color value storage', 'spider-boxes' ),
				'value'       => $existing_config['color_format'] ?? 'hex',
				'options'     => array(
					'hex'  => __( 'Hexadecimal (#ffffff)', 'spider-boxes' ),
					'rgb'  => __( 'RGB (255, 255, 255)', 'spider-boxes' ),
					'rgba' => __( 'RGBA (255, 255, 255, 1)', 'spider-boxes' ),
					'hsl'  => __( 'HSL (0, 0%, 100%)', 'spider-boxes' ),
				),
			),
			array(
				'id'          => 'alpha_channel',
				'type'        => 'checkbox',
				'title'       => __( 'Enable Alpha Channel', 'spider-boxes' ),
				'description' => __( 'Allow transparency/opacity selection', 'spider-boxes' ),
				'value'       => $existing_config['alpha_channel'] ?? false,
			),
		);
	}

	/**
	 * Get relationship configuration fields
	 *
	 * @param array $existing_config Existing configuration
	 * @return array Relationship configuration fields
	 */
	private function get_relationship_config_fields( array $existing_config ): array {
		return array(
			array(
				'id'          => 'post_type',
				'type'        => 'select',
				'title'       => __( 'Post Type', 'spider-boxes' ),
				'description' => __( 'Which post type to relate to', 'spider-boxes' ),
				'value'       => $existing_config['post_type'] ?? 'post',
				'options'     => $this->get_post_types(),
			),
			array(
				'id'          => 'return_format',
				'type'        => 'select',
				'title'       => __( 'Return Format', 'spider-boxes' ),
				'description' => __( 'How to return the selected data', 'spider-boxes' ),
				'value'       => $existing_config['return_format'] ?? 'id',
				'options'     => array(
					'id'     => __( 'Post ID', 'spider-boxes' ),
					'object' => __( 'Post Object', 'spider-boxes' ),
					'url'    => __( 'Post URL', 'spider-boxes' ),
				),
			),
		);
	}

	/**
	 * Get available post types
	 *
	 * @return array Post types array
	 */
	private function get_post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$options    = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}
}
