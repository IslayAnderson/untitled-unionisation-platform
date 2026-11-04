"""Draws docs/architecture/Entity Diagram.drawio from the database schema. Run from anywhere:

    python3 docs/architecture/diagrams/entities.py

It runs the migrations into a throwaway SQLite database (with php artisan migrate), so it doesn't touch
your own database. Each table goes in the column listed in COLUMNS below: add new tables there.
"""
import html
import os
import sqlite3
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
OUT = ROOT / "docs" / "architecture" / "Entity Diagram.drawio"

schema = Path(tempfile.mkdtemp()) / "schema.sqlite"
schema.touch()
subprocess.run(
    ["php", "artisan", "migrate", "--force", "--no-interaction", "--quiet"],
    cwd=ROOT, check=True,
    env={**os.environ, "DB_CONNECTION": "sqlite", "DB_DATABASE": str(schema), "DB_URL": ""},
)
db = sqlite3.connect(f"file:{schema}?mode=ro", uri=True)

COLOURS = {  # the original diagram's groups, plus union mail and uploads
    "building": ("#dae8fc", "#6c8ebf"), "people": ("#d5e8d4", "#82b366"), "governance": ("#ffe6cc", "#d79b00"),
    "noticeboard": ("#e1d5e7", "#9673a6"), "mail": ("#f8cecc", "#b85450"), "files": ("#f5f5f5", "#666666"),
}
EDGE = {"buildings": "#1f6fd1", "contacts": "#2e8b3e"}
COLUMNS = [
    ("governance", ["constitutions", "policies", "referendums", "referendum_voters"]),
    ("building", ["buildings", "units", "qr_scans"]),
    ("building", ["landlords", "landlord_types", "mailing_lists:people", "contact_mailing_list:people", "contact_types:people"]),
    ("people", ["contacts", "users", "vouches", "roles", "role_actions", "contact_role"]),
    ("mail", ["union_mails", "union_mail_approvers", "union_mail_attachments", "uploaded_images:files"]),
    ("noticeboard", ["bulletins", "polls", "poll_answers", "poll_voters"]),
    ("noticeboard", ["threads", "replies"]),
]
POLYMORPHIC = {("referendums", "referendable_type"): "referendable_type", ("referendums", "referendable_id"): "referendable_id (policy, constitution, role or building)",
               ("replies", "replyable_type"): "replyable_type", ("replies", "replyable_id"): "replyable_id (bulletin or thread)"}
ROW, W = 30, 230

cells, edges, rowid, n = [], [], {}, [1]


def nid():
    n[0] += 1
    return f"erd-{n[0]}"


def table(name, group, x, y):
    cols = [(r[1], r[5]) for r in db.execute(f'pragma table_info("{name}")') if r[1] not in ("created_at", "updated_at")]
    fks = {r[3]: r[2] for r in db.execute(f'pragma foreign_key_list("{name}")')}
    fill, stroke = COLOURS[group]
    tid = nid()
    cells.append(f'<mxCell id="{tid}" parent="1" style="shape=table;startSize=30;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;html=1;fillColor={fill};strokeColor={stroke};swimlaneFillColor=#ffffff;" value="{name}" vertex="1"><mxGeometry height="{ROW * (len(cols) + 1)}" width="{W}" x="{x}" y="{y}" as="geometry" /></mxCell>')
    for i, (col, pk) in enumerate(cols):
        key = "PK" if pk else ("FK" if col in fks or (name, col) in POLYMORPHIC else "")
        rid = nid()
        rowid[(name, col)] = rid
        bottom = 1 if pk and not (i + 1 < len(cols) and cols[i + 1][1]) else 0
        cells.append(f'<mxCell id="{rid}" parent="{tid}" style="shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom={bottom};" value="" vertex="1"><mxGeometry height="{ROW}" width="{W}" y="{ROW * (i + 1)}" as="geometry" /></mxCell>')
        cells.append(f'<mxCell id="{nid()}" parent="{rid}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;{"fontStyle=1;" if key else "editable=1;"}overflow=hidden;whiteSpace=wrap;html=1;" value="{key}" vertex="1"><mxGeometry height="{ROW}" width="30" as="geometry"><mxRectangle height="{ROW}" width="30" as="alternateBounds" /></mxGeometry></mxCell>')
        label = html.escape(POLYMORPHIC.get((name, col), col))
        cells.append(f'<mxCell id="{nid()}" parent="{rid}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;{"fontStyle=5;" if pk else ""}overflow=hidden;whiteSpace=wrap;html=1;" value="{label}" vertex="1"><mxGeometry height="{ROW}" width="{W - 30}" x="30" as="geometry"><mxRectangle height="{ROW}" width="{W - 30}" as="alternateBounds" /></mxGeometry></mxCell>')
    for col, parent in fks.items():
        if parent != name:
            edges.append((parent, name, col))
    return ROW * (len(cols) + 1)


placed = {entry.partition(":")[0] for _, tables in COLUMNS for entry in tables}
skip = {"migrations", "password_reset_tokens", "sessions", "cache", "cache_locks", "jobs", "job_batches", "failed_jobs", "personal_access_tokens", "sqlite_sequence"}
missing = {t for (t,) in db.execute("select name from sqlite_master where type='table'")} - placed - skip
if missing:
    sys.exit(f"Add these tables to COLUMNS in {Path(__file__).name}: {', '.join(sorted(missing))}")

