"""Shapes for the process diagrams: rounded steps, decision diamonds, Object boxes and notes, in draw.io's format."""
import html
import random
import string

STEP = "rounded=1;whiteSpace=wrap;html=1;absoluteArcSize=1;arcSize=14;strokeWidth=2;"
DECISION = "strokeWidth=2;html=1;shape=mxgraph.flowchart.decision;whiteSpace=wrap;"
OBJECT = "verticalAlign=top;align=left;overflow=fill;html=1;whiteSpace=wrap;"
TEXT = "text;html=1;whiteSpace=wrap;strokeColor=none;fillColor=none;align=center;verticalAlign=middle;rounded=0;"
TITLE = "text;html=1;whiteSpace=wrap;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;rounded=0;fontStyle=1;fontSize=16;"
SIDES = {"t": (0.5, 0), "b": (0.5, 1), "l": (0, 0.5), "r": (1, 0.5)}


class Diagram:
    def __init__(self, name):
        self.name = name
        self.prefix = "".join(random.Random(name).choices(string.ascii_letters + string.digits, k=20)) + "-"
        self.cells, self.n, self.shapes = [], 1, {}
        self.width = self.height = 0

    def _id(self):
        self.n += 1
        return f"{self.prefix}{self.n}"

    def _vertex(self, key, style, value, x, y, w, h):
        cid = self._id()
        self.shapes[key] = (cid, style)
        self.width, self.height = max(self.width, x + w), max(self.height, y + h)
        self.cells.append(
            f'        <mxCell id="{cid}" parent="1" style="{style}" value="{html.escape(value, quote=True)}" vertex="1">\n'
            f'          <mxGeometry height="{h}" width="{w}" x="{x}" y="{y}" as="geometry" />\n        </mxCell>')

    def title(self, text, x=10, y=0, w=600):
        self._vertex("title" + str(self.n), TITLE, text, x, y, w, 30)

    def step(self, key, text, x, y, w=180, h=100):
        self._vertex(key, STEP, text, x, y, w, h)

    def decision(self, key, text, x, y, w=140, h=140):
        self._vertex(key, DECISION, text, x, y, w, h)

    def object(self, key, name, fields, x, y, w=170, h=None):
        body = "<br>".join(html.escape(f) for f in fields)
        value = (f'<p style="margin:0px;margin-top:4px;text-align:center;text-decoration:underline;"><b>Object:{name}</b></p>'
                 f'<hr size="1" style="border-style:solid;"><p style="margin:0px;margin-left:8px;">{body}</p>')
        self._vertex(key, OBJECT, value, x, y, w, h or 40 + 17 * len(fields))

    def note(self, key, text, x, y, w=180, h=60):
        self._vertex(key, TEXT, text, x, y, w, h)

    def edge(self, source, target, exit="b", entry="t", label="", dashed=False, points=()):
        src, src_style = self.shapes[source]
        dst, dst_style = self.shapes[target]
        ex, ey = SIDES[exit]
        nx, ny = SIDES[entry]
        style = (f"edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;"
                 f"exitX={ex};exitY={ey};exitDx=0;exitDy=0;" + ("exitPerimeter=0;" if src_style == DECISION else "")
                 + f"entryX={nx};entryY={ny};entryDx=0;entryDy=0;" + ("entryPerimeter=0;" if dst_style == DECISION else "")
                 + "defaultLabelBackgroundColor=page;" + ("dashed=1;endArrow=none;endFill=0;" if dashed else ""))
        geometry = '          <mxGeometry relative="1" as="geometry" />'
        if points:
            pts = "".join(f'<mxPoint x="{x}" y="{y}" />' for x, y in points)
            geometry = f'          <mxGeometry relative="1" as="geometry">\n            <Array as="points">{pts}</Array>\n          </mxGeometry>'
        self.cells.insert(0,
            f'        <mxCell id="{self._id()}" edge="1" parent="1" source="{src}" style="{style}" target="{dst}" value="{html.escape(label, quote=True)}">\n'
            f'{geometry}\n        </mxCell>')

    def write(self, path):
        w, h = max(1169, self.width + 40), max(827, self.height + 40)
        out = (f'<mxfile host="Electron">\n  <diagram name="Page-1" id="{self.prefix[:20]}">\n'
               f'    <mxGraphModel dx="{w}" dy="{h}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="{w}" pageHeight="{h}" math="0" shadow="0">\n'
               '      <root>\n        <mxCell id="0" />\n        <mxCell id="1" parent="0" />\n'
               + "\n".join(self.cells) + "\n      </root>\n    </mxGraphModel>\n  </diagram>\n</mxfile>\n")
        open(path, "w").write(out)
