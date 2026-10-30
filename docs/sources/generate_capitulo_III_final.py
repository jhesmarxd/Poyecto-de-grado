from __future__ import annotations

from pathlib import Path
from typing import Sequence

from docx import Document
from docx.enum.section import WD_ORIENT
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[2]
OUTPUT = ROOT / "docs" / "Capitulo_III_Wally_Reservas_4_Sprints_Reestructurado.docx"
GENERAL_IMAGES = ROOT / "docs" / "uml" / "lucid"
SPRINT_IMAGES = ROOT / "docs" / "diagramas" / "lucid" / "4_sprints"
INK = "000000"
BLUE_GRAY = "B7C6D2"
BLUE_GRAY_LIGHT = "E5EBF0"
BLUE_GRAY_ALT = "F1F4F6"


def set_cell_shading(cell, fill: str) -> None:
    properties = cell._tc.get_or_add_tcPr()
    shading = properties.find(qn("w:shd"))
    if shading is None:
        shading = OxmlElement("w:shd")
        properties.append(shading)
    shading.set(qn("w:fill"), fill)


def set_cell_margins(cell, margin: int = 95) -> None:
    properties = cell._tc.get_or_add_tcPr()
    margins = properties.first_child_found_in("w:tcMar")
    if margins is None:
        margins = OxmlElement("w:tcMar")
        properties.append(margins)
    for side in ("top", "start", "bottom", "end"):
        node = margins.find(qn(f"w:{side}"))
        if node is None:
            node = OxmlElement(f"w:{side}")
            margins.append(node)
        node.set(qn("w:w"), str(margin))
        node.set(qn("w:type"), "dxa")


def set_repeat_table_header(row) -> None:
    properties = row._tr.get_or_add_trPr()
    header = OxmlElement("w:tblHeader")
    header.set(qn("w:val"), "true")
    properties.append(header)


def set_keep_together(row) -> None:
    properties = row._tr.get_or_add_trPr()
    properties.append(OxmlElement("w:cantSplit"))


def set_run(run, bold: bool | None = None) -> None:
    run.font.name = "Arial"
    run._element.get_or_add_rPr().rFonts.set(qn("w:eastAsia"), "Arial")
    run.font.size = Pt(11)
    run.font.color.rgb = RGBColor.from_string(INK)
    if bold is not None:
        run.bold = bold


def set_document_styles(document: Document) -> None:
    section = document.sections[0]
    section.top_margin = Cm(2.2)
    section.bottom_margin = Cm(2.0)
    section.left_margin = Cm(2.5)
    section.right_margin = Cm(2.2)

    for style_name in ("Normal", "Title", "Subtitle", "Caption", "Heading 1", "Heading 2", "Heading 3", "Heading 4", "Heading 5", "List Bullet", "List Number"):
        style = document.styles[style_name]
        style.font.name = "Arial"
        style._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
        style.font.size = Pt(11)
        style.font.color.rgb = RGBColor.from_string(INK)
        if style_name.startswith("Heading") or style_name == "Title":
            style.font.bold = True
            style.paragraph_format.keep_with_next = True
            style.paragraph_format.space_before = Pt(9)
            style.paragraph_format.space_after = Pt(4)

    normal = document.styles["Normal"]
    normal.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    normal.paragraph_format.line_spacing = 1.15
    normal.paragraph_format.space_after = Pt(5)
    caption = document.styles["Caption"]
    caption.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    caption.paragraph_format.keep_with_next = True

    header = section.header.paragraphs[0]
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = header.add_run("WALLY RESERVAS | CAPÍTULO III")
    set_run(run, True)
    footer = section.footer.paragraphs[0]
    footer.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = footer.add_run("Página ")
    set_run(run)
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instruction = OxmlElement("w:instrText")
    instruction.set(qn("xml:space"), "preserve")
    instruction.text = "PAGE"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([begin, instruction, end])


def add_toc(document: Document) -> None:
    document.add_heading("ÍNDICE DE CONTENIDO", level=1)
    paragraph = document.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
    run = paragraph.add_run()
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instruction = OxmlElement("w:instrText")
    instruction.set(qn("xml:space"), "preserve")
    instruction.text = 'TOC \\o "1-4" \\h \\z \\u'
    separator = OxmlElement("w:fldChar")
    separator.set(qn("w:fldCharType"), "separate")
    value = OxmlElement("w:t")
    value.text = "Índice automático"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([begin, instruction, separator, value, end])
    document.add_page_break()


def add_text(document: Document, text: str, bold_prefix: str | None = None):
    paragraph = document.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    if bold_prefix and text.startswith(bold_prefix):
        set_run(paragraph.add_run(bold_prefix), True)
        set_run(paragraph.add_run(text[len(bold_prefix):]))
    else:
        set_run(paragraph.add_run(text))
    return paragraph


def add_bullets(document: Document, items: Sequence[str]) -> None:
    for item in items:
        paragraph = document.add_paragraph(style="List Bullet")
        paragraph.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        set_run(paragraph.add_run(item))


