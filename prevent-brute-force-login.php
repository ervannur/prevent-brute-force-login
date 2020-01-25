<?php
/**
 * Plugin Name: Prevent Brute Force Login
 * Description: Prevent site from brute force by limit failed login attempts
 * Version: 1.0.1
 * Author: Ervan Adhitiya
 * Text Domain: prevent-brute-force-login
 * Domain Path: /languages
 *
 * @package PreventBruteForceLogin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class kdbm_pdfl
 */
class Kdbm_Pbfl {

	/**
	 * Version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Saved Settings.
	 *
	 * @var array<string,string>
	 */
	public $saved_settings;

	/**
	 * Saved admin email.
	 *
	 * @var string
	 */
	public $saved_admin_email;

	/**
	 * Saved unclock email.
	 *
	 * @var string
	 */
	public $saved_unlock_email;


	/**
	 * A dummy constructor to ensure kdbm_pbfl is only initialized once
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		/* Do nothing here */
	}

	/**
	 * The real constructor to initialize Limit Login
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function initialize() {
		global $wpdb;

		/**
		* Settings.
		*
		* @var array
		*/
		$this->settings = array(

			// basic.
			'name'                 => __( 'Prevent Brute Force Login', 'prevent-brute-force-login' ),
			'version'              => $this->version,

			// urls.
			'file'                 => __FILE__,
			'basename'             => plugin_basename( __FILE__ ),
			'path'                 => plugin_dir_path( __FILE__ ),
			'url'                  => plugin_dir_url( __FILE__ ),

			// options default.
			'enable'               => 'true',
			'max_login_attempts'   => 5,
			'retry_time_period'    => 60, // in minutes.
			'lockout_time_length'  => 60, // in minutes.

			// database.
			'db_login_fails'       => $wpdb->prefix . 'kdbm_pbfl_login_fails',
			'db_lockdowns'         => $wpdb->prefix . 'kdbm_pbfl_lockdowns',

			// Emails.
			'admin_email_subject'  => __( '[{site_url}] Site Lockout Notification', 'prevent-brute-force-login' ),
			'admin_email_body'     => __(
				'A lockdown event has occurred due to too many failed login attempts. \n\n<strong style="color: #ee782e;">Username:</strong> {username} \n<strong style="color: #ee782e;">IP Address:</strong> {ip} \n<strong style="color: #ee782e;">IP Range:</strong> {ip_range}.* \n',
				'prevent-brute-force-login'
			),
			'unlock_email_subject' => __( '[{site_url}] Unlock Request Notification', 'prevent-brute-force-login' ),
			'unlock_email_body'    => __(
				'You have requested for the account with email address {email} to be unlocked. Please click the link below to unlock your account:\n\n<strong style="color: #ee782e;">Unlock link:</strong> {unlock_link}\n\nAfter clicking the above link you will be able to login to the WordPress administration panel.',
				'prevent-brute-force-login'
			),
		);

		// constants.
		$this->define( 'KDBM_PBFL_URL', $this->settings['url'] );
		$this->define( 'KDBM_PBFL_PATH', $this->settings['path'] );

		// include all files.
		include_once KDBM_PBFL_PATH . 'includes/api/api-helpers.php';
		include_once KDBM_PBFL_PATH . 'includes/lockdown.php';
		include_once KDBM_PBFL_PATH . 'includes/notification.php';
		include_once KDBM_PBFL_PATH . 'includes/admin/settings.php';

		// plugin Event.
		register_activation_hook( $this->settings['file'], array( $this, 'activate_plugin' ) );

		// actions.
		add_action( 'init', array( $this, 'init' ), 5 );
	}

	/**
	 * This function will run after all plugins and theme functions have been included
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// textdomain.
		$this->load_plugin_textdomain();
	}

	/**
	 * This function will run once when plugin activated
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate_plugin() {
		// run when plugin activated.
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// include upgrade.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// create table bundle log.
		$login_fails_db_name = $this->settings['db_login_fails'];
		$sql                 = "CREATE TABLE IF NOT EXISTS $login_fails_db_name (
			login_attempt_id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20),
			login_attempt_date datetime NOT NULL default '0000-00-00 00:00:00',
			login_attempt_ip varchar(100) NOT NULL default '',
			PRIMARY KEY  (login_attempt_ID)
		) $charset_collate;";
		dbDelta( $sql );

		// create table bundle log.
		$lockdowns_db_name = $this->settings['db_lockdowns'];
		$sql               = "CREATE TABLE IF NOT EXISTS $lockdowns_db_name (
			lockdown_id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20),
			lockdown_date datetime NOT NULL default '0000-00-00 00:00:00',
			release_date datetime NOT NULL default '0000-00-00 00:00:00',
			lockdown_ip varchar(100) NOT NULL default '',
			lockdown_reason varchar(100) NOT NULL default '',
			unlock_key varchar(100) NOT NULL default '',
			whitelists_ip longtext,
			PRIMARY KEY  (lockdown_ID)
		) $charset_collate;";
		dbDelta( $sql );
	}


	/**
	 * This function will load the textdomain file
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_plugin_textdomain() {
		// vars.
		load_plugin_textdomain( 'prevent-brute-force-login', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}


	/**
	 * This function will safely define a constant
	 *
	 * @since 1.0.0
	 * @param string $name  Constant name.
	 * @param mixed  $value Constant value.
	 * @return mixed
	 */
	public function define( $name, $value = true ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}


	/**
	 * This function will return a value from the settings array found in the kdbm_pbfl object
	 *
	 * @since 1.0.0
	 * @param  string $name  Setting name.
	 * @param  mixed  $value Setting default value.
	 * @return mixed
	 */
	public function get_setting( $name, $value = null ) {
		// check settings.
		if ( isset( $this->settings[ $name ] ) ) {

			$value = $this->settings[ $name ];

		}

		// return.
		return $value;
	}


	/**
	 * Get current client ip
	 *
	 * @since 1.0.0
	 * @return string ip address
	 */
	public static function get_user_ip() {

		$ipaddress = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ipaddress = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ); // phpcs:ignore
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ipaddress = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ); // phpcs:ignore
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ipaddress = wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ); // phpcs:ignore
		} elseif ( isset( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) ) {
			$ipaddress = wp_unslash( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ); // phpcs:ignore
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ipaddress = wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ); // phpcs:ignore
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ipaddress = wp_unslash( $_SERVER['HTTP_FORWARDED'] ); // phpcs:ignore
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ipaddress = wp_unslash( $_SERVER['REMOTE_ADDR'] ); // phpcs:ignore
		} else {
			$ipaddress = 'UNKNOWN';
		}

		return sanitize_text_field( $ipaddress );
	}


	/**
	 * Returns the first three octets of a sanitized IP address so it can used as an IP address range
	 *
	 * @since 1.0.0
	 * @param  string $ip IP Address.
	 * @return string ip  range.
	 */
	public static function get_ip_range( $ip ) {
		$ip_range = '';
		$valid_ip = filter_var( $ip, FILTER_VALIDATE_IP ); // Sanitize the IP address.

		if ( $valid_ip ) {
			$ip_type = \WP_Http::is_ip_address( $ip ); // returns 4 or 6 if ipv4 or ipv6 or false if invalid.
			if ( 6 === $ip_type || false === $ip_type ) {
				return ''; // for now return empty if ipv6 or invalid IP.
			}
			$ip_range = substr( $valid_ip, 0, strrpos( $valid_ip, '.' ) ); // strip last portion of address to leave an IP range.
		}
		return $ip_range;
	}


	/**
	 * Get option from database if available and return from settings if not.
	 *
	 * @since  1.0.0
	 * @param  string $option Saved setting name.
	 * @return mixed
	 */
	public function get_saved_settings( $option ) {
		if ( ! $this->saved_settings ) {
			$this->saved_settings = (array) get_option( 'kdbm_pbfl_settings', array() );
		}

		if ( isset( $this->saved_settings[ $option ] ) && ! empty( $this->saved_settings[ $option ] ) ) {
			return $this->saved_settings[ $option ];
		}

		return $this->settings[ $option ];
	}


	/**
	 * Get option from database if available and return from settings if not.
	 *
	 * @since 1.0.0
	 * @param  string $option Option.
	 * @return mixed
	 */
	public function get_saved_admin_email( $option ) {
		if ( ! $this->saved_admin_email ) {
			$this->saved_admin_email = (array) get_option( 'kdbm_pbfl_admin_email', array() );
		}

		if ( isset( $this->saved_admin_email[ $option ] ) && ! empty( $this->saved_admin_email[ $option ] ) ) {
			return $this->saved_admin_email[ $option ];
		}

		return $this->settings[ $option ];
	}


	/**
	 * Get option from database if available and return from settings if not.
	 *
	 * @since 1.0.0
	 * @param  string $option Option.
	 * @return mixed
	 */
	public function get_saved_unlock_email( $option ) {
		if ( ! $this->saved_unlock_email ) {
			$this->saved_unlock_email = (array) get_option( 'kdbm_pbfl_unlock_email', array() );
		}

		if ( isset( $this->saved_unlock_email[ $option ] ) && ! empty( $this->saved_unlock_email[ $option ] ) ) {
			return $this->saved_unlock_email[ $option ];
		}

		return $this->settings[ $option ];
	}

}

/**
 * The main function responsible for returning the one true kdbm_pbfl Instance to functions everywhere.
 * Use this function like you would a global variable, except without needing to declare the global.
 *
 * @since 1.0.0
 * @return object main object of kdbm_pbfl plugin
 */
function kdbm_pbfl() {

	global $kdbm_pbfl;

	if ( ! isset( $kdbm_pbfl ) ) {

		$kdbm_pbfl = new kdbm_pbfl();

		$kdbm_pbfl->initialize();

	}

	return $kdbm_pbfl;
}

// initialize.
kdbm_pbfl();
