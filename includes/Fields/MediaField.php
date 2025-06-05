<?php
/**
 * Media Field
 *
 * @package SpiderBoxes\Fields
 */

namespace SpiderBoxes\Fields;

/**
 * Media Field Class
 */
class MediaField extends BaseField {

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'type'        => 'media',
				'multiple'    => false,
				'mime_types'  => array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ),
				'button_text' => __( 'Choose Media', 'spider-boxes' ),
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
		$multiple    = $this->config['multiple'];
		$button_text = $this->config['button_text'];

		// Ensure value is array for multiple media
		if ( $multiple && ! is_array( $value ) ) {
			$value = ! empty( $value ) ? array( $value ) : array();
		} elseif ( ! $multiple && is_array( $value ) ) {
			$value = ! empty( $value ) ? $value[0] : '';
		}

		$content = '<div class="spider-boxes-media-field" data-multiple="' . ( $multiple ? 'true' : 'false' ) . '">';

		// Hidden input to store value(s)
		$input_name = $multiple ? $this->id . '[]' : $this->id;
		if ( $multiple && is_array( $value ) ) {
			foreach ( $value as $media_id ) {
				$content .= '<input type="hidden" name="' . esc_attr( $input_name ) . '" value="' . esc_attr( $media_id ) . '" />';
			}
		} else {
			$content .= '<input type="hidden" name="' . esc_attr( $this->id ) . '" value="' . esc_attr( $value ) . '" />';
		}

		// Media preview
		$content .= '<div class="spider-boxes-media-preview">';

		if ( $multiple && is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $media_id ) {
				$content .= $this->render_media_item( $media_id );
			}
		} elseif ( ! $multiple && ! empty( $value ) ) {
			$content .= $this->render_media_item( $value );
		}

		$content .= '</div>';

		// Upload button
		$content .= '<button type="button" class="spider-boxes-button spider-boxes-media-upload" data-field-id="' . esc_attr( $this->id ) . '">';
		$content .= esc_html( $button_text );
		$content .= '</button>';

		$content .= '</div>';

		// Add media uploader script
		$this->enqueue_media_script();

		return $this->wrap_field( $content );
	}

	/**
	 * Render media item
	 *
	 * @param int $media_id Media ID
	 * @return string
	 */
	private function render_media_item( $media_id ) {
		if ( empty( $media_id ) ) {
			return '';
		}

		$attachment = get_post( $media_id );
		if ( ! $attachment ) {
			return '';
		}

		$content = '<div class="spider-boxes-media-item" data-id="' . esc_attr( $media_id ) . '">';

		if ( wp_attachment_is_image( $media_id ) ) {
			$image_url = wp_get_attachment_image_url( $media_id, 'medium' );
			$content  .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $attachment->post_title ) . '" class="spider-boxes-media-image" />';
		} else {
			$content .= '<div class="spider-boxes-media-file">';
			$content .= '<span class="spider-boxes-media-filename">' . esc_html( $attachment->post_title ) . '</span>';
			$content .= '</div>';
		}

		$content .= '<button type="button" class="spider-boxes-media-remove" title="' . esc_attr__( 'Remove', 'spider-boxes' ) . '">';
		$content .= '×';
		$content .= '</button>';
		$content .= '</div>';

		return $content;
	}

	/**
	 * Enqueue media uploader script
	 */
	private function enqueue_media_script() {
		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		add_action(
			'admin_footer',
			function () {
				?>
			<script>
			(function($) {
				$('.spider-boxes-media-upload').on('click', function(e) {
					e.preventDefault();
					
					const button = $(this);
					const fieldId = button.data('field-id');
					const container = button.closest('.spider-boxes-media-field');
					const isMultiple = container.data('multiple');
					
					const mediaUploader = wp.media({
						title: '<?php echo esc_js( __( 'Choose Media', 'spider-boxes' ) ); ?>',
						button: {
							text: '<?php echo esc_js( __( 'Use this media', 'spider-boxes' ) ); ?>'
						},
						multiple: isMultiple
					});
					
					mediaUploader.on('select', function() {
						const selection = mediaUploader.state().get('selection');
						const preview = container.find('.spider-boxes-media-preview');
						
						if (!isMultiple) {
							preview.empty();
							container.find('input[type="hidden"]').remove();
						}
						
						selection.each(function(attachment) {
							const data = attachment.toJSON();
							
							// Add hidden input
							const input = $('<input type="hidden" name="' + fieldId + (isMultiple ? '[]' : '') + '" value="' + data.id + '" />');
							container.prepend(input);
							
							// Add preview
							let mediaItem = '<div class="spider-boxes-media-item" data-id="' + data.id + '">';
							
							if (data.type === 'image') {
								mediaItem += '<img src="' + (data.sizes?.medium?.url || data.url) + '" alt="' + data.title + '" class="spider-boxes-media-image" />';
							} else {
								mediaItem += '<div class="spider-boxes-media-file"><span class="spider-boxes-media-filename">' + data.title + '</span></div>';
							}
							
							mediaItem += '<button type="button" class="spider-boxes-media-remove" title="<?php echo esc_js( __( 'Remove', 'spider-boxes' ) ); ?>">×</button>';
							mediaItem += '</div>';
							
							preview.append(mediaItem);
						});
					});
					
					mediaUploader.open();
				});
				
				$(document).on('click', '.spider-boxes-media-remove', function(e) {
					e.preventDefault();
					const item = $(this).closest('.spider-boxes-media-item');
					const mediaId = item.data('id');
					const container = item.closest('.spider-boxes-media-field');
					
					// Remove hidden input
					container.find('input[value="' + mediaId + '"]').remove();
					
					// Remove preview item
					item.remove();
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
	 * @return mixed
	 */
	public function sanitize( $value ) {
		if ( $this->config['multiple'] ) {
			if ( ! is_array( $value ) ) {
				return array();
			}
			return array_map( 'intval', array_filter( $value ) );
		} else {
			return ! empty( $value ) ? intval( $value ) : '';
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

		$mime_types = $this->config['mime_types'];
		$values     = $this->config['multiple'] ? (array) $value : array( $value );

		foreach ( $values as $media_id ) {
			$attachment = get_post( $media_id );
			if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
				return new \WP_Error( 'invalid_media', __( 'Invalid media file', 'spider-boxes' ) );
			}

			$mime_type = get_post_mime_type( $media_id );
			if ( ! empty( $mime_types ) && ! in_array( $mime_type, $mime_types ) ) {
				return new \WP_Error( 'invalid_mime_type', __( 'Media file type not allowed', 'spider-boxes' ) );
			}
		}

		return true;
	}
}