def add_table(document: Document, number: int, title: str, headers: Sequence[str], rows: Sequence[Sequence[str]], widths: Sequence[float] | None = None):
    caption = document.add_paragraph(f"Tabla {number}. {title}", style="Caption")
    caption.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for run in caption.runs:
        set_run(run, True)

    table = document.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = "Table Grid"
    table.autofit = False if widths else True
    set_repeat_table_header(table.rows[0])
    set_keep_together(table.rows[0])

    for index, value in enumerate(headers):
        cell = table.rows[0].cells[index]
        set_cell_shading(cell, BLUE_GRAY)
        cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        paragraph = cell.paragraphs[0]
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        set_run(paragraph.add_run(str(value)), True)
        set_cell_margins(cell)

    for row_index, values in enumerate(rows):
        cells = table.add_row().cells
        set_keep_together(table.rows[-1])
        for index, value in enumerate(values):
            cell = cells[index]
            set_cell_shading(cell, BLUE_GRAY_LIGHT if row_index % 2 == 0 else BLUE_GRAY_ALT)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
            paragraph = cell.paragraphs[0]
            paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
            set_run(paragraph.add_run(str(value)))
            set_cell_margins(cell)

    if widths:
        for row in table.rows:
            for index, width in enumerate(widths):
                row.cells[index].width = Inches(width)
    document.add_paragraph()
    return table


def add_figure(document: Document, number: int, path: Path, title: str, width: float = 6.15) -> None:
    if not path.exists():
        raise FileNotFoundError(path)
    paragraph = document.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    paragraph.paragraph_format.keep_with_next = True
    paragraph.add_run().add_picture(str(path), width=Inches(width))
    caption = document.add_paragraph(f"Figura {number}. {title}", style="Caption")
    caption.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for run in caption.runs:
        set_run(run, True)
    source = document.add_paragraph("Fuente: Elaboración propia con base en el sistema Wally Reservas.")
    source.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for run in source.runs:
        set_run(run)


def add_screenshot_placeholder(document: Document, sprint: int, title: str) -> None:
    paragraph = document.add_paragraph()
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    paragraph.paragraph_format.space_before = Pt(8)
    paragraph.paragraph_format.space_after = Pt(8)
    run = paragraph.add_run(f"[ESPACIO RESERVADO PARA CAPTURA REAL DE INTERFAZ — SPRINT {sprint}: {title}]")
    set_run(run, True)


