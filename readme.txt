=== WPUpSaga ===
Contributors: ten80snowboarder
Requires at least: 6.2
Tested up to: 6.9.4
Requires PHP: 8.3
Stable tag: 0.1.9
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


= 0.1.9 =

* Handle WordPress bulk plugin updates correctly when `upgrader_pre_install` only passes the plugin basename, so `from_version` is captured during normal plugin update runs.

= 0.1.8 =

* Persist pre-update version snapshots during the WordPress upgrader run so `from_version` survives more update flows and is delivered more reliably.

= 0.1.7 =

* Capture previous plugin, theme, and core versions more reliably in delivered update events so `from_version` is populated more often.

= 0.1.6 =

* Render pairing and delivery timestamps in the WordPress site timezone instead of raw GMT.

= 0.1.5 =

* Update the declared tested WordPress version to 6.9.4.
* Raise the minimum supported PHP version to 8.3.

= 0.1.4 =

* Add a stable `wpupsaga.zip` package that installs into `/wp-content/plugins/wpupsaga`.
* Normalize Plugin Update Checker compatibility metadata so the UI does not show a fake `.999` WordPress version.

= 0.1.3 =

* Stop relying on GitHub Releases for update checks. The updater now tracks the repository branch directly.

= 0.1.2 =

* Persist pairing state correctly after a successful pair request.

= 0.1.1 =

* Keep the Pair Site button available after saving settings so pairing can be retried normally.

= 0.1.0 =

* Initial plugin scaffold with pairing and update reporting foundations.