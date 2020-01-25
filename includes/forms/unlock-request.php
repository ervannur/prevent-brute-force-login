<?php
/**
 * Unlock Request form.
 *
 * @package PreventBruteForceLogin
 */

defined( 'ABSPATH' ) || die( 'Can\'t access directly' );

require ABSPATH . '/wp-load.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php bloginfo( 'name' ); ?></title>
	<?php
	// Make this page look like the WP login page.
	wp_admin_css( 'login', true );
	?>
</head>

<body class="login login-action-login wp-core-ui kdbl-pbfl-unlock-request">
	<div id="login">
		<h1><a href="https://wordpress.org/" title="Powered by WordPress" tabindex="-1">Powered by WordPress</a></h1>
		<?php
		if ( isset( $_POST['wp_submit_unlock_request'] ) ) {
			$errors = '';

			// phpcs:ignore
			$email = trim( $_POST['unlock_request_email'] );
			if ( empty( $email ) || ! is_email( $email ) ) {
				$errors .= '<p>' . __( 'Please enter a valid email address', 'prevent-brute-force-login' ) . '</p>';
			}

			if ( $errors ) {
				echo '<div id="login_error">' . $errors . '</div>';
				$sanitized_email = sanitize_email( $email );
				echo kdbm_pbfl_display_unlock_form( $sanitized_email );
			} else {
				$locked_user = get_user_by( 'email', $email );
				if ( ! $locked_user ) {
					// user with this email does not exist in the system.
					$errors .= '<p>' . __( 'User account not found!', 'prevent-brute-force-login' ) . '</p>';
					echo '<div id="login_error">' . $errors . '</div>';
				} else {
					// Process unlock request
					// Generate a special code and unlock url.
					$ip       = kdbm_pbfl_get_user_ip(); // Get the IP address of user.
					$ip_range = kdbm_pbfl_get_ip_range( $ip ); // Get the IP range of the current user.
					if ( empty( $ip_range ) ) {
						$unlock_url = false;
					} else {
						$unlock_url = kdbm_pbfl_notification::generate_unlock_request_link( $ip_range );
					}

					if ( ! $unlock_url ) {
						// No entry found in lockdown table with this IP range.
						$error_msg = '<p>' . __( 'Error: No locked entry was found in the DB with your IP address range!', 'prevent-brute-force-login' ) . '</p>';
						echo '<div id="login_error">' . $error_msg . '</div>';
					} else {
						// Send an email to the user.
						kdbm_pbfl_notification::send_unlock_request_email( $email, $unlock_url );
						echo '<p class="message">' . __( 'An email has been sent to you with the unlock instructions.', 'prevent-brute-force-login' ) . '</p>';
					}
				}
			}
		} else {
			echo kdbm_pbfl_display_unlock_form();
		}
		?>
	</div> <!-- end #login -->

</body>
</html>
<?php

/**
 * Display unlock form
 *
 * @since 1.0.0
 * @param  string $email Email.
 * @return string HTML of unlock form.
 */
function kdbm_pbfl_display_unlock_form( $email = '' ) {
	ob_start();
	// Display the unlock request form.
	$unlock_form_msg = '<p>' . __( 'You are here because you have been locked out due to too many incorrect login attempts.', 'prevent-brute-force-login' ) . '</p>'
	. '<p>' . __( 'Please enter your email address and you will receive an email with instructions on how to unlock yourself.', 'prevent-brute-force-login' ) . '</p>';
	?>
	<div class="message"><?php echo $unlock_form_msg; ?></div>
	<form name="loginform" id="loginform" action="<?php echo wp_login_url(); ?>" method="post">
		<p>
			<label for="tf_unlock_request_email"><?php esc_html_e( 'Email Address', 'prevent-brute-force-login' ); ?><br>
				<input type="text" name="unlock_request_email" id="unlock_request_email" class="input" value="<?php echo $email; ?>" size="20">
			</label>
		</p>
		<p class="submit">
			<input type="submit" name="wp_submit_unlock_request" id="wp_submit_unlock_request" class="button button-primary button-large" value="<?php esc_attr_e( 'Send Unlock Request', 'prevent-brute-force-login' ); ?>">
		</p>
	</form>
	<?php
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}