def add_general_sections(document: Document) -> None:
    document.add_heading("CAPÍTULO III: INGENIERÍA DEL PROYECTO", level=1)
    add_text(document, "Este capítulo presenta la ingeniería realizada para Wally Reservas, desde los requerimientos y el modelado del sistema hasta los incrementos implementados, sus interfaces y sus pruebas. La organización relaciona los objetivos aprobados en los capítulos I y II con funcionalidades verificables y distribuye el desarrollo en cuatro sprints.")
    document.add_heading("3.1 REQUERIMIENTOS GENERALES DEL SISTEMA", level=2)
    add_text(document, "Los requerimientos se derivan del problema de gestión de reservas y atención al cliente planteado en el Capítulo I. El sistema centraliza la información de usuarios, canchas, horarios y reservas; brinda acceso diferenciado a clientes, recepción y administración; y ofrece atención conversacional basada en datos del sistema.")
    document.add_heading("3.1.1 Requerimientos Funcionales Generales", level=3)
    add_bullets(document, [
        "Autenticar usuarios, gestionar perfiles y proteger las operaciones según el rol y el estado de la cuenta.",
        "Administrar canchas, horarios y balones, validando los datos antes de persistirlos.",
        "Consultar disponibilidad por cancha y fecha, registrar reservas y conservar su historial al cancelarlas.",
        "Permitir a recepción registrar reservas presenciales, consultar la agenda y controlar check-in y check-out.",
        "Permitir a administración supervisar reservas, usuarios y recursos, consultar indicadores y exportar reportes CSV.",
        "Atender consultas sobre canchas, precios, horarios y disponibilidad mediante un chatbot NLP con respuestas obtenidas del sistema.",
        "Integrar opcionalmente autenticación Google y sincronización con Google Calendar sin hacerlas requisito para reservar.",
    ])
    document.add_heading("3.1.2 Requerimientos No Funcionales Generales", level=3)
    add_bullets(document, [
        "Seguridad: contraseñas cifradas, validación del lado servidor, protección CSRF, sesiones controladas y autorización por rol.",
        "Integridad: claves foráneas, transacciones y restricciones que previenen reservas duplicadas y horarios superpuestos.",
        "Usabilidad: interfaz en español con formularios, estados y navegación diferenciados para cada tipo de usuario.",
        "Disponibilidad: las funciones centrales de reserva operan aunque los servicios externos no estén configurados.",
        "Mantenibilidad: separación entre vistas, controladores, servicios, modelos y reglas de validación.",
        "Privacidad: cada cliente consulta sus propias reservas; las respuestas del chatbot se limitan a datos autorizados.",
    ])
    document.add_heading("3.1.3 Identificación y Descripción de los Actores del Sistema", level=3)
    add_table(document, 1, "Identificación y descripción de los actores del sistema", ["Actor", "Tareas principales", "Alcance"], [
        ("Visitante", "Consultar información pública, canchas y disponibilidad; iniciar registro o autenticación.", "Acceso a funciones públicas y consultas permitidas."),
        ("Cliente", "Gestionar su perfil, realizar reservas, consultar o cancelar las propias y usar el chatbot.", "Acceso a sus datos y a las funciones públicas."),
        ("Recepcionista", "Consultar agenda, buscar clientes, registrar reservas presenciales y controlar ingreso y salida.", "Acceso a la operación diaria autorizada."),
        ("Administrador", "Gestionar usuarios, canchas, horarios, balones y reservas; consultar dashboard, reportes y ajustes.", "Acceso a las funciones administrativas."),
        ("Google Identity / Calendar", "Proveer autenticación opcional y recibir proyecciones de reservas confirmadas.", "Servicios externos opcionales; no son fuente de verdad del sistema."),
        ("Proveedor NLP", "Proponer intención y entidades de un mensaje cuando está configurado.", "No recibe autoridad para decidir disponibilidad, precios ni permisos."),
    ], [1.05, 3.35, 2.05])
    document.add_heading("3.1.4 Diagrama de Casos de Usos General", level=3)
    add_text(document, "El diagrama resume la interacción de visitantes, clientes, recepcionistas y administradores con el portal, las reservas, la atención presencial y la administración. Las integraciones externas aparecen como colaboraciones opcionales.")
    add_figure(document, 5, GENERAL_IMAGES / "caso_uso_general.png", "Diagrama general de casos de uso de Wally Reservas.")
    document.add_heading("3.1.5 Diagrama de Clases General", level=3)
    add_text(document, "El modelo de dominio representa usuarios, canchas, horarios, reservas, balones, conversaciones y mensajes. Las entidades de Google Calendar conservan por separado la asociación externa para que la base local mantenga la autoridad sobre el estado de las reservas.")
    add_figure(document, 6, GENERAL_IMAGES / "diagrama_clases_general.png", "Diagrama general de clases del dominio.")
    document.add_heading("3.1.6 Diseño de Base de Datos General", level=3)
    add_text(document, "La persistencia relacional utiliza MySQL/MariaDB mediante Eloquent y migraciones versionadas. Las claves primarias y foráneas mantienen las relaciones entre usuarios, recursos y reservas; las restricciones de unicidad refuerzan las reglas de negocio.")
    document.add_heading("3.1.6.1 Modelo Relacional", level=4)
    add_text(document, "Una reserva relaciona al usuario cliente, la cancha y la franja horaria seleccionada; puede asociarse a un balón. Las conversaciones pueden pertenecer a un usuario autenticado y contienen mensajes. Las conexiones y eventos de Calendar se almacenan en entidades separadas.")
    document.add_heading("3.1.6.2 Nivel de Normalización", level=4)
    add_text(document, "El esquema separa entidades con responsabilidades distintas y enlaza sus registros mediante claves. Los atributos de usuario, cancha, horario, reserva y conversación se mantienen en sus respectivas tablas, reduciendo duplicación y facilitando la actualización coherente de los datos. Las restricciones únicas y las claves foráneas completan los controles de integridad.")
    document.add_heading("3.1.6.3 Estructura General de la Base de Datos", level=4)
    document.add_heading("3.1.6.3.1 Tablas de origen Laravel", level=5)
    add_text(document, "La instalación conserva las tablas de autenticación y soporte del framework, entre ellas users, password_reset_tokens, sessions, cache, cache_locks, jobs y failed_jobs, conforme a las migraciones activas.")
    document.add_heading("3.1.6.3.2 Tablas de integración y autorización", level=5)
    add_text(document, "El sistema no utiliza tablas de Spatie para permisos. El rol se gestiona desde el usuario y el acceso se controla con middleware y políticas. social_accounts vincula identidades externas; google_calendar_connections y google_calendar_events conservan la conexión y la proyección de eventos.")
    document.add_heading("3.1.6.3.3 Tablas creadas para Wally Reservas", level=5)
    entities = [
        ("users", "Cuentas, datos de perfil, rol y estado."),
        ("canchas / horarios", "Recursos deportivos y franjas habilitadas por día."),
        ("balones", "Catálogo y disponibilidad de balones."),
        ("reservas", "Usuario, cancha, horario, fecha, monto, estado y observaciones."),
        ("conversaciones / mensajes_chatbot", "Contexto y mensajes intercambiados con el chatbot."),
        ("ajustes", "Parámetros de presentación y horario informativo."),
        ("chatbot_ejecuciones", "Resultado técnico y metadatos sanitizados del procesamiento NLP."),
    ]
    add_table(document, 2, "Entidades principales y responsabilidad", ["Entidad", "Responsabilidad"], entities, [2.1, 4.3])
    add_figure(document, 7, GENERAL_IMAGES / "modelo_entidad_relacion.png", "Modelo entidad-relación general.")
    document.add_heading("3.1.7 Arquitectura del Sistema", level=3)
    add_text(document, "La aplicación sigue una organización MVC de Laravel. Las solicitudes pasan desde las vistas y rutas hasta los controladores, servicios y modelos; las reglas se validan antes de acceder a la base de datos. Las llamadas a proveedores NLP o Google se encapsulan y no sustituyen la persistencia local.")
    add_figure(document, 8, GENERAL_IMAGES / "arquitectura_general.png", "Arquitectura general por capas de Wally Reservas.")
    document.add_heading("3.1.7.1 Capa de Presentación (Vista)", level=4)
    add_text(document, "Las vistas Blade presentan páginas públicas, formularios, tablas, calendarios, indicadores y componentes del chatbot. Bootstrap, JavaScript y los componentes visuales permiten operar desde navegadores web modernos.")
    document.add_heading("3.1.7.2 Capa de Lógica del Sistema (Controlador)", level=4)
    add_text(document, "Las rutas aplican middleware de autenticación y rol; los controladores validan solicitudes y coordinan los servicios de aplicación. Las policies delimitan operaciones sobre reservas y recursos según la identidad del usuario.")
    document.add_heading("3.1.7.3 Capa de Acceso a Datos (Modelo)", level=4)
    add_text(document, "Los modelos Eloquent representan usuarios, canchas, horarios, balones, reservas y conversaciones, y declaran sus relaciones. Las migraciones describen el esquema y las restricciones que garantizan la consistencia de los registros.")
    document.add_heading("3.1.7.4 Capa de Servicios Externos", level=4)
    add_text(document, "Google OAuth y Google Calendar se activan mediante configuración. El proveedor NLP es complementario: analiza el lenguaje y, ante errores o ausencia de configuración, el motor local mantiene la atención. Las operaciones de negocio y sus datos permanecen en Laravel y MySQL.")
    document.add_heading("3.1.7.5 Comunicación entre Capas", level=4)
    add_text(document, "El navegador envía una solicitud a una ruta; el middleware verifica identidad y acceso; el controlador valida y delega la operación; los servicios aplican reglas; los modelos consultan o modifican MySQL; y la respuesta regresa a la vista. Las tareas de Calendar se procesan mediante la cola después de confirmar la transacción local.")


