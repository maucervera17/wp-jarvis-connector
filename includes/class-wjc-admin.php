<?php
/**
 * WJC_Admin — registers the WP Admin page and enqueues assets.
 */

defined( 'ABSPATH' ) || exit;

class WJC_Admin {

	public function init() {
		add_action( 'admin_menu',            [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function register_menu() {
		add_menu_page(
			__( 'WP Jarvis', 'wp-jarvis-connector' ),
			__( 'WP Jarvis', 'wp-jarvis-connector' ),
			'manage_options',
			'wp-jarvis-connector',
			[ $this, 'render_page' ],
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>' ),
			30
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_wp-jarvis-connector' !== $hook ) return;

		wp_enqueue_style(
			'wjc-admin',
			WJC_PLUGIN_URL . 'assets/css/admin.css',
			[],
			WJC_VERSION
		);

		wp_enqueue_script(
			'wjc-admin',
			WJC_PLUGIN_URL . 'assets/js/admin.js',
			[],
			WJC_VERSION,
			true
		);

		wp_localize_script( 'wjc-admin', 'WJC', [
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'restUrl'     => rest_url( 'wp-jarvis-connector/v1' ),
			'backendUrl'  => WJC_BACKEND_URL,
			'connected'   => WJC_Connect::is_connected(),
			'email'       => WJC_Connect::get_account_email(),
			'builderUrl'  => WJC_BACKEND_URL . '/builder?site=' . rawurlencode( home_url() ) . '&license=' . rawurlencode( WJC_Connect::get_license_key() ),
		] );
	}

	public function render_page() {
		?>
		<div class="wjc-wrap" id="wjc-app">

			<!-- Connected state -->
			<div class="wjc-screen wjc-screen--connected" id="wjc-connected" style="display:none">
				<div class="wjc-header">
					<div class="wjc-logo">
						<span class="wjc-orb"></span>
						<strong>WP Jarvis</strong>
					</div>
					<div class="wjc-account">
						<span id="wjc-email"></span>
						<button class="wjc-btn wjc-btn--ghost wjc-btn--sm" id="wjc-disconnect-btn">Disconnect</button>
					</div>
				</div>

				<div class="wjc-hero">
					<h1><?php esc_html_e( 'Build pages with AI', 'wp-jarvis-connector' ); ?></h1>
					<p><?php esc_html_e( 'Open WP Jarvis to generate, preview and publish pages directly to your WordPress site.', 'wp-jarvis-connector' ); ?></p>
					<a href="#" class="wjc-btn wjc-btn--primary wjc-btn--lg" id="wjc-open-builder">
						<?php esc_html_e( 'Open WP Jarvis Builder', 'wp-jarvis-connector' ); ?>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
					</a>
				</div>

				<div class="wjc-info-grid">
					<div class="wjc-info-card">
						<span class="wjc-info-icon">⚡</span>
						<strong><?php esc_html_e( 'AI Generation', 'wp-jarvis-connector' ); ?></strong>
						<p><?php esc_html_e( 'Describe your page and WP Jarvis builds it instantly.', 'wp-jarvis-connector' ); ?></p>
					</div>
					<div class="wjc-info-card">
						<span class="wjc-info-icon">👁</span>
						<strong><?php esc_html_e( 'Live Preview', 'wp-jarvis-connector' ); ?></strong>
						<p><?php esc_html_e( 'See exactly how your page will look before publishing.', 'wp-jarvis-connector' ); ?></p>
					</div>
					<div class="wjc-info-card">
						<span class="wjc-info-icon">🚀</span>
						<strong><?php esc_html_e( 'One-click Publish', 'wp-jarvis-connector' ); ?></strong>
						<p><?php esc_html_e( 'Publish directly to your WordPress site — no copy-pasting.', 'wp-jarvis-connector' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Auth / sign-in state -->
			<div class="wjc-screen wjc-screen--auth" id="wjc-auth">
				<div class="wjc-auth-wrap">
					<div class="wjc-auth-card">
						<div class="wjc-auth-logo">
							<span class="wjc-orb wjc-orb--lg"></span>
							<strong>WP Jarvis</strong>
						</div>
						<h2><?php esc_html_e( 'Connect your site', 'wp-jarvis-connector' ); ?></h2>
						<p><?php esc_html_e( 'Sign in to your WP Jarvis account to start building pages with AI.', 'wp-jarvis-connector' ); ?></p>

						<div id="wjc-error" class="wjc-alert wjc-alert--error" style="display:none"></div>
						<div id="wjc-success" class="wjc-alert wjc-alert--success" style="display:none"></div>

						<!-- Google -->
						<button class="wjc-btn wjc-btn--google wjc-btn--full" id="wjc-google-btn">
							<svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/><path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/><path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/><path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/></svg>
							<?php esc_html_e( 'Continue with Google', 'wp-jarvis-connector' ); ?>
						</button>

						<div class="wjc-divider"><span><?php esc_html_e( 'or', 'wp-jarvis-connector' ); ?></span></div>

						<!-- Email form -->
						<form id="wjc-email-form">
							<div class="wjc-field">
								<label for="wjc-input-email"><?php esc_html_e( 'Email', 'wp-jarvis-connector' ); ?></label>
								<input type="email" id="wjc-input-email" placeholder="you@example.com" required autocomplete="email" />
							</div>
							<div class="wjc-field">
								<label for="wjc-input-password"><?php esc_html_e( 'Password', 'wp-jarvis-connector' ); ?></label>
								<input type="password" id="wjc-input-password" placeholder="••••••••" required autocomplete="current-password" />
							</div>
							<button type="submit" class="wjc-btn wjc-btn--primary wjc-btn--full" id="wjc-signin-btn">
								<?php esc_html_e( 'Sign in', 'wp-jarvis-connector' ); ?>
							</button>
						</form>

						<div class="wjc-auth-links">
							<a href="#" id="wjc-forgot-link"><?php esc_html_e( 'Forgot password?', 'wp-jarvis-connector' ); ?></a>
							<span>·</span>
							<a href="https://wpjarvis.com" target="_blank" rel="noopener"><?php esc_html_e( 'Create account', 'wp-jarvis-connector' ); ?></a>
						</div>

						<p class="wjc-disclaimer">
							<?php esc_html_e( 'This plugin connects to the WP Jarvis service. By connecting, you agree to the', 'wp-jarvis-connector' ); ?>
							<a href="https://wpjarvis.com/terms" target="_blank" rel="noopener"><?php esc_html_e( 'Terms of Service', 'wp-jarvis-connector' ); ?></a>
							<?php esc_html_e( 'and', 'wp-jarvis-connector' ); ?>
							<a href="https://wpjarvis.com/privacy" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy Policy', 'wp-jarvis-connector' ); ?></a>.
						</p>
					</div>
				</div>
			</div>

		</div><!-- /.wjc-wrap -->
		<?php
	}
}
