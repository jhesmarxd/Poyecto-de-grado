from pathlib import Path
import hashlib

from docx import Document
from docx.enum.section import WD_SECTION_START
from docx.enum.style import WD_STYLE_TYPE
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_LINE_SPACING
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[2]
REFERENCE = ROOT / "Proyecto de Grado Jhesmar Fuentes -Capitulo I y II.docx"
OUTPUT = ROOT / "docs" / "Capitulo_III_Sprints_1_al_3_Wally_Reservas.docx"
DIAGRAMS = ROOT / "docs" / "diagramas" / "lucid" / "4_sprints"
UML = ROOT / "docs" / "uml" / "lucid"
EXPECTED_REFERENCE_HASH = "5C65722F52215E0DD3399D82B149D725FD562C86C5A1D88E79FEF47C567BCEFF"


def file_hash(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as source:
        for block in iter(lambda: source.read(1024 * 1024), b""):
            digest.update(block)
    return digest.hexdigest().upper()


def clear_body(document: Document) -> None:
    body = document._element.body
    section_properties = body.sectPr
    for child in list(body):
        if child is not section_properties:
            body.remove(child)


def set_cell_shading(cell, fill: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_margins(cell, top=80, start=90, bottom=80, end=90) -> None:
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for margin_name, margin_value in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        node = tc_mar.find(qn(f"w:{margin_name}"))
        if node is None:
            node = OxmlElement(f"w:{margin_name}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(margin_value))
        node.set(qn("w:type"), "dxa")


def repeat_table_header(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement("w:tblHeader")
    tbl_header.set(qn("w:val"), "true")
    tr_pr.append(tbl_header)


def keep_with_next(paragraph) -> None:
    paragraph.paragraph_format.keep_with_next = True


def set_repeatable_fonts(run, size=11, bold=None, italic=None, color="000000") -> None:
    run.font.name = "Arial"
    run._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
    run.font.size = Pt(size)
    if bold is not None:
        run.bold = bold
    if italic is not None:
        run.italic = italic
    run.font.color.rgb = RGBColor.from_string(color)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run()
    set_repeatable_fonts(run, 11)
    fld_char_begin = OxmlElement("w:fldChar")
    fld_char_begin.set(qn("w:fldCharType"), "begin")
    instr_text = OxmlElement("w:instrText")
    instr_text.set(qn("xml:space"), "preserve")
    instr_text.text = " PAGE "
    fld_char_end = OxmlElement("w:fldChar")
    fld_char_end.set(qn("w:fldCharType"), "end")
    run._r.extend([fld_char_begin, instr_text, fld_char_end])


def configure_document(document: Document) -> None:
    for section in document.sections:
        section.page_width = Cm(21.59)
        section.page_height = Cm(27.94)
        section.top_margin = Cm(2.5)
        section.bottom_margin = Cm(2.5)
        section.left_margin = Cm(3)
        section.right_margin = Cm(3)
        section.header_distance = Cm(1.25)
        section.footer_distance = Cm(1.25)
        section.start_type = WD_SECTION_START.NEW_PAGE
        section.different_first_page_header_footer = False
        footer = section.footer
        if not footer.paragraphs:
            footer.add_paragraph()
        footer.paragraphs[0].clear()
        add_page_number(footer.paragraphs[0])

    styles = document.styles
    normal = styles["Normal"]
    normal.font.name = "Arial"
    normal._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
    normal.font.size = Pt(11)
    normal.font.color.rgb = RGBColor(0, 0, 0)
    normal.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    normal.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.widow_control = True

    for style_name in ("Title", "Heading 1", "Heading 2", "Heading 3", "Heading 4"):
        style = styles[style_name]
        style.font.name = "Arial"
        style._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
        style.font.color.rgb = RGBColor(0, 0, 0)
        style.font.size = Pt(11)
        style.paragraph_format.line_spacing = 1.0
        style.paragraph_format.keep_with_next = True
        style.paragraph_format.page_break_before = False
    styles["Title"].font.bold = True
    styles["Title"].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    styles["Heading 1"].font.bold = True
    styles["Heading 1"].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    styles["Heading 1"].paragraph_format.space_before = Pt(12)
    styles["Heading 1"].paragraph_format.space_after = Pt(12)
    for style_name in ("Heading 2", "Heading 3"):
        styles[style_name].font.bold = True
        styles[style_name].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
        styles[style_name].paragraph_format.space_before = Pt(10)
        styles[style_name].paragraph_format.space_after = Pt(6)
    styles["Heading 4"].font.bold = True
    styles["Heading 4"].font.italic = True
    styles["Heading 4"].paragraph_format.space_before = Pt(8)
    styles["Heading 4"].paragraph_format.space_after = Pt(4)

    for level in range(1, 5):
        style_name = f"Capitulo H{level}"
        if style_name not in styles:
            custom = styles.add_style(style_name, WD_STYLE_TYPE.PARAGRAPH)
        else:
            custom = styles[style_name]
        custom.base_style = normal
        custom.font.name = "Arial"
        custom._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
        custom.font.size = Pt(11)
        custom.font.bold = True
        custom.font.italic = level == 4
        custom.font.color.rgb = RGBColor(0, 0, 0)
        custom.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER if level == 1 else WD_ALIGN_PARAGRAPH.LEFT
        custom.paragraph_format.line_spacing = 1.0
        custom.paragraph_format.space_before = Pt(12 if level == 1 else 10 if level in (2, 3) else 8)
        custom.paragraph_format.space_after = Pt(12 if level == 1 else 6 if level in (2, 3) else 4)
        custom.paragraph_format.keep_with_next = True
        p_pr = custom._element.get_or_add_pPr()
        for num_pr in list(p_pr.findall(qn("w:numPr"))):
            p_pr.remove(num_pr)
        outline = p_pr.find(qn("w:outlineLvl"))
        if outline is None:
            outline = OxmlElement("w:outlineLvl")
            p_pr.append(outline)
        outline.set(qn("w:val"), str(level - 1))

    if "Referencia APA" not in styles:
        apa = styles.add_style("Referencia APA", WD_STYLE_TYPE.PARAGRAPH)
    else:
        apa = styles["Referencia APA"]
    apa.font.name = "Arial"
    apa._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
    apa.font.size = Pt(11)
    apa.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
    apa.paragraph_format.left_indent = Cm(1.27)
    apa.paragraph_format.first_line_indent = Cm(-1.27)
    apa.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
    apa.paragraph_format.space_after = Pt(6)


def add_text(document: Document, text: str, *, align=WD_ALIGN_PARAGRAPH.JUSTIFY, bold_lead: str | None = None):
    paragraph = document.add_paragraph()
    paragraph.alignment = align
    paragraph.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
    paragraph.paragraph_format.space_after = Pt(6)
    if bold_lead and text.startswith(bold_lead):
        lead = paragraph.add_run(bold_lead)
        set_repeatable_fonts(lead, bold=True)
        rest = paragraph.add_run(text[len(bold_lead):])
        set_repeatable_fonts(rest)
    else:
        run = paragraph.add_run(text)
        set_repeatable_fonts(run)
    return paragraph


def add_heading(document: Document, text: str, level: int):
    paragraph = document.add_paragraph(style=f"Capitulo H{level}")
    paragraph.add_run(text)
    for run in paragraph.runs:
        set_repeatable_fonts(run, 11, bold=True, italic=(level == 4))
    return paragraph


def add_bullets(document: Document, items: list[str]) -> None:
    for item in items:
        paragraph = document.add_paragraph(style="List Bullet")
        paragraph.paragraph_format.left_indent = Cm(0.75)
        paragraph.paragraph_format.first_line_indent = Cm(-0.4)
        paragraph.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
        paragraph.paragraph_format.space_after = Pt(3)
        run = paragraph.add_run(item)
        set_repeatable_fonts(run)


def add_table(document: Document, headers: list[str], rows: list[list[str]], widths: list[float] | None = None, font_size=10.5):
    table = document.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = "Table Grid"
    table.autofit = False
    header = table.rows[0]
    repeat_table_header(header)
    for index, title in enumerate(headers):
        cell = header.cells[index]
        set_cell_shading(cell, "D9E2F3")
        cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        set_cell_margins(cell)
        paragraph = cell.paragraphs[0]
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        paragraph.paragraph_format.space_after = Pt(0)
        run = paragraph.add_run(title)
        set_repeatable_fonts(run, font_size, bold=True)
        if widths:
            cell.width = Inches(widths[index])
    for row_index, row_values in enumerate(rows):
        row = table.add_row()
        if row_index % 2 == 1:
            for cell in row.cells:
                set_cell_shading(cell, "F7F7F7")
        for index, value in enumerate(row_values):
            cell = row.cells[index]
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            set_cell_margins(cell)
            if widths:
                cell.width = Inches(widths[index])
            paragraph = cell.paragraphs[0]
            paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
            paragraph.paragraph_format.line_spacing = 1.0
            paragraph.paragraph_format.space_after = Pt(0)
            run = paragraph.add_run(value)
            set_repeatable_fonts(run, font_size)
    document.add_paragraph().paragraph_format.space_after = Pt(0)
    return table


def add_figure(document: Document, number: int, title: str, image_path: Path, note: str, width: float = 5.55) -> None:
    table = document.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    cell = table.cell(0, 0)
    cell.width = Inches(5.75)
    set_cell_margins(cell, top=0, start=0, bottom=0, end=0)
    tr_pr = table.rows[0]._tr.get_or_add_trPr()
    cant_split = OxmlElement("w:cantSplit")
    tr_pr.append(cant_split)
    tbl_pr = table._tbl.tblPr
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        border = OxmlElement(f"w:{edge}")
        border.set(qn("w:val"), "nil")
        borders.append(border)
    tbl_pr.append(borders)

    label = cell.paragraphs[0]
    label.paragraph_format.space_after = Pt(0)
    keep_with_next(label)
    run = label.add_run(f"Figura {number}")
    set_repeatable_fonts(run, 11, bold=True)
    title_p = cell.add_paragraph()
    title_p.paragraph_format.space_after = Pt(6)
    keep_with_next(title_p)
    title_run = title_p.add_run(title)
    set_repeatable_fonts(title_run, 11, italic=True)
    image_p = cell.add_paragraph()
    image_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    image_p.paragraph_format.space_after = Pt(6)
    keep_with_next(image_p)
    image_run = image_p.add_run()
    image_run.add_picture(str(image_path), width=Inches(width))
    note_p = cell.add_paragraph()
    note_p.paragraph_format.space_after = Pt(6)
    prefix = note_p.add_run("Nota. ")
    set_repeatable_fonts(prefix, 10.5, italic=True)
    rest = note_p.add_run(note)
    set_repeatable_fonts(rest, 10.5, italic=True)


def add_cover(document: Document) -> None:
    for _ in range(2):
        document.add_paragraph()
    lines = [
        ("INSTITUTO TECNOLÓGICO SACABA", True, 13),
        ("CARRERA DE SISTEMAS INFORMÁTICOS", True, 12),
        ("", False, 11),
        ("SISTEMA WEB PARA LA GESTIÓN Y RESERVA DE CANCHAS DE WALLY", True, 14),
        ("", False, 11),
        ("CAPÍTULO III", True, 13),
        ("INGENIERÍA DEL PROYECTO", True, 13),
        ("DESARROLLO DE LOS SPRINTS 1, 2 Y 3", True, 12),
    ]
    for text, bold, size in lines:
        paragraph = document.add_paragraph()
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        paragraph.paragraph_format.space_after = Pt(10 if text else 6)
        run = paragraph.add_run(text)
        set_repeatable_fonts(run, size, bold=bold)
    for _ in range(4):
        document.add_paragraph()
    details = [
        "POSTULANTE: JHESMAR FUENTES",
        "TUTOR: LIC. RENÉ GERMÁN QUISPE GUTIÉRREZ",
        "SACABA – COCHABAMBA – BOLIVIA",
        "2026",
    ]
    for detail in details:
        paragraph = document.add_paragraph()
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        paragraph.paragraph_format.space_after = Pt(8)
        run = paragraph.add_run(detail)
        set_repeatable_fonts(run, 11, bold=True)
    document.add_page_break()


def main() -> None:
    if file_hash(REFERENCE) != EXPECTED_REFERENCE_HASH:
        raise RuntimeError("El documento aprobado cambió desde la etapa de análisis; se detuvo la generación para proteger su integridad.")
    required_images = [
        UML / "caso_uso_general.png",
        UML / "diagrama_clases_general.png",
        *(DIAGRAMS / f"4s_sprint_{sprint:02d}_{kind}.png" for sprint in (1, 2, 3) for kind in ("casos_de_uso", "actividad", "secuencia")),
    ]
    missing = [str(path) for path in required_images if not path.exists()]
    if missing:
        raise FileNotFoundError("No se encontraron imágenes requeridas: " + "; ".join(missing))

    document = Document(str(REFERENCE))
    clear_body(document)
    configure_document(document)
    add_cover(document)

    add_heading(document, "CAPÍTULO III INGENIERÍA DEL PROYECTO", 1)
    add_heading(document, "3.1 Organización del desarrollo", 2)
    add_text(document, "El presente capítulo documenta la ingeniería aplicada al sistema web de reservas de canchas de wally. La exposición se organiza en tres incrementos funcionales y establece trazabilidad entre los objetivos aprobados, las historias de usuario, las reglas implementadas, los diagramas UML y las pruebas automatizadas del repositorio. Cada sprint entrega una capacidad operativa utilizable y verificable dentro de la arquitectura Laravel del proyecto.")

    add_heading(document, "3.1.1 Aplicación de Scrum", 3)
    add_text(document, "Scrum se aplicó como marco de organización iterativa. El Product Backlog concentra las necesidades del negocio; el Sprint Backlog selecciona historias con criterios de aceptación medibles; el incremento integra código, base de datos, interfaz y pruebas; y la revisión de cada sprint contrasta el resultado con los criterios definidos. Esta adaptación mantiene los eventos y artefactos esenciales del marco y los articula con la documentación académica del proyecto.")
    add_text(document, "Para considerar una historia como terminada se adoptó una definición de terminado compuesta por: validación en el servidor, autorización por rol, persistencia coherente, respuesta visible para el usuario, pruebas automatizadas y ausencia de regresiones en las funciones relacionadas. En las operaciones sensibles de reserva también se exige control transaccional y verificación de concurrencia.")
    add_table(document,
        ["Elemento Scrum", "Aplicación en el proyecto", "Evidencia"],
        [
            ["Product Backlog", "Necesidades priorizadas de acceso, recursos, reservas y operación.", "Historias agrupadas por sprint."],
            ["Sprint Backlog", "Historias seleccionadas con alcance y criterios verificables.", "Tablas de cada sprint."],
            ["Incremento", "Funcionalidad integrada en Laravel, MariaDB y la interfaz web.", "Controladores, servicios, migraciones y vistas."],
            ["Revisión", "Contraste entre criterio de aceptación y comportamiento del sistema.", "Casos de prueba y resultados automatizados."],
            ["Definición de terminado", "Código validado, autorizado, persistente, probado y documentado.", "Trazabilidad técnica y pruebas."],
        ], [1.25, 2.65, 1.85])

    add_heading(document, "3.1.2 Actores y responsabilidades", 3)
    add_table(document,
        ["Actor", "Responsabilidad principal", "Interacción relevante"],
        [
            ["Visitante", "Consultar información pública e iniciar el acceso.", "Visualiza canchas y disponibilidad pública."],
            ["Cliente", "Gestionar su cuenta y realizar reservas propias.", "Selecciona cancha, fecha, rango horario y recursos."],
            ["Recepcionista", "Atender la operación diaria en el establecimiento.", "Busca clientes, registra reservas y controla ingreso y salida."],
            ["Administrador", "Configurar y supervisar la plataforma.", "Gestiona usuarios, canchas, horarios, balones, reservas y reportes."],
            ["Google Identity", "Proveer autenticación externa cuando el usuario elige esa opción.", "Entrega identidad validada al flujo de acceso."],
        ], [1.25, 2.25, 2.25])

    add_heading(document, "3.1.3 Alineación con los objetivos del proyecto", 3)
    add_text(document, "Los tres incrementos atienden el núcleo del objetivo general: disponer de una plataforma web que organice el acceso, la disponibilidad, el registro de reservas y la gestión operativa. La relación se expresa sin alterar la formulación aprobada del Capítulo I; esta matriz identifica cómo el software aporta evidencia de cumplimiento.")
    add_table(document,
        ["Resultado esperado", "Sprint asociado", "Evidencia de cumplimiento"],
        [
            ["Acceso seguro y diferenciación de responsabilidades.", "Sprint 1", "Autenticación local y Google, recuperación de contraseña, perfiles, roles, middleware y políticas."],
            ["Administración de canchas, horarios y recursos.", "Sprint 2", "CRUD autorizado, reglas de disponibilidad y gestión de balones."],
            ["Registro confiable de reservas.", "Sprint 2", "Rangos consecutivos, cálculo en servidor, transacciones, bloqueo y control de superposición."],
            ["Atención y control operativo.", "Sprint 3", "Agenda de recepción, reservas presenciales, check-in, check-out y cambios de estado."],
            ["Información para administración.", "Sprint 3", "Panel de indicadores, reportes CSV, ajustes de presentación y notificaciones."],
        ], [2.0, 1.05, 2.7])

    add_heading(document, "3.1.4 Modelo funcional general", 3)
    add_text(document, "El modelo general resume la interacción entre los actores y las funciones principales. La autorización se aplica desde el acceso hasta las operaciones administrativas, de modo que cada actor utiliza únicamente las acciones correspondientes a su rol.")
    add_figure(document, 1, "Diagrama general de casos de uso del sistema", UML / "caso_uso_general.png", "Elaboración propia en Lucidchart con base en los módulos implementados.", 5.6)
    add_text(document, "La Figura 1 ubica al cliente como responsable de sus reservas, al recepcionista como operador de la atención diaria y al administrador como responsable de configuración y supervisión. Esta separación sustenta los límites de acceso aplicados en rutas, middleware y políticas.")
    add_figure(document, 2, "Diagrama general de clases del dominio", UML / "diagrama_clases_general.png", "Elaboración propia en Lucidchart. El modelo representa las entidades nucleares; la implementación amplía Reserva y Horario con campos de periodo para rangos consecutivos.", 5.55)
    add_text(document, "La Figura 2 presenta las entidades principales y sus asociaciones. En el código, Usuario se relaciona con Reserva; Cancha organiza Horario y Reserva; Balón puede incorporarse a una reserva; y las entidades de conversación registran la interacción asistida. La base de datos utiliza MariaDB mediante el controlador mysql de Laravel.")

    add_heading(document, "3.2 Desarrollo incremental por sprints", 2)
    add_text(document, "Los apartados siguientes describen los tres incrementos en el orden en que habilitan el proceso del negocio: primero la identidad y autorización, luego los recursos y la reserva, y finalmente la operación presencial y administrativa.")

    # SPRINT 1
    add_heading(document, "3.2.1 Sprint 1: acceso, identidad y roles", 3)
    add_heading(document, "3.2.1.1 Objetivo del sprint", 4)
    add_text(document, "Establecer una entrada segura al sistema y separar las responsabilidades de cliente, recepcionista y administrador. El incremento permite registrar cuentas, autenticar usuarios por credenciales locales o por Google, recuperar el acceso, actualizar el perfil y aplicar restricciones según estado y rol.")
    add_heading(document, "3.2.1.2 Sprint Backlog", 4)
    add_table(document,
        ["ID", "Historia de usuario", "Criterio de aceptación"],
        [
            ["HU-01", "Como visitante, quiero crear una cuenta para utilizar funciones personalizadas.", "Los datos válidos crean un usuario y los datos inválidos muestran errores sin persistir información."],
            ["HU-02", "Como usuario, quiero iniciar y cerrar sesión de forma segura.", "Las credenciales válidas crean una sesión; el cierre invalida la sesión activa."],
            ["HU-03", "Como usuario, quiero ingresar con Google para simplificar el acceso.", "La identidad validada se asocia a una cuenta y respeta el estado del usuario."],
            ["HU-04", "Como usuario, quiero recuperar mi contraseña y administrar mi perfil.", "El sistema procesa la recuperación y valida los cambios de perfil."],
            ["HU-05", "Como responsable del sistema, quiero restringir acciones por rol y estado.", "Un usuario inactivo o sin rol autorizado no accede a funciones protegidas."],
        ], [0.55, 2.75, 2.45])
    add_heading(document, "3.2.1.3 Reglas de negocio y diseño técnico", 4)
    add_bullets(document, [
        "El correo electrónico identifica de manera única a la cuenta de usuario.",
        "Las contraseñas se almacenan mediante el mecanismo de hash provisto por Laravel y nunca como texto legible.",
        "La autenticación externa se vincula con una cuenta local para mantener roles, estado y trazabilidad.",
        "Los roles cliente, recepcionista y administrador delimitan rutas y operaciones mediante middleware y políticas.",
        "Una cuenta inactiva conserva su registro histórico, pero no puede utilizar funciones protegidas.",
        "La validación se ejecuta en el servidor, incluso cuando la interfaz ya ofrece controles preventivos.",
    ])
    add_table(document,
        ["Componente", "Responsabilidad verificada"],
        [
            ["Auth\\RegisterController y Auth\\LoginController", "Registro, autenticación local, manejo de sesión y cierre de sesión."],
            ["Auth\\GoogleAuthController", "Redirección, retorno de identidad externa y vinculación con el usuario local."],
            ["Auth\\ForgotPasswordController y ResetPasswordController", "Solicitud y restablecimiento de credenciales."],
            ["EnsureUserIsAdmin y EnsureUserIsRecepcionista", "Control de acceso a áreas especializadas."],
            ["Policies del dominio", "Autorización contextual sobre canchas, horarios, balones y reservas."],
            ["User y SocialAccount", "Persistencia de identidad, rol, estado y relación con proveedor externo."],
        ], [2.55, 3.2])
    add_heading(document, "3.2.1.4 Modelado del sprint", 4)
    add_figure(document, 3, "Casos de uso del Sprint 1: acceso y control de roles", DIAGRAMS / "4s_sprint_01_casos_de_uso.png", "Elaboración propia en Lucidchart. El administrador representado en el límite de control conserva las facultades de gestión de usuarios; Google interviene únicamente como proveedor de identidad.", 5.45)
    add_text(document, "El diagrama identifica los casos de registro, inicio de sesión, recuperación y administración del perfil. La gestión de usuarios se restringe al administrador, mientras que la autenticación externa actúa como alternativa de identificación y no reemplaza las reglas internas de rol y estado.")
    add_figure(document, 4, "Diagrama de actividades del Sprint 1", DIAGRAMS / "4s_sprint_01_actividad.png", "Elaboración propia en Lucidchart. Flujo de validación de identidad, estado de cuenta y redirección por rol.", 5.05)
    add_text(document, "La actividad comienza con la elección del método de acceso. Tras validar la identidad, el sistema verifica que la cuenta esté habilitada y determina el destino según el rol. Una condición inválida produce una respuesta controlada y no crea una sesión autorizada.")
    add_figure(document, 5, "Diagrama de secuencia del Sprint 1", DIAGRAMS / "4s_sprint_01_secuencia.png", "Elaboración propia en Lucidchart. Interacción representativa entre usuario, interfaz, controlador de autenticación y persistencia.", 4.8)
    add_text(document, "La secuencia evidencia que la interfaz no decide la autorización. El controlador procesa las credenciales, consulta la cuenta y solo después inicia la sesión. Este orden evita que una validación visual se convierta en el único mecanismo de seguridad.")
    add_heading(document, "3.2.1.5 Pruebas y resultado del incremento", 4)
    add_table(document,
        ["Caso", "Verificación", "Resultado esperado"],
        [
            ["CP-01", "Registro con datos válidos e inválidos.", "Creación controlada y mensajes de validación."],
            ["CP-02", "Inicio y cierre de sesión.", "Sesión válida y posterior invalidación."],
            ["CP-03", "Acceso con Google.", "Vinculación de identidad y aplicación del estado local."],
            ["CP-04", "Acceso a rutas de otro rol.", "Respuesta de autorización denegada o redirección segura."],
            ["CP-05", "Uso de una cuenta inactiva.", "Bloqueo del acceso a funciones protegidas."],
        ], [0.65, 2.25, 2.85])
    add_text(document, "Las pruebas AuthenticationTest y GoogleIntegrationTest verifican los flujos de acceso, mientras que las pruebas funcionales de los módulos protegidos confirman la separación por roles. El incremento resultante proporciona la identidad y la autorización sobre las cuales se apoyan los sprints posteriores.")

    # SPRINT 2
    add_heading(document, "3.2.2 Sprint 2: recursos, disponibilidad y reservas", 3)
    add_heading(document, "3.2.2.1 Objetivo del sprint", 4)
    add_text(document, "Implementar el núcleo transaccional del negocio: administrar canchas, horarios y balones; consultar disponibilidad; y registrar reservas por uno o varios periodos consecutivos sin superposiciones. El servidor calcula el importe y conserva la coherencia aun cuando dos solicitudes intentan tomar el mismo recurso.")
    add_heading(document, "3.2.2.2 Sprint Backlog", 4)
    add_table(document,
        ["ID", "Historia de usuario", "Criterio de aceptación"],
        [
            ["HU-06", "Como administrador, quiero gestionar canchas para mantener la oferta disponible.", "El alta, modificación y estado de la cancha respetan validación y autorización."],
            ["HU-07", "Como administrador, quiero configurar horarios para definir periodos reservables.", "Los periodos no presentan superposición dentro de la misma cancha."],
            ["HU-08", "Como cliente, quiero consultar disponibilidad por fecha y cancha.", "La respuesta distingue periodos libres de aquellos ocupados o cerrados."],
            ["HU-09", "Como cliente, quiero reservar varias horas consecutivas en una sola operación.", "El rango se acepta solo si todos sus periodos son continuos y están disponibles."],
            ["HU-10", "Como cliente, quiero solicitar balones junto con la reserva.", "La cantidad asignada no supera el inventario disponible."],
            ["HU-11", "Como cliente, quiero cancelar conforme a las reglas del sistema.", "La cancelación autorizada actualiza el estado sin borrar el historial."],
        ], [0.55, 2.75, 2.45])
    add_heading(document, "3.2.2.3 Reglas de negocio y consistencia", 4)
    add_bullets(document, [
        "La reserva contiene fecha, hora inicial y hora final; el extremo final delimita el rango y no crea una ocupación adicional.",
        "Todos los periodos comprendidos deben pertenecer a la misma cancha, estar activos, ser consecutivos y encontrarse libres.",
        "La superposición se detecta mediante comparación de intervalos, no únicamente por igualdad de la hora inicial.",
        "El importe se calcula en el servidor a partir de la duración y la tarifa aplicable; un valor enviado por el navegador no se toma como fuente de verdad.",
        "La creación se ejecuta dentro de una transacción y utiliza bloqueo de filas para evitar dobles reservas en solicitudes concurrentes.",
        "La asignación de balones valida disponibilidad y conserva la relación con la reserva para el control operativo.",
        "La cancelación cambia el estado y mantiene la trazabilidad histórica de la operación.",
    ])
    add_table(document,
        ["Componente", "Responsabilidad verificada"],
        [
            ["Admin\\CanchaController, HorarioController y BalonController", "Gestión autorizada de recursos y periodos de atención."],
            ["DisponibilidadController y DisponibilidadService", "Construcción de la disponibilidad por cancha y fecha."],
            ["ReservaController y StoreReservaRequest", "Recepción, validación y respuesta del flujo de reserva del cliente."],
            ["ReservaService", "Cálculo, transacción, bloqueo, detección de superposición y persistencia."],
            ["BalonService", "Validación y asignación consistente de inventario complementario."],
            ["Migración de periodos de horarios y reservas", "Soporte persistente para hora inicial y hora final."],
        ], [2.65, 3.1])
    add_heading(document, "3.2.2.4 Modelado del sprint", 4)
    add_figure(document, 6, "Casos de uso del Sprint 2: disponibilidad y reservas", DIAGRAMS / "4s_sprint_02_casos_de_uso.png", "Elaboración propia en Lucidchart. El modelo agrupa gestión de recursos, consulta de disponibilidad, reserva y cancelación.", 5.45)
    add_text(document, "Los casos de uso relacionan la preparación administrativa de los recursos con la operación realizada por el cliente. La consulta de disponibilidad precede a la reserva, pero la confirmación vuelve a validar las reglas en el servidor para impedir inconsistencias causadas por información desactualizada en el navegador.")
    add_figure(document, 7, "Diagrama de actividades del Sprint 2", DIAGRAMS / "4s_sprint_02_actividad.png", "Elaboración propia en Lucidchart. Flujo base de selección, validación y confirmación transaccional.", 5.0)
    add_text(document, "La actividad ilustra el flujo representativo para un periodo. En la reserva por rango, la misma validación se aplica a cada periodo comprendido y se confirma como una sola unidad transaccional. Si una condición falla, la operación se revierte y la disponibilidad permanece coherente.")
    add_figure(document, 8, "Diagrama de secuencia del Sprint 2", DIAGRAMS / "4s_sprint_02_secuencia.png", "Elaboración propia en Lucidchart. Interacción entre cliente, interfaz, controlador, servicio de reservas y base de datos.", 4.75)
    add_text(document, "La secuencia separa la coordinación HTTP de las reglas del dominio. ReservaController recibe la solicitud validada y delega la operación en ReservaService. El servicio inicia la transacción, bloquea los registros relevantes, verifica intervalos y recursos, calcula el total y confirma la persistencia.")
    add_heading(document, "3.2.2.5 Pruebas y resultado del incremento", 4)
    add_table(document,
        ["Caso", "Verificación", "Resultado esperado"],
        [
            ["CP-06", "Reserva de un periodo disponible.", "Creación con cancha, fecha, rango, estado e importe correctos."],
            ["CP-07", "Reserva de periodos consecutivos.", "Una sola reserva cubre el rango completo y calcula su importe."],
            ["CP-08", "Intento de superposición.", "Rechazo sin duplicar ocupación ni alterar reservas existentes."],
            ["CP-09", "Dos solicitudes concurrentes sobre el mismo periodo.", "Solo una confirma la reserva; la otra recibe una respuesta controlada."],
            ["CP-10", "Solicitud de balones superior al inventario.", "Rechazo de la asignación y conservación de existencias."],
            ["CP-11", "Cancelación autorizada.", "Cambio de estado con historial preservado."],
        ], [0.65, 2.25, 2.85])
    add_text(document, "ReservaTest, ReservaServiceTest, HorarioManagementTest, CanchaManagementTest y BalonServiceTest cubren las reglas centrales. La verificación específica sobre MariaDB ejecutó dos pruebas de concurrencia con catorce aserciones, confirmando que el bloqueo y la transacción impiden la doble ocupación del mismo periodo.")

    # SPRINT 3
    add_heading(document, "3.2.3 Sprint 3: recepción, operación y administración", 3)
    add_heading(document, "3.2.3.1 Objetivo del sprint", 4)
    add_text(document, "Consolidar la operación diaria y el control administrativo. El recepcionista dispone de agenda, búsqueda de clientes, registro presencial y control de ingreso y salida; el administrador supervisa usuarios, recursos, estados, indicadores, reportes, ajustes de presentación y notificaciones asociadas a las reservas.")
    add_heading(document, "3.2.3.2 Sprint Backlog", 4)
    add_table(document,
        ["ID", "Historia de usuario", "Criterio de aceptación"],
        [
            ["HU-12", "Como recepcionista, quiero consultar la agenda para organizar la atención diaria.", "La agenda presenta reservas relevantes por fecha y estado."],
            ["HU-13", "Como recepcionista, quiero buscar clientes y registrar reservas presenciales.", "La reserva se vincula al cliente y utiliza las mismas reglas de disponibilidad."],
            ["HU-14", "Como recepcionista, quiero registrar ingreso y salida.", "Los cambios de estado respetan la secuencia operativa y quedan persistidos."],
            ["HU-15", "Como administrador, quiero gestionar usuarios y supervisar reservas.", "Las acciones administrativas validan rol, datos y estado del registro."],
            ["HU-16", "Como administrador, quiero visualizar indicadores y exportar información.", "El panel presenta métricas y el reporte CSV contiene datos consistentes."],
            ["HU-17", "Como responsable, quiero configurar la presentación y emitir avisos.", "Los ajustes autorizados se reflejan en la interfaz y las notificaciones se generan según el evento."],
        ], [0.55, 2.75, 2.45])
    add_heading(document, "3.2.3.3 Reglas de negocio y diseño técnico", 4)
    add_bullets(document, [
        "La reserva presencial reutiliza la validación del dominio y no crea una ruta paralela sin controles de disponibilidad.",
        "El check-in se aplica a una reserva apta para ingreso; el check-out registra la conclusión del uso y evita transiciones incompatibles.",
        "Las acciones del recepcionista se limitan al flujo de atención y no incluyen la configuración global del sistema.",
        "La administración de usuarios preserva los datos históricos relacionados con reservas y aplica validación en altas y modificaciones.",
        "Los indicadores se calculan desde datos persistidos y los reportes CSV se generan mediante un servicio dedicado.",
        "Los ajustes de presentación requieren autorización administrativa y se consumen de forma controlada por las vistas.",
        "Las notificaciones se producen como consecuencia de eventos de reserva y conservan el contexto de la operación.",
    ])
    add_table(document,
        ["Componente", "Responsabilidad verificada"],
        [
            ["Recepcionista\\ReservaController y ClienteController", "Agenda, búsqueda y registro de reservas en atención presencial."],
            ["Recepcionista\\CheckinController", "Transiciones de ingreso y salida conforme al estado de la reserva."],
            ["Admin\\UserController y Admin\\ReservaController", "Gestión de usuarios y supervisión de reservas."],
            ["Admin\\DashboardController y EstadisticasService", "Preparación de métricas para el panel administrativo."],
            ["Admin\\ReporteController y ReporteService", "Generación de reportes exportables en CSV."],
            ["Admin\\AjusteController", "Actualización autorizada de parámetros de presentación."],
            ["ReservaNotificationService y ReservaNotification", "Creación y entrega de avisos asociados a la reserva."],
        ], [2.65, 3.1])
    add_heading(document, "3.2.3.4 Modelado del sprint", 4)
    add_figure(document, 9, "Casos de uso del Sprint 3: operación y administración", DIAGRAMS / "4s_sprint_03_casos_de_uso.png", "Elaboración propia en Lucidchart. El recepcionista atiende la operación diaria y el administrador gestiona la supervisión institucional.", 5.45)
    add_text(document, "El diagrama muestra dos ámbitos complementarios. Recepción atiende el contacto inmediato con el cliente y la reserva, mientras administración mantiene usuarios, recursos, estados e información de control. Ambos ámbitos utilizan el mismo modelo de datos y las mismas reglas centrales de reserva.")
    add_figure(document, 10, "Diagrama de actividades del Sprint 3", DIAGRAMS / "4s_sprint_03_actividad.png", "Elaboración propia en Lucidchart. Flujo operativo desde la consulta de agenda hasta el cierre de la atención.", 5.0)
    add_text(document, "La actividad organiza la jornada del recepcionista: consulta la agenda, identifica al cliente, valida la reserva y registra la transición que corresponda. Las decisiones se apoyan en el estado persistido, lo que evita registrar una salida antes del ingreso o repetir una transición ya aplicada.")
    add_figure(document, 11, "Diagrama de secuencia del Sprint 3", DIAGRAMS / "4s_sprint_03_secuencia.png", "Elaboración propia en Lucidchart. Interacción representativa de recepción con controladores, servicios y persistencia.", 4.75)
    add_text(document, "La secuencia refleja que la interfaz de recepción solicita la operación, pero el controlador valida identidad, rol y estado antes de modificar la reserva. Los servicios administrativos preparan estadísticas y reportes sin mezclar su responsabilidad con el flujo transaccional de reserva.")
    add_heading(document, "3.2.3.5 Pruebas y resultado del incremento", 4)
    add_table(document,
        ["Caso", "Verificación", "Resultado esperado"],
        [
            ["CP-12", "Consulta de agenda por recepcionista.", "Listado autorizado y coherente con fecha y estado."],
            ["CP-13", "Registro presencial para un cliente.", "Reserva vinculada y validada con las reglas del dominio."],
            ["CP-14", "Check-in y check-out en secuencia válida.", "Persistencia de las transiciones y rechazo de secuencias incompatibles."],
            ["CP-15", "Gestión administrativa de usuarios.", "Validación de datos, rol y permisos."],
            ["CP-16", "Panel y exportación CSV.", "Métricas consistentes y archivo exportable con encabezados y registros."],
            ["CP-17", "Cambio de ajustes y emisión de notificación.", "Reflejo autorizado del ajuste y aviso asociado al evento."],
        ], [0.65, 2.25, 2.85])
    add_text(document, "RecepcionistaTest valida la agenda, la reserva presencial y las transiciones operativas. UserManagementTest, DashboardReportesTest, AjustesPresentacionTest y ReservaNotificationTest verifican el ámbito administrativo. El incremento integra la operación cotidiana con la supervisión sin duplicar las reglas centrales de reserva.")

    add_heading(document, "3.3 Verificación integrada y trazabilidad", 2)
    add_heading(document, "3.3.1 Estrategia de verificación", 3)
    add_text(document, "La verificación combina pruebas unitarias, funcionales y de integración. Las pruebas unitarias aíslan reglas de servicios; las pruebas funcionales ejercitan rutas, validación, autorización, persistencia y respuestas; y las pruebas de integración sobre MariaDB comprueban el comportamiento de bloqueo que depende del motor de base de datos. Las pruebas JavaScript cubren interacciones del cliente y la compilación de Vite confirma la generación de los recursos de interfaz.")
    add_text(document, "La última ejecución integral del repositorio registró 246 pruebas PHP con 907 aserciones. La comprobación aislada de concurrencia en MariaDB registró 2 pruebas con 14 aserciones. Las suites JavaScript y la compilación de producción finalizaron correctamente. Estos resultados demuestran que los tres incrementos conviven en una base de código integrada y ejecutable.")
    add_table(document,
        ["Sprint", "Capacidad", "Componentes principales", "Pruebas representativas"],
        [
            ["1", "Identidad y autorización", "Controladores Auth, User, SocialAccount, middleware y políticas", "AuthenticationTest; GoogleIntegrationTest"],
            ["2", "Recursos y reserva transaccional", "Cancha, Horario, Balón, DisponibilidadService y ReservaService", "ReservaTest; ReservaServiceTest; HorarioManagementTest"],
            ["3", "Recepción y administración", "Módulo Recepcionista, controladores Admin, EstadisticasService y ReporteService", "RecepcionistaTest; UserManagementTest; DashboardReportesTest"],
        ], [0.55, 1.35, 2.2, 1.65], 9.5)

    add_heading(document, "3.3.2 Matriz de trazabilidad", 3)
    add_table(document,
        ["Necesidad", "Historia", "Regla o componente", "Evidencia"],
        [
            ["Acceso seguro", "HU-01 a HU-05", "Auth, middleware, políticas y estado del usuario", "CP-01 a CP-05"],
            ["Disponibilidad confiable", "HU-06 a HU-08", "Cancha, Horario y DisponibilidadService", "CP-06 y pruebas de gestión"],
            ["Reserva por rango", "HU-09", "ReservaService, intervalos, transacción y lockForUpdate", "CP-07 a CP-09"],
            ["Recursos adicionales", "HU-10", "BalonService e inventario", "CP-10"],
            ["Ciclo de reserva", "HU-11 a HU-14", "Estados, cancelación, agenda y CheckinController", "CP-11 a CP-14"],
            ["Supervisión", "HU-15 a HU-17", "Administración, estadísticas, reportes, ajustes y avisos", "CP-15 a CP-17"],
        ], [1.25, 1.05, 2.25, 1.2], 9.5)
    add_text(document, "La matriz confirma que cada necesidad documentada dispone de una historia, una implementación identificable y una evidencia de prueba. Esta relación permite revisar el alcance desde el objetivo del proyecto hasta el comportamiento observable del sistema.")

    add_heading(document, "3.4 Conclusión del desarrollo documentado", 2)
    add_text(document, "Los tres sprints forman una cadena funcional completa para el núcleo del sistema. El Sprint 1 aporta identidad y control de acceso; el Sprint 2 administra los recursos y ejecuta reservas consistentes, incluso para rangos consecutivos y solicitudes concurrentes; y el Sprint 3 convierte esas capacidades en una operación diaria atendida por recepción y supervisada por administración. La arquitectura separa validación, autorización, coordinación HTTP, reglas del dominio y persistencia, lo que favorece el mantenimiento y la comprobación del sistema.")
    add_text(document, "La evidencia técnica indica que el proyecto se encuentra alineado con los alcances centrales aprobados: existe gestión diferenciada de usuarios, administración de canchas y horarios, consulta de disponibilidad, reserva y cancelación, apoyo de recursos, operación de recepción, indicadores, reportes y notificaciones. Las pruebas automatizadas y la trazabilidad presentada respaldan esa correspondencia entre documento y software.")

    add_heading(document, "REFERENCIAS", 1)
    references = [
        "Laravel. (2026). Laravel 12.x documentation. https://laravel.com/docs/12.x",
        "Object Management Group. (2017). Unified Modeling Language (UML), version 2.5.1. https://www.omg.org/spec/UML/2.5.1",
        "PHPUnit. (2026). PHPUnit 11.5 manual. https://docs.phpunit.de/en/11.5/",
        "Schwaber, K., & Sutherland, J. (2020). The Scrum Guide: The definitive guide to Scrum. https://scrumguides.org/",
    ]
    for reference in references:
        paragraph = document.add_paragraph(style="Referencia APA")
        run = paragraph.add_run(reference)
        set_repeatable_fonts(run)

    add_heading(document, "ANEXO A. ENLACES EDITABLES DE DIAGRAMAS", 1)
    add_text(document, "Los diagramas se elaboraron en Lucidchart y se incorporaron como figuras para conservar su presentación en Word. Los siguientes vínculos permiten su edición:")
    add_table(document,
        ["Contenido", "Enlace editable"],
        [
            ["Casos de uso y actividades de los Sprints 1, 2 y 3", "https://lucid.app/lucidchart/c3abc572-6f23-4265-84bc-3e0e58f5c278/edit"],
            ["Secuencia del Sprint 1", "https://lucid.app/lucidchart/0ba8c28e-460d-4d97-8411-c5797c2be14e/edit"],
            ["Secuencia del Sprint 2", "https://lucid.app/lucidchart/22d8fae6-bc9e-4fe3-9f55-59de4b012831/edit"],
            ["Secuencia del Sprint 3", "https://lucid.app/lucidchart/fe0a38b4-8aff-4ec8-80d3-d0250f00ede6/edit"],
        ], [2.35, 3.4], 9.5)

    document.core_properties.title = "Capítulo III: Ingeniería del Proyecto — Sprints 1, 2 y 3"
    document.core_properties.subject = "Sistema web para la gestión y reserva de canchas de wally"
    document.core_properties.author = "Jhesmar Fuentes"
    document.core_properties.keywords = "Scrum, Laravel, reservas, wally, UML, Sprints"
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    document.save(str(OUTPUT))
    if file_hash(REFERENCE) != EXPECTED_REFERENCE_HASH:
        raise RuntimeError("La integridad del documento aprobado no pudo confirmarse después de la generación.")
    print(f"GENERATED={OUTPUT}")
    print(f"REFERENCE_SHA256={file_hash(REFERENCE)}")


if __name__ == "__main__":
    main()