OBJECTIVES = [
    ("OE-1", "Analizar los procesos actuales de gestión de reservas y atención al cliente en complejos deportivos de Wally para identificar las problemáticas existentes y determinar los requerimientos del sistema.", "Sprint 1", "Requerimientos generales, reglas de negocio, actores e historias vinculados a necesidades de acceso, reserva y atención."),
    ("OE-2", "Diseñar la arquitectura funcional y la interfaz de usuario del sistema web para garantizar una navegación intuitiva y eficiente.", "Sprints 1 y 3", "Arquitectura MVC, vistas Blade y navegación diferenciada por perfil; diagramas y capturas de interfaces."),
    ("OE-3", "Modelar la base de datos relacional que permita almacenar y administrar la información de usuarios, canchas, horarios y reservas.", "Sprint 2", "Modelo entidad-relación, migraciones, relaciones Eloquent, claves y restricciones de integridad."),
    ("OE-4", "Construir los módulos funcionales del sistema para la gestión de reservas, horarios, usuarios y administración de canchas deportivas.", "Sprints 1, 2 y 3", "Autenticación y usuarios; canchas, horarios y reservas; recepción, administración y reportes."),
    ("OE-5", "Integrar un chatbot basado en inteligencia artificial para automatizar la atención de consultas frecuentes relacionadas con los servicios deportivos.", "Sprint 4", "Chatbot NLP con análisis estructurado opcional, motor local de respaldo y respuestas construidas con datos reales."),
    ("OE-6", "Verificar el funcionamiento del sistema mediante pruebas funcionales que permitan comprobar el cumplimiento de los requerimientos establecidos.", "Sprint 4", "Pruebas unitarias y Feature sobre autenticación, recursos, reservas, recepción, reportes, chatbot e integraciones."),
    ("OE-7", "Elaborar un manual de usuario que facilite la utilización y administración de la plataforma.", "Sprint 4", "Guía operativa de acceso, consulta, reserva, recepción y administración incluida como apartado final del capítulo."),
]


