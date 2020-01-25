<?php
/**
 * Lockdown class.
 *
 * @package PreventBruteForceLogin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Kdbm_Pbfl_Lockdown
 */
class Kdbm_Pbfl_Lockdown {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'authenticate', array( $this, 'validate_authenticate' ), 100, 3 );
		add_action( 'init', array( $this, 'process_unlock_request' ), 0 );
	}

	/**
	 * Handle authentication steps (in case of failed login):
	 * - increment number of failed logins for $username
	 * - lock the user
	 * - display a generic error message
	 *
	 * @since 1.0.0
	 * @param  WP_User|WP_Error $user WP  User.
	 * @param  string           $username Username.
	 * @param  string           $password Password.
	 * @return WP_User|WP_Error
	 */
	public function validate_authenticate( $user, $username, $password ) {
		if ( true !== (bool) kdbm_pbfl_get_saved_setting( 'enable' ) ) {
			return $user;
		}

		global $wpdb;
		// check if user locked or not.
		$lockdowns_table_name = kdbm_pbfl_get_setting( 'db_lockdowns' );
		$ip                   = kdbm_pbfl_get_user_ip(); // Get the IP address of user.
		$ip_range             = kdbm_pbfl_get_ip_range( $ip );
		$now                  = current_time( 'mysql' );
		$locked_user          = self::current_locked_user();

		if ( $locked_user ) {
			if ( isset( $locked_user->whitelists_ip ) && ! empty( $locked_user->whitelists_ip ) ) {
				$whitelists_ip = ( isset( $locked_user->whitelists_ip ) ) ? (array) @unserialize( $locked_user->whitelists_ip ) : array();
				if ( ! in_array( $ip, $whitelists_ip, true ) ) {
					$this->lock_the_login();
				}
			} else {
				$this->lock_the_login();
			}
		}

		if (
			// Authentication has been successful, there's nothing to do here.
			! is_wp_error( $user )
			||
			// Neither log nor block login attempts with empty username or password.
			empty( $username ) || empty( $password )
		) {
			return $user;
		}

		// increment failed login.
		$userdata = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username ); // Returns WP_User object.
		$user_id  = ( isset( $userdata->ID ) && ! is_wp_error( $userdata ) ) ? $userdata->ID : 0;

		$this->increment_failed_logins( $user_id );

		$max_login_attempts = kdbm_pbfl_get_saved_setting( 'max_login_attempts' );
		$login_fail_count   = $this->get_login_fail_count();
		$remaining_attempts = $max_login_attempts - $login_fail_count;

		if ( $remaining_attempts <= 0 ) {
			$this->lock_the_user( $username, $ip_range, $ip, $user_id, 'login_fail' );
			$this->lock_the_login();
		}

		$errors = new \WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Invalid login credentials.', 'prevent-brute-force-login' ) );
		$errors->add( 'remaining_attempts', sprintf( esc_html__( '%d attempts remaining', 'prevent-brute-force-login' ), $remaining_attempts ) );

		return $errors;
	}

	/**
	 * Lock the login with unlock field
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function lock_the_login() {
		nocache_headers();
		remove_action( 'wp_head', 'head_addons', 7 );
		include_once KDBM_PBFL_PATH . '/includes/forms/unlock-request.php';
		die();
	}

	/**
	 * Adds an entry to the `login_fails` table.
	 *
	 * @since 1.0.0
	 * @param  integer $user_id user ID of the user.
	 * @return void
	 */
	private function increment_failed_logins( $user_id = 0 ) {
		global $wpdb;
		$login_fails_table_name = kdbm_pbfl_get_setting( 'db_login_fails' );
		$ip                     = kdbm_pbfl_get_user_ip();

		$result = $wpdb->insert(
			$login_fails_table_name,
			array(
				'user_id'            => $user_id,
				'login_attempt_date' => current_time( 'mysql' ),
				'login_attempt_ip'   => $ip,
			),
			array( '%d', '%s', '%s' )
		);
	}

	/**
	 * This function queries login_fails table and returns the number of failures for current IP within allowed failure period
	 *
	 * @since 1.0.0
	 * @return integer number of failures
	 */
	private function get_login_fail_count() {
		global $wpdb;
		$login_fails_table_name = kdbm_pbfl_get_setting( 'db_login_fails' );
		$ip                     = kdbm_pbfl_get_user_ip();
		$login_retry_interval   = kdbm_pbfl_get_saved_setting( 'retry_time_period' );
		$now                    = current_time( 'mysql' );

		$login_failures = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(login_attempt_id) FROM $login_fails_table_name WHERE login_attempt_date + INTERVAL %d SECOND >= %s AND login_attempt_ip = %s",
				array( $login_retry_interval * 60, $now, $ip )
			)
		);
		return $login_failures;
	}

	/**
	 * Lock_the_user
	 *
	 * @since 1.0.0
	 * @param  string  $username Username.
	 * @param  string  $ip_range IP Range.
	 * @param  string  $ip       IP Address.
	 * @param  integer $user_id  User ID.
	 * @param  string  $locked_reason Locked Reason.
	 * @return void
	 */
	private function lock_the_user( $username, $ip_range, $ip, $user_id = 0, $locked_reason = 'login_fail' ) {
		global $wpdb;
		$locked_user          = self::current_locked_user();
		$lockdowns_table_name = kdbm_pbfl_get_setting( 'db_lockdowns' );
		$lock_time            = current_time( 'mysql' );
		$release_time         = strtotime( $lock_time . ' + ' . kdbm_pbfl_get_saved_setting( 'lockout_time_length' ) . ' minute' );
		$release_time         = gmdate( 'Y-m-d H:i:s', $release_time );

		// if already has locked record.
		if ( $locked_user ) {
			$whitelists_ip = ( isset( $locked_user->whitelists_ip ) ) ? (array) unserialize( $locked_user->whitelists_ip ) : array();

			$key = array_search( $ip, $whitelists_ip, true );
			if ( false !== $key ) {
				unset( $whitelists_ip[ $key ] );
			}

			$result = $wpdb->update(
				$lockdowns_table_name,
				array(
					'whitelists_ip' => serialize( $whitelists_ip ),
					'release_date'  => $release_time,
				),
				array( 'lockdown_id' => $locked_user->lockdown_id ),
				array( '%s', '%s' )
			);

		} else {
			$result = $wpdb->insert(
				$lockdowns_table_name,
				array(
					'user_id'         => $user_id,
					'lockdown_date'   => $lock_time,
					'release_date'    => $release_time,
					'lockdown_ip'     => $ip,
					'lockdown_reason' => $locked_reason,
					'whitelists_ip'   => serialize( array() ),
				),
				array( '%d', '%s', '%s', '%s', '%s' )
			);
		}

		if ( $result ) {
			Kdbm_Pbfl_Notification::send_ip_lock_notification_email( $username, $ip_range, $ip );
		}
	}

	/**
	 * Get from database the record that make user lockeddown
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public static function current_locked_user() {
		global $wpdb;

		$lockdowns_table_name = kdbm_pbfl_get_setting( 'db_lockdowns' );
		$ip                   = kdbm_pbfl_get_user_ip(); // Get the IP address of user.
		$ip_range             = kdbm_pbfl_get_ip_range( $ip );
		$now                  = current_time( 'mysql' );
		$locked_user          = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $lockdowns_table_name WHERE release_date > %s AND lockdown_ip LIKE %s", array( $now, $ip_range . '%' ) )
		);
		return $locked_user;
	}


	/**
	 * This function will process an unlock request when someone clicks on the special URL
	 * It will check if the special random code matches that in lockdown table for the relevant user
	 * If so, it will unlock the user
	 *
	 * @since 10.0.0
	 * @param  string $unlock_key Unlock Key.
	 * @return void
	 */
	public static function process_unlock_request( $unlock_key ) {
		if ( ! isset( $_GET['auth_key'] ) ) {
			return;
		}

		// If URL contains unlock key in query param then process the request.
		$unlock_key = wp_strip_all_tags( sanitize_text_field( wp_unslash( $_GET['auth_key'] ) ) );

		global $wpdb;
		$lockdowns_table_name = kdbm_pbfl_get_setting( 'db_lockdowns' );
		$ip                   = kdbm_pbfl_get_user_ip();

		// current locked user by unlock key.
		$ip_range    = kdbm_pbfl_get_ip_range( $ip );
		$now         = current_time( 'mysql' );
		$locked_user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $lockdowns_table_name WHERE release_date > %s AND lockdown_ip LIKE %s AND unlock_key = %s",
				array( $now, $ip_range . '%', $unlock_key )
			)
		);

		if ( ! $locked_user ) {
			return;
		}

		$whitelists_ip = ( isset( $locked_user->whitelists_ip ) ) ? (array) @unserialize( $locked_user->whitelists_ip ) : array();
		if ( ! in_array( $ip, $whitelists_ip, true ) ) {
			$whitelists_ip[] = $ip;
		}

		$result = $wpdb->update(
			$lockdowns_table_name,
			array(
				'whitelists_ip' => serialize( $whitelists_ip ),
			),
			array( 'lockdown_id' => $locked_user->lockdown_id ),
			array(
				'%s',
				'%d',
			)
		);

		if ( $result ) {
			self::redirect_to_url( wp_login_url() );
		}
	}

	/**
	 * Redirects to specified URL
	 *
	 * @since 1.0.0
	 * @param string  $url   URL.
	 * @param integer $delay Delay.
	 * @param integer $exit  Exit.
	 */
	public static function redirect_to_url( $url, $delay = '0', $exit = '1' ) {
		if ( empty( $url ) ) {
			printf( '<br /><strong>%s</strong>', __( 'Error! The URL value is empty. Please specify a correct URL value to redirect to!', 'prevent-brute-force-login' ) );
			exit;
		}
		if ( ! headers_sent() ) {
			header( 'Location: ' . $url );
		} else {
			echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '" />';
		}
		if ( '1' === $exit ) {
			exit;
		}
	}
}

new Kdbm_Pbfl_Lockdown();
