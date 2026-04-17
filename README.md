# WPUpSaga Plugin

Plugin used to connect a WordPress site to the WPUpSaga app.

## Development notes

- Composer manages dependencies and autoloading.
- `yahnis-elsts/plugin-update-checker` is included so GitHub releases can drive in-plugin updates.
- Release assets should be named like `wpupsaga-1.2.3.zip` so Plugin Update Checker can pick them predictably.
- `readme.txt` and the plugin header are the source of truth for the released version.

## Packaging

The plugin is expected to ship with the `vendor/` directory included so update installs work without running Composer on the target WordPress site.

## Release workflow

1. Run `php scripts/bump-version.php 0.1.1`.
2. Review `readme.txt`, `CHANGELOG.md`, and `wpupsaga.php`.
3. Run `bash scripts/package-release.sh 0.1.1`.
4. Upload the generated ZIP from `dist/` to a GitHub Release.