def add_scrum_and_plan(document: Document) -> None:
    document.add_heading("3.2 APLICACIÓN DE LA METODOLOGÍA ÁGIL SCRUM", level=2)
    add_text(document, "El desarrollo se organiza en cuatro sprints, con incrementos asociados a los objetivos específicos del proyecto. Cada sprint reúne análisis, diseño, construcción de interfaces y verificación, y termina con funcionalidades integradas en el sistema.")
    document.add_heading("3.2.1 Roles en Scrum", level=3)
    add_table(document, 3, "Responsabilidades Scrum aplicadas al proyecto", ["Rol", "Responsabilidad en Wally Reservas"], [
        ("Product Owner", "Prioriza necesidades de reserva y atención y valida que el incremento responda a los objetivos del proyecto."),
        ("Scrum Master", "Facilita planificación, revisión y retrospectiva; identifica impedimentos que afectan el objetivo del sprint."),
        ("Equipo de desarrollo", "Analiza, diseña, implementa, prueba e integra las funciones del sistema y su documentación."),
        ("Usuarios representantes", "Aportan criterios para las tareas de administración, recepción y uso del portal."),
    ], [1.55, 4.9])
    document.add_heading("3.2.2 Planificación y Gestión de Sprints", level=3)
    document.add_heading("3.2.2.1 Product Backlog", level=4)
    add_table(document, 4, "Trazabilidad de objetivos específicos con los incrementos", ["ID", "Objetivo específico", "Sprint", "Evidencia de cumplimiento"], OBJECTIVES, [0.5, 2.6, 1.0, 2.3])
    document.add_heading("3.2.2.2 Planificación de Sprints", level=4)
    roadmap = [
        ("1", "Análisis, acceso y usuarios", "Caracterizar necesidades, establecer acceso seguro y administrar cuentas."),
        ("2", "Recursos, base de datos y reservas", "Modelar y operar canchas, horarios, balones, disponibilidad y reservas."),
        ("3", "Recepción y administración", "Completar la operación presencial, supervisión, interfaces e informes."),
        ("4", "Chatbot, calidad y documentación", "Integrar la atención NLP, verificar flujos y documentar el uso del sistema."),
    ]
    add_table(document, 5, "Plan de trabajo en cuatro sprints", ["Sprint", "Incremento", "Resultado"], roadmap, [0.65, 2.05, 3.7])
    add_text(document, "La definición de terminado aplicada a los incrementos comprende validación en servidor, autorización, persistencia consistente, interfaz operable, pruebas pertinentes aprobadas y documentación relacionada actualizada.")


