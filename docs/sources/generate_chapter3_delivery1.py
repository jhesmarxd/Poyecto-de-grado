from __future__ import annotations

import re
from datetime import date
from pathlib import Path

from docx import Document
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_LINE_SPACING
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "docs" / "chapter3" / "ENTREGA_01_REQUISITOS.md"
OUTPUT = ROOT / "docs" / "Capitulo_III_Entrega_01_Ingenieria_de_Requerimientos.docx"

GREEN = "176B55"
DARK = "20352E"
LIGHT = "EEF6F2"
GRAY = "5E6B66"
WHITE = "FFFFFF"


def shade(cell, color: str) -> None:
    properties = cell._tc.get_or_add_tcPr()
    node = properties.find(qn("w:shd"))
    if node is None:
        node = OxmlElement("w:shd")
        properties.append(node)
    node.set(qn("w:fill"), color)


def set_cell_margins(cell, value: int = 70) -> None:
    properties = cell._tc.get_or_add_tcPr()
    margins = properties.first_child_found_in("w:tcMar")
    if margins is None:
        margins = OxmlElement("w:tcMar")
        properties.append(margins)
    for edge in ("top", "start", "bottom", "end"):
        node = margins.find(qn(f"w:{edge}"))
        if node is None:
            node = OxmlElement(f"w:{edge}")
            margins.append(node)
        node.set(qn("w:w"), str(value))
        node.set(qn("w:type"), "dxa")


def repeat_header(row) -> None:
    properties = row._tr.get_or_add_trPr()
    node = OxmlElement("w:tblHeader")
    node.set(qn("w:val"), "true")
    properties.append(node)


def prevent_row_split(row) -> None:
    properties = row._tr.get_or_add_trPr()
    node = OxmlElement("w:cantSplit")
    properties.append(node)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = paragraph.add_run("Página ")
    run.font.name = "Arial"
    run.font.size = Pt(9)
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instruction = OxmlElement("w:instrText")
    instruction.set(qn("xml:space"), "preserve")
    instruction.text = "PAGE"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([begin, instruction, end])


def add_toc(paragraph) -> None:
    run = paragraph.add_run()
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instruction = OxmlElement("w:instrText")
    instruction.set(qn("xml:space"), "preserve")
    instruction.text = 'TOC \\o "1-3" \\h \\z \\u'
    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")
    placeholder = OxmlElement("w:t")
    placeholder.text = "Actualice esta tabla en Word: clic derecho → Actualizar campo."
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([begin, instruction, separate, placeholder, end])


def setup_document() -> Document:
    document = Document()
    section = document.sections[0]
    section.top_margin = Cm(2.5)
    section.bottom_margin = Cm(2.5)
    section.left_margin = Cm(3.0)
    section.right_margin = Cm(2.5)

    normal = document.styles["Normal"]
    normal.font.name = "Arial"
    normal.font.size = Pt(11)
    normal.font.color.rgb = RGBColor.from_string(DARK)
    normal.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    normal.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
    normal.paragraph_format.space_after = Pt(6)

    for name, size, color in (
        ("Title", 22, DARK),
        ("Heading 1", 16, GREEN),
        ("Heading 2", 14, DARK),
        ("Heading 3", 12, GREEN),
        ("Heading 4", 11, DARK),
    ):
        style = document.styles[name]
        style.font.name = "Arial"
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.color.rgb = RGBColor.from_string(color)
        style.paragraph_format.space_before = Pt(12)
        style.paragraph_format.space_after = Pt(6)
        style.paragraph_format.keep_with_next = True

    header = section.header.paragraphs[0]
    header.text = "WALLY RESERVAS  |  CAPÍTULO III"
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    for run in header.runs:
        run.font.name = "Arial"
        run.font.size = Pt(8)
        run.font.bold = True
        run.font.color.rgb = RGBColor.from_string(GREEN)
    add_page_number(section.footer.paragraphs[0])

    settings = document.settings._element
    update = settings.find(qn("w:updateFields"))
    if update is None:
        update = OxmlElement("w:updateFields")
        settings.append(update)
    update.set(qn("w:val"), "true")
    return document


INLINE_PATTERN = re.compile(r"(\*\*.+?\*\*|`.+?`)")


