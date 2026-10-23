#!/usr/bin/env bash
# Build a release package for one WISECP generation.
#
#   tools/build-release-zip.sh v5 [ref] [output.zip]   WISECP 5 (marketplace package)
#   tools/build-release-zip.sh v3 [ref] [output.zip]   WISECP 3
#
# Archive root: the coremio/ folder of that generation, ready to unpack over a WISECP
# installation, plus the README files, CHANGELOG and LICENSE. Only tracked files are
# packed, so OS files (__MACOSX, ._*, .DS_Store), editor folders and tests/ can never
# end up in it. The module's config.php ships on purpose: a server module keeps no
# credentials there (they live on the server record), only the module definition.
#
# v5: manifest.json goes into the package only once it carries the marketplace product
# id (WISECP ignores a marketplace manifest without one). Until then the package ships
# without it - a marketplace download gets its manifest written by the store anyway.
# The manifest version must match config.php.
set -euo pipefail

cd "$(dirname "$0")/.."
GEN="${1:-}"
REF="${2:-HEAD}"
MODULE="coremio/modules/Servers/DNAHosting"

case "$GEN" in
    v5)
        VERSION="$(git show "$REF:v5/$MODULE/config.php" | sed -n "s/.*'version' *=> *'\([^']*\)'.*/\1/p" | head -1)"
        MANIFEST="$(git show "$REF:v5/$MODULE/manifest.json")"
        M_ID="$(printf '%s' "$MANIFEST" | sed -n 's/.*"id" *: *\([0-9]*\).*/\1/p' | head -1)"
        M_VER="$(printf '%s' "$MANIFEST" | sed -n 's/.*"version" *: *"\([^"]*\)".*/\1/p' | head -1)"
        [ "$M_VER" = "$VERSION" ] || { echo "manifest.json version $M_VER != config.php version $VERSION" >&2; exit 1; }
        DROP_MANIFEST=0
        if [ "${M_ID:-0}" -lt 1 ]; then
            echo "note: manifest.json has no marketplace id yet; packaging without it" >&2
            DROP_MANIFEST=1
        fi
        LABEL="WiseCP5"
        ;;
    v3)
        VERSION="$(git show "$REF:v3/VERSION" | tr -d '[:space:]')"
        LABEL="WiseCP3"
        ;;
    *)
        echo "usage: $0 v3|v5 [ref] [output.zip]" >&2; exit 2 ;;
esac
[ -n "$VERSION" ] || { echo "version not found for $GEN" >&2; exit 1; }
OUT="${3:-dist/DNAHosting-${LABEL}-v${VERSION}.zip}"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

git archive "$REF" "$GEN/coremio" | tar -x -C "$WORK"
mv "$WORK/$GEN/coremio" "$WORK/coremio"
rmdir "$WORK/$GEN"
[ "${DROP_MANIFEST:-0}" = 1 ] && rm -f "$WORK/$MODULE/manifest.json"

EXTRA=()
while IFS= read -r f; do
    git show "$REF:$f" > "$WORK/$f"
    EXTRA+=("$f")
done < <(git ls-tree --name-only "$REF" | grep -E '^(README.*\.md|CHANGELOG\.md|LICENSE)$')

mkdir -p "$(dirname "$OUT")"
OUT_ABS="$(cd "$(dirname "$OUT")" && pwd)/$(basename "$OUT")"
rm -f "$OUT_ABS"
(cd "$WORK" && zip -q -r -X "$OUT_ABS" coremio "${EXTRA[@]}")

echo "$OUT_ABS"
