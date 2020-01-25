<?php
/**
 * Api helpers.
 *
 * @package PreventBruteForceLogin
 */

/**
 * Kdbm_pbfl_get_setting
 *
 * @since 1.0.0
 * @param  string $name Setting name.
 * @param  mixed  $value Default setting value.
 * @return mixed
 */
function kdbm_pbfl_get_setting( $name, $value = null ) {
	return kdbm_pbfl()->get_setting( $name, $value );
}


/**
 * Kdbm_pbfl_get_saved_setting
 *
 * @since 1.0.0
 * @param  string $option Option name.
 * @return mixed
 */
function kdbm_pbfl_get_saved_setting( $option ) {
	return kdbm_pbfl()->get_saved_settings( $option );
}


/**
 * Kdbm_pbfl_get_saved_admin_email
 *
 * @since 1.0.0
 * @param  string $option Option name.
 * @return mixed
 */
function kdbm_pbfl_get_saved_admin_email( $option ) {
	return kdbm_pbfl()->get_saved_admin_email( $option );
}

/**
 * Kdbm_pbfl_get_saved_unlock_email
 *
 * @since 1.0.0
 * @param  string $option Option name.
 * @return mixed
 */
function kdbm_pbfl_get_saved_unlock_email( $option ) {
	return kdbm_pbfl()->get_saved_unlock_email( $option );
}

/**
 * Kdbm_pbfl_get_user_ip
 *
 * @since 1.0.0
 * @return string ip of current client
 */
function kdbm_pbfl_get_user_ip() {
	return kdbm_pbfl()->get_user_ip();
}

/**
 * Kdbm_pbfl_get_ip_range
 *
 * @since 1.0.0
 * @return string ip range
 */

/**
 * Kdbm_pbfl_get_ip_range
 *
 * @since 1.0.0
 * @param  string $ip IP Address.
 * @return string     IP Range.
 */
function kdbm_pbfl_get_ip_range( $ip ) {
	return kdbm_pbfl()->get_ip_range( $ip );
}
