<?php
/**
 * React Select Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * React Select Field Class
 */
class ReactSelectField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'        => 'react-select',
				'options'     => array(),
				'multiple'    => false,
				'async'       => false,
				'ajax_action' => '',
				'placeholder' => __( 'Choose an option...', 'spider-boxes' ),
				'searchable'  => true,
				'clearable'   => true,
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
		$value       = $value !== null ? $value : $this->config['value'];
		$options     = $this->config['options'];
		$multiple    = $this->config['multiple'];
		$async       = $this->config['async'];
		$ajax_action = $this->config['ajax_action'];
		$placeholder = $this->config['placeholder'];
		$searchable  = $this->config['searchable'];
		$clearable   = $this->config['clearable'];

		// Ensure value is array for multiple select
		if ( $multiple && ! is_array( $value ) ) {
			$value = ! empty( $value ) ? array( $value ) : array();
		}

		// Container for React component
		$field_id = 'spider-boxes-react-select-' . $this->id;
		$content  = '<div id="' . esc_attr( $field_id ) . '" class="spider-boxes-react-select-container"></div>';

		// Hidden input to store value
		$input_name = $multiple ? $this->id . '[]' : $this->id;
		if ( $multiple && is_array( $value ) ) {
			foreach ( $value as $val ) {
				$content .= '<input type="hidden" name="' . esc_attr( $input_name ) . '" value="' . esc_attr( $val ) . '" />';
			}
		} else {
			$content .= '<input type="hidden" name="' . esc_attr( $this->id ) . '" value="' . esc_attr( $value ) . '" />';
		}

		// Enqueue React Select script
		$this->enqueue_react_select_script(
			$field_id,
			array(
				'value'       => $value,
				'options'     => $options,
				'multiple'    => $multiple,
				'async'       => $async,
				'ajaxAction'  => $ajax_action,
				'placeholder' => $placeholder,
				'searchable'  => $searchable,
				'clearable'   => $clearable,
				'fieldId'     => $this->id,
			)
		);

		return $this->wrap_field( $content );
	}

	/**
	 * Enqueue React Select script
	 *
	 * @param string $field_id Field container ID
	 * @param array  $config Field configuration
	 */
	private function enqueue_react_select_script( $field_id, $config ) {
		// Enqueue React and ReactDOM if not already loaded
		wp_enqueue_script( 'react' );
		wp_enqueue_script( 'react-dom' );

		add_action(
			'admin_footer',
			function () use ( $field_id, $config ) {
				?>
			<script>
			(function() {
				const React = window.React;
				const ReactDOM = window.ReactDOM;
				const { useState, useEffect } = React;
				
				const ReactSelect = ({ config }) => {
					const [value, setValue] = useState(config.value || (config.multiple ? [] : ''));
					const [options, setOptions] = useState(config.options || []);
					const [loading, setLoading] = useState(false);
					
					// Convert options to React Select format
					const formatOptions = (opts) => {
						return Object.entries(opts).map(([val, label]) => ({
							value: val,
							label: typeof label === 'object' ? label.label : label
						}));
					};
					
					const formatValue = (val) => {
						if (config.multiple) {
							return Array.isArray(val) ? val.map(v => ({ value: v, label: findOptionLabel(v) })) : [];
						} else {
							return val ? { value: val, label: findOptionLabel(val) } : null;
						}
					};
					
					const findOptionLabel = (val) => {
						const option = Object.entries(config.options).find(([optVal]) => optVal === val);
						return option ? (typeof option[1] === 'object' ? option[1].label : option[1]) : val;
					};
					
					const handleChange = (selectedOption) => {
						let newValue;
						if (config.multiple) {
							newValue = selectedOption ? selectedOption.map(opt => opt.value) : [];
						} else {
							newValue = selectedOption ? selectedOption.value : '';
						}
						
						setValue(newValue);
						updateHiddenInputs(newValue);
					};
					
					const updateHiddenInputs = (newValue) => {
						const container = document.getElementById('<?php echo esc_js( $field_id ); ?>').parentElement;
						const hiddenInputs = container.querySelectorAll('input[type="hidden"]');
						
						// Remove existing hidden inputs
						hiddenInputs.forEach(input => input.remove());
						
						// Add new hidden inputs
						if (config.multiple && Array.isArray(newValue)) {
							newValue.forEach(val => {
								const input = document.createElement('input');
								input.type = 'hidden';
								input.name = config.fieldId + '[]';
								input.value = val;
								container.appendChild(input);
							});
						} else {
							const input = document.createElement('input');
							input.type = 'hidden';
							input.name = config.fieldId;
							input.value = newValue || '';
							container.appendChild(input);
						}
					};
					
					const loadAsyncOptions = async (inputValue) => {
						if (!config.async || !config.ajaxAction) {
							return formatOptions(config.options);
						}
						
						setLoading(true);
						
						try {
							const response = await fetch(ajaxurl, {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded',
								},
								body: new URLSearchParams({
									action: config.ajaxAction,
									search: inputValue,
									nonce: '<?php echo wp_create_nonce( 'spider_boxes_ajax' ); ?>'
								})
							});
							
							const data = await response.json();
							setLoading(false);
							
							if (data.success) {
								return formatOptions(data.data);
							} else {
								console.error('AJAX error:', data.data);
								return [];
							}
						} catch (error) {
							console.error('Network error:', error);
							setLoading(false);
							return [];
						}
					};
					
					// Simple select component (this would be replaced with actual React Select library)
					return React.createElement('div', { className: 'spider-boxes-react-select' },
						React.createElement('select', {
							multiple: config.multiple,
							value: config.multiple ? value : (value || ''),
							onChange: (e) => {
								const selectedValues = config.multiple 
									? Array.from(e.target.selectedOptions).map(opt => opt.value)
									: e.target.value;
								handleChange(config.multiple 
									? selectedValues.map(val => ({ value: val, label: findOptionLabel(val) }))
									: selectedValues ? { value: selectedValues, label: findOptionLabel(selectedValues) } : null
								);
							},
							className: 'spider-boxes-select-field'
						}, [
							!config.multiple && React.createElement('option', { key: '', value: '' }, config.placeholder),
							...Object.entries(config.options).map(([val, label]) =>
								React.createElement('option', { 
									key: val, 
									value: val 
								}, typeof label === 'object' ? label.label : label)
							)
						])
					);
				};
				
				// Render the component
				const container = document.getElementById('<?php echo esc_js( $field_id ); ?>');
				if (container && React && ReactDOM) {
					ReactDOM.render(
						React.createElement(ReactSelect, { config: <?php echo wp_json_encode( $config ); ?> }),
						container
					);
				}
			})();
			</script>
				<?php
			}
		);
	}

	/**
	 * Sanitize field value
	 *
	 * @param mixed $value Raw value
	 * @return mixed
	 */
	public function sanitize( $value ) {
		if ( $this->config['multiple'] ) {
			if ( ! is_array( $value ) ) {
				return array();
			}
			return array_map( 'sanitize_text_field', $value );
		} else {
			return sanitize_text_field( $value );
		}
	}

	/**
	 * Validate field value
	 *
	 * @param mixed $value Value to validate
	 * @return bool|\WP_Error
	 */
	public function validate( $value ) {
		if ( empty( $value ) ) {
			return true; // Allow empty values
		}

		// If not async, validate against provided options
		if ( ! $this->config['async'] ) {
			$options = array_keys( $this->config['options'] );

			if ( $this->config['multiple'] ) {
				if ( ! is_array( $value ) ) {
					return new \WP_Error( 'invalid_format', __( 'Multiple select value must be an array', 'spider-boxes' ) );
				}

				foreach ( $value as $val ) {
					if ( ! in_array( $val, $options ) ) {
						return new \WP_Error( 'invalid_option', __( 'Invalid option selected', 'spider-boxes' ) );
					}
				}
			} elseif ( ! in_array( $value, $options ) ) {
					return new \WP_Error( 'invalid_option', __( 'Invalid option selected', 'spider-boxes' ) );
			}
		}

		return true;
	}
}
