# -*- coding: utf-8 -*-
"""
Generador del Documento Completo: Capitulo_III_Proyecto_Final.docx
Auditoría y completitud de Sprints 1 a 4 para el Sistema de Reservas de Canchas de Wally.
"""

import os
import shutil
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

def apply_table_styles(table, col_widths=None):
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    tblPr = table._tbl.tblPr
    borders = parse_xml(f'''
        <w:tblBorders {nsdecls("w")}>
            <w:top w:val="single" w:sz="4" w:space="0" w:color="B0B0B0"/>
            <w:left w:val="single" w:sz="4" w:space="0" w:color="B0B0B0"/>
            <w:bottom w:val="single" w:sz="4" w:space="0" w:color="B0B0B0"/>
            <w:right w:val="single" w:sz="4" w:space="0" w:color="B0B0B0"/>
            <w:insideH w:val="single" w:sz="4" w:space="0" w:color="D3D3D3"/>
            <w:insideV w:val="single" w:sz="4" w:space="0" w:color="D3D3D3"/>
        </w:tblBorders>
    ''')
    tblPr.append(borders)
    if col_widths:
        for row in table.rows:
            for idx, width in enumerate(col_widths):
                if idx < len(row.cells):
                    row.cells[idx].width = width

def set_cell_shd(cell, color_hex):
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{color_hex}"/>')
    cell._tc.get_or_add_tcPr().append(shd)

def add_backlog_table(doc, sprint_num, items):
    table = doc.add_table(rows=len(items) + 1, cols=5)
    apply_table_styles(table, [Inches(0.6), Inches(1.8), Inches(2.6), Inches(0.8), Inches(0.9)])
    
    headers = ["N°", "Historia de Usuario", "Tareas principales", "Prioridad", "Tiempo estimado"]
    r0 = table.rows[0]
    for idx, h in enumerate(headers):
        r0.cells[idx].text = h
        set_cell_shd(r0.cells[idx], "2B4C7E")
        for p in r0.cells[idx].paragraphs:
            for r in p.runs:
                r.bold = True
                r.font.color.rgb = RGBColor(255, 255, 255)
                
    for row_idx, item in enumerate(items, start=1):
        row = table.rows[row_idx]
        for col_idx, val in enumerate(item):
            row.cells[col_idx].text = str(val)
            if col_idx in [0, 3, 4]:
                set_cell_shd(row.cells[col_idx], "F9FAFB")
                row.cells[col_idx].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
    doc.add_paragraph("", style="Normal")

def add_cu_table(doc, cu_id, cu_name, actors, pre, post, basic_flow, alt_flow, exceptions):
    table = doc.add_table(rows=8, cols=2)
    apply_table_styles(table, [Inches(1.8), Inches(4.7)])
    
    r0 = table.rows[0]
    r0.cells[0].text = "Elemento"
    r0.cells[1].text = "Descripción"
    set_cell_shd(r0.cells[0], "2B4C7E")
    set_cell_shd(r0.cells[1], "2B4C7E")
    for c in r0.cells:
        for p in c.paragraphs:
            for r in p.runs:
                r.bold = True
                r.font.color.rgb = RGBColor(255, 255, 255)
                
    data = [
        ("Caso de Uso", f"{cu_name} ({cu_id})"),
        ("Actores", actors),
        ("Precondición", pre),
        ("Postcondición", post),
        ("Flujo Básico", basic_flow),
        ("Flujo Alternativo", alt_flow),
        ("Excepciones", exceptions)
    ]
    for idx, (label, val) in enumerate(data, start=1):
        r = table.rows[idx]
        r.cells[0].text = label
        r.cells[1].text = val
        set_cell_shd(r.cells[0], "F2F4F8")
        for p in r.cells[0].paragraphs:
            for run in p.runs:
                run.bold = True
    doc.add_paragraph("", style="Normal")

