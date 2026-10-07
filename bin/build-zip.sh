#!/usr/bin/env bash
#
# Build build/roadmap-starter.zip (top-level roadmap-starter/ folder) and build/notes.md (this
# version's readme.txt changelog entry). Used by .github/workflows/release.yml; safe to run locally.
#
# Ships the PHP framework, the parent SCSS/JS sources and build/webpack.factory.js (child themes
# compile against them), scaffold/ and vendor_prefixed/. No public/ — each child builds its own.
#
# Usage: bin/build-zip.sh [tag]   e.g. bin/build-zip.sh 2.0.0  (theme tags have no "v" prefix)

set -euo pipefail

SLUG="roadmap-starter"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TOOLING="$ROOT"
ROOT="$(cd "${THEME_DIR:-$ROOT}" && pwd)"
BUILD="$TOOLING/build-zip"
cd "$ROOT"

style_version="$(sed -n 's/^Version:[[:space:]]*//p' style.css | head -1 | tr -d '[:space:]')"
readme_version="$(sed -n 's/^Stable tag:[[:space:]]*//p' readme.txt | head -1 | tr -d '[:space:]')"
package_version="$(sed -n 's/^  "version":[[:space:]]*"\([^"]*\)".*/\1/p' package.json | head -1)"

if [ "$style_version" != "$readme_version" ] || [ "$style_version" != "$package_version" ]; then
    echo "::error::Versions differ: style.css $style_version, readme.txt $readme_version, package.json $package_version" >&2
    exit 1
fi

version="$style_version"
if [ -n "${1:-}" ] && [ "${1#v}" != "$version" ]; then
    echo "::error::Tag $1 does not match theme version $version — bump style.css/readme.txt/package.json before tagging" >&2
    exit 1
fi

rm -rf "$BUILD"
mkdir -p "$BUILD/$SLUG"
rsync -a --exclude-from="$TOOLING/.distignore" --exclude=/build-zip --exclude=/.tooling "$ROOT/" "$BUILD/$SLUG/"
(cd "$BUILD" && zip -rq "$SLUG.zip" "$SLUG")

# Release notes: the "= <version> - <date> =" entry of readme.txt's changelog.
awk -v v="$version" '
    /^= / { if (found) exit; if (index($0, "= " v " ") == 1) { found = 1; next } }
    found { print }
' readme.txt > "$BUILD/notes.md"
if ! grep -q '[^[:space:]]' "$BUILD/notes.md"; then
    echo "See readme.txt." > "$BUILD/notes.md"
fi
printf '\n---\n\n**Install:** download **`%s.zip`** below. Sites with the theme installed update from wp-admin (Appearance → Themes) — child themes are rebuilt only when the changelog says the parent SCSS changed.\n' "$SLUG" >> "$BUILD/notes.md"

echo "version=$version"
echo "zip=build-zip/$SLUG.zip"