SPRINTS = [
    {
        "title": "Análisis, acceso y gestión de usuarios",
        "objective": "Resolver las necesidades iniciales de identificación, navegación y control de acceso, y establecer requerimientos e historias que guían el sistema.",
        "backlog": [
            ("HU-01", "Como visitante quiero comprender la oferta del complejo y acceder a sus funciones públicas.", "El portal presenta información pública y navegación hacia canchas y disponibilidad."),
            ("HU-02", "Como usuario quiero registrarme, iniciar y cerrar sesión de forma segura.", "Los datos se validan; el registro público no concede privilegios administrativos."),
            ("HU-03", "Como usuario quiero recuperar mi acceso y actualizar mi perfil.", "La recuperación y la edición se validan en servidor y respetan la identidad autenticada."),
            ("HU-04", "Como administrador quiero gestionar cuentas y controlar el acceso por rol.", "Middleware y policies restringen operaciones; las cuentas inactivas no pueden operar."),
        ],
        "design": "El flujo de identidad separa rutas públicas y autenticadas. El modelo User conserva rol y estado; las vistas presentan inicio de sesión, registro y perfil. La autenticación Google funciona como integración opcional y no determina privilegios locales.",
        "interfaces": "Portal público, formulario de acceso y registro, recuperación de contraseña, perfil y pantallas administrativas de gestión de usuarios.",
        "tests": "AuthenticationTest y UserManagementTest verifican registro, acceso válido/inválido, cuenta inactiva, redirección por rol y operaciones de usuarios.",
        "result": "Quedaron establecidos los actores, reglas y requisitos de acceso que habilitan el uso controlado de los módulos posteriores.",
        "screen": "Acceso, registro y administración de usuarios",
        "figures": [(9, "4s_sprint_01_casos_de_uso.png", "Casos de uso del Sprint 1."), (10, "4s_sprint_01_actividad.png", "Actividad de autenticación y asignación de sesión."), (11, "4s_sprint_01_secuencia.png", "Secuencia de autenticación con Google como opción externa.")],
    },
    {
        "title": "Recursos deportivos, base de datos y reservas",
        "objective": "Modelar los datos del negocio y permitir la gestión de canchas, horarios y balones, la consulta de disponibilidad y la reserva de una franja con integridad transaccional.",
        "backlog": [
            ("HU-05", "Como visitante quiero consultar las canchas y balones disponibles.", "El catálogo muestra recursos activos con información procedente de la base de datos."),
            ("HU-06", "Como usuario quiero consultar disponibilidad por cancha y fecha.", "Las franjas se contrastan con los horarios y las reservas activas."),
            ("HU-07", "Como administrador quiero gestionar canchas y horarios.", "La validación evita horarios inválidos y superpuestos."),
            ("HU-08", "Como administrador quiero gestionar el catálogo de balones.", "El tipo, precio y estado se mantienen desde la persistencia."),
            ("HU-09", "Como cliente quiero crear y consultar mis reservas.", "La operación valida identidad, recursos, fecha, monto y disponibilidad en el servidor."),
            ("HU-10", "Como cliente quiero cancelar una reserva permitida.", "Se conserva el historial y la franja vuelve a estar disponible."),
            ("HU-11", "Como sistema quiero prevenir duplicidad ante solicitudes simultáneas.", "La transacción y la restricción de base de datos evitan ocupar dos veces la misma franja."),
        ],
        "design": "El modelo relacional enlaza usuarios, canchas, horarios y reservas; las relaciones Eloquent corresponden a las claves foráneas. ReservaService revalida disponibilidad dentro de una transacción, aplica bloqueos y usa las restricciones persistentes para evitar conflictos concurrentes.",
        "interfaces": "Catálogo y detalle de cancha, consulta de disponibilidad, formularios de gestión de canchas/horarios/balones, formulario de reserva y pantalla Mis reservas.",
        "tests": "CanchasPublicasTest, HorarioManagementTest, BalonesPublicosTest, ReservaTest y ReservaServiceTest verifican catálogo, reglas horarias, disponibilidad, cálculos, cancelación y conflictos.",
        "result": "El cliente consulta la oferta vigente y reserva una franja; el servidor conserva la integridad aunque varias solicitudes compitan por el mismo recurso.",
        "screen": "Disponibilidad, catálogo y reserva de cancha",
        "figures": [(12, "4s_sprint_02_casos_de_uso.png", "Casos de uso del Sprint 2."), (13, "4s_sprint_02_actividad.png", "Actividad de consulta de disponibilidad y reserva."), (14, "4s_sprint_02_secuencia.png", "Secuencia desde consulta hasta reserva transaccional.")],
    },
    {
        "title": "Recepción y administración del sistema",
        "objective": "Completar el ciclo operativo presencial y ofrecer al administrador herramientas para supervisar usuarios, recursos, reservas e indicadores.",
        "backlog": [
            ("HU-12", "Como recepcionista quiero buscar clientes y registrar reservas presenciales.", "Solo se seleccionan cuentas válidas y se reutilizan las reglas de reserva."),
            ("HU-13", "Como recepcionista quiero consultar la agenda diaria.", "La agenda muestra fecha, franja, cancha, cliente y estado."),
            ("HU-14", "Como recepcionista quiero registrar check-in y check-out.", "Las transiciones se autorizan y se guardan con la reserva correspondiente."),
            ("HU-15", "Como administrador quiero gestionar usuarios, canchas, horarios, balones y reservas.", "Las rutas administrativas validan rol, datos y relaciones."),
            ("HU-16", "Como administrador quiero consultar indicadores y exportar reportes.", "Filtros, dashboard y CSV reflejan las consultas persistidas."),
            ("HU-17", "Como administrador quiero configurar la presentación del sistema.", "Los ajustes se validan y se aplican a las vistas correspondientes."),
        ],
        "design": "La recepción utiliza rutas y permisos específicos, con agenda y operaciones de reserva presencial y check-in/check-out. La administración concentra los módulos de catálogo, reservas, dashboard, reportes y ajustes; los servicios reúnen reglas compartidas.",
        "interfaces": "Agenda de recepción, búsqueda de clientes, reserva presencial, control de ingreso/salida, dashboard, calendario administrativo, filtros y exportación de reportes.",
        "tests": "RecepcionistaTest verifica el flujo operativo; UserManagementTest, DashboardReportesTest y AjustesPresentacionTest validan administración, indicadores, exportación y configuración.",
        "result": "Recepción gestiona las actividades del día y administración supervisa la operación desde datos compartidos, sin duplicar las reglas de reserva.",
        "screen": "Agenda de recepción y panel administrativo",
        "figures": [(15, "4s_sprint_03_casos_de_uso.png", "Casos de uso del Sprint 3."), (16, "4s_sprint_03_actividad.png", "Actividad de atención presencial y actualización operativa."), (17, "4s_sprint_03_secuencia.png", "Secuencia de reserva presencial, check-in y check-out.")],
    },
    {
        "title": "Chatbot, verificación y documentación de usuario",
        "objective": "Automatizar consultas frecuentes con NLP, preservar los permisos y la fuente local de datos, probar los flujos integrados y documentar la operación de la plataforma.",
        "backlog": [
            ("HU-18", "Como usuario quiero consultar canchas, precios, horarios y disponibilidad en lenguaje natural.", "El chatbot responde con datos vigentes y conserva el contexto de la conversación."),
            ("HU-19", "Como sistema quiero analizar intención y entidades sin delegar decisiones operativas al proveedor.", "La salida estructurada se valida y el motor local responde ante error externo."),
            ("HU-20", "Como administrador quiero consultar el historial de conversaciones.", "El historial se protege mediante autenticación y permisos."),
            ("HU-21", "Como administrador quiero sincronizar reservas confirmadas con Calendar.", "La cola mantiene la operación local independiente y usa el vínculo persistido para sincronizar."),
            ("HU-22", "Como equipo quiero comprobar requisitos con pruebas automatizadas.", "Los casos cubren autenticación, acceso, disponibilidad, reservas, recepción, chatbot e integraciones."),
            ("HU-23", "Como usuario quiero una guía para utilizar la plataforma.", "El manual describe acceso, consultas, reservas, atención presencial y administración."),
        ],
        "design": "ChatbotService combina detección local, extracción de entidades y un proveedor externo opcional. El proveedor propone intención y entidades; Laravel valida, consulta los modelos y construye la respuesta. Google Calendar opera como proyección asíncrona del estado local mediante jobs idempotentes.",
        "interfaces": "Widget de chatbot, historial administrativo de conversaciones, pantalla de conexión de Google Calendar y páginas descritas en la guía de usuario incluida al cierre del capítulo.",
        "tests": "ChatbotTest, ChatbotMotorTest, AnalisisNlpTest y GoogleIntegrationTest cubren respuestas, persistencia, fallback, permisos y sincronización simulada. La suite completa se ejecuta para verificar regresiones.",
        "result": "El chatbot responde con información autorizada, las integraciones externas no gobiernan las reservas y la documentación permite operar las funciones principales.",
        "screen": "Chatbot, historial y sincronización de calendario",
        "figures": [(18, "4s_sprint_04_casos_de_uso.png", "Casos de uso del Sprint 4."), (19, "4s_sprint_04_actividad.png", "Actividad de análisis seguro y respuesta del chatbot."), (20, "4s_sprint_04_secuencia.png", "Secuencia de sincronización con Google Calendar.")],
    },
]


