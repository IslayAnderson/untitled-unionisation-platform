"""Exports an SVG copy of every diagram in docs/architecture, next to its .drawio file. Run it after
entities.py or processes.py:

    python3 docs/architecture/diagrams/export.py

It needs the draw.io desktop app. draw.io gives every label a PNG copy, for viewers that can't show
HTML text in an SVG. Browsers, GitHub and editors all can, and the copies make the files ten times
bigger, so they're removed.
"""
import re
import shutil
import subprocess
import sys
from pathlib import Path

DOCS = Path(__file__).resolve().parent.parent
DRAWIO = shutil.which("drawio") or "/Applications/draw.io.app/Contents/MacOS/draw.io"
if not Path(DRAWIO).exists():
    sys.exit("Install the draw.io desktop app (https://www.drawio.com) to export the diagrams.")

for diagram in sorted(DOCS.glob("*.drawio")):
    svg = diagram.with_suffix(".svg")
    subprocess.run([DRAWIO, "--export", "--format", "svg", "--output", str(svg), str(diagram)], check=True, capture_output=True)
    text = re.sub(r'(</foreignObject>)<image [^>]*xlink:href="data:image/png;base64,[^"]*"[^>]*/?>(</image>)?', r"\1", svg.read_text())
    svg.write_text(text)
    print(f"Exported {svg.relative_to(DOCS.parents[1])}")
