<?php
/**
 * Admin Pages
 *
 * @package SpiderBoxes\Admin
 */

namespace SpiderBoxes\Admin;

/**
 * Admin Pages Class
 */
class AdminPages {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_admin_settings' ) );
	}

	/**
	 * Add admin menu pages
	 */
	public function add_admin_menu() {
		// Main Spider Boxes page
		add_menu_page(
			esc_html__( 'Spider Boxes', 'spider-boxes' ),
			esc_html__( 'Spider Boxes', 'spider-boxes' ),
			'manage_options',
			'spider-boxes',
			array( $this, 'render_main_page' ),
			'dashicons-layout',
			30
		);

		// Spider Product Reviews subpage
		add_submenu_page(
			'spider-boxes',
			esc_html__( 'Product Reviews', 'spider-boxes' ),
			esc_html__( 'Product Reviews', 'spider-boxes' ),
			'manage_options',
			'spider-boxes-reviews',
			array( $this, 'render_reviews_page' )
		);

		// Settings subpage
		add_submenu_page(
			'spider-boxes',
			esc_html__( 'Settings', 'spider-boxes' ),
			esc_html__( 'Settings', 'spider-boxes' ),
			'manage_options',
			'spider-boxes-settings',
			array( $this, 'render_settings_page' )
		);

		/**
		 * Allow developers to add custom admin pages
		 */
		do_action( 'spider_boxes_admin_menu' );
	}

	/**
	 * Initialize admin settings
	 */
	public function init_admin_settings() {
		// Register settings
		register_setting( 'spider_boxes_settings', 'spider_boxes_options' );

		// Add settings sections
		add_settings_section(
			'spider_boxes_general',
			esc_html__( 'General Settings', 'spider-boxes' ),
			array( $this, 'render_general_section' ),
			'spider_boxes_settings'
		);

		// Add settings fields
		add_settings_field(
			'enable_reviews_management',
			esc_html__( 'Enable Reviews Management', 'spider-boxes' ),
			array( $this, 'render_checkbox_field' ),
			'spider_boxes_settings',
			'spider_boxes_general',
			array(
				'name'        => 'enable_reviews_management',
				'description' => esc_html__( 'Enable advanced WooCommerce reviews management features.', 'spider-boxes' ),
			)
		);

		add_settings_field(
			'custom_field_storage',
			esc_html__( 'Custom Field Storage', 'spider-boxes' ),
			array( $this, 'render_select_field' ),
			'spider_boxes_settings',
			'spider_boxes_general',
			array(
				'name'        => 'custom_field_storage',
				'options'     => array(
					'wp_meta'      => esc_html__( 'WordPress Meta Tables', 'spider-boxes' ),
					'custom_table' => esc_html__( 'Custom Database Table', 'spider-boxes' ),
				),
				'description' => esc_html__( 'Choose how custom field data should be stored.', 'spider-boxes' ),
			)
		);
	}

	/**
	 * Render main admin page
	 */
	public function render_main_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="spider-boxes-main-app"></div>
		</div>
		<?php
	}

	/**
	 * Render reviews page
	 */
	public function render_reviews_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="spider-boxes-reviews-app"></div>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'spider_boxes_settings' );
				do_settings_sections( 'spider_boxes_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render general settings section
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure general Spider Boxes settings.', 'spider-boxes' ) . '</p>';
	}

	/**
	 * Render checkbox field
	 *
	 * @param array $args Field arguments
	 */
	public function render_checkbox_field( $args ) {
		$options = get_option( 'spider_boxes_options', array() );
		$value   = isset( $options[ $args['name'] ] ) ? $options[ $args['name'] ] : '';
		$checked = checked( $value, '1', false );

		printf(
			'<input type="checkbox" id="%1$s" name="spider_boxes_options[%1$s]" value="1" %2$s />',
			esc_attr( $args['name'] ),
			$checked
		);

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render select field
	 *
	 * @param array $args Field arguments
	 */
	public function render_select_field( $args ) {
		$options = get_option( 'spider_boxes_options', array() );
		$value   = isset( $options[ $args['name'] ] ) ? $options[ $args['name'] ] : '';

		printf( '<select id="%s" name="spider_boxes_options[%s]">', esc_attr( $args['name'] ), esc_attr( $args['name'] ) );

		foreach ( $args['options'] as $option_value => $option_label ) {
			$selected = selected( $value, $option_value, false );
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $option_value ),
				$selected,
				esc_html( $option_label )
			);
		}

		echo '</select>';

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}
}
