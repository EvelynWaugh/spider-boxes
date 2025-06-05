<?php
/**
 * Column Component
 *
 * @package SpiderBoxes
 */

namespace SpiderBoxes\Components;

use SpiderBoxes\Components\BaseComponent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Column Component Class
 */
class ColumnComponent extends BaseComponent {

	/**
	 * Component type
	 *
	 * @var string
	 */
	protected $type = 'column';

	/**
	 * Default configuration
	 *
	 * @var array
	 */
	protected $default_config = array(
		'width'  => 'auto',
		'class'  => '',
		'fields' => array(),
	);

	/**
	 * Render the column component
	 *
	 * @param array $value Current values.
	 * @return string
	 */
	public function render( $value = array() ) {
		$config      = wp_parse_args( $this->config, $this->default_config );
		$width_class = $this->get_width_class( $config['width'] );

		ob_start();
		?>
		<div class="spider-boxes-column <?php echo esc_attr( $width_class . ' ' . $config['class'] ); ?>">
			<?php
			if ( ! empty( $config['fields'] ) ) {
				foreach ( $config['fields'] as $field_config ) {
					$field = $this->field_registry->get( $field_config['type'] );
					if ( $field ) {
						$field->set_config( $field_config );
						$field_value = isset( $value[ $field_config['id'] ] ) ? $value[ $field_config['id'] ] : '';
						echo $field->render( $field_value );
					}
				}
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get CSS width class based on configuration
	 *
	 * @param string $width Width configuration.
	 * @return string
	 */
	private function get_width_class( $width ) {
		$width_classes = array(
			'1/12'  => 'spider-boxes-col-1',
			'2/12'  => 'spider-boxes-col-2',
			'3/12'  => 'spider-boxes-col-3',
			'4/12'  => 'spider-boxes-col-4',
			'5/12'  => 'spider-boxes-col-5',
			'6/12'  => 'spider-boxes-col-6',
			'7/12'  => 'spider-boxes-col-7',
			'8/12'  => 'spider-boxes-col-8',
			'9/12'  => 'spider-boxes-col-9',
			'10/12' => 'spider-boxes-col-10',
			'11/12' => 'spider-boxes-col-11',
			'12/12' => 'spider-boxes-col-12',
			'auto'  => 'spider-boxes-col-auto',
		);

		return isset( $width_classes[ $width ] ) ? $width_classes[ $width ] : 'spider-boxes-col-auto';
	}

	/**
	 * Sanitize column component values
	 *
	 * @param mixed $value Value to sanitize.
	 * @return array
	 */
	public function sanitize( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		$config    = wp_parse_args( $this->config, $this->default_config );

		if ( ! empty( $config['fields'] ) ) {
			foreach ( $config['fields'] as $field_config ) {
				$field = $this->field_registry->get( $field_config['type'] );
				if ( $field && isset( $value[ $field_config['id'] ] ) ) {
					$field->set_config( $field_config );
					$sanitized[ $field_config['id'] ] = $field->sanitize( $value[ $field_config['id'] ] );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Validate column component values
	 *
	 * @param mixed $value Value to validate.
	 * @return bool|WP_Error
	 */
	public function validate( $value ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error( 'invalid_column_value', __( 'Column value must be an array.', 'spider-boxes' ) );
		}

		$config = wp_parse_args( $this->config, $this->default_config );

		if ( ! empty( $config['fields'] ) ) {
			foreach ( $config['fields'] as $field_config ) {
				$field = $this->field_registry->get( $field_config['type'] );
				if ( $field && isset( $value[ $field_config['id'] ] ) ) {
					$field->set_config( $field_config );
					$validation = $field->validate( $value[ $field_config['id'] ] );
					if ( is_wp_error( $validation ) ) {
						return $validation;
					}
				}
			}
		}

		return true;
	}
}