def add_sprints(document: Document) -> None:
    document.add_heading("3.2.3 Implementación por Sprints", level=3)
    add_text(document, "Cada incremento se organiza con la secuencia del índice de referencia: objetivo, Sprint Backlog, diseño y arquitectura, diseño e implementación de interfaces, y pruebas de testing. Los diagramas de casos de uso, actividades y secuencia describen las interacciones reales del incremento.")
    for number, sprint in enumerate(SPRINTS, start=1):
        document.add_heading(f"3.2.3.{number} Sprint {number}: {sprint['title']}", level=4)
        document.add_heading(f"3.2.3.{number}.1 Objetivo del Sprint", level=5)
        add_text(document, sprint["objective"])
        document.add_heading(f"3.2.3.{number}.2 Sprint Backlog: {sprint['title']}", level=5)
        add_table(document, 5 + number, f"Sprint Backlog del Sprint {number}", ["ID", "Historia de usuario", "Criterio de aceptación"], sprint["backlog"], [0.55, 2.85, 2.8])
        document.add_heading(f"3.2.3.{number}.3 Diseño y Arquitectura", level=5)
        add_text(document, sprint["design"])
        for figure_number, filename, title in sprint["figures"]:
            add_figure(document, figure_number, SPRINT_IMAGES / filename, title)
        document.add_heading(f"3.2.3.{number}.4 Diseño e Implementación de Interfaces", level=5)
        add_text(document, sprint["interfaces"])
        add_screenshot_placeholder(document, number, sprint["screen"])
        document.add_heading(f"3.2.3.{number}.5 Pruebas de Testing", level=5)
        add_text(document, sprint["tests"])
        add_text(document, f"Resultado del Sprint {number}: {sprint['result']}", "Resultado del Sprint")


