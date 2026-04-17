#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
TEMP_DIR="$(mktemp -d)"
PACKAGE_DIR="$TEMP_DIR/wpupsaga"

cleanup() {
  rm -rf "$TEMP_DIR"
}

trap cleanup EXIT

VERSION="${1:-}"

if [[ -z "$VERSION" ]]; then
  VERSION="$(php -r '$contents = file_get_contents("wpupsaga.php"); if (!preg_match("/^ \* Version:\\s+(.+)$/m", $contents, $m)) { exit(1); } echo trim($m[1]);' 2>/dev/null)"
fi

if [[ -z "$VERSION" ]]; then
  echo "Unable to determine plugin version" >&2
  exit 1
fi

if [[ ! -d "$ROOT_DIR/vendor" ]]; then
  echo "vendor/ is missing. Run composer install before packaging." >&2
  exit 1
fi

mkdir -p "$DIST_DIR" "$PACKAGE_DIR"

rsync -a \
  --exclude '.git/' \
  --exclude '.gitignore' \
  --exclude 'dist/' \
  --exclude 'scripts/' \
  --exclude '.DS_Store' \
  --exclude 'composer.json' \
  --exclude 'composer.lock' \
  --exclude 'README.md' \
  "$ROOT_DIR/" "$PACKAGE_DIR/"

(
  cd "$TEMP_DIR"
  zip -qr "$DIST_DIR/wpupsaga-$VERSION.zip" wpupsaga
)

echo "Created $DIST_DIR/wpupsaga-$VERSION.zip"