# Changelog

## Unreleased

- No unreleased changes.

## 0.1.6 - 2026-04-16

- Render `Last paired at` and `Last delivery` in the WordPress site timezone instead of raw GMT values.

## 0.1.5 - 2026-04-16

- Update the declared tested WordPress version to 6.9.4.
- Raise the minimum supported PHP version to 8.3.

## 0.1.4 - 2026-04-16

- Add a stable `wpupsaga.zip` package that installs into `/wp-content/plugins/wpupsaga`.
- Normalize Plugin Update Checker compatibility metadata so the UI does not show a synthetic `.999` WordPress version.

## 0.1.3 - 2026-04-16

- Stop requiring GitHub Releases for plugin update checks.
- Use the public repository branch as the update source so update checks keep working without manually publishing releases.

## 0.1.2 - 2026-04-16

- Fix plugin settings sanitization so `paired`, `paired_at`, `site_status`, and delivery state persist after the pair action updates the option.

## 0.1.1 - 2026-04-16

- Stop disabling the Pair Site button in the settings UI. Validation remains server-side so pairing can still surface errors clearly.

## 0.1.0 - 2026-04-16

- Initial plugin scaffold with Composer-managed dependencies.
- Public GitHub update checking via Plugin Update Checker.