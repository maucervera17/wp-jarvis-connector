<?php
/**
 * WJC_API — REST endpoints exposed by the connector plugin.
 *
 * Namespace: wp-jarvis-connector/v1
 *
 * Public endpoints (no auth needed):
 *   POST /publish         ← Render calls this to create/update a WP page
 *   POST /google-callback ← Receives wpjarvis_google_token after OAuth
 *
 * Authenticated endpoints (nonce required):
 *   POST /connect         ← Plugin JS calls this to save license + gen app password
 *   POST /disconnect      ← Logout
 *   GET  /status          ← Returns connection state for the admin UI
 */

defined( 'ABSPATH' ) || exit;

class WJC_API {

	const NS = 'wp-jarvis-connector/v1';

	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		// Connection management (requires nonce / login)
		register_rest_route( self::NS, '/connect', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_connect' ],
			'permission_callback' => [ $this, 'require_admin' ],
		] );

		register_rest_route( self::NS, '/disconnect', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_disconnect' ],
			'permission_callback' => [ $this, 'require_admin' ],
		] );

		register_rest_route( self::NS, '/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_status' ],
			'permission_callback' => [ $this, 'require_admin' ],
		] );

		// Render → WordPress: publish a page (verified by license key secret)
		register_rest_route( self::NS, '/publish', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_publish' ],
			'permission_callback' => '__return_true', // verified internally by license key
		] );
	}

	// ── Permission ────────────────────────────────────────────

	public function require_admin() {
		return current_user_can( 'manage_options' );
	}

	// ── Handlers ──────────────────────────────────────────────

	/**
	 * POST /connect
	 * Body: { license_key, email }
	 * Creates an Application Password and registers the site with Render.
	 */
	public function handle_connect( WP_REST_Request $request ) {
		$license_key = sanitize_text_field( $request->get_param( 'license_key' ) );
		$email       = sanitize_email( $request->get_param( 'email' ) );

		if ( empty( $license_key ) || empty( $email ) ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => 'License key and email are required.' ], 400 );
		}

		// Generate Application Password for this admin user.
		$user_id      = get_current_user_id();
		$app_password = WJC_Connect::ensure_app_password( $user_id );

		if ( ! $app_password ) {
			return new WP_REST_Response( [
				'success' => false,
				'error'   => 'Could not generate a WordPress Application Password. Make sure Application Passwords are enabled on your site.',
			], 500 );
		}

		// Save connection credentials locally.
		update_option( WJC_Connect::OPT_LICENSE_KEY,   $license_key );
		update_option( WJC_Connect::OPT_ACCOUNT_EMAIL, $email );

		// Register credentials with the Render backend.
		$registered = WJC_Connect::register_site( $license_key, $app_password, $user_id );

		return new WP_REST_Response( [
			'success'    => true,
			'registered' => $registered,
			'message'    => $registered
				? 'Connected. Your site is ready to publish pages from WP Jarvis.'
				: 'Connected locally, but could not reach the WP Jarvis server. Publishing will be enabled once the server is reachable.',
		] );
	}

	/**
	 * POST /disconnect
	 */
	public function handle_disconnect( WP_REST_Request $request ) {
		WJC_Connect::disconnect();
		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * GET /status
	 */
	public function handle_status( WP_REST_Request $request ) {
		return new WP_REST_Response( [
			'success'      => true,
			'connected'    => WJC_Connect::is_connected(),
			'email'        => WJC_Connect::get_account_email(),
			'license_key'  => WJC_Connect::get_license_key() ? '••••••••' : '',
			'site_url'     => home_url(),
			'rest_url'     => rest_url(),
			'builder_url'  => WJC_BACKEND_URL . '/builder?site=' . rawurlencode( home_url() ),
		] );
	}

	/**
	 * POST /publish
	 * Called by Render to create or update a WordPress page.
	 *
	 * Body: {
	 *   license_key: string,   ← verified against stored value
	 *   title:       string,
	 *   html:        string,
	 *   page_id:     int|null, ← if set, updates existing page
	 *   status:      'draft'|'publish'
	 * }
	 */
	public function handle_publish( WP_REST_Request $request ) {
		$license_key = sanitize_text_field( $request->get_param( 'license_key' ) );
		$stored_key  = WJC_Connect::get_license_key();

		// Verify license key matches what's stored.
		if ( empty( $license_key ) || $license_key !== $stored_key ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => 'Invalid license key.' ], 403 );
		}

		$title   = sanitize_text_field( $request->get_param( 'title' ) ?: 'WP Jarvis Page' );
		$html    = wp_kses_post( $request->get_param( 'html' ) ?: '' );
		$page_id = intval( $request->get_param( 'page_id' ) );
		$status  = in_array( $request->get_param( 'status' ), [ 'draft', 'publish' ], true )
			? $request->get_param( 'status' )
			: 'draft';

		if ( empty( $html ) ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => 'HTML content is required.' ], 400 );
		}

		$page_data = [
			'post_title'   => $title,
			'post_content' => $html,
			'post_status'  => $status,
			'post_type'    => 'page',
		];

		if ( $page_id && get_post( $page_id ) ) {
			$page_data['ID'] = $page_id;
			$result = wp_update_post( $page_data, true );
		} else {
			$result = wp_insert_post( $page_data, true );
		}

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => $result->get_error_message() ], 500 );
		}

		return new WP_REST_Response( [
			'success'   => true,
			'page_id'   => $result,
			'page_url'  => get_permalink( $result ),
			'edit_url'  => get_edit_post_link( $result, 'raw' ),
			'status'    => $status,
		] );
	}
}
