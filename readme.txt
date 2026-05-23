=== WP Jarvis Connector ===
Contributors: wpjarvis
Tags: ai, page builder, artificial intelligence, content generation, page creation
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to WP Jarvis — the AI-powered page builder that generates, previews and publishes pages directly to your site.

== Description ==

**WP Jarvis Connector** is a lightweight bridge plugin that links your WordPress site to the [WP Jarvis](https://wpjarvis.com) AI page-building service.

= How it works =

1. Install and activate this plugin.
2. Sign in with your WP Jarvis account (or create one for free at wpjarvis.com).
3. Click **Open WP Jarvis Builder** to start generating pages with AI.
4. When you're happy with a page, publish it directly to your WordPress site — no copy-pasting required.

= Features =

* **AI page generation** — describe what you need and WP Jarvis builds the page instantly.
* **Live preview** — see exactly how the page will look before it goes live.
* **One-click publish** — pages are pushed directly to WordPress from the builder.
* **Secure authentication** — uses WordPress Application Passwords (built-in since WP 5.6) so no plain passwords are stored anywhere.
* **Google sign-in** — connect with your Google account in seconds.

= External Service =

This plugin connects to the **WP Jarvis** external service hosted at `https://wp-jarvis-dashboard-owner.onrender.com`.

The plugin sends the following data to this service:

* Your WordPress site URL and REST API URL — so WP Jarvis can publish pages back to your site.
* A WordPress Application Password generated specifically for WP Jarvis — used for authenticated REST API calls only. The password is encrypted at rest on the WP Jarvis servers and never exposed in plaintext after the initial handshake.
* Your WP Jarvis account email and license key — to verify your subscription.

**No personal visitor data, post content, or database contents are ever sent to WP Jarvis.**

By connecting your site you agree to the WP Jarvis [Terms of Service](https://wpjarvis.com/terms) and [Privacy Policy](https://wpjarvis.com/privacy).

== Installation ==

1. Upload the `wp-jarvis-connector` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WP Jarvis** in the admin sidebar.
4. Sign in with your WP Jarvis account to connect your site.

== Frequently Asked Questions ==

= Do I need a WP Jarvis account? =

Yes. You can create a free account at [wpjarvis.com](https://wpjarvis.com).

= Is my WordPress password stored anywhere? =

No. The plugin uses WordPress Application Passwords — a separate, revocable credential that is not your WordPress login password. You can revoke it at any time from **Users → Profile → Application Passwords**.

= What WordPress version do I need? =

WordPress 5.6 or higher (Application Passwords are built-in since 5.6).

= Does this plugin collect any data? =

Only what is described in the External Service section above. No visitor data or site content is ever collected.

= Can I disconnect my site? =

Yes — click **Disconnect** in the WP Jarvis admin page. This immediately revokes the Application Password and clears all stored credentials.

== Screenshots ==

1. The connected dashboard — open the AI builder with one click.
2. The sign-in screen — connect with email/password or Google.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
