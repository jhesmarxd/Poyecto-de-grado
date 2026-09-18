from __future__ import annotations

from datetime import date
from pathlib import Path
from typing import Iterable, Sequence

from docx import Document
from docx.enum.section import WD_ORIENT, WD_SECTION
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "docs" / "Proyecto_de_Grado_Jhesmar_Fuentes_Continuacion_Capitulo_III_4_Sprints.docx"
DIAGRAMS = ROOT / "docs" / "diagramas" / "lucid" / "4_sprints"

GREEN = "006B57"
DARK = "12352F"
BLUE = "2F6FEB"
LIGHT_GREEN = "EAF7F1"
LIGHT_BLUE = "E8F1FF"
LIGHT_GRAY = "F4F6F5"
MID_GRAY = "63736F"
WHITE = "FFFFFF"


def set_cell_shading(cell, fill: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_margins(cell, top=90, start=100, bottom=90, end=100) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for name, value in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        element = tc_mar.find(qn(f"w:{name}"))
        if element is None:
            element = OxmlElement(f"w:{name}")
            tc_mar.append(element)
        element.set(qn("w:w"), str(value))
        element.set(qn("w:type"), "dxa")


def repeat_header(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    element = OxmlElement("w:tblHeader")
    element.set(qn("w:val"), "true")
    tr_pr.append(element)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = paragraph.add_run("Página ")
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
    instruction.text = 'TOC \\o "1-4" \\h \\z \\u'
    separator = OxmlElement("w:fldChar")
    separator.set(qn("w:fldCharType"), "separate")
    placeholder = OxmlElement("w:t")
    placeholder.text = "Actualice el índice en Word mediante clic derecho → Actualizar campo."
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([begin, instruction, separator, placeholder, end])


def add_hyperlink(paragraph, text: str, url: str) -> None:
    rel_id = paragraph.part.relate_to(
        url,
        "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink",
        is_external=True,
    )
    hyperlink = OxmlElement("w:hyperlink")
    hyperlink.set(qn("r:id"), rel_id)
    run = OxmlElement("w:r")
    properties = OxmlElement("w:rPr")
    color = OxmlElement("w:color")
    color.set(qn("w:val"), BLUE)
    underline = OxmlElement("w:u")
    underline.set(qn("w:val"), "single")
    properties.extend([color, underline])
    node = OxmlElement("w:t")
    node.text = text
    run.extend([properties, node])
    hyperlink.append(run)
    paragraph._p.append(hyperlink)


def setup_document() -> Document:
    doc = Document()
    section = doc.sections[0]
    section.top_margin = Cm(2.1)
    section.bottom_margin = Cm(1.8)
    section.left_margin = Cm(2.5)
    section.right_margin = Cm(2.2)

    normal = doc.styles["Normal"]
    normal.font.name = "Arial"
    normal.font.size = Pt(11)
    normal.font.color.rgb = RGBColor.from_string(DARK)
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.line_spacing = 1.15
    normal.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    heading_specs = {
        "Title": (22, DARK),
        "Heading 1": (16, GREEN),
        "Heading 2": (14, DARK),
        "Heading 3": (12, GREEN),
        "Heading 4": (11, DARK),
    }
    for name, (size, color) in heading_specs.items():
        style = doc.styles[name]
        style.font.name = "Arial"
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.color.rgb = RGBColor.from_string(color)
        style.paragraph_format.space_before = Pt(10)
        style.paragraph_format.space_after = Pt(6)
        style.paragraph_format.keep_with_next = True

    caption = doc.styles["Caption"]
    caption.font.name = "Arial"
    caption.font.size = Pt(9)
    caption.font.bold = True
    caption.font.color.rgb = RGBColor.from_string(DARK)
    caption.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    caption.paragraph_format.keep_with_next = True

    for sec in doc.sections:
        header = sec.header.paragraphs[0]
        header.text = "INSTITUTO TECNOLÓGICO SACABA  |  WALLY RESERVAS"
        header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        for run in header.runs:
            run.font.name = "Arial"
            run.font.size = Pt(8)
            run.font.bold = True
            run.font.color.rgb = RGBColor.from_string(GREEN)
        add_page_number(sec.footer.paragraphs[0])
    return doc


def add_body(doc: Document, text: str, bold_prefix: str | None = None):
    paragraph = doc.add_paragraph()
    if bold_prefix and text.startswith(bold_prefix):
        paragraph.add_run(bold_prefix).bold = True
        paragraph.add_run(text[len(bold_prefix):])
    else:
        paragraph.add_run(text)
    return paragraph


def add_bullets(doc: Document, items: Iterable[str]) -> None:
    for item in items:
        paragraph = doc.add_paragraph(item, style="List Bullet")
        paragraph.paragraph_format.space_after = Pt(3)


def add_table(
    doc: Document,
    number: int | None,
    title: str | None,
    headers: Sequence[str],
    rows: Sequence[Sequence[str]],
    widths: Sequence[float] | None = None,
):
    if number is not None and title:
        doc.add_paragraph(f"Tabla {number}. {title}", style="Caption")
    table = doc.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = "Table Grid"
    repeat_header(table.rows[0])
    for index, header in enumerate(headers):
        cell = table.rows[0].cells[index]
        set_cell_shading(cell, GREEN)
        cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        paragraph = cell.paragraphs[0]
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = paragraph.add_run(str(header))
        run.bold = True
        run.font.name = "Arial"
        run.font.size = Pt(8.5)
        run.font.color.rgb = RGBColor.from_string(WHITE)
        set_cell_margins(cell)
    for row_index, values in enumerate(rows):
        cells = table.add_row().cells
        for index, value in enumerate(values):
            cell = cells[index]
            if row_index % 2 == 0:
                set_cell_shading(cell, LIGHT_GRAY)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
            paragraph = cell.paragraphs[0]
            paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
            run = paragraph.add_run(str(value))
            run.font.name = "Arial"
            run.font.size = Pt(8.2)
            run.font.color.rgb = RGBColor.from_string(DARK)
            set_cell_margins(cell)
    if widths:
        for row in table.rows:
            for index, width in enumerate(widths):
                row.cells[index].width = Inches(width)
    if number is not None:
        note = doc.add_paragraph(
            "Fuente: Elaboración propia con base en los artefactos y el código fuente de Wally Reservas."
        )
        note.alignment = WD_ALIGN_PARAGRAPH.CENTER
        for run in note.runs:
            run.font.name = "Arial"
            run.font.size = Pt(8)
            run.font.italic = True
            run.font.color.rgb = RGBColor.from_string(MID_GRAY)
    return table


def add_figure(doc: Document, number: int, filename: str, title: str, width: float) -> None:
    path = DIAGRAMS / filename
    if not path.exists():
        raise FileNotFoundError(path)
    paragraph = doc.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    paragraph.paragraph_format.keep_with_next = True
    paragraph.add_run().add_picture(str(path), width=Inches(width))
    doc.add_paragraph(f"Figura {number}. {title}", style="Caption")
    source = doc.add_paragraph(
        "Fuente: Elaboración propia en Lucid con base en la implementación vigente de Wally Reservas."
    )
    source.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for run in source.runs:
        run.font.name = "Arial"
        run.font.size = Pt(8)
        run.font.italic = True
        run.font.color.rgb = RGBColor.from_string(MID_GRAY)


SPRINTS = [
    {
        "number": 1,
        "title": "Portal, autenticación y control de acceso",
        "goal": "Entregar una entrada pública comprensible y mecanismos de identidad seguros que limiten cada módulo según el rol y el estado de la cuenta.",
        "actors": "Visitante, cliente, administrador y Google Identity.",
        "scope": "Portada, registro, inicio de sesión local y Google, recuperación de contraseña, perfil, roles, middleware y autorización.",
        "stories": [
            ("HU-01", "Como visitante quiero conocer el servicio para decidir si deseo registrarme.", "La portada y la navegación pública son accesibles y responsivas."),
            ("HU-02", "Como visitante quiero registrarme e iniciar sesión para usar funciones privadas.", "El registro crea un cliente, valida los datos y establece una sesión segura."),
            ("HU-03", "Como usuario quiero continuar con Google para reducir fricción.", "OAuth valida state, respeta cuentas activas y vincula la identidad sin duplicarla."),
            ("HU-04", "Como usuario quiero gestionar mi perfil y recuperar mi contraseña.", "Solo el propietario actualiza sus datos y los tokens de recuperación deben ser válidos."),
        ],
        "rules": [
            "El registro público asigna exclusivamente el rol cliente.",
            "Las cuentas inactivas no pueden autenticarse ni operar sobre reservas.",
            "El inicio de sesión con Google usa un cliente OAuth separado de Calendar y un callback propio.",
            "Middleware y policies protegen las rutas de recepción y administración.",
        ],
        "evidence": "Rutas de autenticación, RegisterController, LoginController, GoogleAuthController, SocialAccount, middleware por rol, policies, vistas de acceso y AuthenticationTest/GoogleIntegrationTest.",
        "figures": [
            (9, "4s_sprint_01_casos_de_uso.png", "Casos de uso del Sprint 1.", 6.6),
            (10, "4s_sprint_01_actividad.png", "Actividad de autenticación y asignación de sesión.", 6.6),
            (11, "4s_sprint_01_secuencia.png", "Secuencia de inicio de sesión con Google.", 5.35),
        ],
        "result": "El incremento ofrece una frontera de acceso coherente: el visitante conoce el servicio, el cliente crea o recupera su cuenta y cada rol recibe únicamente las capacidades autorizadas.",
    },
    {
        "number": 2,
        "title": "Recursos, disponibilidad y reservas",
        "goal": "Publicar la oferta deportiva y completar reservas de una franja con validación transaccional, datos operativos reales y control de concurrencia.",
        "actors": "Visitante, cliente y administrador.",
        "scope": "Canchas, franjas reservables, balones, disponibilidad, cálculo de importe, creación y cancelación de reservas, integridad y manejo de conflictos.",
        "stories": [
            ("HU-05", "Como visitante quiero comparar canchas y balones.", "Solo se muestran recursos vigentes con información proveniente de la base de datos."),
            ("HU-06", "Como usuario quiero consultar disponibilidad por cancha y fecha.", "Cada franja se presenta como libre u ocupada según reservas activas."),
            ("HU-07", "Como administrador quiero gestionar canchas y franjas reservables.", "Las altas y ediciones validan datos y rechazan horarios superpuestos."),
            ("HU-08", "Como administrador quiero gestionar el catálogo de balones.", "Cada balón conserva tipo, características, precio y estado operativo."),
            ("HU-09", "Como cliente quiero reservar una cancha en una franja disponible.", "La operación revalida fecha, cancha, horario y permiso dentro de la transacción."),
            ("HU-10", "Como cliente quiero elegir balón propio o un balón del catálogo.", "La selección usa inventario real y el importe se calcula en el servidor."),
            ("HU-11", "Como cliente quiero consultar y cancelar mis reservas futuras.", "Solo el propietario cancela reservas pendientes o confirmadas y autorizadas."),
            ("HU-12", "Como sistema quiero impedir reservas incompatibles simultáneas.", "Bloqueos, restricción única y respuesta HTTP 409 resuelven la carrera."),
        ],
        "rules": [
            "MySQL es la fuente de verdad para recursos, precios, disponibilidad y reservas.",
            "La disponibilidad mostrada se vuelve a comprobar al confirmar.",
            "El importe se calcula en el backend usando precios persistidos; no se aceptan valores operativos del cliente.",
            "El modelo horario_id se mantiene porque cada reserva del alcance actual corresponde a una sola franja.",
            "La cancelación cambia el estado y conserva el registro histórico.",
        ],
        "evidence": "CanchaController, DisponibilidadController/Service, ReservaController/Service, StoreReservaRequest, BalonService, transacciones, lockForUpdate, índice único y CanchasPublicasTest/HorarioManagementTest/ReservaTest/ReservaServiceTest.",
        "figures": [
            (12, "4s_sprint_02_casos_de_uso.png", "Casos de uso del Sprint 2.", 6.6),
            (13, "4s_sprint_02_actividad.png", "Actividad de consulta de disponibilidad y reserva.", 6.6),
            (14, "4s_sprint_02_secuencia.png", "Secuencia desde disponibilidad hasta creación transaccional.", 5.35),
        ],
        "result": "El incremento integra catálogo, disponibilidad y reserva en un solo flujo verificable. Las restricciones persistentes y los bloqueos evitan que dos solicitudes activas ocupen la misma franja o el mismo recurso.",
    },
    {
        "number": 3,
        "title": "Recepción y administración",
        "goal": "Digitalizar la atención presencial y concentrar el gobierno de usuarios, recursos, agenda, estados, indicadores, reportes y ajustes.",
        "actors": "Recepcionista, cliente atendido y administrador.",
        "scope": "Reservas presenciales, agenda diaria, búsqueda de clientes, check-in/out, usuarios, recursos, estados, dashboard, reportes CSV y configuración informativa/visual.",
        "stories": [
            ("HU-13", "Como recepcionista quiero buscar clientes activos.", "La búsqueda y el autocompletado limitan resultados y excluyen cuentas inactivas."),
            ("HU-14", "Como recepcionista quiero registrar una reserva presencial.", "Se aplican las mismas reglas de disponibilidad e integridad que al cliente."),
            ("HU-15", "Como recepcionista quiero consultar la agenda de hoy.", "La vista muestra estado, cancha, franja y acciones operativas permitidas."),
            ("HU-16", "Como recepcionista quiero registrar check-in y check-out.", "Solo se aceptan transiciones válidas y el check-out completa la reserva."),
            ("HU-17", "Como administrador quiero gestionar usuarios, roles y estado.", "Las acciones requieren rol admin y protegen operaciones sensibles."),
            ("HU-18", "Como administrador quiero gestionar recursos y reservas.", "CRUD, agenda y cambios de estado mantienen relaciones consistentes."),
            ("HU-19", "Como administrador quiero consultar indicadores y reportes.", "Filtros, métricas y tablas se calculan sobre las mismas consultas."),
            ("HU-20", "Como administrador quiero exportar CSV y ajustar la presentación.", "El CSV respeta filtros y los ajustes se validan antes de persistir."),
        ],
        "rules": [
            "Recepción solo opera sobre clientes activos y transiciones de estado permitidas.",
            "La reserva presencial reutiliza las reglas transaccionales del autoservicio.",
            "Las rutas administrativas requieren rol administrador y validación de servidor.",
            "Dashboard, reportes y CSV derivan de consultas consistentes sobre la base de datos.",
            "El horario general de atención es informativo y no reemplaza las franjas reservables por cancha.",
        ],
        "evidence": "Módulo Recepcionista, CheckinController, controladores Admin, EstadisticasService, ReporteService, AjusteController y RecepcionistaTest/UserManagementTest/DashboardReportesTest/AjustesPresentacionTest.",
        "figures": [
            (15, "4s_sprint_03_casos_de_uso.png", "Casos de uso del Sprint 3.", 6.6),
            (16, "4s_sprint_03_actividad.png", "Actividad de atención presencial y actualización operativa.", 6.6),
            (17, "4s_sprint_03_secuencia.png", "Secuencia de reserva presencial, check-in y check-out.", 5.35),
        ],
        "result": "El incremento cubre el ciclo diario de recepción y la supervisión administrativa. La misma reserva avanza por estados válidos y alimenta agenda, métricas y reportes sin duplicar reglas de negocio.",
    },
    {
        "number": 4,
        "title": "Chatbot, Gemini, Google Calendar y calidad",
        "goal": "Integrar asistencia conversacional y sincronización externa de forma resiliente, trazable e idempotente sin desplazar a la base de datos como fuente de verdad.",
        "actors": "Visitante, usuarios autenticados, administrador, Gemini y Google Calendar.",
        "scope": "NLP estructurado, fallback local, rate limiting, contexto autorizado, OAuth, cola, eventos idempotentes, estados de sincronización y regresión automatizada.",
        "stories": [
            ("HU-21", "Como usuario quiero preguntar por canchas, horarios, precios y reservas.", "El chatbot conserva contexto y responde únicamente con datos autorizados."),
            ("HU-22", "Como sistema quiero entender intención y entidades de manera segura.", "Gemini realiza como máximo una llamada estructurada por mensaje necesario y el JSON se valida."),
            ("HU-23", "Como administrador quiero conectar y verificar Google Calendar.", "OAuth conserva una conexión configurable sin exponer credenciales o tokens."),
            ("HU-24", "Como sistema quiero sincronizar eventos sin bloquear reservas.", "Jobs idempotentes actualizan el vínculo y registran pending, synced, cancelled o error."),
            ("HU-25", "Como equipo quiero una regresión automatizada.", "La suite cubre autenticación, permisos, reservas, recepción, chatbot e integraciones simuladas."),
        ],
        "rules": [
            "Gemini devuelve intención y entidades mediante JSON validado; no decide disponibilidad, precios ni existencia de reservas.",
            "Cada mensaje libre necesario produce como máximo una llamada NLP; confirmaciones deterministas evitan la llamada externa.",
            "El chatbot construye respuestas operativas con datos del backend y activa fallback local ante error o timeout.",
            "El rate limiting diferencia usuarios autenticados y visitantes por IP o sesión.",
            "Las reservas pendientes no generan eventos; confirmadas, canceladas y completadas sincronizan el mismo evento cuando existe.",
            "google_calendar_events conserva el vínculo; Calendar es una proyección externa reconciliable mediante cola.",
        ],
        "evidence": "ChatbotService y DTO AnalisisNlp, proveedor Gemini, detector local, conversaciones/mensajes, GoogleCalendarService/Gateway, jobs, conexiones y eventos persistidos, ChatbotTest/AnalisisNlpTest/GoogleIntegrationTest.",
        "figures": [
            (18, "4s_sprint_04_casos_de_uso.png", "Casos de uso del Sprint 4.", 6.6),
            (19, "4s_sprint_04_actividad.png", "Actividad de análisis seguro y respuesta del chatbot.", 6.6),
            (20, "4s_sprint_04_secuencia.png", "Secuencia idempotente de sincronización con Google Calendar.", 5.35),
        ],
        "result": "El incremento añade integraciones opcionales sin comprometer la reserva local. Gemini se limita al análisis lingüístico; Calendar replica el estado confirmado y conserva el mismo evento al actualizar, cancelar o completar.",
    },
]


def add_cover(doc: Document) -> None:
    for text, size, bold, color in [
        ("INSTITUTO TECNOLÓGICO SACABA", 15, True, DARK),
        ("CARRERA DE SISTEMAS INFORMÁTICOS", 13, True, GREEN),
        ("R.M. N.º 0823/2019", 11, False, MID_GRAY),
    ]:
        paragraph = doc.add_paragraph()
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = paragraph.add_run(text)
        run.font.name = "Arial"
        run.font.size = Pt(size)
        run.bold = bold
        run.font.color.rgb = RGBColor.from_string(color)
    doc.add_paragraph().paragraph_format.space_after = Pt(18)
    paragraph = doc.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run(
        "“SISTEMA WEB PARA LA OPTIMIZACIÓN DE RESERVAS Y ATENCIÓN AUTOMATIZADA EN CANCHAS DE WALLY MEDIANTE UN CHATBOT BASADO EN NLP”"
    )
    run.font.name = "Arial"
    run.font.size = Pt(16)
    run.bold = True
    run.font.color.rgb = RGBColor.from_string(DARK)
    doc.add_paragraph().paragraph_format.space_after = Pt(10)
    paragraph = doc.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run("CONTINUACIÓN DEL CAPÍTULO III\nINGENIERÍA DEL PROYECTO: DESARROLLO EN CUATRO SPRINTS")
    run.font.name = "Arial"
    run.font.size = Pt(20)
    run.bold = True
    run.font.color.rgb = RGBColor.from_string(GREEN)
    doc.add_paragraph().paragraph_format.space_after = Pt(28)
    for text in [
        "Postulante: Fuentes Heredia Jhesmar",
        "Tutor: Lic. Nardy Candelaria Miranda Fernandez",
        "Documento: Entrega complementaria del Capítulo III",
    ]:
        paragraph = doc.add_paragraph(text)
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        paragraph.runs[0].font.size = Pt(11)
    doc.add_paragraph().paragraph_format.space_after = Pt(20)
    paragraph = doc.add_paragraph("Sacaba, Cochabamba, Bolivia · 2026")
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    paragraph.runs[0].font.size = Pt(11)
    doc.add_page_break()


def add_control_and_toc(doc: Document) -> None:
    doc.add_heading("CONTROL DEL DOCUMENTO", level=1)
    add_table(
        doc,
        None,
        None,
        ["Versión", "Fecha", "Descripción", "Responsable"],
        [("2.0", date.today().strftime("%d/%m/%Y"), "Reorganización académica del desarrollo en cuatro sprints y actualización de diagramas Lucid.", "Fuentes Heredia Jhesmar")],
        [0.8, 1.1, 3.8, 1.6],
    )
    add_body(doc, "Esta versión se genera como archivo independiente. La edición previa permanece sin cambios como respaldo documental.")
    doc.add_page_break()
    doc.add_heading("ÍNDICE DE CONTENIDO", level=1)
    add_toc(doc.add_paragraph())
    add_body(doc, "Nota: Word actualizará el índice al abrir el archivo o al seleccionar Actualizar campo.")
    doc.add_page_break()


def add_planning(doc: Document) -> None:
    doc.add_heading("CAPÍTULO III: INGENIERÍA DEL PROYECTO (CONTINUACIÓN)", level=1)
    doc.add_heading("3.2 Planificación y organización incremental", level=2)
    doc.add_heading("3.2.1 Propósito de la continuación", level=3)
    add_body(doc, "Esta entrega continúa el Capítulo III después de los requerimientos, el modelo general y la arquitectura documentados en la sección 3.1. La construcción de Wally Reservas se organiza en exactamente cuatro sprints, manteniendo trazabilidad entre historias de usuario, reglas de negocio, componentes implementados, diagramas UML y pruebas automatizadas.")
    add_body(doc, "La numeración inicia en la Figura 9 y la Tabla 2 porque el documento de origen finaliza su modelado general en la Figura 8 y utiliza la Tabla 1 para identificar actores.")
    doc.add_heading("3.2.2 Criterio metodológico", level=3)
    add_body(doc, "Se utiliza Scrum de manera adaptada a un proyecto académico. La agrupación se realiza por capas de capacidad: identidad, núcleo transaccional, operación administrativa e integraciones. No se asignan fechas ni duraciones que no estén registradas en los artefactos del proyecto.")
    add_bullets(doc, [
        "Product Backlog: historias priorizadas por valor, riesgo y dependencia.",
        "Sprint Backlog: historias, tareas técnicas, pruebas y documentación seleccionadas.",
        "Incremento: funcionalidad integrada, demostrable y protegida por pruebas.",
        "Definición de terminado: autorización validada, reglas persistentes, pruebas pertinentes aprobadas y documentación actualizada.",
    ])
    doc.add_heading("3.2.3 Alcance vigente", level=3)
    add_body(doc, "El alcance comprende portal público, autenticación local y Google, perfiles, roles cliente/recepcionista/administrador, recursos deportivos, disponibilidad, reservas de una franja, recepción, administración, reportes, chatbot con Gemini y fallback local, y sincronización con Google Calendar. Quedan fuera de esta entrega los pagos en línea y las reservas de múltiples franjas o rangos variables.")
    roadmap = [(str(s["number"]), s["title"], s["goal"], s["actors"]) for s in SPRINTS]
    add_table(doc, 2, "Hoja de ruta del desarrollo por sprints", ["Sprint", "Incremento", "Objetivo", "Actores"], roadmap, [0.5, 1.7, 3.4, 1.6])


def add_sprints(doc: Document) -> None:
    doc.add_heading("3.3 Desarrollo incremental por sprints", level=2)
    add_body(doc, "Los incrementos se presentan en el orden en que habilitan capacidades posteriores. En cada sprint se muestran tres vistas complementarias: casos de uso para delimitar valor y actores, actividades con carriles para distribuir responsabilidades y secuencia para exponer la colaboración temporal.")
    for sprint in SPRINTS:
        n = sprint["number"]
        doc.add_page_break()
        doc.add_heading(f"3.3.{n} Sprint {n}: {sprint['title']}", level=3)
        doc.add_heading(f"3.3.{n}.1 Objetivo, actores y alcance", level=4)
        add_body(doc, f"Objetivo: {sprint['goal']}", "Objetivo:")
        add_body(doc, f"Actores: {sprint['actors']}", "Actores:")
        add_body(doc, f"Alcance: {sprint['scope']}", "Alcance:")
        doc.add_heading(f"3.3.{n}.2 Historias de usuario y criterios de aceptación", level=4)
        add_table(doc, 2 + n, f"Backlog seleccionado del Sprint {n}", ["ID", "Historia de usuario", "Criterio verificable"], sprint["stories"], [0.75, 3.05, 3.15])
        doc.add_heading(f"3.3.{n}.3 Reglas y evidencia de implementación", level=4)
        add_bullets(doc, sprint["rules"])
        add_body(doc, f"Evidencia técnica: {sprint['evidence']}", "Evidencia técnica:")
        doc.add_heading(f"3.3.{n}.4 Modelado UML del incremento", level=4)
        for index, figure in enumerate(sprint["figures"]):
            if index > 0:
                doc.add_page_break()
            add_figure(doc, *figure)
        doc.add_heading(f"3.3.{n}.5 Resultado del sprint", level=4)
        add_body(doc, sprint["result"])


def add_database(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("3.4 Evolución y diseño persistente de la base de datos", level=2)
    doc.add_heading("3.4.1 Criterio de persistencia", level=3)
    add_body(doc, "La base de datos continúa siendo la única fuente de verdad operativa. Gemini no recibe precios, disponibilidad, clientes ni confirmaciones; Google Calendar conserva una proyección externa reconciliable. El modelo horario_id es suficiente mientras una reserva represente una sola franja y, por tanto, no se modifica en esta entrega.")
    rows = [
        ("users / social_accounts", "Identidad local, roles y vinculación de Google.", "Email único; proveedor e identidad externa únicos."),
        ("canchas / horarios", "Oferta deportiva y franjas reservables por día.", "No se repite cancha, día y hora de inicio."),
        ("balones", "Catálogo de balones prestados o premium.", "Estado y precio consultados en servidor."),
        ("reservas", "Transacción central de cancha, fecha, franja, balón y estado.", "Única por cancha, fecha, horario y condición activa."),
        ("conversaciones / mensajes_chatbot", "Contexto y trazabilidad conversacional.", "Los mensajes dependen de una conversación."),
        ("ajustes", "Configuración clave/valor, incluido horario informativo.", "Clave única y lectura centralizada."),
        ("google_calendar_connections", "Conexión OAuth y calendario predeterminado.", "Tokens cifrados y activación controlada."),
        ("google_calendar_events", "Vínculo idempotente entre reserva y evento externo.", "Una reserva por vínculo y unicidad de calendario/evento."),
    ]
    add_table(doc, 7, "Responsabilidad de las entidades principales", ["Entidad", "Responsabilidad", "Restricción relevante"], rows, [2.0, 2.7, 2.4])
    doc.add_heading("3.4.2 Modelo entidad-relación actualizado", level=3)
    landscape = doc.add_section(WD_SECTION.NEW_PAGE)
    landscape.orientation = WD_ORIENT.LANDSCAPE
    landscape.page_width, landscape.page_height = landscape.page_height, landscape.page_width
    landscape.top_margin = Cm(1.5)
    landscape.bottom_margin = Cm(1.4)
    landscape.left_margin = Cm(1.5)
    landscape.right_margin = Cm(1.5)
    add_figure(doc, 21, "modelo_datos_erd.png", "Modelo entidad-relación actualizado de Wally Reservas.", 10.1)
    portrait = doc.add_section(WD_SECTION.NEW_PAGE)
    portrait.orientation = WD_ORIENT.PORTRAIT
    portrait.page_width, portrait.page_height = landscape.page_height, landscape.page_width
    portrait.top_margin = Cm(2.1)
    portrait.bottom_margin = Cm(1.8)
    portrait.left_margin = Cm(2.5)
    portrait.right_margin = Cm(2.2)


def add_traceability(doc: Document) -> None:
    doc.add_heading("3.5 Trazabilidad entre sprints, componentes y pruebas", level=2)
    rows = [
        ("1", "HU-01 a HU-04", "Auth controllers, middleware, policies y SocialAccount", "AuthenticationTest; GoogleIntegrationTest"),
        ("2", "HU-05 a HU-08", "Canchas, horarios, balones y disponibilidad", "CanchasPublicasTest; HorarioManagementTest; BalonesPublicosTest"),
        ("2", "HU-09 a HU-12", "ReservaController, ReservaService, requests y restricción única", "ReservaTest; ReservaServiceTest"),
        ("3", "HU-13 a HU-16", "Recepcionista, agenda y CheckinController", "RecepcionistaTest"),
        ("3", "HU-17 a HU-20", "Controladores Admin, EstadisticasService, ReporteService y AjusteController", "UserManagementTest; DashboardReportesTest; AjustesPresentacionTest"),
        ("4", "HU-21 a HU-22", "ChatbotService, AnalisisNlp, Gemini y fallback", "ChatbotTest; AnalisisNlpTest; ChatbotMotorTest"),
        ("4", "HU-23 a HU-24", "GoogleCalendarService, jobs, conexiones y eventos", "GoogleIntegrationTest"),
        ("4", "HU-25", "tests/Feature y tests/Unit", "Suite PHPUnit completa"),
    ]
    add_table(doc, 8, "Matriz de trazabilidad resumida", ["Sprint", "Historias", "Componentes principales", "Pruebas"], rows, [0.55, 1.2, 3.55, 2.1])


def add_quality_risks_conclusions(doc: Document) -> None:
    doc.add_heading("3.6 Verificación y aseguramiento de calidad", level=2)
    add_body(doc, "La suite automatizada se ejecutó nuevamente para esta edición documental. El resultado fue 180 pruebas aprobadas y 547 aserciones. Las pruebas de Gemini y Calendar utilizaron respuestas simuladas; no se efectuaron llamadas externas reales durante la validación.")
    rows = [
        ("Unidad", "Validar DTO, normalización y reglas aisladas.", "AnalisisNlpTest, ChatbotMotorTest, ReservaServiceTest y BalonServiceTest."),
        ("Feature", "Validar rutas, autenticación, autorización, vistas y persistencia.", "AuthenticationTest, ReservaTest, RecepcionistaTest y pruebas administrativas."),
        ("Integración simulada", "Comprobar HTTP, OAuth, renovación, errores e idempotencia sin servicios reales.", "ChatbotTest y GoogleIntegrationTest con respuestas controladas."),
        ("Visual / estructural", "Confirmar navegación, tema, numeración y horario informativo.", "VisualThemeTest, NavigationTest, WelcomePageTest y AjustesPresentacionTest."),
    ]
    add_table(doc, 9, "Cobertura por nivel de prueba", ["Nivel", "Propósito", "Evidencia del proyecto"], rows, [1.25, 2.8, 3.35])

    doc.add_heading("3.7 Riesgos, límites y trabajo pendiente", level=2)
    risks = [
        ("Concurrencia en una misma franja", "Sobreventa de cancha o balón.", "Transacción, lockForUpdate, restricción única y respuesta 409."),
        ("Falla o latencia de Gemini", "Análisis NLP no disponible.", "Una llamada controlada, JSON validado, timeout y fallback local."),
        ("Falla de Calendar o del worker", "Evento externo atrasado.", "Reserva local independiente, estados pending/error, reintentos y sincronización manual."),
        ("OAuth en modo de prueba", "La conexión puede requerir renovación periódica.", "Panel administrativo de verificación y reconexión controlada."),
        ("Una reserva contiene horario_id", "No representa rangos de varias franjas.", "Adecuado al alcance; tabla hija pendiente solo para la fase multifranja."),
        ("Operaciones conversacionales avanzadas", "No se reprograma una reserva completa desde el chatbot.", "Pendiente: identificación inequívoca, resumen y confirmación antes de ejecutar."),
    ]
    add_table(doc, 10, "Riesgos y tratamiento", ["Riesgo o límite", "Impacto", "Tratamiento / estado"], risks, [2.0, 2.0, 3.4])

    doc.add_heading("3.8 Conclusiones de la continuación", level=2)
    add_body(doc, "La organización en cuatro sprints cumple la normativa institucional sin cambiar el producto. La progresión conserva dependencias técnicas: identidad y autorización habilitan el acceso; recursos y disponibilidad sostienen la reserva; recepción y administración explotan los datos operativos; chatbot y Calendar se integran al final mediante límites que preservan el núcleo local.")
    add_body(doc, "Los doce diagramas de sprint presentan casos de uso, actividades con carriles y secuencias representativas. El ERD confirma que google_calendar_events mantiene la relación externa y que reservas conserva horario_id para el alcance de una sola franja.")
    add_body(doc, "La evidencia automatizada confirma el estado documentado con 180 pruebas y 547 aserciones aprobadas. La fase de múltiples franjas continúa pendiente y no debe iniciarse hasta comprobar de forma controlada las integraciones externas en el ambiente autorizado.")


def add_references_and_appendix(doc: Document) -> None:
    doc.add_heading("REFERENCIAS DEL CAPÍTULO", level=2)
    references = [
        ("R1", "Object Management Group. Unified Modeling Language (UML), versión 2.5.1.", "https://www.omg.org/spec/UML/2.5.1/PDF"),
        ("R2", "Schwaber, K. y Sutherland, J. The Scrum Guide, 2020.", "https://scrumguides.org/download"),
        ("R3", "Laravel. Documentación oficial de Laravel 12.x.", "https://laravel.com/docs/12.x"),
        ("R4", "Google AI for Developers. Structured output de Gemini.", "https://ai.google.dev/gemini-api/docs/structured-output"),
        ("R5", "Google for Developers. Google Calendar API.", "https://developers.google.com/workspace/calendar/api/v3/reference"),
        ("R6", "PHPUnit. Manual de PHPUnit 11.5.", "https://docs.phpunit.de/en/11.5/"),
    ]
    for code, title, url in references:
        paragraph = doc.add_paragraph()
        paragraph.paragraph_format.left_indent = Cm(0.6)
        paragraph.paragraph_format.first_line_indent = Cm(-0.6)
        paragraph.add_run(f"[{code}] ").bold = True
        paragraph.add_run(title + " ")
        add_hyperlink(paragraph, url, url)

    doc.add_heading("ANEXO A. DOCUMENTOS EDITABLES EN LUCID", level=2)
    links = [
        ("Casos de uso y actividades de los cuatro sprints", "https://lucid.app/lucidchart/c3abc572-6f23-4265-84bc-3e0e58f5c278/edit"),
        ("Sprint 1 — secuencia de inicio de sesión con Google", "https://lucid.app/lucidchart/0ba8c28e-460d-4d97-8411-c5797c2be14e/edit"),
        ("Sprint 2 — secuencia de disponibilidad y reserva transaccional", "https://lucid.app/lucidchart/22d8fae6-bc9e-4fe3-9f55-59de4b012831/edit"),
        ("Sprint 3 — secuencia de operación de recepción", "https://lucid.app/lucidchart/fe0a38b4-8aff-4ec8-80d3-d0250f00ede6/edit"),
        ("Sprint 4 — secuencia idempotente de Google Calendar", "https://lucid.app/lucidchart/6249fd8c-e8d8-4c2e-8001-ee834491cac2/edit"),
        ("Modelo entidad-relación actualizado", "https://lucid.app/lucidchart/ef2067e6-f692-4948-a8e9-51575249d66c/edit"),
    ]
    for title, url in links:
        paragraph = doc.add_paragraph(style="List Bullet")
        add_hyperlink(paragraph, title, url)
    add_body(doc, "Las exportaciones PNG utilizadas en este documento se encuentran en docs/diagramas/lucid/4_sprints/. Los originales anteriores se conservaron como respaldo.")


def update_fields_on_open(doc: Document) -> None:
    settings = doc.settings._element
    update = settings.find(qn("w:updateFields"))
    if update is None:
        update = OxmlElement("w:updateFields")
        settings.append(update)
    update.set(qn("w:val"), "true")


def main() -> None:
    expected = [
        *(f"4s_sprint_{number:02d}_{kind}.png" for number in range(1, 5) for kind in ("casos_de_uso", "actividad", "secuencia")),
        "modelo_datos_erd.png",
    ]
    missing = [name for name in expected if not (DIAGRAMS / name).exists()]
    if missing:
        raise RuntimeError(f"Faltan diagramas: {', '.join(missing)}")

    doc = setup_document()
    add_cover(doc)
    add_control_and_toc(doc)
    add_planning(doc)
    add_sprints(doc)
    add_database(doc)
    add_traceability(doc)
    add_quality_risks_conclusions(doc)
    add_references_and_appendix(doc)
    update_fields_on_open(doc)
    OUT.parent.mkdir(parents=True, exist_ok=True)
    doc.save(OUT)
    print(f"Documento generado: {OUT}")
    print("Sprints: 4 | Figuras: 13 | Tablas numeradas: 9")


if __name__ == "__main__":
    main()
