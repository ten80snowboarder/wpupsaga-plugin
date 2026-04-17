# WPUpSaga Plugin

Plugin used to connect a WordPress site to the WPUpSaga app.

## Development notes

- Composer manages dependencies and autoloading.
- `yahnis-elsts/plugin-update-checker` is included so the public GitHub repository can drive in-plugin updates.
- The update checker follows the `main` branch version header instead of requiring GitHub Releases.
- `readme.txt` and the plugin header are the source of truth for the released version.

## Packaging

The plugin is expected to ship with the `vendor/` directory included so update installs work without running Composer on the target WordPress site.

Running `bash scripts/package-release.sh <version>` now produces:

- `dist/wpupsaga-<version>.zip`
- `dist/wpupsaga.zip`

Both ZIPs unpack into a top-level `wpupsaga/` directory so manual installs land in `/wp-content/plugins/wpupsaga` instead of a GitHub source folder like `wpupsaga-plugin-main`.

## Release workflow

1. Run `php scripts/bump-version.php 0.1.3`.
2. Review `readme.txt`, `CHANGELOG.md`, and `wpupsaga.php`.
3. Run `bash scripts/package-release.sh 0.1.3`.
4. Commit, tag, and push the updated plugin repo.
