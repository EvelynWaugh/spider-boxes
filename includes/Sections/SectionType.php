<?php
/**
 * Section Type
 *
 * @package SpiderBoxes
 */

namespace SpiderBoxes\Sections;

use SpiderBoxes\Sections\BaseSection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Section Type Class
 */
class SectionType extends BaseSection {

	/**
	 * Section type
	 *
	 * @var string
	 */
	protected $type = 'section';

	/**
	 * Default configuration
	 *
	 * @var array
	 */
	protected $default_config = array(
		'title'       => '',
		'description' => '',
		'class'       => '',
		'collapsible' => false,
		'collapsed'   => false,
		'fields'      => array(),
		'components'  => array(),
	);

	/**
	 * Render the section
	 *
	 * @param array $value Current values.
	 * @return string
	 */
	public function render( $value = array() ) {
		$config = wp_parse_args( $this->config, $this->default_config );

		ob_start();
		?>
		<div class="spider-boxes-section <?php echo esc_attr( $config['class'] ); ?>" data-collapsible="<?php echo $config['collapsible'] ? 'true' : 'false'; ?>">
			<?php if ( ! empty( $config['title'] ) ) : ?>
				<div class="spider-boxes-section-header">
					<h3 class="spider-boxes-section-title">
						<?php echo esc_html( $config['title'] ); ?>
						<?php if ( $config['collapsible'] ) : ?>
							<button type="button" class="spider-boxes-section-toggle" aria-expanded="<?php echo $config['collapsed'] ? 'false' : 'true'; ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle section', 'spider-boxes' ); ?></span>
								<span class="spider-boxes-section-toggle-icon"></span>
							</button>
						<?php endif; ?>
					</h3>
					<?php if ( ! empty( $config['description'] ) ) : ?>
						<p class="spider-boxes-section-description"><?php echo esc_html( $config['description'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<div class="spider-boxes-section-content" <?php echo $config['collapsed'] ? 'style="display: none;"' : ''; ?>>
				<?php
				// Render fields
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

				// Render components
				if ( ! empty( $config['components'] ) ) {
					foreach ( $config['components'] as $component_config ) {
						$component = $this->component_registry->get( $component_config['type'] );
						if ( $component ) {
							$component->set_config( $component_config );
							$component_value = isset( $value[ $component_config['id'] ] ) ? $value[ $component_config['id'] ] : array();
							echo $component->render( $component_value );
						}
					}
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sanitize section values
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

		// Sanitize field values
		if ( ! empty( $config['fields'] ) ) {
			foreach ( $config['fields'] as $field_config ) {
				$field = $this->field_registry->get( $field_config['type'] );
				if ( $field && isset( $value[ $field_config['id'] ] ) ) {
					$field->set_config( $field_config );
					$sanitized[ $field_config['id'] ] = $field->sanitize( $value[ $field_config['id'] ] );
				}
			}
		}

		// Sanitize component values
		if ( ! empty( $config['components'] ) ) {
			foreach ( $config['components'] as $component_config ) {
				$component = $this->component_registry->get( $component_config['type'] );
				if ( $component && isset( $value[ $component_config['id'] ] ) ) {
					$component->set_config( $component_config );
					$sanitized[ $component_config['id'] ] = $component->sanitize( $value[ $component_config['id'] ] );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Validate section values
	 *
	 * @param mixed $value Value to validate.
	 * @return bool|WP_Error
	 */
	public function validate( $value ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error( 'invalid_section_value', __( 'Section value must be an array.', 'spider-boxes' ) );
		}

		$config = wp_parse_args( $this->config, $this->default_config );

		// Validate field values
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

		// Validate component values
		if ( ! empty( $config['components'] ) ) {
			foreach ( $config['components'] as $component_config ) {
				$component = $this->component_registry->get( $component_config['type'] );
				if ( $component && isset( $value[ $component_config['id'] ] ) ) {
					$component->set_config( $component_config );
					$validation = $component->validate( $value[ $component_config['id'] ] );
					if ( is_wp_error( $validation ) ) {
						return $validation;
					}
				}
			}
		}

		return true;
	}
}
