<?php
/**
 * Admin setting page.
 *
 * @package PreventBruteForceLogin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  Class kdbm_pbfl_settings.
 */
final class Kdbm_Pbfl_Settings {


	/**
	 * Call WordPress action to make settings page
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'submenu_page' ), 999 );
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 * Add custom submenu page under the Settings menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function submenu_page() {

		add_submenu_page(
			'options-general.php',
			__( 'Prevent Brute Force', 'prevent-brute-force-login' ),
			__( 'Prevent Brute Force', 'prevent-brute-force-login' ),
			'manage_options',
			'kdbm-pbfl-settings',
			array( $this, 'render_submenu_page' )
		);
	}


	/**
	 * Content for the custom submenu page under the Settings menu.
	 *
	 * @since 1.0.0
	 * @see $this->submenu_page()
	 * @return void
	 */
	public function render_submenu_page() {
		?>
		<div class="wrap kdbm-pbfl-settings">

			<h2><?php esc_html_e( 'Prevent Brute Force Login', 'prevent-brute-force-login' ); ?></h2>
			<?php settings_errors(); ?>
			<?php $active_tab = ( isset( $_GET['tab'] ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings'; ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo add_query_arg( 'tab', 'settings' ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'prevent-brute-force-login' ); ?></a>
				<a href="<?php echo add_query_arg( 'tab', 'admin-email' ); ?>" class="nav-tab <?php echo 'admin-email' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Admin Notification', 'prevent-brute-force-login' ); ?></a>
				<a href="<?php echo add_query_arg( 'tab', 'unlock-email' ); ?>" class="nav-tab <?php echo 'unlock-email' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Unlock Request', 'prevent-brute-force-login' ); ?></a>
			</h2>

			<form method="post" action="options.php">
				<?php
				if ( 'admin-email' === $active_tab ) {

					settings_fields( 'kdbm_pbfl_admin_email_page' );

					do_settings_sections( 'kdbm_pbfl_admin_email_page' );
				} elseif ( 'unlock-email' === $active_tab ) {

					settings_fields( 'kdbm_pbfl_unlock_email_page' );

					do_settings_sections( 'kdbm_pbfl_unlock_email_page' );
				} else {

					settings_fields( 'kdbm_pbfl_settings_page' );

					do_settings_sections( 'kdbm_pbfl_settings_page' );
				}

				submit_button();

				?>
			</form>

		</div>
		<?php
	}

	/**
	 * Register custom setting sections and fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {

		register_setting(
			'kdbm_pbfl_settings_page',
			'kdbm_pbfl_settings'
		);

		add_settings_section(
			'kdbm_pbfl_settings_page_section',
			false,
			false,
			'kdbm_pbfl_settings_page'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_enable',
			esc_html__( 'Enable Plugin', 'prevent-brute-force-login' ),
			array( $this, 'render_field_enable' ),
			'kdbm_pbfl_settings_page',
			'kdbm_pbfl_settings_page_section'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_max_login_attempts',
			esc_html__( 'Max login attempts', 'prevent-brute-force-login' ),
			array( $this, 'render_field_max_login_attempts' ),
			'kdbm_pbfl_settings_page',
			'kdbm_pbfl_settings_page_section'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_retry_time_period',
			esc_html__( 'Retry max period', 'prevent-brute-force-login' ),
			array( $this, 'render_field_retry_time_period' ),
			'kdbm_pbfl_settings_page',
			'kdbm_pbfl_settings_page_section'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_lockout_time_length',
			esc_html__( 'Retry max period', 'prevent-brute-force-login' ),
			array( $this, 'render_field_lockout_time_length' ),
			'kdbm_pbfl_settings_page',
			'kdbm_pbfl_settings_page_section'
		);

		register_setting(
			'kdbm_pbfl_admin_email_page',
			'kdbm_pbfl_admin_email'
		);

		add_settings_section(
			'kdbm_pbfl_admin_email_page_section',
			esc_html__( 'Admin Email', 'prevent-brute-force-login' ),
			false,
			'kdbm_pbfl_admin_email_page'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_admin_email_subject',
			esc_html__( 'Subject', 'prevent-brute-force-login' ),
			array( $this, 'render_field_admin_email_subject' ),
			'kdbm_pbfl_admin_email_page',
			'kdbm_pbfl_admin_email_page_section'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_admin_email_body',
			esc_html__( 'Body', 'prevent-brute-force-login' ),
			array( $this, 'render_field_admin_email_body' ),
			'kdbm_pbfl_admin_email_page',
			'kdbm_pbfl_admin_email_page_section'
		);

		register_setting(
			'kdbm_pbfl_unlock_email_page',
			'kdbm_pbfl_unlock_email'
		);

		add_settings_section(
			'kdbm_pbfl_unlock_email_page_section',
			esc_html__( 'Unlock Request Emails', 'prevent-brute-force-login' ),
			false,
			'kdbm_pbfl_unlock_email_page'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_unlock_email_subject',
			esc_html__( 'Subject', 'prevent-brute-force-login' ),
			array( $this, 'render_field_unlock_email_subject' ),
			'kdbm_pbfl_unlock_email_page',
			'kdbm_pbfl_unlock_email_page_section'
		);

		add_settings_field(
			'kdbm_pbfl_settings_field_unlock_email_body',
			esc_html__( 'Body', 'prevent-brute-force-login' ),
			array( $this, 'render_field_unlock_email_body' ),
			'kdbm_pbfl_unlock_email_page',
			'kdbm_pbfl_unlock_email_page_section'
		);
	}


	/**
	 * Content for the subject of unlock email field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_unlock_email_subject() {

		$value = kdbm_pbfl_get_saved_unlock_email( 'unlock_email_subject' );

		printf(
			'<input name="kdbm_pbfl_unlock_email[unlock_email_subject]" type="text" value="%s" class="regular-text">',
			esc_attr( $value )
		);
	}

	/**
	 * Content for the body of unlock email field.
	 *
	 * @see $this->init()
	 */
	/**
	 * Content for the body of unlock email field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_unlock_email_body() {

		$value = kdbm_pbfl_get_saved_unlock_email( 'unlock_email_body' );

		wp_editor(
			$value,
			'unlock_email_body',
			array(
				'textarea_name' => 'kdbm_pbfl_unlock_email[unlock_email_body]',
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'       => array(
					'toolbar1' => 'bold, italic, underline, link',
				),
			)
		);
	}

	/**
	 * Content for the subject of admin email field.
	 *
	 * @see $this->init()
	 */
	/**
	 * Content for the subject of admin email field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_admin_email_subject() {

		$value = kdbm_pbfl_get_saved_admin_email( 'admin_email_subject' );

		printf(
			'<input name="kdbm_pbfl_admin_email[admin_email_subject]" type="text" value="%s" class="regular-text">',
			esc_attr( $value )
		);
	}

	/**
	 * Content for the body of admin email field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_admin_email_body() {

		$value = kdbm_pbfl_get_saved_admin_email( 'admin_email_body' );

		wp_editor(
			$value,
			'admin_email_body',
			array(
				'textarea_name' => 'kdbm_pbfl_admin_email[admin_email_body]',
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'       => array(
					'toolbar1' => 'bold, italic, underline, link',
				),
			)
		);
	}


	/**
	 * Content for the enable the plugin setting field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_enable() {

		$value = kdbm_pbfl_get_saved_setting( 'enable' );

		printf(
			'<input name="kdbm_pbfl_settings[enable]" type="hidden" value="false"><input name="kdbm_pbfl_settings[enable]" type="checkbox" value="true" %s>',
			checked( esc_attr( $value ), 'true', false )
		);
	}


	/**
	 * Content for the max login attempts setting field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_max_login_attempts() {

		$value = kdbm_pbfl_get_saved_setting( 'max_login_attempts' );

		printf(
			'<input name="kdbm_pbfl_settings[max_login_attempts]" type="number" step="1" min="3" id="max_login_attempts" value="%d" class="small-text"> times',
			esc_attr( $value )
		);
	}


	/**
	 * Content for the retry time period setting field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_retry_time_period() {

		$value = kdbm_pbfl_get_saved_setting( 'retry_time_period' );

		printf(
			'<input name="kdbm_pbfl_settings[retry_time_period]" type="number" step="1" min="10" id="retry_time_period" value="%d" class="small-text"> minutes',
			esc_attr( $value )
		);
	}


	/**
	 * Content for the lockout time setting field.
	 *
	 * @since 1.0.0
	 * @see $this->init()
	 * @return void
	 */
	public function render_field_lockout_time_length() {

		$value = kdbm_pbfl_get_saved_setting( 'lockout_time_length' );

		printf(
			'<input name="kdbm_pbfl_settings[lockout_time_length]" type="number" step="1" min="10" id="lockout_time_length" value="%d" class="small-text"> minutes',
			esc_attr( $value )
		);
	}

}

// Instance Class.
new Kdbm_Pbfl_Settings();
