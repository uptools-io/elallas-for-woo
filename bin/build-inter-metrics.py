#!/usr/bin/env python3
"""Generate includes/Compliance/GaranMetricsTable.php from the bundled Inter 3.19 TTFs.

Dev-only (not shipped). Requirements (throwaway venv, never inside the repo):
    pip install fonttools

Usage:
    python3 bin/build-inter-metrics.py

Reads assets/fonts/inter/Inter-Regular.ttf and Inter-ExtraBold.ttf and writes the
advance width (font units, hmtx) of every mapped code point in the covered ranges
(Basic Latin, Latin-1 Supplement, Latin Extended-A/B, a few punctuation and
symbol code points). Kerning (GPOS) is deliberately not included: browsers apply
Inter's GPOS kerning (which only narrows the tested strings, by up to ~10 units
for "7,5" at 80 px), while GD/FreeType rasterising does not, so the plain
advance sum is the conservative width for both outputs (M0 fit measurement).
"""

import os
import sys

from fontTools.ttLib import TTFont  # type: ignore

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FONTS = os.path.join(ROOT, "assets", "fonts", "inter")
OUT = os.path.join(ROOT, "includes", "Compliance", "GaranMetricsTable.php")

RANGES = [
    (0x0020, 0x007E),  # Basic Latin
    (0x00A0, 0x00FF),  # Latin-1 Supplement
    (0x0100, 0x017F),  # Latin Extended-A
    (0x0180, 0x024F),  # Latin Extended-B
    (0x2010, 0x2027),  # Dashes, quotes, bullets, ellipsis
    (0x2030, 0x203A),  # Per mille, primes, angle quotes
    (0x20AC, 0x20AC),  # Euro sign
    (0x2122, 0x2122),  # Trade mark sign
]


def advances(path: str) -> "tuple[int, dict[int, int]]":
    font = TTFont(path)
    cmap = font.getBestCmap()
    hmtx = font["hmtx"].metrics
    result = {}
    for start, end in RANGES:
        for cp in range(start, end + 1):
            glyph = cmap.get(cp)
            if glyph is not None:
                result[cp] = int(hmtx[glyph][0])
    return int(font["head"].unitsPerEm), result


def php_array(name: str, data: "dict[int, int]") -> str:
    lines = [f"\tpublic const {name} = ["]
    for cp in sorted(data):
        lines.append(f"\t\t0x{cp:04X} => {data[cp]},")
    lines.append("\t];")
    return "\n".join(lines)


def main() -> None:
    upm_r, regular = advances(os.path.join(FONTS, "Inter-Regular.ttf"))
    upm_b, bold = advances(os.path.join(FONTS, "Inter-ExtraBold.ttf"))
    if upm_r != upm_b:
        sys.exit("units per em differ between weights")

    php = f"""<?php
/**
 * GENERATED FILE - do not edit. Regenerate with `python3 bin/build-inter-metrics.py`.
 *
 * Inter 3.19 (rsms/inter v3.19, SIL OFL 1.1) advance widths in font units for
 * the Regular (wght 400) and ExtraBold (wght 800) cuts, as used by the official
 * GARAN label SVG. Kerning is intentionally absent: the advance sum is the
 * conservative (widest) width. Pure data class, exempt from the file-size guideline.
 *
 * @package LightweightPlugins\\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\\Elallas\\Compliance;

// phpcs:disable WordPress.Arrays -- Generated data table.

/**
 * Inter 3.19 advance-width table (generated).
 */
final class GaranMetricsTable {{

\t/**
\t * Font units per em.
\t */
\tpublic const UNITS_PER_EM = {upm_r};

\t/**
\t * Widest glyph in each table (fallback for unknown characters).
\t */
\tpublic const MAX_REGULAR = {max(regular.values())};

\t/**
\t * Widest ExtraBold glyph.
\t */
\tpublic const MAX_EXTRABOLD = {max(bold.values())};

\t/**
\t * Regular (400) advance widths, code point => font units.
\t *
\t * @var array<int, int>
\t */
{php_array("REGULAR", regular)}

\t/**
\t * ExtraBold (800) advance widths, code point => font units.
\t *
\t * @var array<int, int>
\t */
{php_array("EXTRABOLD", bold)}
}}
"""
    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    with open(OUT, "w", encoding="utf-8") as handle:
        handle.write(php)
    print(f"wrote {os.path.relpath(OUT, ROOT)}: {len(regular)} regular, {len(bold)} extrabold glyphs")


if __name__ == "__main__":
    main()
