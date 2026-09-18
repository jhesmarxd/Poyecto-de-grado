from __future__ import annotations

from datetime import date
from pathlib import Path

from docx import Document
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_ROW_HEIGHT_RULE, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

from generate_development_chapter import (
    DARK,
    GREEN,
    LIGHT_GRAY,
    MID_GRAY,
    PALE,
    WHITE,
    add_body,
    add_bullets,
    add_link,
    add_page_number,
    add_table,
    add_toc,
    set_update_fields_on_open,
    setup_document,
    shade,
)


ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "docs" / "Informe_Test_Automatizado_Wally_Reservas.docx"


def set_cell_border(cell, color=GREEN, size="18", style="dashed") -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = tc_pr.first_child_found_in("w:tcBorders")
    if borders is None:
        borders = OxmlElement("w:tcBorders")
        tc_pr.append(borders)
    for edge in ("top", "left", "bottom", "right"):
        node = borders.find(qn(f"w:{edge}"))
        if node is None:
            node = OxmlElement(f"w:{edge}")
            borders.append(node)
        node.set(qn("w:val"), style)
        node.set(qn("w:sz"), size)
        node.set(qn("w:color"), color)


def add_capture_placeholder(doc: Document, code: str, title: str, instruction: str, height_cm: float = 8.1, page_break: bool = False) -> None:
    if page_break:
        doc.add_page_break()
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    row = table.rows[0]
    row.height = Cm(height_cm)
    row.height_rule = WD_ROW_HEIGHT_RULE.EXACTLY
    cell = row.cells[0]
    cell.width = Cm(16.0)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    shade(cell, PALE)
    set_cell_border(cell)
    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(f"{code}\n\nINSERTAR CAPTURA DE PANTALLA\n\n{title}")
    r.bold = True
    r.font.name = "Aptos Display"
    r.font.size = Pt(15)
    r.font.color.rgb = RGBColor.from_string(GREEN)
    p2 = cell.add_paragraph(instruction)
    p2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p2.runs[0].font.size = Pt(9)
    p2.runs[0].font.italic = True
    p2.runs[0].font.color.rgb = RGBColor.from_string(MID_GRAY)
    caption = doc.add_paragraph(f"Captura {code}. {title}")
    caption.alignment = WD_ALIGN_PARAGRAPH.CENTER
    caption.runs[0].bold = True
    caption.runs[0].font.size = Pt(9)
    source = doc.add_paragraph("Fuente: captura propia del entorno de desarrollo o del sistema Wally Reservas.")
    source.alignment = WD_ALIGN_PARAGRAPH.CENTER
    source.runs[0].italic = True
    source.runs[0].font.size = Pt(8)
    source.runs[0].font.color.rgb = RGBColor.from_string(MID_GRAY)


def add_code(doc: Document, code: str, label: str | None = None) -> None:
    if label:
        p = doc.add_paragraph()
        p.add_run(label).bold = True
        p.paragraph_format.keep_with_next = True
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    shade(cell, "F1F5F3")
    set_cell_border(cell, color="A9C2BB", size="8", style="single")
    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    run = p.add_run(code.strip())
    run.font.name = "Consolas"
    run.font.size = Pt(8)
    run.font.color.rgb = RGBColor.from_string(DARK)
    doc.add_paragraph().paragraph_format.space_after = Pt(1)


