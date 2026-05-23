<?php
/**
 * WJC_Connect — manages the WordPress ↔ WP Jarvis connection.
 *
 * Responsibilities:
 *  - Generate / revoke a WordPress Application Password for WP Jarvis
 *  - Store / retrieve the license key and account email
 *  - Register the site credentials with the Render backend so it can
 *    publish pages back to WordPress server-side (no CORS issues)
 */

defined( 'ABSPATH' ) || exit;

class WJC_Connect {

	const OPT_LICENSE_KEY   = 'wjc_license_key';
	const OPT_ACCOUNT_EMAIL = 'wjc_account_email';
	const OPT_APP_PASS_UUID = 'wjc_app_password_uuid';
	const OPT_SITE_REGISTERED = 'wjc_site_registered';
	const APP_PASS_NAME     = 'WP Jarvis Connector';

	// ── Lifecycle ────────────────────────────────────────────

	public static function on_activate() {
		// Nothing destructive on activate — credentials generated on first connect.
	}

	public static function on_deactivate() {
		// Optionally revoke the Application Password on deactivation.
		self::revoke_app_password();
	}

	// ── Getters ──────────────────────────────────────────────

	public static function get_license_key() {
		return (string) get_option( self::OPT_LICENSE_KEY, '' );
	}

	public static function get_account_email() {
		return (string) get_option( self::OPT_ACCOUNT_EMAIL, '' );
	}

	public static function is_connected() {
		return '' !== self::get_license_key();
	}

	// ── Application Password ─────────────────────────────────

	/**
	 * Creates (or re-uses) a WordPress Application Password for the current admin user.
	 * Returns the plain-text password (only available once) or null on failure.
	 */
	public static function ensure_app_password( $user_id = null ) {
		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			return null;
		}

		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) return null;

		// Revoke any existing WP Jarvis app password first to avoid duplicates.
		self::revoke_app_password( $user_id );

		$result = WP_Application_Passwords::create_new_application_password(
			$user_id,
			[ 'name' => self::APP_PASS_NAME ]
		);

		if ( is_wp_error( $result ) ) {
			return null;
		}

		// Save the UUID so we can revoke it later.
		update_option( self::OPT_APP_PASS_UUID, $result[1]['uuid'] ?? '' );

		// Return plain-text password (only available at creation time).
		return $result[0];
	}

	/**
	 * Revokes the stored Application Password.
	 */
	public static function revoke_app_password( $user_id = null ) {
		if ( ! class_exists( 'WP_Application_Passwords' ) ) return;

		$uuid = (string) get_option( self::OPT_APP_PASS_UUID, '' );
		if ( ! $uuid ) return;

		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) return;

		WP_Application_Passwords::delete_application_password( $user_id, $uuid );
		delete_option( self::OPT_APP_PASS_UUID );
	}

	// ── Backend registration ─────────────────────────────────

	/**
	 * Sends site credentials to the Render backend so it can publish pages
	 * back to this WordPress site without CORS issues.
	 *
	 * Called after the user successfully authenticates.
	 *
	 * @param string $license_key
	 * @param string $app_password   Plain-text Application Password
	 * @param int    $wp_user_id     WordPress user ID that owns the app password
	 */
	public static function register_site( $license_key, $app_password, $wp_user_id ) {
		$wp_user = get_userdata( $wp_user_id );
		if ( ! $wp_user ) return false;

		$body = wp_json_encode( [
			'license_key'      => $license_key,
			'site_url'         => home_url(),
			'wp_rest_url'      => rest_url(),
			'wp_username'      => $wp_user->user_login,
			'wp_app_password'  => $app_password,
		] );

		$response = wp_remote_post(
			WJC_BACKEND_URL . '/api/connector/register-site',
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => $body,
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) return false;

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $data['success'] ) ) {
			update_option( self::OPT_SITE_REGISTERED, true );
			return true;
		}

		return false;
	}

	// ── Disconnect ───────────────────────────────────────────

	public static function disconnect() {
		self::revoke_app_password();
		delete_option( self::OPT_LICENSE_KEY );
		delete_option( self::OPT_ACCOUNT_EMAIL );
		delete_option( self::OPT_APP_PASS_UUID );
		delete_option( self::OPT_SITE_REGISTERED );
	}
}
