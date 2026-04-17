=== WPUpSaga ===
Contributors: ten80snowboarder
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track WordPress core, plugin, theme, and translation updates in your WPUpSaga account.

== Description ==

WPUpSaga connects a WordPress site to the WPUpSaga app so you can pair the site, send completed update events, and review those updates in the hosted dashboard.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpupsaga` directory, or install the plugin ZIP from a GitHub release.
2. Activate the plugin through the WordPress plugins screen.
3. Open Settings > WPUpSaga.
4. Paste the App URL, Site UUID, and API key from the WPUpSaga dashboard.
5. Save the settings, then click Pair Site.

== Changelog ==


= 0.1.2 =

* Persist pairing state correctly after a successful pair request.

= 0.1.1 =

* Keep the Pair Site button available after saving settings so pairing can be retried normally.

= 0.1.0 =

* Initial plugin scaffold with pairing and update reporting foundations.