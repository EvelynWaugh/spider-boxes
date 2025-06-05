<?php
/**
 * Form Type
 *
 * @package SpiderBoxes
 */

namespace SpiderBoxes\Sections;

use SpiderBoxes\Sections\BaseSection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Type Class
 */
class FormType extends BaseSection {

	/**
	 * Section type
	 *
	 * @var string
	 */
	protected $type = 'form';

	/**
	 * Default configuration
	 *
	 * @var array
	 */
	protected $default_config = array(
		'title'        => '',
		'description'  => '',
		'class'        => '',
		'method'       => 'post',
		'action'       => '',
		'ajax'         => false,
		'nonce_action' => '',
		'submit_text'  => 'Submit',
		'fields'       => array(),
		'components'   => array(),
	);

	/**
	 * Render the form
	 *
	 * @param array $value Current values.
	 * @return string
	 */
	public function render( $value = array() ) {
		$config = wp_parse_args( $this->config, $this->default_config );

		ob_start();
		?>
		<div class="spider-boxes-form-wrapper <?php echo esc_attr( $config['class'] ); ?>">
			<?php if ( ! empty( $config['title'] ) ) : ?>
				<div class="spider-boxes-form-header">
					<h3 class="spider-boxes-form-title"><?php echo esc_html( $config['title'] ); ?></h3>
					<?php if ( ! empty( $config['description'] ) ) : ?>
						<p class="spider-boxes-form-description"><?php echo esc_html( $config['description'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<form 
				class="spider-boxes-form <?php echo $config['ajax'] ? 'spider-boxes-form-ajax' : ''; ?>" 
				method="<?php echo esc_attr( $config['method'] ); ?>"
				<?php if ( ! empty( $config['action'] ) ) : ?>
					action="<?php echo esc_url( $config['action'] ); ?>"
				<?php endif; ?>
			>
				<?php
				// Add nonce field if specified
				if ( ! empty( $config['nonce_action'] ) ) {
					wp_nonce_field( $config['nonce_action'], '_spider_boxes_nonce' );
				}

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
				
				<div class="spider-boxes-form-submit">
					<button type="submit" class="spider-boxes-button spider-boxes-button-primary">
						<?php echo esc_html( $config['submit_text'] ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sanitize form values
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
	 * Validate form values
	 *
	 * @param mixed $value Value to validate.
	 * @return bool|WP_Error
	 */
	public function validate( $value ) {
		if ( ! is_array( $value ) ) {
			return new \WP_Error( 'invalid_form_value', __( 'Form value must be an array.', 'spider-boxes' ) );
		}

		$config = wp_parse_args( $this->config, $this->default_config );

		// Validate nonce if specified
		if ( ! empty( $config['nonce_action'] ) && ! empty( $_POST['_spider_boxes_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_spider_boxes_nonce'] ) ), $config['nonce_action'] ) ) {
				return new \WP_Error( 'invalid_nonce', __( 'Invalid security token.', 'spider-boxes' ) );
			}
		}

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