def add_cover(doc: Document) -> None:
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(48)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("WALLY RESERVAS")
    r.bold = True
    r.font.name = "Aptos Display"
    r.font.size = Pt(33)
    r.font.color.rgb = RGBColor.from_string(GREEN)

    p = doc.add_paragraph("TAREA: IMPLEMENTACIÓN Y AUTOMATIZACIÓN DE PRUEBAS")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.runs[0].font.size = Pt(12)
    p.runs[0].font.bold = True
    p.runs[0].font.color.rgb = RGBColor.from_string(MID_GRAY)

    doc.add_paragraph().paragraph_format.space_after = Pt(25)
    title = doc.add_paragraph()
    title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = title.add_run("IMPLEMENTACIÓN DE UN TEST AUTOMATIZADO\nEN UN PROYECTO WEB")
    r.bold = True
    r.font.name = "Aptos Display"
    r.font.size = Pt(25)
    r.font.color.rgb = RGBColor.from_string(DARK)

    subtitle = doc.add_paragraph("Caso de estudio: creación segura de reservas de canchas\nFramework: Laravel 12 · Herramienta: PHPUnit 11")
    subtitle.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for run in subtitle.runs:
        run.font.size = Pt(12)
        run.font.color.rgb = RGBColor.from_string(GREEN)

    doc.add_paragraph().paragraph_format.space_after = Pt(30)
    add_table(doc, ["Dato", "Información editable"], [
        ("Estudiante", "[Nombre completo]"),
        ("Materia", "[Nombre de la materia]"),
        ("Docente", "[Nombre del docente]"),
        ("Institución", "[Nombre de la institución]"),
        ("Curso / Paralelo", "[Curso o paralelo]"),
        ("Fecha de entrega", "[dd/mm/aaaa]"),
        ("Versión del informe", "1.0"),
    ])
    note = doc.add_paragraph("Los recuadros verdes están preparados para reemplazarse por capturas propias. Deben borrarse al insertar cada imagen y conservarse el título y la fuente.")
    note.alignment = WD_ALIGN_PARAGRAPH.CENTER
    note.runs[0].font.italic = True
    note.runs[0].font.size = Pt(9)
    doc.add_page_break()


def add_document_guide(doc: Document) -> None:
    doc.add_heading("Control y uso del documento", level=1)
    add_table(doc, ["Versión", "Fecha", "Cambio", "Responsable"], [
        ("1.0", date.today().strftime("%d/%m/%Y"), "Documento inicial basado en pruebas reales de Wally Reservas.", "[Estudiante]"),
    ])
    doc.add_heading("Cómo completar las capturas", level=2)
    add_bullets(doc, [
        "Inserte la captura indicada dentro del recuadro correspondiente y elimine el texto de marcador.",
        "Recorte la imagen para que se vea el dato relevante; evite mostrar contraseñas, tokens, cookies o claves.",
        "Mantenga visible la barra de direcciones o el comando de terminal cuando sea necesario demostrar el contexto.",
        "No altere manualmente el resultado de PHPUnit: la evidencia debe corresponder a una ejecución real.",
        "Actualice la tabla de contenido de Word con clic derecho → Actualizar campo.",
    ])
    doc.add_heading("Índice", level=1)
    p = doc.add_paragraph()
    add_toc(p)
    doc.add_page_break()


def add_intro(doc: Document) -> None:
    doc.add_heading("Introducción", level=1)
    add_body(doc, "El presente informe demuestra la configuración, diseño, ejecución y análisis de una prueba automatizada en Wally Reservas, aplicación web desarrollada con Laravel. Se eligió el módulo de reservas porque concentra reglas críticas: autenticación, disponibilidad, cálculo de monto, persistencia, propiedad de los datos y prevención de duplicidad.")
    add_body(doc, "La automatización se realiza con PHPUnit integrado en Laravel. Las pruebas Feature simulan solicitudes HTTP y verifican respuestas, sesión y base de datos sin requerir interacción manual con un navegador. Para asegurar aislamiento se utiliza SQLite en memoria y RefreshDatabase.")
    doc.add_heading("Objetivo general", level=2)
    add_body(doc, "Implementar y documentar pruebas automatizadas que comprueben el flujo de reservas de Wally Reservas de manera repetible, eficiente y trazable.")
    doc.add_heading("Objetivos específicos", level=2)
    add_bullets(doc, [
        "Configurar correctamente PHP, Composer, Laravel y PHPUnit.",
        "Diseñar casos positivos, negativos, de autorización y de integridad.",
        "Ejecutar una prueba focalizada y la clase completa de reservas.",
        "Comparar resultados esperados y obtenidos.",
        "Registrar evidencias visuales y conclusiones reproducibles.",
    ])
    doc.add_heading("Alcance de la evidencia", level=2)
    add_table(doc, ["Elemento", "Alcance"], [
        ("Sistema", "Wally Reservas, proyecto Laravel ubicado en el entorno local XAMPP."),
        ("Módulo bajo prueba", "Disponibilidad, creación, validación, cancelación y autorización de reservas."),
        ("Tipo principal", "Pruebas Feature automatizadas con acceso a rutas y base de datos de prueba."),
        ("Archivo", "tests/Feature/ReservaTest.php"),
        ("Resultado focal", "20 pruebas aprobadas, 48 aserciones, 7,27 s en la ejecución documentada."),
    ])