def add_traceability_and_quality(document: Document) -> None:
    document.add_heading("3.3 Verificación de objetivos y resultados", level=2)
    add_text(document, "Objetivo general: Desarrollar un sistema web que automatice el proceso de reservas y la atención al cliente en complejos deportivos de Wally mediante la integración de tecnologías web y un chatbot inteligente basado en procesamiento de lenguaje natural (NLP), con el fin de optimizar la gestión administrativa y mejorar la experiencia del usuario. El sistema integra autenticación, gestión de recursos y reservas, operación administrativa, atención conversacional y pruebas automatizadas; estos incrementos conforman la evidencia funcional del objetivo general.")
    add_text(document, "La siguiente matriz confirma la relación entre los objetivos específicos formulados en el Capítulo I, los incrementos realizados y la evidencia técnica descrita en este capítulo.")
    add_table(document, 10, "Matriz final de cumplimiento de objetivos", ["Objetivo", "Incremento", "Evidencia"], [(row[0] + " — " + row[1], row[2], row[3]) for row in OBJECTIVES], [2.65, 1.0, 3.0])
    add_table(document, 11, "Niveles de prueba y evidencia", ["Nivel", "Propósito", "Evidencia del sistema"], [
        ("Unitarias", "Comprobar reglas, normalización NLP y cálculos aislados.", "AnalisisNlpTest, ChatbotMotorTest y servicios de reserva."),
        ("Feature", "Verificar rutas, formularios, roles, persistencia y respuesta HTTP.", "AuthenticationTest, ReservaTest, RecepcionistaTest y pruebas administrativas."),
        ("Integración simulada", "Validar los límites con proveedor NLP y Google Calendar sin depender de producción.", "ChatbotTest y GoogleIntegrationTest."),
        ("Regresión", "Revisar que cambios mantengan las funciones previas.", "Ejecución completa de la suite PHPUnit."),
    ], [1.25, 2.55, 2.85])
    add_text(document, "La ejecución de validación del 25/09/2026 finalizó con 250 pruebas aprobadas, 2 omitidas y 938 aserciones. Las pruebas omitidas se mantienen diferenciadas de las aprobadas en el resultado reportado.")
    document.add_heading("3.4 Manual de usuario de Wally Reservas", level=2)
    add_text(document, "Este manual resume las operaciones disponibles para que clientes, recepción y administración utilicen la plataforma.")
    document.add_heading("3.4.1 Acceso y perfil", level=3)
    add_text(document, "El visitante consulta el portal y puede crear una cuenta de cliente. El usuario registrado inicia sesión con sus credenciales; al acceder, el sistema abre las opciones permitidas por su rol. Desde el perfil se actualizan los datos personales y se cierra la sesión mediante la opción correspondiente.")
    document.add_heading("3.4.2 Consulta de canchas y reserva", level=3)
    add_text(document, "El usuario abre el catálogo, revisa las características y el precio de una cancha y consulta las franjas disponibles para la fecha requerida. El cliente autenticado selecciona cancha, horario y opciones habilitadas, confirma la solicitud y revisa el resultado en Mis reservas. Las reservas futuras elegibles pueden cancelarse desde esa sección; el sistema conserva el registro y libera la franja.")
    document.add_heading("3.4.3 Atención en recepción", level=3)
    add_text(document, "La persona de recepción consulta la agenda, busca un cliente activo y registra una reserva presencial seleccionando los recursos disponibles. Cuando el cliente llega, se registra el check-in; al finalizar la atención, se registra el check-out según las transiciones permitidas. La agenda refleja el estado actualizado.")
    document.add_heading("3.4.4 Administración", level=3)
    add_text(document, "El administrador gestiona usuarios y recursos desde los módulos correspondientes. En reservas puede consultar listados y calendario, filtrar registros y actualizar estados autorizados. El dashboard presenta indicadores; los reportes admiten filtros y exportación CSV. Los ajustes de presentación se guardan desde el módulo de configuración.")
    document.add_heading("3.4.5 Uso del chatbot", level=3)
    add_text(document, "El usuario escribe una consulta sobre canchas, horarios, precios o disponibilidad en el widget. El chatbot interpreta el mensaje y responde a partir de los datos locales. Para información privada, como las reservas personales, el usuario debe autenticarse. Si el proveedor externo no responde, se utiliza el análisis NLP local.")
    document.add_heading("3.5 Conclusión del capítulo", level=2)
    add_text(document, "La ingeniería del sistema articuló el diagnóstico y los requerimientos con el diseño relacional, la arquitectura MVC, las interfaces y las pruebas. Los cuatro sprints cubren autenticación y usuarios, recursos y reservas, operación administrativa y de recepción, y atención NLP con verificación y documentación. En conjunto, estos incrementos aportan evidencia para los siete objetivos específicos y el objetivo general del proyecto.")


def update_fields_on_open(document: Document) -> None:
    settings = document.settings._element
    update = settings.find(qn("w:updateFields"))
    if update is None:
        update = OxmlElement("w:updateFields")
        settings.append(update)
    update.set(qn("w:val"), "true")


def main() -> None:
    expected = [
        *(SPRINT_IMAGES / f"4s_sprint_{number:02d}_{kind}.png" for number in range(1, 5) for kind in ("casos_de_uso", "actividad", "secuencia")),
        SPRINT_IMAGES / "modelo_datos_erd.png",
        *(GENERAL_IMAGES / name for name in ("caso_uso_general.png", "diagrama_clases_general.png", "modelo_entidad_relacion.png", "arquitectura_general.png")),
    ]
    missing = [str(path.relative_to(ROOT)) for path in expected if not path.exists()]
    if missing:
        raise FileNotFoundError("Faltan imágenes requeridas: " + ", ".join(missing))

    document = Document()
    set_document_styles(document)
    add_toc(document)
    add_general_sections(document)
    add_scrum_and_plan(document)
    add_sprints(document)
    add_traceability_and_quality(document)
    update_fields_on_open(document)
    document.save(OUTPUT)
    print(f"Documento generado: {OUTPUT}")
    print("Sprints: 4 | Objetivos específicos trazados: 7 | Figuras: 16 | Tablas numeradas: 11")


if __name__ == "__main__":
    main()