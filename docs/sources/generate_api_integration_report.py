"""Genera el informe Word de ocho páginas sobre las APIs de Wally Reservas."""

from __future__ import annotations

import re
from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "docs" / "INTEGRACION_APIS_WALLY_RESERVAS_8_PAGINAS.md"
OUTPUT = ROOT / "docs" / "Integracion_APIs_Wally_Reservas_8_paginas.docx"

NAVY = "071A2B"
TEAL = "008C95"
LIME = "B8E000"
LIGHT = "EAF5F5"
GRAY = "5F6B76"


def set_cell_shading(cell, fill: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    shading = tc_pr.find(qn("w:shd"))
    if shading is None:
        shading = OxmlElement("w:shd")
        tc_pr.append(shading)
    shading.set(qn("w:fill"), fill)


def set_repeat_table_header(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement("w:tblHeader")
    tbl_header.set(qn("w:val"), "true")
    tr_pr.append(tbl_header)


def prevent_row_split(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    cant_split = OxmlElement("w:cantSplit")
    tr_pr.append(cant_split)


def add_field(paragraph, instruction: str) -> None:
    run = paragraph.add_run()
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    field = OxmlElement("w:instrText")
    field.set(qn("xml:space"), "preserve")
    field.text = instruction
    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")
    value = OxmlElement("w:t")
    value.text = "1"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([begin, field, separate, value, end])


def add_inline(paragraph, text: str) -> None:
    """Interpreta negrita y código en línea del Markdown usado por el informe."""
    token = re.compile(r"(\*\*.+?\*\*|`.+?`)")
    position = 0
    for match in token.finditer(text):
        if match.start() > position:
            paragraph.add_run(text[position : match.start()])
        value = match.group(0)
        if value.startswith("**"):
            run = paragraph.add_run(value[2:-2])
            run.bold = True
        else:
            run = paragraph.add_run(value[1:-1])
            run.font.name = "Consolas"
            run.font.size = Pt(8)
            run.font.color.rgb = RGBColor.from_string(NAVY)
        position = match.end()
    if position < len(text):
        paragraph.add_run(text[position:])


def configure_document(document: Document) -> None:
    section = document.sections[0]
    section.page_width = Cm(21)
    section.page_height = Cm(29.7)
    section.top_margin = Cm(1.45)
    section.bottom_margin = Cm(1.45)
    section.left_margin = Cm(1.65)
    section.right_margin = Cm(1.65)
    section.header_distance = Cm(0.6)
    section.footer_distance = Cm(0.6)

    normal = document.styles["Normal"]
    normal.font.name = "Arial"
    normal.font.size = Pt(9.2)
    normal.font.color.rgb = RGBColor.from_string(NAVY)
    normal.paragraph_format.space_after = Pt(3)
    normal.paragraph_format.line_spacing = 1.0
    normal.paragraph_format.widow_control = True

    title = document.styles["Title"]
    title.font.name = "Arial"
    title.font.size = Pt(21)
    title.font.bold = True
    title.font.color.rgb = RGBColor.from_string(NAVY)
    title.paragraph_format.space_after = Pt(8)

    heading1 = document.styles["Heading 1"]
    heading1.font.name = "Arial"
    heading1.font.size = Pt(15)
    heading1.font.bold = True
    heading1.font.color.rgb = RGBColor.from_string(TEAL)
    heading1.paragraph_format.space_before = Pt(0)
    heading1.paragraph_format.space_after = Pt(6)
    heading1.paragraph_format.keep_with_next = True

    heading2 = document.styles["Heading 2"]
    heading2.font.name = "Arial"
    heading2.font.size = Pt(11.2)
    heading2.font.bold = True
    heading2.font.color.rgb = RGBColor.from_string(TEAL)
    heading2.paragraph_format.space_before = Pt(5)
    heading2.paragraph_format.space_after = Pt(3)
    heading2.paragraph_format.keep_with_next = True

    header = section.header.paragraphs[0]
    header.text = "WALLY RESERVAS  |  INTEGRACIÓN DE APIS"
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    for run in header.runs:
        run.font.name = "Arial"
        run.font.size = Pt(7.5)
        run.font.bold = True
        run.font.color.rgb = RGBColor.from_string(TEAL)

    footer = section.footer.paragraphs[0]
    footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
    footer.add_run("Documento técnico  ·  Página ")
    add_field(footer, "PAGE")
    footer.add_run(" de 8")
    for run in footer.runs:
        run.font.name = "Arial"
        run.font.size = Pt(7.5)
        run.font.color.rgb = RGBColor.from_string(GRAY)

    settings = document.settings._element
    update_fields = OxmlElement("w:updateFields")
    update_fields.set(qn("w:val"), "true")
    settings.append(update_fields)


def add_table(document: Document, rows: list[list[str]]) -> None:
    if not rows:
        return
    columns = max(len(row) for row in rows)
    table = document.add_table(rows=len(rows), cols=columns)
    table.style = "Table Grid"
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = True

    for row_index, values in enumerate(rows):
        row = table.rows[row_index]
        prevent_row_split(row)
        if row_index == 0:
            set_repeat_table_header(row)
        for column_index in range(columns):
            cell = row.cells[column_index]
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            value = values[column_index] if column_index < len(values) else ""
            paragraph = cell.paragraphs[0]
            paragraph.paragraph_format.space_after = Pt(0)
            add_inline(paragraph, value)
            for run in paragraph.runs:
                run.font.name = "Arial"
                run.font.size = Pt(7.4)
                if row_index == 0:
                    run.bold = True
                    run.font.color.rgb = RGBColor(255, 255, 255)
            set_cell_shading(cell, TEAL if row_index == 0 else (LIGHT if row_index % 2 else "FFFFFF"))
    document.add_paragraph().paragraph_format.space_after = Pt(0)


def add_code_block(document: Document, lines: list[str]) -> None:
    paragraph = document.add_paragraph()
    paragraph.paragraph_format.left_indent = Cm(0.35)
    paragraph.paragraph_format.right_indent = Cm(0.35)
    paragraph.paragraph_format.space_before = Pt(2)
    paragraph.paragraph_format.space_after = Pt(4)
    run = paragraph.add_run("\n".join(lines))
    run.font.name = "Consolas"
    run.font.size = Pt(7.7)
    run.font.color.rgb = RGBColor.from_string(NAVY)
    p_pr = paragraph._p.get_or_add_pPr()
    shading = OxmlElement("w:shd")
    shading.set(qn("w:fill"), LIGHT)
    p_pr.append(shading)


def render_page(document: Document, markdown: str, page_number: int) -> None:
    lines = markdown.strip().splitlines()
    index = 0
    first_heading = True

    while index < len(lines):
        raw = lines[index].rstrip()
        stripped = raw.strip()
        if not stripped:
            index += 1
            continue

        if stripped.startswith("```"):
            code_lines: list[str] = []
            index += 1
            while index < len(lines) and not lines[index].strip().startswith("```"):
                code_lines.append(lines[index].rstrip())
                index += 1
            add_code_block(document, code_lines)
            index += 1
            continue

        if stripped.startswith("|"):
            table_lines: list[str] = []
            while index < len(lines) and lines[index].strip().startswith("|"):
                table_lines.append(lines[index].strip())
                index += 1
            rows = []
            for position, line in enumerate(table_lines):
                cells = [cell.strip() for cell in line.strip("|").split("|")]
                if position == 1 and all(re.fullmatch(r":?-{3,}:?", cell) for cell in cells):
                    continue
                rows.append(cells)
            add_table(document, rows)
            continue

        if stripped.startswith("# "):
            text = stripped[2:]
            if page_number == 1 and first_heading:
                paragraph = document.add_paragraph(style="Title")
                paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
                add_inline(paragraph, text)
                accent = document.add_paragraph()
                accent.paragraph_format.space_after = Pt(7)
                run = accent.add_run("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
                run.font.color.rgb = RGBColor.from_string(LIME)
            else:
                paragraph = document.add_paragraph(style="Heading 1")
                add_inline(paragraph, text)
            first_heading = False
            index += 1
            continue

        if stripped.startswith("## "):
            paragraph = document.add_paragraph(style="Heading 1" if page_number == 1 else "Heading 2")
            add_inline(paragraph, stripped[3:])
            index += 1
            continue

        if stripped.startswith("### "):
            paragraph = document.add_paragraph(style="Heading 2")
            add_inline(paragraph, stripped[4:])
            index += 1
            continue

        if stripped.startswith("- "):
            paragraph = document.add_paragraph(style="List Bullet")
            paragraph.paragraph_format.left_indent = Cm(0.45)
            paragraph.paragraph_format.first_line_indent = Cm(-0.2)
            paragraph.paragraph_format.space_after = Pt(1.5)
            add_inline(paragraph, stripped[2:])
            index += 1
            continue

        paragraph_lines = [stripped]
        index += 1
        while index < len(lines):
            candidate = lines[index].strip()
            if not candidate or candidate.startswith(("#", "- ", "|", "```")):
                break
            paragraph_lines.append(candidate)
            index += 1
        paragraph = document.add_paragraph()
        paragraph.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        add_inline(paragraph, " ".join(paragraph_lines))


def main() -> None:
    markdown = SOURCE.read_text(encoding="utf-8")
    pages = [page.strip() for page in markdown.split("<!-- PAGE BREAK -->")]
    if len(pages) != 8:
        raise ValueError(f"Se esperaban 8 páginas y se encontraron {len(pages)}")

    document = Document()
    configure_document(document)
    for page_number, page in enumerate(pages, start=1):
        if page_number > 1:
            document.add_page_break()
        render_page(document, page, page_number)

    document.core_properties.title = "Integración de APIs y flujo de interacción de Wally Reservas"
    document.core_properties.subject = "Google OAuth, Google Calendar, Gemini/OpenAI, voz, base de datos y restricciones"
    document.core_properties.author = "Proyecto Wally Reservas"
    document.core_properties.keywords = "Wally Reservas, APIs, Laravel, Gemini, Google Calendar, OAuth"
    document.save(OUTPUT)
    print(OUTPUT)


if __name__ == "__main__":
    main()