def add_inline(paragraph, text: str) -> None:
    for part in INLINE_PATTERN.split(text):
        if not part:
            continue
        if part.startswith("**") and part.endswith("**"):
            paragraph.add_run(part[2:-2]).bold = True
        elif part.startswith("`") and part.endswith("`"):
            run = paragraph.add_run(part[1:-1])
            run.font.name = "Consolas"
            run.font.size = Pt(9)
            run.font.color.rgb = RGBColor.from_string(GREEN)
        else:
            paragraph.add_run(part)


def parse_table_row(line: str) -> list[str]:
    return [cell.strip() for cell in line.strip().strip("|").split("|")]


def is_separator(line: str) -> bool:
    cells = parse_table_row(line)
    return bool(cells) and all(re.fullmatch(r":?-{3,}:?", cell) for cell in cells)


def add_table(document: Document, rows: list[list[str]]) -> None:
    if not rows:
        return
    columns = max(len(row) for row in rows)
    table = document.add_table(rows=1, cols=columns)
    table.style = "Table Grid"
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = True
    repeat_header(table.rows[0])
    prevent_row_split(table.rows[0])

    for index, value in enumerate(rows[0]):
        cell = table.rows[0].cells[index]
        shade(cell, GREEN)
        cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        paragraph = cell.paragraphs[0]
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        add_inline(paragraph, value)
        for run in paragraph.runs:
            run.font.name = "Arial"
            run.font.size = Pt(8)
            run.font.bold = True
            run.font.color.rgb = RGBColor.from_string(WHITE)
        set_cell_margins(cell)

    for row_number, values in enumerate(rows[1:]):
        cells = table.add_row().cells
        prevent_row_split(table.rows[-1])
        for index in range(columns):
            value = values[index] if index < len(values) else ""
            cell = cells[index]
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
            if row_number % 2 == 0:
                shade(cell, LIGHT)
            paragraph = cell.paragraphs[0]
            paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
            paragraph.paragraph_format.line_spacing = 1.0
            paragraph.paragraph_format.space_after = Pt(0)
            add_inline(paragraph, value)
            for run in paragraph.runs:
                if run.font.name != "Consolas":
                    run.font.name = "Arial"
                run.font.size = Pt(7.6 if columns >= 7 else 8.1)
            set_cell_margins(cell)
        cells[0].paragraphs[0].runs[0].bold = True
    document.add_paragraph().paragraph_format.space_after = Pt(1)


def add_figure(document: Document, title: str, relative_path: str) -> None:
    image_path = (SOURCE.parent / relative_path).resolve()
    if not image_path.exists():
        raise FileNotFoundError(image_path)

    paragraph = document.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    paragraph.paragraph_format.keep_with_next = True
    paragraph.add_run().add_picture(str(image_path), width=Cm(15.5))

    caption = document.add_paragraph()
    caption.alignment = WD_ALIGN_PARAGRAPH.CENTER
    caption.paragraph_format.space_after = Pt(1)
    caption.paragraph_format.keep_with_next = True
    run = caption.add_run(title)
    run.bold = True
    run.font.name = "Arial"
    run.font.size = Pt(9.5)

    source = document.add_paragraph()
    source.alignment = WD_ALIGN_PARAGRAPH.CENTER
    source.paragraph_format.space_after = Pt(8)
    run = source.add_run("Fuente: Elaboración propia mediante Lucid.")
    run.italic = True
    run.font.name = "Arial"
    run.font.size = Pt(8.5)
    run.font.color.rgb = RGBColor.from_string(GRAY)


