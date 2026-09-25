#!/bin/bash
# ZIP for the WordPress.org plugin directory ("Add your plugin" upload, or a
# manual SVN update): the git archive of a ref, minus the paths listed in
# .distignore (translation files among them: the directory delivers language
# packs itself). The GitHub release archive is the plain git archive and
# keeps languages/, so an install from GitHub is translated.
#
#   bash tools/build-wporg-zip.sh [REF] [OUTPUT]
#   bash tools/build-wporg-zip.sh v2.1.3 ~/Downloads/mapped-places-2.1.3.zip
set -euo pipefail

REF="${1:-HEAD}"
OUT="${2:-$PWD/mapped-places-wporg.zip}"
ROOT="$(git rev-parse --show-toplevel)"
SLUG="mapped-places"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

git -C "$ROOT" archive --format=tar --prefix="$SLUG/" "$REF" | tar -x -C "$TMP"

# .distignore: one pattern per line, relative to the plugin root.
while IFS= read -r line; do
    line="${line%%#*}"
    line="$(echo "$line" | xargs)"
    [ -z "$line" ] && continue
    # shellcheck disable=SC2086
    rm -rf $TMP/$SLUG/${line#/}
done < "$ROOT/.distignore"

rm -f "$OUT"
(cd "$TMP" && zip -qr "$OUT" "$SLUG")
echo "$OUT ($(unzip -l "$OUT" | tail -1 | awk '{print $2}') files, version $(grep -m1 'Version:' "$TMP/$SLUG/$SLUG.php" | awk '{print $NF}'))"
if unzip -l "$OUT" | grep -qE 'languages/.*\.(po|mo|json)$'; then
    echo "translation files found in the archive" >&2
    exit 1
fi
