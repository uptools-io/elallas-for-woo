#!/usr/bin/env bash
# Build the official compliance assets (dev-only, not shipped in the release ZIP).
#
# Copies the European Commission's official files BYTE-IDENTICALLY into assets/
# (rename only), decodes the notice QR targets and regenerates
# assets/CHECKSUMS.sha256. It never rasterises, converts, recompresses or
# recolours anything: "No colour substitutions or format conversions should be
# made outside of the provided files" (Commission practical guidelines).
#
# Usage:
#   bin/build-compliance-assets.sh <PNG and JPG.zip> <SVG.zip> <GARAN label for website.zip> <Inter-3.19.zip>
#
# Sources (commission.europa.eu, "Practical guidelines and high-resolution vector
# files: EU notice and label for product guarantees"):
#   /document/download/dbd46ba2-77d2-4a74-ad52-120bc7bf02ea_en?filename=PNG%20and%20JPG.zip
#   /document/download/27c45f1f-78a1-47a7-a7cc-adf23afee5ea_en?filename=SVG.zip
#   /document/download/435fbeb1-fccc-4ead-bfa9-96625962ba09_en?filename=GARAN%20label%20for%20website.zip
# Inter: https://github.com/rsms/inter/releases/tag/v3.19 (Inter-3.19.zip)
#
# Optional: QR_PYTHON=/path/to/venv/bin/python (with opencv-python-headless) to
# print the decoded QR targets for OfficialAssets::LINKS.
#
# Note: the official "PNG and JPG.zip" contains no English (EN) PNG; the English
# notice is only published as SVG/PDF. EN therefore ships as SVG only.

set -euo pipefail

if [ "$#" -ne 4 ]; then
	sed -n '2,25p' "$0"
	exit 1
fi

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ASSETS="$ROOT/assets"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

LANGS="bg cs da de el en es et fi fr ga hr hu it lt lv mt nl pl pt ro sk sl sv"

unzip -q "$1" -d "$WORK/png"
unzip -q "$2" -d "$WORK/svg"
unzip -q "$3" -d "$WORK/garan"
unzip -q "$4" -d "$WORK/inter"

mkdir -p "$ASSETS/notice" "$ASSETS/garan" "$ASSETS/fonts/inter"

for code in $LANGS; do
	up="$(echo "$code" | tr '[:lower:]' '[:upper:]')"
	png="$WORK/png/PNG/Legal guarantee_notice_${up}.png"
	svg="$WORK/svg/Legal guarantee_notice ${up}.svg"
	if [ -f "$png" ]; then
		cp "$png" "$ASSETS/notice/notice-${code}.png"
	else
		echo "note: no official colour PNG for ${code} (SVG only)" >&2
	fi
	cp "$svg" "$ASSETS/notice/notice-${code}.svg"
done

cp "$WORK/garan/GARAN Label_colour.svg" "$ASSETS/garan/garan-label-colour.svg"
cp "$WORK/garan/GARAN Label_nested display.svg" "$ASSETS/garan/garan-label-nested.svg"
cp "$WORK/garan/GARAN Label_colour.png" "$ASSETS/garan/garan-label-colour.png"

cp "$WORK/inter/Inter Web/Inter-Regular.woff2" "$ASSETS/fonts/inter/Inter-Regular.woff2"
cp "$WORK/inter/Inter Web/Inter-ExtraBold.woff2" "$ASSETS/fonts/inter/Inter-ExtraBold.woff2"
cp "$WORK/inter/Inter Hinted for Windows/Desktop/Inter-Regular.ttf" "$ASSETS/fonts/inter/Inter-Regular.ttf"
cp "$WORK/inter/Inter Hinted for Windows/Desktop/Inter-ExtraBold.ttf" "$ASSETS/fonts/inter/Inter-ExtraBold.ttf"
cp "$WORK/inter/LICENSE.txt" "$ASSETS/fonts/inter/OFL.txt"

if [ -n "${QR_PYTHON:-}" ]; then
	"$QR_PYTHON" "$ROOT/bin/decode-notice-qr.py" "$ASSETS"/notice/notice-*.png
fi

if command -v sha256sum >/dev/null 2>&1; then
	SHA="sha256sum"
else
	SHA="shasum -a 256"
fi

cd "$ROOT"
{
	ls assets/notice/*.png assets/notice/*.svg assets/garan/* assets/fonts/inter/* | LC_ALL=C sort | while read -r f; do
		$SHA "$f"
	done
} > assets/CHECKSUMS.sha256

echo "wrote assets/CHECKSUMS.sha256 ($(wc -l < assets/CHECKSUMS.sha256 | tr -d ' ') files)"