x = 40
for group, tables in COLUMNS:
    y = 330
    for entry in tables:
        name, _, g = entry.partition(":")
        y += table(name, g or group, x, y) + 70
    x += W + 110

for parent, child, col in edges:
    src = rowid.get((parent, "id")) or rowid[(parent, [c for (t, c) in rowid if t == parent][0])]
    colour = EDGE.get(parent, "#666666")
    edges_xml = f'<mxCell id="{nid()}" edge="1" parent="1" source="{src}" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;defaultLabelBackgroundColor=page;endArrow=ERmany;endFill=0;strokeColor={colour};jumpStyle=arc;jumpSize=8;" target="{rowid[(child, col)]}"><mxGeometry relative="1" as="geometry" /></mxCell>'
    cells.insert(0, edges_xml)

legend = ("Generated from the database schema by docs/architecture/diagrams/entities.py. Every table also has "
          "created_at and updated_at, except poll_voters and referendum_voters (no timestamps, so a vote's timing "
          "can't reveal it) and qr_scans (created_at only). referendable and replyable are polymorphic, so have no "
          "lines. policies.replaces_policy_id points to another policy.")
cells.append(f'<mxCell id="{nid()}" parent="1" style="text;html=1;whiteSpace=wrap;strokeColor=none;fillColor=none;align=left;verticalAlign=top;rounded=0;" value="&lt;b style=&quot;font-size:16px&quot;&gt;Entities&lt;/b&gt;&lt;br&gt;{html.escape(legend)}" vertex="1"><mxGeometry height="70" width="1100" x="40" y="10" as="geometry" /></mxCell>')

# Key ---------------------------------------------------------------------------
GROUP_NAMES = {
    "building": "Buildings, flats and landlords", "people": "People, mailing lists and roles",
    "governance": "Constitutions, policies and referendums", "noticeboard": "Bulletins, polls and discussions",
    "mail": "Union mail", "files": "Uploaded images",
}
KX, KY, KW = 40, 100, 1560
TEXT = "text;html=1;whiteSpace=wrap;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;rounded=0;"


def key_cell(style, value, x, y, w, h):
    cells.append(f'<mxCell id="{nid()}" parent="1" style="{style}" value="{html.escape(value)}" vertex="1"><mxGeometry height="{h}" width="{w}" x="{x}" y="{y}" as="geometry" /></mxCell>')


def key_line(colour, x, y, w=60):
    cells.append(f'<mxCell id="{nid()}" edge="1" parent="1" style="edgeStyle=none;rounded=0;html=1;endArrow=ERmany;endFill=0;strokeColor={colour};" value=""><mxGeometry relative="1" as="geometry"><mxPoint x="{x}" y="{y}" as="sourcePoint" /><mxPoint x="{x + w}" y="{y}" as="targetPoint" /></mxGeometry></mxCell>')


key_cell("swimlane;startSize=30;fontStyle=1;align=center;html=1;fillColor=#ffffff;strokeColor=#666666;swimlaneFillColor=#ffffff;collapsible=0;", "Key", KX, KY, KW, 190)
for i, (group, label) in enumerate(GROUP_NAMES.items()):
    fill, stroke = COLOURS[group]
    gx = KX + 20 + i * 255
    key_cell(f"rounded=0;html=1;fillColor={fill};strokeColor={stroke};", "", gx, KY + 45, 30, 20)
    key_cell(TEXT, label, gx + 38, KY + 40, 215, 30)
key_cell(TEXT + "fontStyle=1;", "PK", KX + 20, KY + 85, 30, 30)
key_cell(TEXT, "Primary key: the table's own id (underlined). Two PKs mean the pair is unique", KX + 50, KY + 85, 520, 30)
key_cell(TEXT + "fontStyle=1;", "FK", KX + 780, KY + 85, 30, 30)
key_cell(TEXT, "Foreign key: holds the id of a row in another table, shown by a line", KX + 810, KY + 85, 600, 30)
for i, (colour, label) in enumerate([
    (EDGE["buildings"], "From buildings: the union a row belongs to"),
    (EDGE["contacts"], "From contacts: the person a row is about or by"),
    ("#666666", "From any other table"),
]):
    lx = KX + 20 + i * 500
    key_line(colour, lx, KY + 145)
    key_cell(TEXT, label, lx + 70, KY + 130, 330, 30)
key_cell(TEXT + "fontColor=#666666;", "Each line runs from a table's id to the column that points to it. The forked end is the many side: one building has many flats, one contact has many vouches.", KX + 20, KY + 158, 1200, 30)

width, height = x + 40, 2400
open(OUT, "w").write(
    f'<mxfile host="Electron">\n  <diagram name="Page-1" id="erd">\n    <mxGraphModel dx="{width}" dy="{height}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="{width}" pageHeight="{height}" math="0" shadow="0">\n      <root>\n        <mxCell id="0" />\n        <mxCell id="1" parent="0" />\n'
    + "\n".join("        " + c for c in cells) + "\n      </root>\n    </mxGraphModel>\n  </diagram>\n</mxfile>\n")
print(f"Drew {len(placed)} tables and {len(edges)} relationships in {OUT.relative_to(ROOT)}")