def add_cover(document: Document) -> None:
    document.add_paragraph().paragraph_format.space_after = Pt(42)
    title = document.add_paragraph()
    title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    title.add_run("CAPÍTULO III\nINGENIERÍA DEL PROYECTO").bold = True
    for run in title.runs:
        run.font.name = "Arial"
        run.font.size = Pt(24)
        run.font.color.rgb = RGBColor.from_string(DARK)

    subtitle = document.add_paragraph()
    subtitle.alignment = WD_ALIGN_PARAGRAPH.CENTER
    subtitle.add_run("Entrega 01: Ingeniería de requerimientos").bold = True
    for run in subtitle.runs:
        run.font.name = "Arial"
        run.font.size = Pt(16)
        run.font.color.rgb = RGBColor.from_string(GREEN)

    document.add_paragraph().paragraph_format.space_after = Pt(38)
    metadata = [
        ("Proyecto", "Sistema web de reservas y atención automatizada para canchas de Wally"),
        ("Autor", "Jhesmar Fuentes"),
        ("Estado", "Para revisión y aprobación del estudiante"),
        ("Fecha de generación", date.today().isoformat()),
        ("Alcance", "Secciones 3.1 y 3.2; sin diagramas UML"),
    ]
    table = document.add_table(rows=0, cols=2)
    table.style = "Table Grid"
    for label, value in metadata:
        cells = table.add_row().cells
        shade(cells[0], GREEN)
        left = cells[0].paragraphs[0]
        left.add_run(label).bold = True
        for run in left.runs:
            run.font.color.rgb = RGBColor.from_string(WHITE)
        cells[1].text = value
        for cell in cells:
            set_cell_margins(cell, 100)
            for paragraph in cell.paragraphs:
                paragraph.paragraph_format.line_spacing = 1.0
                for run in paragraph.runs:
                    run.font.name = "Arial"
                    run.font.size = Pt(9)

    note = document.add_paragraph()
    note.alignment = WD_ALIGN_PARAGRAPH.CENTER
    note.paragraph_format.space_before = Pt(28)
    add_inline(note, "Documento independiente. **No modifica los capítulos I y II.**")
    document.add_page_break()

    document.add_heading("Contenido de la entrega", level=1)
    add_toc(document.add_paragraph())
    document.add_page_break()


def render_markdown(document: Document, source: str) -> None:
    lines = source.splitlines()
    index = 0
    paragraph_buffer: list[str] = []

    def flush_paragraph() -> None:
        nonlocal paragraph_buffer
        if paragraph_buffer:
            paragraph = document.add_paragraph()
            add_inline(paragraph, " ".join(value.strip() for value in paragraph_buffer))
            paragraph_buffer = []

    while index < len(lines):
        line = lines[index].rstrip()
        stripped = line.strip()

        if not stripped:
            flush_paragraph()
            index += 1
            continue

        heading = re.match(r"^(#{1,4})\s+(.+)$", stripped)
        if heading:
            flush_paragraph()
            level = min(len(heading.group(1)), 4)
            document.add_heading(heading.group(2), level=level)
            index += 1
            continue

        if stripped.startswith(">"):
            flush_paragraph()
            paragraph = document.add_paragraph()
            paragraph.paragraph_format.left_indent = Cm(0.6)
            paragraph.paragraph_format.right_indent = Cm(0.3)
            paragraph.paragraph_format.space_before = Pt(4)
            paragraph.paragraph_format.space_after = Pt(8)
            add_inline(paragraph, stripped.lstrip("> "))
            for run in paragraph.runs:
                run.italic = True
                run.font.color.rgb = RGBColor.from_string(GRAY)
            index += 1
            continue

        if stripped.startswith("|") and index + 1 < len(lines) and is_separator(lines[index + 1]):
            flush_paragraph()
            rows = [parse_table_row(stripped)]
            index += 2
            while index < len(lines) and lines[index].strip().startswith("|"):
                rows.append(parse_table_row(lines[index]))
                index += 1
            add_table(document, rows)
            continue

        image_match = re.match(r"^!\[(.+)\]\((.+)\)$", stripped)
        if image_match:
            flush_paragraph()
            add_figure(document, image_match.group(1), image_match.group(2))
            index += 1
            continue

        bullet = re.match(r"^-\s+(.+)$", stripped)
        numbered = re.match(r"^\d+\.\s+(.+)$", stripped)
        if bullet or numbered:
            flush_paragraph()
            paragraph = document.add_paragraph(style="List Bullet" if bullet else "List Number")
            add_inline(paragraph, (bullet or numbered).group(1))
            paragraph.paragraph_format.space_after = Pt(3)
            index += 1
            continue

        if stripped == "---":
            flush_paragraph()
            index += 1
            continue

        paragraph_buffer.append(stripped)
        index += 1

    flush_paragraph()


def main() -> None:
    if not SOURCE.exists():
        raise FileNotFoundError(SOURCE)
    document = setup_document()
    document.core_properties.title = "Capítulo III — Entrega 01: Ingeniería de requerimientos"
    document.core_properties.subject = "Wally Reservas"
    document.core_properties.author = "Jhesmar Fuentes"
    document.core_properties.comments = "Generado desde una fuente Markdown versionable y contrastada con el repositorio."
    add_cover(document)
    render_markdown(document, SOURCE.read_text(encoding="utf-8"))
    document.save(OUTPUT)
    print(f"Generado: {OUTPUT}")


if __name__ == "__main__":
    main()