def add_fig_placeholder(doc, fig_num, fig_title, puml_file=None):
    p_title = doc.add_paragraph(style="Tabla figuras")
    r_title = p_title.add_run(f"Figura {fig_num}. {fig_title}")
    r_title.bold = True
    
    p_box = doc.add_paragraph()
    p_box.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r_box = p_box.add_run(f"\n[ESPACIO RESERVADO PARA INSERTAR LA IMAGEN DEL DIAGRAMA / CAPTURA]\n(Archivo fuente asociado: {puml_file or 'Captura de interfaz real'})\n")
    r_box.font.italic = True
    r_box.font.color.rgb = RGBColor(110, 110, 110)
    
    doc.add_paragraph("Fuente: Elaboración propia.", style="List Paragraph")
    doc.add_paragraph("", style="Normal")

import re

def clean_xml_str(s):
    if not isinstance(s, str):
        return str(s)
    # Remove XML-incompatible control characters except tab (\x09), newline (\x0A), CR (\x0D)
    return re.sub(r'[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]', '', s)

def add_code_item(doc, title, location, tech, relation, code, explanation):
    doc.add_paragraph(clean_xml_str(title), style="Heading 4")
    
    p1 = doc.add_paragraph(style="List Paragraph")
    p1.add_run("Dónde: ").bold = True
    p1.add_run(clean_xml_str(location))
    
    p2 = doc.add_paragraph(style="List Paragraph")
    p2.add_run("Cómo: ").bold = True
    p2.add_run(clean_xml_str(tech))
    
    p3 = doc.add_paragraph(style="List Paragraph")
    p3.add_run("Para qué: ").bold = True
    p3.add_run(clean_xml_str(relation))
    
    table = doc.add_table(rows=1, cols=1)
    apply_table_styles(table, [Inches(6.5)])
    cell = table.rows[0].cells[0]
    cell.text = clean_xml_str(code.strip())
    set_cell_shd(cell, "F8F9FA")
    for p in cell.paragraphs:
        for r in p.runs:
            r.font.name = "Consolas"
            r.font.size = Pt(8.5)
            r.font.color.rgb = RGBColor(30, 30, 30)
            
    p4 = doc.add_paragraph(style="Normal")
    p4.add_run("Explicación: ").bold = True
    p4.add_run(clean_xml_str(explanation))
    doc.add_paragraph("", style="Normal")

def add_tc_table(doc, tc_code, tc_title, priority, pre, desc, steps, expected):
    table = doc.add_table(rows=7, cols=3)
    apply_table_styles(table, [Inches(1.5), Inches(3.8), Inches(1.2)])
    
    r0 = table.rows[0]
    r0.cells[0].text = "Título"
    r0.cells[1].text = clean_xml_str(tc_title)
    r0.cells[2].text = clean_xml_str(tc_code)
    set_cell_shd(r0.cells[0], "F2F4F7")
    set_cell_shd(r0.cells[2], "EBF3FC")
    for c in [r0.cells[0], r0.cells[2]]:
        for p in c.paragraphs:
            for r in p.runs:
                r.bold = True
                
    def fill_m_row(idx, label, val):
        r = table.rows[idx]
        r.cells[0].text = label
        set_cell_shd(r.cells[0], "F2F4F7")
        for p in r.cells[0].paragraphs:
            for run in p.runs:
                run.bold = True
        r.cells[1].merge(r.cells[2])
        r.cells[1].text = clean_xml_str(val)
        
    fill_m_row(1, "Prioridad", priority)
    fill_m_row(2, "Precondición", pre)
    fill_m_row(3, "Descripción", desc)
    fill_m_row(4, "Pasos", steps)
    fill_m_row(5, "Resultado esperado", expected)
    
    r6 = table.rows[6]
    r6.cells[0].merge(r6.cells[1]).merge(r6.cells[2])
    r6.cells[0].text = "Resultado obtenido\n\n[ESPACIO RESERVADO PARA ADJUNTAR CAPTURA DE RESULTADO OBTENIDO]"
    set_cell_shd(r6.cells[0], "FAFAFA")
    r6.cells[0].paragraphs[0].runs[0].bold = True
    
    doc.add_paragraph("", style="Normal")

print("Base builder framework loaded.")