def point_1(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("1. Correcta configuración de la herramienta de automatización", level=1)
    add_body(doc, "La automatización depende de un entorno coherente. El repositorio declara PHP ^8.2, Laravel ^12 y PHPUnit ^11.5. En el equipo utilizado se verificaron PHP 8.2.12, Laravel 12.66.0 y PHPUnit 11.5.56. Composer gestiona las dependencias y Artisan ofrece el comando de ejecución.")
    add_table(doc, ["Componente", "Versión verificada", "Función"], [
        ("PHP", "8.2.12", "Ejecuta Laravel y PHPUnit."),
        ("Composer", "2.x", "Instala dependencias y genera autoload."),
        ("Laravel", "12.66.0", "Framework y utilidades de pruebas HTTP/BD."),
        ("PHPUnit", "11.5.56", "Descubrimiento, ejecución, aserciones y reporte."),
        ("SQLite", "En memoria", "Base aislada y rápida para cada ejecución."),
    ])
    doc.add_heading("1.1 Preparación reproducible", level=2)
    add_code(doc, """
composer install
php artisan key:generate
php artisan migrate
php artisan --version
php vendor/bin/phpunit --version
""", "Comandos de instalación y verificación:")
    add_body(doc, "En Windows también puede ejecutarse `vendor\\bin\\phpunit`. El comando recomendado del proyecto es `php artisan test`, porque carga la integración de Laravel y presenta una salida legible.")
    add_capture_placeholder(doc, "CAP-01", "Versiones de PHP, Laravel y PHPUnit", "Ejecute: php -v, php artisan --version y php vendor/bin/phpunit --version. Muestre las versiones completas.")
    add_capture_placeholder(doc, "CAP-02", "Dependencias instaladas correctamente", "Capture la terminal después de composer install o composer show phpunit/phpunit. No muestre credenciales.", page_break=True)
    doc.add_heading("1.2 Criterio de comprobación", level=2)
    add_table(doc, ["Comprobación", "Resultado esperado"], [
        ("Autoload", "vendor/autoload.php existe y PHPUnit inicia sin errores de clase."),
        ("Artisan", "Laravel reconoce el proyecto y muestra su versión."),
        ("Base de prueba", "Las migraciones pueden ejecutarse sobre SQLite en memoria."),
        ("Aislamiento", "No se utilizan datos ni credenciales de producción."),
    ])


def point_2(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("2. Funcionalidad y eficiencia del script desarrollado", level=1)
    add_body(doc, "El test principal sigue el patrón Preparar–Ejecutar–Verificar (Arrange–Act–Assert). Crea datos mínimos con factories, autentica un cliente, envía una petición POST real al endpoint de reservas y comprueba tanto la redirección como los valores persistidos.")
    add_code(doc, """
public function test_un_cliente_puede_crear_una_reserva(): void
{
    $fecha = now()->addDays(3)->toDateString();
    [$cancha, $horario] = $this->canchaConHorario($fecha);

    $balon = Balon::create([
        'tipo' => Balon::TIPO_ECONOMICO,
        'marca' => 'Asatex',
        'nombre' => '1',
        'precio_por_hora' => 25.00,
    ]);

    $this->actingAs($this->cliente())
        ->post('/reservas', [
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'balon_id' => $balon->id,
            'observaciones' => 'Partido amistoso',
        ])
        ->assertRedirect(route('reservas.mis'));

    $this->assertDatabaseHas('reservas', [
        'cancha_id' => $cancha->id,
        'horario_id' => $horario->id,
        'estado' => Reserva::ESTADO_PENDIENTE,
        'balon_id' => $balon->id,
        'monto_total' => 25.00,
    ]);
}
""", "Script automatizado seleccionado (extracto real):")
    doc.add_heading("2.1 Análisis funcional", level=2)
    add_table(doc, ["Fase", "Acción", "Validación"], [
        ("Preparar", "Genera fecha futura, cancha, horario, balón y cliente.", "Datos independientes y válidos."),
        ("Ejecutar", "Autentica al cliente y envía POST /reservas.", "Recorre ruta, request, policy, controlador, servicio y modelo."),
        ("Verificar HTTP", "Comprueba redirección a Mis reservas.", "El flujo fue aceptado."),
        ("Verificar BD", "Busca cancha, horario, estado, balón y monto.", "La reserva se persistió con valores confiables."),
    ])
    doc.add_heading("2.2 Eficiencia", level=2)
    add_bullets(doc, [
        "SQLite `:memory:` reduce E/S y evita usar la base operativa.",
        "RefreshDatabase garantiza limpieza y repetibilidad.",
        "Factories eliminan preparación manual y hacen los datos expresivos.",
        "El helper canchaConHorario evita duplicación dentro de la clase.",
        "El filtro de PHPUnit permite ejecutar solo el caso durante el desarrollo.",
        "Las aserciones verifican comportamiento externo; no dependen de detalles visuales frágiles.",
    ])
    add_capture_placeholder(doc, "CAP-03", "Código del test en el editor", "Muestre ReservaTest.php con el nombre del método, actingAs(), post(), assertRedirect() y assertDatabaseHas().", page_break=True)
    add_capture_placeholder(doc, "CAP-04", "Formulario de reserva del sistema", "Inicie sesión como cliente y capture cancha, fecha, horario y modalidad de balón antes de confirmar.", page_break=True)


def point_3(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("3. Precisión en la documentación y análisis de resultados", level=1)
    add_body(doc, "La ejecución focalizada se realizó con la clase completa para evitar que un caso aislado ocultara regresiones del mismo módulo.")
    add_code(doc, "php artisan test tests\\Feature\\ReservaTest.php", "Comando ejecutado:")
    add_table(doc, ["Indicador", "Resultado observado", "Interpretación"], [
        ("Clase", "Tests\\Feature\\ReservaTest", "Se descubrió el archivo correcto."),
        ("Pruebas", "20 aprobadas", "Todos los escenarios del módulo finalizaron correctamente."),
        ("Aserciones", "48", "Se verificaron respuestas, errores, permisos y persistencia."),
        ("Duración", "7,27 segundos", "Tiempo adecuado para ejecución local focalizada."),
        ("Resultado", "PASS", "El módulo cumple los resultados definidos por estos casos."),
    ])
    add_capture_placeholder(doc, "CAP-05", "Ejecución aprobada de ReservaTest", "Capture desde el comando hasta la línea final: Tests: 20 passed (48 assertions), Duration: 7.27s.")
    doc.add_heading("3.1 Lectura de los resultados", level=2)
    add_body(doc, "Un resultado PASS indica que los datos preparados produjeron exactamente las respuestas y cambios esperados. No demuestra ausencia total de defectos: su alcance se limita a los escenarios automatizados y al entorno de prueba configurado.")
    add_body(doc, "La suite completa del proyecto, ejecutada adicionalmente, registró 143 pruebas aprobadas y 2 fallidas (413 aserciones). Ambas fallas se ubican en VisualThemeTest y corresponden a expectativas de texto/orden del login que no coinciden con la vista actual. El hallazgo no invalida ReservaTest, pero debe registrarse y corregirse antes de declarar la regresión global completamente verde.")
    add_table(doc, ["Hallazgo", "Severidad", "Causa probable", "Acción propuesta"], [
        ("VisualThemeTest espera “Entrar a la cancha”", "Baja", "El texto visible cambió a “Wally Canchas”.", "Definir el copy oficial y alinear vista o prueba."),
        ("Orden/separador de Google no coincide", "Media", "El markup actual no conserva el orden esperado por la prueba.", "Colocar registro antes del bloque Google y restaurar texto accesible del separador, o actualizar requisito/prueba."),
        ("Advertencia de OpenSSL duplicado", "Baja", "La extensión se carga más de una vez en PHP CLI.", "Revisar php.ini y archivos adicionales cargados; mantener una sola declaración."),
    ])
    add_capture_placeholder(doc, "CAP-06", "Resultado de la suite completa", "Ejecute php artisan test y capture el resumen final. La imagen debe mostrar pruebas aprobadas/fallidas y número de aserciones.", page_break=True)


def point_4(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("4. Correcta configuración de la herramienta de pruebas", level=1)
    add_body(doc, "La configuración reside en phpunit.xml. Se separan suites Unit y Feature, se carga vendor/autoload.php y se aísla el ambiente mediante variables específicas de testing.")
    add_code(doc, """
<phpunit bootstrap="vendor/autoload.php" colors="true">
  <testsuites>
    <testsuite name="Unit"><directory>tests/Unit</directory></testsuite>
    <testsuite name="Feature"><directory>tests/Feature</directory></testsuite>
  </testsuites>
  <source><include><directory>app</directory></include></source>
  <php>
    <env name="APP_ENV" value="testing"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="GOOGLE_CALENDAR_ENABLED" value="false" force="true"/>
  </php>
</phpunit>
""", "Configuración relevante (resumen de phpunit.xml):")
    add_table(doc, ["Parámetro", "Valor", "Justificación"], [
        ("APP_ENV", "testing", "Activa comportamiento específico de pruebas."),
        ("DB_CONNECTION", "sqlite", "Motor liviano e independiente."),
        ("DB_DATABASE", ":memory:", "Cada proceso trabaja con una base temporal."),
        ("BCRYPT_ROUNDS", "4", "Reduce tiempo de hashing sin afectar producción."),
        ("CACHE_STORE", "array", "Evita dependencia de servicios externos."),
        ("SESSION_DRIVER", "array", "Mantiene sesiones efímeras."),
        ("QUEUE_CONNECTION", "sync", "Hace determinista la ejecución de jobs cuando corresponda."),
        ("GOOGLE_CALENDAR_ENABLED", "false", "Impide llamadas reales durante la regresión."),
    ])
    add_capture_placeholder(doc, "CAP-07", "Configuración phpunit.xml", "Capture el archivo mostrando testsuites y las variables APP_ENV, DB_CONNECTION y DB_DATABASE.", page_break=True)
    doc.add_heading("4.1 Comandos de ejecución", level=2)
    add_code(doc, """
# Caso individual
php artisan test --filter=test_un_cliente_puede_crear_una_reserva

# Módulo de reservas
php artisan test tests\\Feature\\ReservaTest.php

# Suite completa
php artisan test

# Alternativa mediante Composer
composer test
""")
    add_capture_placeholder(doc, "CAP-08", "Ejecución de un único caso con --filter", "Capture el comando con --filter y el resumen PASS de una prueba.")


def point_5(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("5. Precisión en el diseño y ejecución de casos de prueba", level=1)
    add_body(doc, "El diseño utiliza particiones equivalentes: flujo válido, dato inexistente, fecha inválida, recurso ocupado, permisos insuficientes y transición de estado no permitida. Esto evita limitar la evaluación al camino feliz.")
    cases = [
        ("CP-01", "Disponibilidad libre", "Cancha y horario sin reserva", "GET /disponibilidad", "200 y franja 18:00 visible", "PASS"),
        ("CP-02", "Ocultar franja ocupada", "Reserva activa existente", "Consultar disponibilidad", "Horario no se incluye como libre", "PASS"),
        ("CP-03", "Crear reserva válida", "Cliente, cancha, horario y balón válidos", "POST /reservas", "Redirección y registro pendiente", "PASS"),
        ("CP-04", "Balón inexistente", "balon_id no registrado", "POST /reservas", "Error de validación y cero reservas", "PASS"),
        ("CP-05", "Balón ya ocupado", "Mismo balón reservado en igual fecha/hora", "Intentar reservar en otra cancha", "HTTP 409 y un solo registro", "PASS"),
        ("CP-06", "Franja ya ocupada", "Cancha/horario/fecha reservados", "Intentar duplicar", "HTTP 409", "PASS"),
        ("CP-07", "Protección en base de datos", "Duplicado activo directo", "Insertar segundo registro", "QueryException por índice único", "PASS"),
        ("CP-08", "Fecha pasada", "Fecha anterior a hoy", "POST /reservas", "Error en fecha y cero registros", "PASS"),
        ("CP-09", "Día incompatible", "Fecha no coincide con horario", "POST /reservas", "HTTP 422", "PASS"),
        ("CP-10", "Cancelar reserva propia", "Propietario y reserva pendiente", "POST cancelar", "Estado cancelado y franja liberada", "PASS"),
        ("CP-11", "Cancelar reserva ajena", "Otro cliente es propietario", "POST cancelar", "HTTP 403 y estado intacto", "PASS"),
        ("CP-12", "Acceso administrativo", "Cliente sin rol admin", "GET panel/calendario admin", "HTTP 403", "PASS"),
    ]
    add_table(doc, ["ID", "Escenario", "Precondición", "Acción", "Resultado esperado", "Estado"], cases)

    doc.add_heading("5.1 Caso detallado CP-03", level=2)
    add_table(doc, ["Campo", "Definición"], [
        ("Nombre", "Creación de una reserva válida por un cliente autenticado."),
        ("Objetivo", "Comprobar el flujo HTTP y la persistencia confiable de la reserva."),
        ("Prioridad", "Alta; corresponde a la operación principal del negocio."),
        ("Precondiciones", "Usuario cliente activo; cancha y horario activos; fecha futura; balón disponible."),
        ("Datos", "Horario 18:00–19:00; balón económico Asatex; precio Bs 25; observación Partido amistoso."),
        ("Pasos", "1) Autenticar cliente. 2) Enviar POST. 3) seguir/validar redirección. 4) consultar la tabla reservas."),
        ("Esperado", "Redirección a reservas.mis; estado pendiente; claves foráneas correctas; monto total 25.00."),
        ("Limpieza", "RefreshDatabase revierte la base de pruebas para el siguiente caso."),
    ])
    add_capture_placeholder(doc, "CAP-09", "Disponibilidad previa a la reserva", "Capture la pantalla pública o del cliente mostrando la fecha y la franja libre que utilizará en la prueba.", page_break=True)
    add_capture_placeholder(doc, "CAP-10", "Reserva creada en Mis reservas", "Después de reservar manualmente, capture la tarjeta/fila con cancha, fecha, horario, estado y monto; oculte datos personales.", page_break=True)
    add_capture_placeholder(doc, "CAP-11", "Validación de conflicto o franja ocupada", "Capture el mensaje mostrado al intentar reservar nuevamente la misma cancha, fecha y horario.", page_break=True)
    add_capture_placeholder(doc, "CAP-12", "Cancelación y liberación del horario", "Capture el estado Cancelada o la franja nuevamente disponible después de cancelar.", page_break=True)


def point_6(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("6. Calidad y claridad en la documentación de resultados", level=1)
    add_body(doc, "Los resultados se presentan con identificación, fecha, entorno, datos, comando, resultado esperado, resultado real, estado y evidencia. Esta estructura permite que otra persona repita la prueba y comprenda un fallo sin revisar toda la implementación.")
    add_table(doc, ["Campo de reporte", "Contenido de esta ejecución"], [
        ("Identificador", "EJ-RES-001"),
        ("Fecha", date.today().strftime("%d/%m/%Y")),
        ("Entorno", "Windows + XAMPP; PHP 8.2.12; Laravel 12.66.0; PHPUnit 11.5.56."),
        ("Comando", "php artisan test tests\\Feature\\ReservaTest.php"),
        ("Resultado esperado", "Todos los casos de ReservaTest aprobados."),
        ("Resultado real", "20 passed; 48 assertions; 7.27s."),
        ("Estado", "APROBADO para el alcance del módulo."),
        ("Observación", "La suite global conserva dos fallos de presentación en VisualThemeTest, registrados por separado."),
    ])
    doc.add_heading("6.1 Criterios de calidad aplicados", level=2)
    add_bullets(doc, [
        "Títulos y códigos únicos para cruzar casos, ejecuciones y capturas.",
        "Diferenciación explícita entre resultado del módulo y estado global del proyecto.",
        "Resultados numéricos reproducibles, sin afirmar que una prueba cubre escenarios no diseñados.",
        "Registro de advertencias y fallos aunque no pertenezcan al caso focal.",
        "Protección de datos sensibles en imágenes y anexos.",
        "Conclusiones sustentadas en aserciones, no solo en observación visual.",
    ])
    add_capture_placeholder(doc, "CAP-13", "Resumen final limpio de la prueba automatizada", "Capture nuevamente el resumen focal después de corregir cualquier advertencia de entorno. Debe verse PASS y el comando completo.")
    add_capture_placeholder(doc, "CAP-14", "Evidencia final del sistema operativo", "Capture una vista general del módulo de reservas funcionando, preferiblemente Mis reservas o agenda administrativa.", page_break=True)


def add_conclusion(doc: Document) -> None:
    doc.add_page_break()
    doc.add_heading("Conclusiones", level=1)
    add_body(doc, "La configuración de PHPUnit integrada con Laravel permite ejecutar pruebas repetibles, rápidas y aisladas. El uso de SQLite en memoria, RefreshDatabase, factories y aserciones HTTP/BD reduce la preparación manual y protege los datos operativos.")
    add_body(doc, "La prueba seleccionada verifica una capacidad central del sistema y la clase completa amplía la cobertura hacia conflictos, fechas, balones, cancelaciones y permisos. El resultado de 20 pruebas aprobadas y 48 aserciones sustenta el comportamiento esperado del módulo en el entorno documentado.")
    add_body(doc, "La ejecución global también permitió identificar dos discrepancias entre VisualThemeTest y el login actual. Reportarlas separadamente demuestra que un informe de calidad debe comunicar tanto los éxitos como la deuda pendiente. Tras alinear las expectativas visuales, se recomienda repetir la suite y sustituir CAP-06 por una captura completamente verde.")

    doc.add_heading("Recomendaciones", level=1)
    add_bullets(doc, [
        "Corregir o actualizar las dos expectativas de VisualThemeTest según el requisito definitivo del login.",
        "Eliminar la carga duplicada de OpenSSL en la configuración PHP CLI para obtener una salida limpia.",
        "Ejecutar ReservaTest en cada cambio del módulo y la suite completa antes de integrar código.",
        "Incorporar el comando `composer test` a integración continua cuando exista un repositorio remoto.",
        "Conservar capturas fechadas solo como evidencia complementaria; el resultado automatizado es la fuente principal.",
    ])


def add_refs_and_annex(doc: Document) -> None:
    doc.add_heading("Referencias", level=1)
    refs = [
        ("Laravel 12: Testing", "https://laravel.com/docs/12.x/testing"),
        ("Laravel 12: Database Testing", "https://laravel.com/docs/12.x/database-testing"),
        ("Laravel 12: HTTP Tests", "https://laravel.com/docs/12.x/http-tests"),
        ("PHPUnit 11.5 Documentation", "https://docs.phpunit.de/en/11.5/"),
        ("Composer: Command-line interface", "https://getcomposer.org/doc/03-cli.md"),
    ]
    for idx, (title, url) in enumerate(refs, 1):
        p = doc.add_paragraph()
        p.add_run(f"[{idx}] {title}. ").bold = True
        add_link(p, url, url)

    doc.add_heading("Fuentes internas", level=2)
    add_table(doc, ["Archivo", "Evidencia utilizada"], [
        ("composer.json", "Versiones requeridas y script composer test."),
        ("phpunit.xml", "Suites, bootstrap y entorno aislado."),
        ("tests/TestCase.php", "Clase base de pruebas Laravel."),
        ("tests/Feature/ReservaTest.php", "Casos y script automatizado principal."),
        ("app/Services/ReservaService.php", "Reglas de disponibilidad, monto y estado."),
        ("routes/cliente.php y routes/admin.php", "Endpoints y controles de acceso."),
    ])

    doc.add_heading("Anexo A. Lista de capturas pendientes", level=1)
    checklist = [
        ("CAP-01", "Versiones de herramientas", "Pendiente"),
        ("CAP-02", "Dependencias instaladas", "Pendiente"),
        ("CAP-03", "Código en el editor", "Pendiente"),
        ("CAP-04", "Formulario de reserva", "Pendiente"),
        ("CAP-05", "ReservaTest aprobado", "Pendiente"),
        ("CAP-06", "Suite completa", "Pendiente"),
        ("CAP-07", "phpunit.xml", "Pendiente"),
        ("CAP-08", "Caso filtrado", "Pendiente"),
        ("CAP-09", "Disponibilidad previa", "Pendiente"),
        ("CAP-10", "Reserva creada", "Pendiente"),
        ("CAP-11", "Conflicto", "Pendiente"),
        ("CAP-12", "Cancelación", "Pendiente"),
        ("CAP-13", "Resumen final", "Pendiente"),
        ("CAP-14", "Vista operativa final", "Pendiente"),
    ]
    add_table(doc, ["Código", "Evidencia", "Estado"], checklist)
    add_body(doc, "Antes de entregar: completar portada, insertar las 14 capturas, actualizar el estado de la lista, actualizar la tabla de contenido y revisar que ninguna imagen exponga información sensible.")


def main() -> None:
    doc = setup_document()
    header = doc.sections[0].header.paragraphs[0]
    header.text = "WALLY RESERVAS  |  INFORME DE PRUEBAS AUTOMATIZADAS"
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    for run in header.runs:
        run.font.name = "Aptos"
        run.font.size = Pt(8)
        run.font.bold = True
        run.font.color.rgb = RGBColor.from_string(GREEN)
    # setup_document ya configura el pie; se conserva la numeración automática.

    add_cover(doc)
    add_document_guide(doc)
    add_intro(doc)
    point_1(doc)
    point_2(doc)
    point_3(doc)
    point_4(doc)
    point_5(doc)
    point_6(doc)
    add_conclusion(doc)
    add_refs_and_annex(doc)
    set_update_fields_on_open(doc)
    OUT.parent.mkdir(parents=True, exist_ok=True)
    doc.save(OUT)
    print(f"Documento generado: {OUT}")
    print("Marcadores de captura: 14")


if __name__ == "__main__":
    main()
