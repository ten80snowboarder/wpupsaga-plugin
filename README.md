# WPUpSaga Plugin

Plugin used to connect a WordPress site to the WPUpSaga app.

## Development notes

- Composer manages dependencies and autoloading.
- `yahnis-elsts/plugin-update-checker` is included so GitHub releases can drive in-plugin updates.
- The update checker is configured against the public GitHub repo and tracks the `main` branch for now.

## Packaging

The plugin is expected to ship with the `vendor/` directory included so update installs work without running Composer on the target WordPress site.
