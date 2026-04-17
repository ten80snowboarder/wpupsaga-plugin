# Changelog

## 0.1.3 - 2026-04-16

- Stop requiring GitHub Releases for plugin update checks.
- Use the public repository branch as the update source so update checks keep working without manually publishing releases.

## 0.1.2 - 2026-04-16

- Fix plugin settings sanitization so `paired`, `paired_at`, `site_status`, and delivery state persist after the pair action updates the option.

## 0.1.1 - 2026-04-16

- Stop disabling the Pair Site button in the settings UI. Validation remains server-side so pairing can still surface errors clearly.

## Unreleased

- Pairing flow against the WPUpSaga app.
- Update delivery hooks for core, plugin, theme, and translation updates.
- Packaging now also creates a stable `wpupsaga.zip` that installs into `/wp-content/plugins/wpupsaga`.

## 0.1.0 - 2026-04-16

- Initial plugin scaffold with Composer-managed dependencies.
- Public GitHub update checking via Plugin Update Checker.