<?php
/**
 * Notification email.
 *
 * @package PreventBruteForceLogin
 */

defined( 'ABSPATH' ) || die( 'Can\'t access directly' );

/**
 * Class Kdbm_Pbfl_Notification.
 */
class Kdbm_Pbfl_Notification {

	/**
	 * Send_ip_lock_notification_email
	 *
	 * @since 1.0.0
	 * @param  string $username Username.
	 * @param  string $ip_range Ip Range.
	 * @param  string $ip       IP Address.
	 * @return void
	 */
	public static function send_ip_lock_notification_email( $username, $ip_range, $ip ) {
		$subject   = kdbm_pbfl_get_saved_admin_email( 'admin_email_subject' );
		$email_msg = kdbm_pbfl_get_saved_admin_email( 'admin_email_body' );
		$recipient = get_bloginfo( 'admin_email' );

		$tags = array(
			'{site_name}' => get_bloginfo( 'name' ),
			'{site_url}'  => get_bloginfo( 'url' ),
			'{username}'  => $username,
			'{ip}'        => $ip,
			'{ip_range}'  => $ip_range,
		);

		$subject      = self::convert( $subject, $tags );
		$message      = self::convert( $email_msg, $tags );
		$html_message = self::html_message( $message );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $recipient, $subject, $html_message, $headers );
	}

	/**
	 * Html_message
	 *
	 * @since 1.0.0
	 * @param  string $body body of email message.
	 * @return string full html email message.
	 */
	public static function html_message( $body ) {
		ob_start();
		?>
		<!doctype html>
		<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

		<head>
			<meta charset="utf-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />
			<meta name="format-detection" content="telephone=no" />
			<meta name="x-apple-disable-message-reformatting">
		</head>

		<body>
			<?php echo nl2br( $body ); ?>
		</body>

		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Convert
	 *
	 * @since 1.0.0
	 * @param  string $message Message.
	 * @param  array  $tags Tags.
	 * @return string converted string.
	 */
	public static function convert( $message, $tags ) {
		$find    = array();
		$replace = array();

		foreach ( $tags as $key => $value ) {
			array_push( $find, $key );
			array_push( $replace, $value );
		}

		return str_ireplace( $find, $replace, $message );
	}

	/**
	 * This function sends an unlock request email to a locked out user
	 *
	 * @since 1.0.0
	 * @param  string $email Email.
	 * @param  string $unlock_link Unlock link.
	 * @return void
	 */
	public static function send_unlock_request_email( $email, $unlock_link ) {
		$subject   = kdbm_pbfl_get_saved_unlock_email( 'unlock_email_subject' );
		$email_msg = kdbm_pbfl_get_saved_unlock_email( 'unlock_email_body' );
		$recipient = $email;

		$tags = array(
			'{site_name}'   => get_bloginfo( 'name' ),
			'{site_url}'    => get_bloginfo( 'url' ),
			'{email}'       => $email,
			'{unlock_link}' => $unlock_link,
		);

		$subject      = self::convert( $subject, $tags );
		$message      = self::convert( $email_msg, $tags );
		$html_message = self::html_message( $message );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $recipient, $subject, $html_message, $headers );
	}


	/**
	 * This function generates a special random string and inserts into the lockdown table for the relevant user
	 * It then generates an unlock request link which will be used to send to the user
	 *
	 * @since 1.0.0
	 * @param  string $ip_range IP Range.
	 * @return string unlock link.
	 */
	public static function generate_unlock_request_link( $ip_range ) {
		$locked_user = kdbm_pbfl_lockdown::current_locked_user();
		if ( isset( $locked_user->unlock_key ) && ! empty( $locked_user->unlock_key ) ) {
			$secret_rand_key = $locked_user->unlock_key;
		} else {
			// Get the locked user row from lockdown table.
			global $wpdb;
			$unlock_link          = '';
			$lockdowns_table_name = kdbm_pbfl_get_setting( 'db_lockdowns' );
			$secret_rand_key      = ( md5( uniqid( wp_rand(), true ) ) );
			$res                  = $wpdb->query(
				$wpdb->prepare(
					"UPDATE $lockdowns_table_name SET unlock_key = %s WHERE release_date > %s AND lockdown_ip LIKE %s",
					array( $secret_rand_key, current_time( 'mysql' ), esc_sql( $ip_range ) . '%' )
				)
			);
			if ( null === $res ) {
				return false;
			}
		}

		$query_param = array( 'auth_key' => $secret_rand_key );
		$wp_site_url = site_url();
		$unlock_link = esc_url( add_query_arg( $query_param, $wp_site_url ) );

		return $unlock_link;
	}
}
