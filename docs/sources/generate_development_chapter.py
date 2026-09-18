from __future__ import annotations

from datetime import date
from pathlib import Path
from typing import Iterable, Sequence

from docx import Document
from docx.enum.section import WD_ORIENT
from docx.enum.style import WD_STYLE_TYPE
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_LINE_SPACING
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "docs" / "Capitulo_Desarrollo_Ingenieria_Proyecto_Wally_Reservas.docx"
DIAGRAMS = ROOT / "docs" / "uml" / "generated"

GREEN = "006B57"
DARK = "12352F"
MINT = "78F0B4"
PALE = "EAF7F1"
LIGHT_BLUE = "DDEFFC"
LIGHT_AMBER = "FFF1CF"
LIGHT_GRAY = "F4F6F5"
MID_GRAY = "63736F"
WHITE = "FFFFFF"


def shade(cell, fill: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_margins(cell, top=90, start=100, bottom=90, end=100) -> None:
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for margin, value in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        node = tc_mar.find(qn(f"w:{margin}"))
        if node is None:
            node = OxmlElement(f"w:{margin}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(value))
        node.set(qn("w:type"), "dxa")


def repeat_table_header(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement("w:tblHeader")
    tbl_header.set(qn("w:val"), "true")
    tr_pr.append(tbl_header)


def keep_with_next(paragraph) -> None:
    paragraph.paragraph_format.keep_with_next = True


def set_repeat_table_header(row) -> None:
    repeat_table_header(row)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = paragraph.add_run("Página ")
    run.font.size = Pt(9)
    fld_char1 = OxmlElement("w:fldChar")
    fld_char1.set(qn("w:fldCharType"), "begin")
    instr_text = OxmlElement("w:instrText")
    instr_text.set(qn("xml:space"), "preserve")
    instr_text.text = "PAGE"
    fld_char2 = OxmlElement("w:fldChar")
    fld_char2.set(qn("w:fldCharType"), "end")
    run._r.extend([fld_char1, instr_text, fld_char2])


def add_toc(paragraph) -> None:
    run = paragraph.add_run()
    fld_char = OxmlElement("w:fldChar")
    fld_char.set(qn("w:fldCharType"), "begin")
    instr_text = OxmlElement("w:instrText")
    instr_text.set(qn("xml:space"), "preserve")
    instr_text.text = 'TOC \\o "1-3" \\h \\z \\u'
    fld_sep = OxmlElement("w:fldChar")
    fld_sep.set(qn("w:fldCharType"), "separate")
    placeholder = OxmlElement("w:t")
    placeholder.text = "Actualice la tabla de contenido en Word (clic derecho → Actualizar campo)."
    fld_end = OxmlElement("w:fldChar")
    fld_end.set(qn("w:fldCharType"), "end")
    run._r.extend([fld_char, instr_text, fld_sep, placeholder, fld_end])


def add_link(paragraph, text: str, url: str) -> None:
    part = paragraph.part
    rel_id = part.relate_to(url, "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink", is_external=True)
    hyperlink = OxmlElement("w:hyperlink")
    hyperlink.set(qn("r:id"), rel_id)
    run = OxmlElement("w:r")
    props = OxmlElement("w:rPr")
    color = OxmlElement("w:color")
    color.set(qn("w:val"), GREEN)
    underline = OxmlElement("w:u")
    underline.set(qn("w:val"), "single")
    props.extend([color, underline])
    text_node = OxmlElement("w:t")
    text_node.text = text
    run.extend([props, text_node])
    hyperlink.append(run)
    paragraph._p.append(hyperlink)


def setup_document() -> Document:
    doc = Document()
    section = doc.sections[0]
    section.top_margin = Cm(2.0)
    section.bottom_margin = Cm(1.8)
    section.left_margin = Cm(2.4)
    section.right_margin = Cm(2.2)

    normal = doc.styles["Normal"]
    normal.font.name = "Aptos"
    normal.font.size = Pt(10.5)
    normal.font.color.rgb = RGBColor.from_string(DARK)
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.line_spacing_rule = WD_LINE_SPACING.SINGLE
    normal.paragraph_format.line_spacing = 1.12
    normal.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    for style_name, size, color in (("Title", 30, DARK), ("Heading 1", 19, GREEN), ("Heading 2", 15, DARK), ("Heading 3", 12, GREEN)):
        style = doc.styles[style_name]
        style.font.name = "Aptos Display"
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.color.rgb = RGBColor.from_string(color)
        style.paragraph_format.space_before = Pt(12)
        style.paragraph_format.space_after = Pt(7)
        style.paragraph_format.keep_with_next = True

    if "Figure Caption" not in [s.name for s in doc.styles]:
        caption_style = doc.styles.add_style("Figure Caption", WD_STYLE_TYPE.PARAGRAPH)
    else:
        caption_style = doc.styles["Figure Caption"]
    caption_style.font.name = "Aptos"
    caption_style.font.size = Pt(9)
    caption_style.font.bold = True
    caption_style.font.color.rgb = RGBColor.from_string(DARK)
    caption_style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    caption_style.paragraph_format.space_after = Pt(2)
    caption_style.paragraph_format.keep_with_next = True

    if "Source Note" not in [s.name for s in doc.styles]:
        source_style = doc.styles.add_style("Source Note", WD_STYLE_TYPE.PARAGRAPH)
    else:
        source_style = doc.styles["Source Note"]
    source_style.font.name = "Aptos"
    source_style.font.size = Pt(8)
    source_style.font.italic = True
    source_style.font.color.rgb = RGBColor.from_string(MID_GRAY)
    source_style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    source_style.paragraph_format.space_after = Pt(8)

    for sec in doc.sections:
        header = sec.header.paragraphs[0]
        header.text = "WALLY RESERVAS  |  INGENIERÍA DEL PROYECTO"
        header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        for r in header.runs:
            r.font.name = "Aptos"
            r.font.size = Pt(8)
            r.font.bold = True
            r.font.color.rgb = RGBColor.from_string(GREEN)
        add_page_number(sec.footer.paragraphs[0])
    return doc


def add_body(doc: Document, text: str, bold_prefix: str | None = None):
    p = doc.add_paragraph()
    if bold_prefix and text.startswith(bold_prefix):
        p.add_run(bold_prefix).bold = True
        p.add_run(text[len(bold_prefix):])
    else:
        p.add_run(text)
    return p


def add_bullets(doc: Document, items: Iterable[str], numbered: bool = False) -> None:
    style = "List Number" if numbered else "List Bullet"
    for item in items:
        p = doc.add_paragraph(item, style=style)
        p.paragraph_format.space_after = Pt(3)


def add_table(doc: Document, headers: Sequence[str], rows: Sequence[Sequence[str]], widths: Sequence[float] | None = None):
    table = doc.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = "Table Grid"
    hdr = table.rows[0]
    set_repeat_table_header(hdr)
    for idx, text in enumerate(headers):
        cell = hdr.cells[idx]
        shade(cell, GREEN)
        cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = p.add_run(str(text))
        run.bold = True
        run.font.color.rgb = RGBColor.from_string(WHITE)
        run.font.size = Pt(8.5)
        set_cell_margins(cell)
    for row_index, row in enumerate(rows):
        cells = table.add_row().cells
        for idx, text in enumerate(row):
            cell = cells[idx]
            if row_index % 2 == 0:
                shade(cell, LIGHT_GRAY)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            run = p.add_run(str(text))
            run.font.size = Pt(8.3)
            run.font.color.rgb = RGBColor.from_string(DARK)
            set_cell_margins(cell)
    if widths:
        for row in table.rows:
            for idx, width in enumerate(widths):
                row.cells[idx].width = Inches(width)
    doc.add_paragraph().paragraph_format.space_after = Pt(1)
    return table


figure_number = 0


def add_figure(doc: Document, filename: str, title: str, source: str = "Elaboración propia con base en el código fuente de Wally Reservas y UML 2.5.1 [R1].", page_break=False) -> None:
    global figure_number
    if page_break:
        doc.add_page_break()
    path = DIAGRAMS / filename
    if not path.exists():
        raise FileNotFoundError(path)
    figure_number += 1
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.keep_with_next = True
    p.add_run().add_picture(str(path), width=Inches(6.65))
    cap = doc.add_paragraph(f"Figura {figure_number}. {title}", style="Figure Caption")
    cap.paragraph_format.keep_with_next = True
    doc.add_paragraph(f"Fuente: {source}", style="Source Note")


def add_cover(doc: Document) -> None:
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(55)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("WALLY\nRESERVAS")
    r.bold = True
    r.font.name = "Aptos Display"
    r.font.size = Pt(34)
    r.font.color.rgb = RGBColor.from_string(GREEN)

    line = doc.add_paragraph("TU PASIÓN, SIEMPRE EN JUEGO")
    line.alignment = WD_ALIGN_PARAGRAPH.CENTER
    line.runs[0].font.size = Pt(11)
    line.runs[0].font.color.rgb = RGBColor.from_string(MID_GRAY)

    doc.add_paragraph().paragraph_format.space_after = Pt(32)
    title = doc.add_paragraph()
    title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = title.add_run("CAPÍTULO III\nDESARROLLO E INGENIERÍA DEL PROYECTO")
    run.bold = True
    run.font.name = "Aptos Display"
    run.font.size = Pt(26)
    run.font.color.rgb = RGBColor.from_string(DARK)

    subtitle = doc.add_paragraph("Modelado UML, planificación por sprints, trazabilidad y validación técnica")
    subtitle.alignment = WD_ALIGN_PARAGRAPH.CENTER
    subtitle.runs[0].font.size = Pt(13)
    subtitle.runs[0].font.color.rgb = RGBColor.from_string(GREEN)

    doc.add_paragraph().paragraph_format.space_after = Pt(35)
    info = add_table(doc, ["Dato", "Información editable"], [
        ("Proyecto", "Sistema web Wally Reservas"),
        ("Autor/a", "[Nombre del estudiante]"),
        ("Institución", "[Nombre de la institución]"),
        ("Carrera / Asignatura", "[Carrera o asignatura]"),
        ("Tutor/a o docente", "[Nombre del tutor o docente]"),
        ("Versión", "1.0"),
        ("Fecha", date.today().strftime("%d/%m/%Y")),
    ], widths=[1.8, 4.5])
    info.rows[0].cells[0].paragraphs[0].runs[0].font.size = Pt(8.5)
    doc.add_paragraph("Documento técnico generado a partir del repositorio vigente. Los campos entre corchetes deben completarse antes de la entrega académica.").alignment = WD_ALIGN_PARAGRAPH.CENTER
    doc.add_page_break()


def add_control_and_toc(doc: Document) -> None:
    doc.add_heading("Control del documento", level=1)
    add_table(doc, ["Versión", "Fecha", "Descripción", "Responsable"], [
        ("1.0", date.today().strftime("%d/%m/%Y"), "Levantamiento técnico, modelado UML y organización por sprints.", "[Autor/a]"),
    ])
    doc.add_heading("Criterio de elaboración", level=2)
    add_body(doc, "La documentación se deriva de rutas, controladores, servicios, modelos, políticas, migraciones, vistas y pruebas automatizadas presentes en el repositorio. La distribución por sprints es una reconstrucción documental coherente con las dependencias del producto; no afirma fechas históricas que el repositorio no registra.")
    add_body(doc, "Se distingue expresamente entre funcionalidad implementada, integraciones opcionales y elementos fuera del alcance actual. No se consideran implementados los pagos en línea, el rol gerente ni una arquitectura multiagente.")
    doc.add_heading("Tabla de contenido", level=1)
    p = doc.add_paragraph()
    add_toc(p)
    doc.add_page_break()


def add_introduction(doc: Document) -> None:
    doc.add_heading("1. Introducción al desarrollo", level=1)
    add_body(doc, "Wally Reservas es una aplicación web para consultar disponibilidad y gestionar reservas de canchas deportivas. El sistema articula una experiencia pública, autoservicio para clientes, operación presencial para recepción y administración integral de usuarios, recursos, agenda, reportes e integraciones.")
    add_body(doc, "Este capítulo presenta la ingeniería aplicada al producto mediante una adaptación de Scrum, requisitos funcionales, actores, casos de uso y diagramas UML. Los diagramas usan la notación conceptual definida por OMG UML 2.5.1 [R1]; la distribución iterativa toma como referencia la Guía Scrum oficial [R2].")

    doc.add_heading("1.1 Propósito", level=2)
    add_bullets(doc, [
        "Documentar el comportamiento observable del sistema y sus límites.",
        "Relacionar necesidades de negocio con componentes técnicos y pruebas.",
        "Explicar la evolución lógica del producto en seis sprints incrementales.",
        "Entregar evidencia visual reutilizable en memoria, defensa o mantenimiento.",
    ])
    doc.add_heading("1.2 Alcance vigente", level=2)
    add_table(doc, ["Clasificación", "Contenido"], [
        ("Implementado", "Portal público, disponibilidad, autenticación local y Google, perfiles, roles cliente/recepcionista/administrador, reservas, recepción, administración, dashboard, calendario, reportes CSV, ajustes visuales y chatbot."),
        ("Integración configurable", "Google Calendar mediante OAuth, trabajos en cola y persistencia del estado de sincronización; proveedor externo de IA con motor NLP local de respaldo."),
        ("Fuera del alcance actual", "Cobros en línea, conciliación de pagos, rol gerente y orquestación multiagente."),
    ])

    doc.add_heading("1.3 Base tecnológica", level=2)
    add_table(doc, ["Capa", "Tecnología / decisión"], [
        ("Servidor", "PHP 8.2+ y Laravel 12; estructura MVC, servicios, Form Requests, middleware y policies [R3]."),
        ("Persistencia", "Eloquent ORM; MySQL/MariaDB en operación y SQLite en pruebas."),
        ("Interfaz", "Blade, Bootstrap, Sass y JavaScript empaquetados con Vite."),
        ("Visualización", "Chart.js, FullCalendar, DataTables y SweetAlert2."),
        ("Calidad", "PHPUnit 11 y pruebas Feature/Unit integradas con Laravel [R4][R5]."),
        ("Integraciones", "Google OAuth 2.0 [R7], Google Calendar API [R8] y proveedor IA opcional."),
    ])


def add_methodology(doc: Document) -> None:
    doc.add_heading("2. Metodología y organización del proyecto", level=1)
    add_body(doc, "Se propone una organización Scrum adaptada a un proyecto académico. Cada sprint produce un incremento demostrable y mantiene trazabilidad entre historia de usuario, caso de uso, código y prueba. En un equipo pequeño, una persona puede asumir más de una responsabilidad, pero conviene conservar la separación conceptual.")

    doc.add_heading("2.1 Roles de trabajo propuestos", level=2)
    add_table(doc, ["Rol Scrum / apoyo", "Responsabilidad aplicada a Wally Reservas"], [
        ("Product Owner", "Prioriza valor: disponibilidad confiable, reserva simple, control operativo y visibilidad administrativa. Valida criterios de aceptación."),
        ("Scrum Master", "Facilita planificación, revisión, retrospectiva y remoción de impedimentos; protege la definición de terminado."),
        ("Equipo de desarrollo", "Analiza, diseña, implementa, prueba y documenta backend, frontend, base de datos e integraciones."),
        ("Representante administrativo", "Aporta reglas sobre usuarios, canchas, horarios, balones, agenda y reportes."),
        ("Representante de recepción", "Valida atención presencial, agenda diaria, búsqueda de clientes y check-in/check-out."),
        ("Usuarios piloto", "Comprueban claridad de consulta, registro, reserva, cancelación y asistencia del chatbot."),
    ])

    doc.add_heading("2.2 Artefactos y ceremonias", level=2)
    add_table(doc, ["Elemento", "Aplicación"], [
        ("Product Backlog", "Historias priorizadas por dependencia y valor de negocio."),
        ("Sprint Backlog", "Historias, tareas técnicas, pruebas y documentación seleccionadas para el incremento."),
        ("Incremento", "Funcionalidad integrada y potencialmente desplegable al cierre del sprint."),
        ("Definición de terminado", "Código integrado, validación de permisos, pruebas relevantes aprobadas, interfaz utilizable y documentación actualizada."),
        ("Eventos", "Planificación, seguimiento breve, revisión funcional y retrospectiva con acciones de mejora."),
    ])

    doc.add_heading("2.3 Hoja de ruta por sprints", level=2)
    add_table(doc, ["Sprint", "Objetivo del incremento", "Actores principales"], [
        ("1", "Portal, autenticación, roles, perfil y acceso con Google.", "Visitante, cliente, administrador, Google Identity"),
        ("2", "Catálogo deportivo, horarios, balones y consulta de disponibilidad.", "Visitante, cliente, administrador"),
        ("3", "Reserva del cliente con integridad transaccional y cancelación.", "Cliente, administrador, cola de calendario"),
        ("4", "Operación presencial: clientes, agenda, estados, check-in y check-out.", "Recepcionista, cliente"),
        ("5", "Administración, indicadores, calendario, reportes CSV y ajustes.", "Administrador"),
        ("6", "Chatbot, Google Calendar, resiliencia, pruebas y documentación.", "Todos, Google Calendar, proveedor IA"),
    ])


def add_analysis_and_general_use_cases(doc: Document) -> None:
    doc.add_heading("3. Análisis del sistema y modelado general", level=1)
    doc.add_heading("3.1 Actores", level=2)
    add_table(doc, ["Actor", "Tipo", "Responsabilidad / interacción"], [
        ("Visitante", "Humano primario", "Explora portada, canchas, balones y disponibilidad; utiliza el chatbot; puede registrarse o iniciar sesión."),
        ("Cliente", "Humano primario", "Gestiona perfil, crea reservas, consulta sus reservas y cancela reservas futuras permitidas."),
        ("Recepcionista", "Humano primario", "Opera agenda diaria, busca clientes activos, registra reservas presenciales y ejecuta check-in/check-out."),
        ("Administrador", "Humano primario", "Gestiona usuarios, canchas, horarios, balones y reservas; consulta indicadores, reportes e integraciones."),
        ("Google Identity", "Sistema externo", "Autentica al usuario mediante OAuth 2.0 y entrega identidad autorizada."),
        ("Google Calendar", "Sistema externo", "Recibe creación, actualización o eliminación de eventos asociados a reservas."),
        ("Proveedor IA", "Sistema externo opcional", "Complementa respuestas del chatbot; la aplicación conserva un motor local de respaldo."),
    ])

    add_figure(doc, "01_casos_uso_general.png", "Diagrama general de casos de uso de Wally Reservas.")
    add_figure(doc, "02_arquitectura_logica.png", "Arquitectura lógica del sistema.", "Elaboración propia con base en la estructura Laravel del repositorio y la documentación oficial [R3].", page_break=True)
    add_figure(doc, "03_modelo_dominio.png", "Modelo de dominio principal.", page_break=True)

    doc.add_heading("3.2 Catálogo general de casos de uso", level=2)
    use_cases = [
        ("CU-01", "Explorar portal", "Visitante", "Consulta portada, canchas y balones públicos.", "Implementado"),
        ("CU-02", "Consultar disponibilidad", "Visitante/cliente", "Filtra cancha y fecha y obtiene franjas libres u ocupadas.", "Implementado"),
        ("CU-03", "Registrarse", "Visitante", "Crea una cuenta de cliente mediante validación local.", "Implementado"),
        ("CU-04", "Autenticarse", "Usuario", "Inicia sesión local o mediante Google; se verifica estado y rol.", "Implementado"),
        ("CU-05", "Gestionar perfil", "Usuario autenticado", "Actualiza datos y administra vinculación de Google.", "Implementado"),
        ("CU-06", "Crear reserva", "Cliente", "Reserva cancha, horario y modalidad de balón sin doble asignación.", "Implementado"),
        ("CU-07", "Gestionar mis reservas", "Cliente", "Consulta historial y cancela reservas futuras permitidas.", "Implementado"),
        ("CU-08", "Registrar atención presencial", "Recepcionista", "Busca cliente y crea una reserva desde recepción.", "Implementado"),
        ("CU-09", "Procesar check-in/out", "Recepcionista", "Registra llegada y salida respetando transiciones de estado.", "Implementado"),
        ("CU-10", "Administrar usuarios", "Administrador", "Crea, actualiza, activa y asigna roles según reglas.", "Implementado"),
        ("CU-11", "Administrar recursos", "Administrador", "Gestiona canchas, horarios y balones.", "Implementado"),
        ("CU-12", "Administrar reservas", "Administrador", "Consulta y modifica agenda y estados autorizados.", "Implementado"),
        ("CU-13", "Analizar operación", "Administrador", "Consulta dashboard, calendario, filtros y exportación CSV.", "Implementado"),
        ("CU-14", "Conversar con chatbot", "Cualquier usuario", "Resuelve consultas con contexto, datos internos y respaldo local.", "Implementado"),
        ("CU-15", "Sincronizar calendario", "Administrador/sistema", "Conecta Google Calendar y procesa eventos de reserva en cola.", "Configurable"),
        ("CU-16", "Configurar apariencia", "Administrador", "Actualiza parámetros visuales validados del sistema.", "Implementado"),
    ]
    add_table(doc, ["ID", "Caso de uso", "Actor", "Resumen", "Estado"], use_cases)

    doc.add_heading("3.3 Especificaciones esenciales", level=2)
    specs = [
        ("CU-02 · Consultar disponibilidad", "Cancha activa y fecha válida.", "1) Seleccionar filtros. 2) Obtener horarios activos. 3) Excluir reservas activas. 4) Mostrar disponibilidad.", "Fecha inválida, cancha inactiva o ausencia de horarios.", "La consulta refleja el estado persistido sin modificarlo."),
        ("CU-04 · Autenticarse", "Cuenta existente o autorización Google válida.", "1) Capturar credenciales. 2) Validar. 3) verificar usuario activo. 4) Crear sesión. 5) Redirigir según rol.", "Credenciales incorrectas, cuenta inactiva, OAuth cancelado o estado inválido.", "Sesión autenticada y acceso limitado por middleware/policies."),
        ("CU-06 · Crear reserva", "Cliente autenticado; cancha/horario activos; fecha futura permitida.", "1) Elegir recursos. 2) validar. 3) iniciar transacción. 4) bloquear y comprobar. 5) guardar. 6) confirmar.", "Conflicto concurrente, recurso no disponible, balón inválido o regla incumplida.", "Reserva persistida una sola vez; sincronización externa se agenda después del commit."),
        ("CU-09 · Check-in/out", "Recepcionista autenticado y reserva elegible del día.", "1) Abrir agenda. 2) seleccionar reserva. 3) validar transición. 4) guardar estado/hora. 5) refrescar agenda.", "Reserva cancelada, transición inválida o falta de permisos.", "Trazabilidad operativa actualizada."),
        ("CU-13 · Analizar operación", "Administrador autenticado.", "1) Definir filtros. 2) validar rango. 3) agregar datos. 4) mostrar métricas. 5) exportar si corresponde.", "Rango inválido o conjunto sin resultados.", "Consulta reproducible; el CSV representa los mismos filtros."),
        ("CU-14 · Conversar con chatbot", "Widget accesible y sesión de conversación.", "1) enviar mensaje. 2) recuperar contexto. 3) detectar intención/entidades. 4) consultar datos. 5) responder y persistir.", "Proveedor externo no disponible: se usa el motor local.", "Mensaje y respuesta quedan vinculados a la conversación."),
    ]
    add_table(doc, ["Caso", "Precondición", "Flujo principal", "Alternativas", "Postcondición"], specs)


SPRINTS = [
    {
        "n": 1,
        "title": "Portal, autenticación y control de acceso",
        "goal": "Entregar una entrada pública clara y una identidad segura que redirija a cada usuario según su rol.",
        "actors": "Visitante, cliente, administrador y Google Identity.",
        "stories": [
            ("HU-01", "Como visitante quiero conocer el servicio para decidir si deseo registrarme.", "La portada y navegación pública son accesibles y responsivas."),
            ("HU-02", "Como visitante quiero registrarme e iniciar sesión para usar funciones privadas.", "Validación, errores claros y sesión segura."),
            ("HU-03", "Como usuario quiero continuar con Google para reducir fricción.", "OAuth valida estado, vincula identidad y respeta cuentas activas."),
            ("HU-04", "Como usuario quiero administrar mi perfil y recuperar mi contraseña.", "Solo el propietario actualiza sus datos; recuperación usa tokens válidos."),
        ],
        "rules": ["El registro público crea clientes; no concede roles elevados.", "Una cuenta inactiva no puede iniciar sesión.", "El middleware y las policies limitan rutas por rol.", "La identidad social se vincula sin duplicar usuarios de forma arbitraria."],
        "evidence": "LoginController, RegisterController, GoogleAuthController, ProfileController, middleware de rol, SocialAccount, vistas auth/profile y AuthenticationTest.",
        "figures": [("04_s1_casos_uso.png", "Casos de uso del Sprint 1."), ("05_s1_actividad_autenticacion.png", "Actividad de autenticación."), ("06_s1_secuencia_google.png", "Secuencia de inicio de sesión con Google.")],
    },
    {
        "n": 2,
        "title": "Recursos deportivos y disponibilidad",
        "goal": "Publicar la oferta deportiva y construir una fuente confiable de horarios disponibles.",
        "actors": "Visitante, cliente y administrador.",
        "stories": [
            ("HU-05", "Como visitante quiero ver canchas y detalles para comparar opciones.", "Solo se publican recursos habilitados y su información vigente."),
            ("HU-06", "Como usuario quiero consultar disponibilidad por fecha y cancha.", "Cada horario aparece como libre u ocupado según reservas activas."),
            ("HU-07", "Como administrador quiero gestionar canchas y horarios.", "Altas/ediciones validan datos y evitan superposición de horarios."),
            ("HU-08", "Como administrador quiero gestionar balones por tipo y estado.", "El inventario diferencia opciones económicas, premium y no disponibles."),
        ],
        "rules": ["La disponibilidad se calcula con horarios y reservas persistidas.", "Los horarios de una misma cancha no deben superponerse.", "Los recursos inactivos no se ofrecen para nuevas operaciones.", "Los precios se obtienen de la configuración del recurso, no del navegador."],
        "evidence": "CanchaController, DisponibilidadController/Service, HorarioController, BalonController/Service, regla SinSuperposicionHorario y pruebas públicas/administrativas.",
        "figures": [("07_s2_casos_uso.png", "Casos de uso del Sprint 2."), ("08_s2_actividad_disponibilidad.png", "Actividad de consulta de disponibilidad."), ("09_s2_secuencia_disponibilidad.png", "Secuencia de consulta de disponibilidad.")],
    },
    {
        "n": 3,
        "title": "Reservas del cliente e integridad transaccional",
        "goal": "Permitir reservas de autoservicio evitando sobreventa de cancha, horario o balón ante solicitudes simultáneas.",
        "actors": "Cliente, administrador y subsistema de colas.",
        "stories": [
            ("HU-09", "Como cliente quiero reservar una cancha en una franja disponible.", "La operación valida fecha, recursos, permiso y disponibilidad al confirmar."),
            ("HU-10", "Como cliente quiero elegir balón propio, económico o premium.", "La selección cambia disponibilidad/costo y no duplica asignaciones."),
            ("HU-11", "Como cliente quiero consultar y cancelar mis reservas futuras.", "Solo el propietario cancela estados y fechas permitidos."),
            ("HU-12", "Como sistema quiero impedir reservas simultáneas incompatibles.", "Transacción, bloqueo y restricción única resuelven carreras; conflicto responde 409."),
        ],
        "rules": ["La disponibilidad mostrada debe revalidarse dentro de la transacción.", "Solo cuentan como ocupantes los estados activos definidos por negocio.", "La cancelación conserva el registro histórico.", "Los efectos externos se despachan después de confirmar la transacción."],
        "evidence": "ReservaController/Service, StoreReservaRequest, ReservaPolicy, modelo Reserva, índice único de reservas activas, jobs de calendario y ReservaTest/ReservaServiceTest.",
        "figures": [("10_s3_casos_uso.png", "Casos de uso del Sprint 3."), ("11_s3_actividad_reserva.png", "Actividad de creación segura de reserva."), ("12_s3_secuencia_reserva.png", "Secuencia transaccional de reserva.")],
    },
    {
        "n": 4,
        "title": "Operación de recepción",
        "goal": "Digitalizar la atención presencial y el ciclo operativo de la reserva durante el día.",
        "actors": "Recepcionista y cliente.",
        "stories": [
            ("HU-13", "Como recepcionista quiero buscar clientes activos para atenderlos rápidamente.", "La búsqueda/autocompletado limita resultados y excluye cuentas inválidas."),
            ("HU-14", "Como recepcionista quiero registrar una reserva presencial.", "Se aplican las mismas reglas de disponibilidad e integridad."),
            ("HU-15", "Como recepcionista quiero consultar la agenda de hoy.", "Se visualizan reservas y estados operativos relevantes."),
            ("HU-16", "Como recepcionista quiero registrar check-in y check-out.", "Solo se permiten transiciones válidas y autorizadas."),
        ],
        "rules": ["Recepción no obtiene privilegios administrativos generales.", "La reserva presencial requiere un cliente válido.", "Check-in/check-out se limita a reservas elegibles.", "Todas las transiciones se validan en servidor."],
        "evidence": "Controladores Recepcionista, StoreRecepcionistaReservaRequest, RecepcionistaPolicy, CheckinController, vistas de agenda/clientes y RecepcionistaTest.",
        "figures": [("13_s4_casos_uso.png", "Casos de uso del Sprint 4."), ("14_s4_actividad_atencion.png", "Actividad de atención presencial."), ("15_s4_secuencia_checkin.png", "Secuencia de check-in y check-out.")],
    },
    {
        "n": 5,
        "title": "Administración, análisis y configuración",
        "goal": "Concentrar el gobierno de datos operativos y ofrecer información útil para la toma de decisiones.",
        "actors": "Administrador.",
        "stories": [
            ("HU-17", "Como administrador quiero gestionar usuarios, roles y estado.", "Las acciones respetan validación y protección de privilegios."),
            ("HU-18", "Como administrador quiero gestionar recursos y reservas.", "CRUD, calendario y cambios de estado mantienen consistencia."),
            ("HU-19", "Como administrador quiero consultar indicadores y reportes.", "Filtros, métricas y tablas usan la misma fuente de datos."),
            ("HU-20", "Como administrador quiero exportar resultados y personalizar colores.", "CSV respeta filtros; colores cumplen formato permitido."),
        ],
        "rules": ["Las rutas administrativas requieren rol admin.", "La lógica de agregación se centraliza en servicios.", "La exportación no altera los datos consultados.", "Los ajustes visuales son validados antes de persistir."],
        "evidence": "Controladores Admin, EstadisticasService, ReporteService, vistas dashboard/calendario/reportes/ajustes y DashboardReportesTest/UserManagementTest/VisualThemeTest.",
        "figures": [("16_s5_casos_uso.png", "Casos de uso del Sprint 5."), ("17_s5_actividad_reporte.png", "Actividad de generación de reportes."), ("18_s5_secuencia_dashboard.png", "Secuencia de dashboard y reportes.")],
    },
    {
        "n": 6,
        "title": "Asistencia inteligente, integraciones y calidad",
        "goal": "Extender el producto con asistencia conversacional e integración de calendario sin comprometer la continuidad del núcleo transaccional.",
        "actors": "Visitante, usuarios autenticados, administrador, Google Calendar y proveedor IA opcional.",
        "stories": [
            ("HU-21", "Como usuario quiero preguntar por canchas, horarios y reservas.", "El chatbot conserva contexto y responde con datos autorizados."),
            ("HU-22", "Como administrador quiero revisar conversaciones para mejorar atención.", "El historial está protegido y conserva mensajes relacionados."),
            ("HU-23", "Como administrador quiero conectar Google Calendar.", "OAuth guarda tokens protegidos y permite desconexión."),
            ("HU-24", "Como sistema quiero sincronizar eventos sin bloquear reservas.", "Jobs idempotentes registran éxito/error y se ejecutan tras commit."),
            ("HU-25", "Como equipo quiero una regresión automatizada.", "La suite cubre autenticación, permisos, reservas, recepción, panel e integraciones."),
        ],
        "rules": ["El chatbot nunca debe exponer información privada sin autorización.", "Si el proveedor IA falla, el motor NLP local mantiene una respuesta útil.", "La sincronización externa no define el éxito de la reserva local.", "Tokens y secretos no se incluyen en el código ni en el documento."],
        "evidence": "ChatbotService y subservicios, modelos de conversación, GoogleCalendarService/Gateway, jobs, conexiones/eventos persistidos y ChatbotTest/ChatbotMotorTest/GoogleIntegrationTest.",
        "figures": [("19_s6_casos_uso.png", "Casos de uso del Sprint 6."), ("20_s6_actividad_chatbot.png", "Actividad de respuesta del chatbot."), ("21_s6_secuencia_calendar.png", "Secuencia de sincronización con Google Calendar.")],
    },
]


def add_sprints(doc: Document) -> None:
    doc.add_heading("4. Desarrollo incremental por sprints", level=1)
    add_body(doc, "La secuencia siguiente respeta dependencias: primero identidad y acceso; luego recursos y disponibilidad; después la transacción de reserva; a continuación la operación presencial; posteriormente la analítica administrativa; finalmente las integraciones y el endurecimiento de calidad.")

    for sprint in SPRINTS:
        doc.add_page_break()
        n = sprint["n"]
        doc.add_heading(f"4.{n} Sprint {n}: {sprint['title']}", level=2)
        add_body(doc, f"Objetivo del sprint: {sprint['goal']}", bold_prefix="Objetivo del sprint:")
        add_body(doc, f"Actores involucrados: {sprint['actors']}", bold_prefix="Actores involucrados:")
        doc.add_heading(f"4.{n}.1 Historias y criterios de aceptación", level=3)
        add_table(doc, ["ID", "Historia de usuario", "Criterio verificable"], sprint["stories"])
        doc.add_heading(f"4.{n}.2 Reglas y decisiones", level=3)
        add_bullets(doc, sprint["rules"])
        doc.add_heading(f"4.{n}.3 Evidencia de implementación", level=3)
        add_body(doc, sprint["evidence"])
        doc.add_heading(f"4.{n}.4 Modelos UML del incremento", level=3)
        for idx, (filename, title) in enumerate(sprint["figures"]):
            add_figure(doc, filename, title, page_break=idx > 0)
        doc.add_heading(f"4.{n}.5 Resultado del sprint", level=3)
        add_body(doc, f"El incremento del Sprint {n} integra las historias anteriores dentro del producto común. La revisión se centra en los criterios observables y la retrospectiva debe registrar mejoras técnicas o de proceso para el siguiente ciclo.")


def add_traceability(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("5. Trazabilidad de requisitos, diseño y pruebas", level=1)
    add_body(doc, "La matriz vincula cada necesidad con su caso de uso, implementación principal y evidencia automatizada. Una fila indica cobertura técnica, no sustituye la validación de aceptación con usuarios.")
    rows = [
        ("S1", "HU-01 / CU-01", "InicioController; welcome.blade.php; layout público", "WelcomePageTest; NavigationTest"),
        ("S1", "HU-02 / CU-03-04", "RegisterController; LoginController; middleware auth", "AuthenticationTest"),
        ("S1", "HU-03 / CU-04", "GoogleAuthController; SocialAccount", "GoogleIntegrationTest"),
        ("S1", "HU-04 / CU-05", "ProfileController; vistas profile/password", "AuthenticationTest"),
        ("S2", "HU-05 / CU-01", "CanchaController público; vistas canchas", "CanchasPublicasTest"),
        ("S2", "HU-06 / CU-02", "DisponibilidadController/Service", "ReservaTest; NavigationTest"),
        ("S2", "HU-07 / CU-11", "Admin Cancha/HorarioController; regla de superposición", "HorarioManagementTest"),
        ("S2", "HU-08 / CU-11", "Admin BalonController; BalonService", "BalonesPublicosTest; BalonServiceTest"),
        ("S3", "HU-09-10 / CU-06", "ReservaController/Service; StoreReservaRequest", "ReservaTest; ReservaServiceTest"),
        ("S3", "HU-11 / CU-07", "ReservaPolicy; cancelación del cliente", "ReservaTest"),
        ("S3", "HU-12 / CU-06", "Transacción, lockForUpdate, índice único activo", "ReservaTest; ReservaServiceTest"),
        ("S4", "HU-13-16 / CU-08-09", "Módulo Recepcionista y CheckinController", "RecepcionistaTest"),
        ("S5", "HU-17 / CU-10", "Admin UserController; UsuarioPolicy", "UserManagementTest"),
        ("S5", "HU-18 / CU-11-12", "CRUD admin y calendario de reservas", "HorarioManagementTest; ReservaTest"),
        ("S5", "HU-19-20 / CU-13-16", "EstadisticasService; ReporteService; AjusteController", "DashboardReportesTest; VisualThemeTest"),
        ("S6", "HU-21-22 / CU-14", "ChatbotService; DetectorIntencion; conversaciones", "ChatbotTest; ChatbotMotorTest"),
        ("S6", "HU-23-24 / CU-15", "GoogleCalendarService; jobs; gateway", "GoogleIntegrationTest"),
        ("S6", "HU-25", "tests/Feature y tests/Unit", "Suite PHPUnit completa"),
    ]
    add_table(doc, ["Sprint", "Historia / caso", "Componentes principales", "Pruebas relacionadas"], rows)

    doc.add_heading("5.1 Reglas de negocio transversales", level=2)
    rules = [
        ("RN-01", "Acceso mínimo", "Cada rol solo accede a operaciones autorizadas por middleware y policy."),
        ("RN-02", "Usuario activo", "Una cuenta desactivada no inicia sesión ni se selecciona para una nueva atención."),
        ("RN-03", "Disponibilidad vigente", "La disponibilidad se vuelve a validar al confirmar una reserva."),
        ("RN-04", "No duplicidad", "Una cancha/horario/fecha activa no admite dos reservas; el balón tampoco se duplica cuando aplica."),
        ("RN-05", "Integridad de precio", "Los importes se calculan en servidor a partir de recursos y modalidad."),
        ("RN-06", "Propiedad", "El cliente solo consulta o cancela sus propias reservas."),
        ("RN-07", "Histórico", "La cancelación cambia estado; no elimina el registro de negocio."),
        ("RN-08", "Integración no bloqueante", "Calendar se sincroniza después del commit y registra su estado por separado."),
        ("RN-09", "Resiliencia conversacional", "El motor local permite responder cuando el proveedor externo no está disponible."),
        ("RN-10", "Secretos", "Credenciales, tokens y claves se administran mediante configuración segura y cifrado."),
    ]
    add_table(doc, ["ID", "Regla", "Descripción"], rules)


def add_quality(doc: Document) -> None:
    doc.add_heading("6. Estrategia de pruebas y aseguramiento de calidad", level=1)
    add_body(doc, "La estrategia combina pruebas de unidad para reglas aisladas y pruebas Feature para rutas, autenticación, autorización, persistencia e integración entre capas. Laravel ofrece utilidades específicas para pruebas HTTP y base de datos [R4][R5], mientras que PHPUnit proporciona el ejecutor y las aserciones [R6].")
    add_table(doc, ["Nivel", "Objetivo", "Ejemplos del proyecto"], [
        ("Unidad", "Validar servicios y motor de reglas sin recorrer toda la interfaz.", "ReservaServiceTest, BalonServiceTest y ChatbotMotorTest."),
        ("Feature", "Validar comportamiento de usuario, middleware, rutas, vistas y base de datos.", "AuthenticationTest, ReservaTest, RecepcionistaTest y UserManagementTest."),
        ("Integración", "Comprobar adaptadores y escenarios de servicios externos sin depender de producción.", "GoogleIntegrationTest y pruebas del proveedor/fallback del chatbot."),
        ("Regresión visual/estructura", "Confirmar navegación, tema, portada y elementos públicos.", "NavigationTest, VisualThemeTest y WelcomePageTest."),
    ])
    doc.add_heading("6.1 Evidencia de ejecución", level=2)
    add_body(doc, "En la ejecución realizada para este documento se ejecutaron 145 pruebas: 143 aprobaron y 2 fallaron, con 413 aserciones. Las dos fallas pertenecen a VisualThemeTest y no afectan reglas de reserva: las expectativas todavía buscan el texto «Entrar a la cancha» y un orden anterior del bloque de Google, mientras la vista de login contiene textos/orden distintos. Este hallazgo se registra como deuda de alineación entre interfaz y prueba; antes de la entrega debe decidirse el copy definitivo, actualizar la vista o la expectativa y repetir php artisan test.")
    add_heading = doc.add_heading
    add_heading("6.2 Criterios no funcionales", level=2)
    add_table(doc, ["Atributo", "Decisión / mecanismo"], [
        ("Seguridad", "CSRF, validación de servidor, autenticación, middleware por rol, policies y protección de secretos."),
        ("Consistencia", "Transacciones, bloqueos pesimistas, restricción única e idempotencia de sincronización."),
        ("Disponibilidad", "Las integraciones externas no bloquean el núcleo; chatbot con fallback local."),
        ("Mantenibilidad", "Separación en controladores, requests, servicios, modelos, contracts y jobs."),
        ("Usabilidad", "Diseño responsivo, navegación específica por rol, estados claros y formularios con mensajes."),
        ("Observabilidad", "Estado persistido de eventos de calendario e historial de conversaciones."),
    ])


def add_risks(doc: Document) -> None:
    doc.add_heading("7. Riesgos técnicos y tratamiento", level=1)
    add_table(doc, ["Riesgo", "Impacto", "Tratamiento incorporado / recomendado"], [
        ("Dos usuarios reservan el mismo turno", "Sobreventa y conflicto operativo", "Revalidación dentro de transacción, lockForUpdate, índice único activo y respuesta HTTP 409."),
        ("Caída o latencia de Google Calendar", "Evento externo desactualizado", "Job en cola, reintentos controlados, estado de sincronización y reserva local independiente [R9]."),
        ("Proveedor IA no disponible", "Chatbot sin respuesta", "Detección de intención y respuesta local como fallback."),
        ("Escalada de privilegios", "Acceso a información o acciones restringidas", "Registro público con rol cliente, middleware y policies en servidor."),
        ("Datos administrativos inconsistentes", "Disponibilidad o reportes erróneos", "Form Requests, servicios de dominio y pruebas de gestión."),
        ("Exposición de tokens", "Compromiso de cuentas externas", "Variables de entorno, cifrado en persistencia, revocación/desconexión y no documentar secretos."),
        ("Documentación diverge del código", "Decisiones incorrectas", "Matriz de trazabilidad, revisión por sprint y regeneración de diagramas desde fuentes controladas."),
    ])


def add_conclusions(doc: Document) -> None:
    doc.add_heading("8. Conclusiones del capítulo", level=1)
    add_body(doc, "La ingeniería de Wally Reservas se apoya en una progresión coherente: identidad y catálogo habilitan la reserva; la reserva segura habilita la operación de recepción; los datos operativos alimentan administración y análisis; las integraciones se incorporan al final mediante fronteras que preservan el núcleo local.")
    add_body(doc, "El modelado UML permite observar el sistema desde tres perspectivas complementarias: los casos de uso delimitan valor y actores; las actividades describen decisiones y responsabilidades; las secuencias muestran colaboración temporal entre interfaz, aplicación, persistencia y servicios externos. La trazabilidad resultante facilita mantenimiento, pruebas y defensa académica.")
    add_body(doc, "Como continuidad se recomienda anexar capturas actualizadas de cada pantalla, registrar fechas reales de cada sprint, completar responsables institucionales y volver a ejecutar la suite en el ambiente de entrega. Cualquier extensión de pagos o nuevos roles debe tratarse como un épico independiente y no confundirse con el alcance implementado.")


def add_references(doc: Document) -> None:
    doc.add_heading("9. Referencias", level=1)
    refs = [
        ("R1", "Object Management Group. Unified Modeling Language (UML), versión 2.5.1.", "https://www.omg.org/spec/UML/2.5.1/PDF"),
        ("R2", "Schwaber, K. y Sutherland, J. The Scrum Guide, noviembre de 2020.", "https://scrumguides.org/download"),
        ("R3", "Laravel. Documentación oficial de Laravel 12.x.", "https://laravel.com/docs/12.x"),
        ("R4", "Laravel. Testing 12.x.", "https://laravel.com/docs/12.x/testing"),
        ("R5", "Laravel. Database Testing 12.x.", "https://laravel.com/docs/12.x/database-testing"),
        ("R6", "PHPUnit. Manual de PHPUnit 11.5.", "https://docs.phpunit.de/en/11.5/"),
        ("R7", "Google for Developers. OAuth 2.0 for Web Server Applications.", "https://developers.google.com/identity/protocols/oauth2/web-server"),
        ("R8", "Google for Developers. Google Calendar API overview.", "https://developers.google.com/workspace/calendar/api/guides/overview"),
        ("R9", "Laravel. Queues y despacho después de transacciones.", "https://laravel.com/docs/12.x/queues"),
    ]
    for code, title, url in refs:
        p = doc.add_paragraph()
        p.paragraph_format.left_indent = Cm(0.6)
        p.paragraph_format.first_line_indent = Cm(-0.6)
        p.add_run(f"[{code}] ").bold = True
        p.add_run(title + " ")
        add_link(p, url, url)

    doc.add_heading("9.1 Fuentes internas del proyecto", level=2)
    internal = [
        ("I1", "README.md", "Descripción del producto, instalación y roles."),
        ("I2", "routes/*.php", "Frontera HTTP, nombres de rutas y middleware."),
        ("I3", "app/Http/Controllers y app/Http/Requests", "Casos de aplicación y validación de entrada."),
        ("I4", "app/Services, app/Policies y app/Jobs", "Reglas, autorización, integraciones y asincronía."),
        ("I5", "app/Models y database/migrations", "Modelo de dominio y restricciones persistentes."),
        ("I6", "resources/views", "Interfaz pública, cliente, recepción y administración."),
        ("I7", "tests/Feature y tests/Unit", "Evidencia automatizada y escenarios de regresión."),
        ("I8", "docs/requirements, docs/architecture, docs/traceability y docs/uml", "Antecedentes documentales contrastados con la implementación vigente."),
    ]
    add_table(doc, ["ID", "Fuente", "Uso en este capítulo"], internal)

    doc.add_heading("Anexo A. Convenciones de los diagramas", level=1)
    add_table(doc, ["Elemento", "Interpretación"], [
        ("Actor", "Persona o sistema externo que intercambia valor con Wally Reservas."),
        ("Óvalo", "Capacidad observable expresada como caso de uso."),
        ("Carril", "Responsable de una actividad dentro del flujo."),
        ("Flecha continua", "Mensaje, transición o dependencia principal."),
        ("Flecha discontinua", "Retorno o respuesta."),
        ("Caja de clase", "Entidad de dominio con atributos y relaciones simplificadas."),
    ])
    add_body(doc, "Nota académica: los diagramas son vistas intencionalmente simplificadas. No reemplazan el código ni pretenden mostrar cada clase auxiliar; su finalidad es comunicar responsabilidades, relaciones y decisiones relevantes.")


def set_update_fields_on_open(doc: Document) -> None:
    settings = doc.settings._element
    update = settings.find(qn("w:updateFields"))
    if update is None:
        update = OxmlElement("w:updateFields")
        settings.append(update)
    update.set(qn("w:val"), "true")


def main() -> None:
    missing = [p.name for p in sorted(DIAGRAMS.glob("*.png")) if not p.exists()]
    if missing or len(list(DIAGRAMS.glob("*.png"))) < 21:
        raise RuntimeError("Primero ejecute docs/sources/generate_uml.ps1 para generar los 21 diagramas.")

    doc = setup_document()
    add_cover(doc)
    add_control_and_toc(doc)
    add_introduction(doc)
    add_methodology(doc)
    add_analysis_and_general_use_cases(doc)
    add_sprints(doc)
    add_traceability(doc)
    add_quality(doc)
    add_risks(doc)
    add_conclusions(doc)
    add_references(doc)
    set_update_fields_on_open(doc)

    OUT.parent.mkdir(parents=True, exist_ok=True)
    doc.save(OUT)
    print(f"Documento generado: {OUT}")
    print(f"Figuras insertadas: {figure_number}")


if __name__ == "__main__":
    main()
