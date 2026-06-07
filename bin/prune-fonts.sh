#!/usr/bin/env bash
#
# Reduce mPDF's bundled fonts to the DejaVu family only.
#
# DejaVu covers Latin, Turkish, Cyrillic, Greek and Western/Central European
# scripts — the common case for form submissions — and shrinks the plugin from
# ~95 MB to ~16 MB.
#
# IMPORTANT: `composer install` / `composer update` restores the full font set,
# so re-run this script afterwards.
#
# To support additional scripts (CJK, Arabic, Hebrew, Thai, …), do NOT prune
# the matching font, or add it back into vendor/mpdf/mpdf/ttfonts and reference
# it from your PDF CSS (font-family) so mPDF embeds it.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FONTS_DIR="${SCRIPT_DIR}/../vendor/mpdf/mpdf/ttfonts"

if [ ! -d "$FONTS_DIR" ]; then
    echo "mPDF font directory not found: $FONTS_DIR" >&2
    echo "Run 'composer install' first." >&2
    exit 1
fi

deleted=0
for f in "$FONTS_DIR"/*.ttf "$FONTS_DIR"/*.otf; do
    [ -e "$f" ] || continue
    base="$(basename "$f")"
    case "$base" in
        DejaVu*) ;;                       # keep the DejaVu family
        *) rm -f "$f"; deleted=$((deleted + 1)) ;;
    esac
done

echo "Pruned ${deleted} non-DejaVu font file(s)."
echo "mPDF fonts now: $(du -sh "$FONTS_DIR" | cut -f1)"
