#!/usr/bin/env python3
"""Decode the QR code of every official EU notice image (dev-only, not shipped).

Usage:
    python3 bin/decode-notice-qr.py assets/notice/notice-*.png [more images...]

Prints a JSON object {"<code>": "<decoded url>"} where <code> is taken from the
file name (notice-<code>.<ext>). The result is copied into
OfficialAssets::LINKS and the OfficialAssetsTest fixture.

Requirements (use a throwaway venv, never inside the repo):
    pip install opencv-python-headless
"""

import json
import re
import sys

import cv2  # type: ignore


def decode(path: str) -> str:
    image = cv2.imread(path, cv2.IMREAD_COLOR)
    if image is None:
        raise SystemExit(f"cannot read {path}")
    detector = cv2.QRCodeDetector()
    # The notice is A4 at 200 dpi; try the full image, then a few downscales.
    for scale in (1.0, 0.75, 0.5, 0.35):
        img = image if scale == 1.0 else cv2.resize(image, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)
        data, _points, _raw = detector.detectAndDecode(img)
        if data:
            return data
    raise SystemExit(f"no QR code found in {path}")


def main() -> None:
    if len(sys.argv) < 2:
        raise SystemExit(__doc__)
    result = {}
    for path in sys.argv[1:]:
        match = re.search(r"notice-([a-z]{2})\.[a-z0-9]+$", path)
        key = match.group(1) if match else path
        result[key] = decode(path)
    print(json.dumps(result, ensure_ascii=False, indent=2, sort_keys=True))


if __name__ == "__main__":
    main()
