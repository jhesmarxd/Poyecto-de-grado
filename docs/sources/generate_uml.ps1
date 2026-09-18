Add-Type -AssemblyName System.Drawing

$ErrorActionPreference = 'Stop'
$outputDirectory = Join-Path $PSScriptRoot '..\uml\generated'
New-Item -ItemType Directory -Force -Path $outputDirectory | Out-Null

$script:Ink = [System.Drawing.ColorTranslator]::FromHtml('#12352F')
$script:Green = [System.Drawing.ColorTranslator]::FromHtml('#006B57')
$script:Mint = [System.Drawing.ColorTranslator]::FromHtml('#78F0B4')
$script:Pale = [System.Drawing.ColorTranslator]::FromHtml('#EAF7F1')
$script:Blue = [System.Drawing.ColorTranslator]::FromHtml('#DDEFFC')
$script:Amber = [System.Drawing.ColorTranslator]::FromHtml('#FFF1CF')
$script:Rose = [System.Drawing.ColorTranslator]::FromHtml('#FCE4E4')
$script:Gray = [System.Drawing.ColorTranslator]::FromHtml('#63736F')
$script:Line = [System.Drawing.ColorTranslator]::FromHtml('#A9C2BB')
$script:White = [System.Drawing.Color]::White

function New-Font([float]$size, [System.Drawing.FontStyle]$style = [System.Drawing.FontStyle]::Regular) {
    return New-Object System.Drawing.Font('Segoe UI', $size, $style, [System.Drawing.GraphicsUnit]::Pixel)
}

function New-Canvas([string]$title, [string]$subtitle, [int]$width = 1800, [int]$height = 1050) {
    $bitmap = New-Object System.Drawing.Bitmap($width, $height)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit
    $graphics.Clear($script:White)
    $graphics.FillRectangle((New-Object System.Drawing.SolidBrush($script:Green)), 0, 0, $width, 16)
    $graphics.DrawString($title, (New-Font 34 ([System.Drawing.FontStyle]::Bold)), (New-Object System.Drawing.SolidBrush($script:Ink)), 54, 36)
    $graphics.DrawString($subtitle, (New-Font 19), (New-Object System.Drawing.SolidBrush($script:Gray)), 56, 84)
    return @{ Bitmap = $bitmap; Graphics = $graphics; Width = $width; Height = $height }
}

function Get-RoundedPath([System.Drawing.RectangleF]$rect, [float]$radius) {
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $diameter = $radius * 2
    $path.AddArc($rect.X, $rect.Y, $diameter, $diameter, 180, 90)
    $path.AddArc($rect.Right - $diameter, $rect.Y, $diameter, $diameter, 270, 90)
    $path.AddArc($rect.Right - $diameter, $rect.Bottom - $diameter, $diameter, $diameter, 0, 90)
    $path.AddArc($rect.X, $rect.Bottom - $diameter, $diameter, $diameter, 90, 90)
    $path.CloseFigure()
    return $path
}

function Draw-RoundedBox($g, [float]$x, [float]$y, [float]$w, [float]$h, [System.Drawing.Color]$fill, [System.Drawing.Color]$stroke, [float]$radius = 18, [float]$strokeWidth = 2) {
    $rect = New-Object System.Drawing.RectangleF($x, $y, $w, $h)
    $path = Get-RoundedPath $rect $radius
    $g.FillPath((New-Object System.Drawing.SolidBrush($fill)), $path)
    $g.DrawPath((New-Object System.Drawing.Pen($stroke, $strokeWidth)), $path)
    $path.Dispose()
}

function Draw-CenteredText($g, [string]$text, [float]$x, [float]$y, [float]$w, [float]$h, [float]$size = 19, [System.Drawing.Color]$color = $script:Ink, [System.Drawing.FontStyle]$style = [System.Drawing.FontStyle]::Regular) {
    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = [System.Drawing.StringAlignment]::Center
    $format.LineAlignment = [System.Drawing.StringAlignment]::Center
    $format.Trimming = [System.Drawing.StringTrimming]::EllipsisWord
    $format.FormatFlags = [System.Drawing.StringFormatFlags]::LineLimit
    $g.DrawString($text, (New-Font $size $style), (New-Object System.Drawing.SolidBrush($color)), (New-Object System.Drawing.RectangleF($x, $y, $w, $h)), $format)
    $format.Dispose()
}

function Draw-Arrow($g, [float]$x1, [float]$y1, [float]$x2, [float]$y2, [System.Drawing.Color]$color = $script:Green, [float]$width = 3, [bool]$dashed = $false) {
    $pen = New-Object System.Drawing.Pen($color, $width)
    if ($dashed) { $pen.DashStyle = [System.Drawing.Drawing2D.DashStyle]::Dash }
    $cap = New-Object System.Drawing.Drawing2D.AdjustableArrowCap(6, 7)
    $pen.CustomEndCap = $cap
    $g.DrawLine($pen, $x1, $y1, $x2, $y2)
    $pen.Dispose()
    $cap.Dispose()
}

function Draw-Line($g, [float]$x1, [float]$y1, [float]$x2, [float]$y2, [System.Drawing.Color]$color = $script:Line, [float]$width = 2, [bool]$dashed = $false) {
    $pen = New-Object System.Drawing.Pen($color, $width)
    if ($dashed) { $pen.DashStyle = [System.Drawing.Drawing2D.DashStyle]::Dash }
    $g.DrawLine($pen, $x1, $y1, $x2, $y2)
    $pen.Dispose()
}

function Draw-Actor($g, [float]$x, [float]$y, [string]$name, [System.Drawing.Color]$color = $script:Green) {
    $pen = New-Object System.Drawing.Pen($color, 4)
    $g.DrawEllipse($pen, $x + 25, $y, 34, 34)
    $g.DrawLine($pen, $x + 42, $y + 34, $x + 42, $y + 105)
    $g.DrawLine($pen, $x + 5, $y + 58, $x + 79, $y + 58)
    $g.DrawLine($pen, $x + 42, $y + 105, $x + 10, $y + 145)
    $g.DrawLine($pen, $x + 42, $y + 105, $x + 74, $y + 145)
    Draw-CenteredText $g $name ($x - 35) ($y + 148) 155 66 18 $script:Ink ([System.Drawing.FontStyle]::Bold)
    $pen.Dispose()
}

function Save-Canvas($canvas, [string]$fileName) {
    $path = Join-Path $outputDirectory $fileName
    $canvas.Bitmap.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
    $canvas.Graphics.Dispose()
    $canvas.Bitmap.Dispose()
}

function New-UseCaseDiagram([string]$fileName, [string]$title, [string[]]$leftActors, [string[]]$rightActors, [string[]]$useCases) {
    $c = New-Canvas $title 'Diagrama UML de casos de uso'
    $g = $c.Graphics
    Draw-RoundedBox $g 325 145 1150 820 $script:Pale $script:Green 28 3
    Draw-CenteredText $g 'Sistema Wally Reservas' 650 157 500 45 22 $script:Green ([System.Drawing.FontStyle]::Bold)

    $leftStep = [Math]::Max(210, [Math]::Floor(690 / [Math]::Max(1, $leftActors.Count)))
    for ($i = 0; $i -lt $leftActors.Count; $i++) {
        $y = 240 + ($i * $leftStep)
        Draw-Actor $g 105 $y $leftActors[$i]
        Draw-Line $g 184 ($y + 70) 405 (270 + ($i * 120)) $script:Line 2
    }
    $rightStep = [Math]::Max(210, [Math]::Floor(690 / [Math]::Max(1, $rightActors.Count)))
    for ($i = 0; $i -lt $rightActors.Count; $i++) {
        $y = 240 + ($i * $rightStep)
        Draw-Actor $g 1610 $y $rightActors[$i]
        Draw-Line $g 1475 (270 + ($i * 120)) 1615 ($y + 70) $script:Line 2
    }

    $columns = if ($useCases.Count -gt 5) { 2 } else { 1 }
    $rows = [Math]::Ceiling($useCases.Count / $columns)
    $boxW = if ($columns -eq 2) { 450 } else { 570 }
    $xStart = if ($columns -eq 2) { 445 } else { 615 }
    $yStep = [Math]::Min(135, [Math]::Floor(680 / [Math]::Max(1, $rows)))
    for ($i = 0; $i -lt $useCases.Count; $i++) {
        $col = [Math]::Floor($i / $rows)
        $row = $i % $rows
        $x = $xStart + ($col * 515)
        $y = 235 + ($row * $yStep)
        $ellipseRect = New-Object System.Drawing.RectangleF($x, $y, $boxW, 84)
        $g.FillEllipse((New-Object System.Drawing.SolidBrush($script:White)), $ellipseRect)
        $g.DrawEllipse((New-Object System.Drawing.Pen($script:Green, 2.5)), $ellipseRect)
        Draw-CenteredText $g $useCases[$i] $x $y $boxW 84 18 $script:Ink
    }
    Save-Canvas $c $fileName
}

function New-ActivityDiagram([string]$fileName, [string]$title, [string[]]$lanes, [string[]]$steps, [int[]]$stepLanes) {
    $c = New-Canvas $title 'Diagrama UML de actividades con carriles'
    $g = $c.Graphics
    $laneX = 70
    $laneY = 145
    $laneW = 1660 / $lanes.Count
    $laneH = 830
    for ($i = 0; $i -lt $lanes.Count; $i++) {
        $x = $laneX + ($i * $laneW)
        $fill = if (($i % 2) -eq 0) { $script:Pale } else { $script:White }
        $g.FillRectangle((New-Object System.Drawing.SolidBrush($fill)), $x, $laneY, $laneW, $laneH)
        $g.DrawRectangle((New-Object System.Drawing.Pen($script:Line, 2)), $x, $laneY, $laneW, $laneH)
        $g.FillRectangle((New-Object System.Drawing.SolidBrush($script:Green)), $x, $laneY, $laneW, 56)
        Draw-CenteredText $g $lanes[$i] $x $laneY $laneW 56 19 $script:White ([System.Drawing.FontStyle]::Bold)
    }
    $startX = $laneX + ($laneW / 2)
    $g.FillEllipse((New-Object System.Drawing.SolidBrush($script:Ink)), $startX - 13, 220, 26, 26)
    $previousX = $startX
    $previousY = 246
    $stepGap = [Math]::Floor(630 / [Math]::Max(1, $steps.Count))
    $stepGap = [Math]::Max(82, [Math]::Min(118, $stepGap))
    for ($i = 0; $i -lt $steps.Count; $i++) {
        $lane = $stepLanes[$i]
        $x = $laneX + ($lane * $laneW) + 34
        $y = 274 + ($i * $stepGap)
        $w = $laneW - 68
        Draw-Arrow $g $previousX $previousY ($x + ($w / 2)) $y $script:Green 3
        Draw-RoundedBox $g $x $y $w 64 $script:White $script:Green 17 2.5
        Draw-CenteredText $g $steps[$i] $x $y $w 64 17 $script:Ink
        $previousX = $x + ($w / 2)
        $previousY = $y + 64
    }
    $endY = [Math]::Min(934, $previousY + 38)
    Draw-Arrow $g $previousX $previousY $previousX ($endY - 13) $script:Green 3
    $g.FillEllipse((New-Object System.Drawing.SolidBrush($script:White)), $previousX - 17, $endY - 17, 34, 34)
    $g.DrawEllipse((New-Object System.Drawing.Pen($script:Ink, 4)), $previousX - 17, $endY - 17, 34, 34)
    $g.FillEllipse((New-Object System.Drawing.SolidBrush($script:Ink)), $previousX - 10, $endY - 10, 20, 20)
    Save-Canvas $c $fileName
}

function New-SequenceDiagram([string]$fileName, [string]$title, [string[]]$participants, [string[]]$from, [string[]]$to, [string[]]$messages) {
    $c = New-Canvas $title 'Diagrama UML de secuencia'
    $g = $c.Graphics
    $left = 90
    $usable = 1620
    $step = if ($participants.Count -gt 1) { $usable / ($participants.Count - 1) } else { 0 }
    $positions = @{}
    for ($i = 0; $i -lt $participants.Count; $i++) {
        $x = $left + ($i * $step)
        $positions[$participants[$i]] = $x
        Draw-RoundedBox $g ($x - 95) 150 190 64 $script:Pale $script:Green 15 2.5
        Draw-CenteredText $g $participants[$i] ($x - 95) 150 190 64 17 $script:Ink ([System.Drawing.FontStyle]::Bold)
        Draw-Line $g $x 214 $x 955 $script:Line 2 $true
    }
    $msgStep = [Math]::Min(105, [Math]::Floor(650 / [Math]::Max(1, $messages.Count)))
    for ($i = 0; $i -lt $messages.Count; $i++) {
        $y = 275 + ($i * $msgStep)
        $x1 = [float]$positions[$from[$i]]
        $x2 = [float]$positions[$to[$i]]
        $return = $messages[$i].StartsWith('Retorna') -or $messages[$i].StartsWith('Muestra') -or $messages[$i].StartsWith('Confirma')
        Draw-Arrow $g $x1 $y $x2 $y $(if ($return) { $script:Gray } else { $script:Green }) 2.5 $return
        $labelX = [Math]::Min($x1, $x2) + 8
        $labelW = [Math]::Abs($x2 - $x1) - 16
        Draw-CenteredText $g $messages[$i] $labelX ($y - 35) $labelW 34 15 $script:Ink
    }
    Save-Canvas $c $fileName
}

function New-ArchitectureDiagram {
    $c = New-Canvas 'Arquitectura lógica de Wally Reservas' 'Capas, servicios e integraciones principales'
    $g = $c.Graphics
    $layers = @(
        @{ Y = 160; H = 120; Label = 'Presentación'; Items = @('Blade + Bootstrap', 'Vite / Sass / JavaScript', 'Chart.js / FullCalendar / DataTables'); Color = $script:Pale },
        @{ Y = 315; H = 135; Label = 'Aplicación'; Items = @('Rutas y middleware', 'Controladores', 'Form Requests / Policies'); Color = $script:Blue },
        @{ Y = 485; H = 135; Label = 'Dominio y servicios'; Items = @('Eloquent Models', 'Disponibilidad y reservas', 'Chatbot / Google Calendar'); Color = $script:Amber },
        @{ Y = 655; H = 135; Label = 'Persistencia'; Items = @('MySQL / MariaDB', 'Transacciones y bloqueos', 'Restricciones e índices'); Color = $script:Rose }
    )
    foreach ($layer in $layers) {
        Draw-RoundedBox $g 80 $layer.Y 1640 $layer.H $layer.Color $script:Line 22 2
        Draw-CenteredText $g $layer.Label 100 ($layer.Y + 15) 270 ($layer.H - 30) 23 $script:Green ([System.Drawing.FontStyle]::Bold)
        $itemW = 390
        for ($i = 0; $i -lt $layer.Items.Count; $i++) {
            $x = 400 + ($i * 425)
            Draw-RoundedBox $g $x ($layer.Y + 26) $itemW ($layer.H - 52) $script:White $script:Green 16 2
            Draw-CenteredText $g $layer.Items[$i] $x ($layer.Y + 26) $itemW ($layer.H - 52) 18 $script:Ink
        }
    }
    for ($i = 0; $i -lt 3; $i++) { Draw-Arrow $g 900 ($layers[$i].Y + $layers[$i].H) 900 $layers[$i + 1].Y $script:Green 3 }
    Draw-RoundedBox $g 210 850 380 88 $script:Pale $script:Green 18 2
    Draw-CenteredText $g 'Google Identity (OAuth 2.0)' 210 850 380 88 19 $script:Ink
    Draw-RoundedBox $g 710 850 380 88 $script:Pale $script:Green 18 2
    Draw-CenteredText $g 'Google Calendar API + colas' 710 850 380 88 19 $script:Ink
    Draw-RoundedBox $g 1210 850 380 88 $script:Pale $script:Green 18 2
    Draw-CenteredText $g 'Proveedor IA opcional + NLP local' 1210 850 380 88 19 $script:Ink
    Save-Canvas $c '02_arquitectura_logica.png'
}

function Draw-ClassBox($g, [float]$x, [float]$y, [float]$w, [float]$h, [string]$name, [string[]]$attrs, [System.Drawing.Color]$fill) {
    Draw-RoundedBox $g $x $y $w $h $fill $script:Green 15 2.5
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($script:Green)), $x, $y, $w, 45)
    Draw-CenteredText $g $name $x $y $w 45 19 $script:White ([System.Drawing.FontStyle]::Bold)
    $body = ($attrs | ForEach-Object { '+ ' + $_ }) -join "`n"
    $bodyRect = [System.Drawing.RectangleF]::new(($x + 14), ($y + 55), ($w - 28), ($h - 65))
    $g.DrawString($body, (New-Font 15), (New-Object System.Drawing.SolidBrush($script:Ink)), $bodyRect)
}

function New-DomainDiagram {
    $c = New-Canvas 'Modelo de dominio principal' 'Diagrama UML de clases simplificado'
    $g = $c.Graphics
    Draw-ClassBox $g 90 165 340 180 'User' @('id, nombre, email', 'rol, activo', 'reservas()', 'socialAccounts()') $script:Pale
    Draw-ClassBox $g 560 165 340 180 'Reserva' @('fecha, estado', 'user_id, cancha_id', 'horario_id, balon_id', 'calendarEvents()') $script:Amber
    Draw-ClassBox $g 1030 165 300 180 'Cancha' @('nombre, tipo', 'precio, activa', 'horarios()', 'reservas()') $script:Pale
    Draw-ClassBox $g 1430 165 280 180 'Horario' @('hora_inicio', 'hora_fin', 'activo', 'reservas()') $script:Blue
    Draw-ClassBox $g 90 500 340 180 'SocialAccount' @('provider', 'provider_user_id', 'user_id', 'user()') $script:Blue
    Draw-ClassBox $g 560 500 340 180 'Balon' @('codigo, tipo', 'estado, precio', 'reservas()', 'mantenimientos()') $script:Pale
    Draw-ClassBox $g 1030 500 300 180 'Conversacion' @('session_id', 'user_id', 'estado', 'mensajes()') $script:Rose
    Draw-ClassBox $g 1430 500 280 180 'MensajeChatbot' @('rol', 'contenido', 'metadatos', 'conversacion()') $script:Rose
    Draw-ClassBox $g 350 790 390 145 'GoogleCalendarConnection' @('tokens cifrados', 'calendar_id', 'user_id') $script:Blue
    Draw-ClassBox $g 1050 790 390 145 'GoogleCalendarEvent' @('reserva_id', 'google_event_id', 'sync_status') $script:Blue

    Draw-Arrow $g 430 255 560 255 $script:Green 3
    Draw-CenteredText $g '1        *' 445 220 105 30 16 $script:Ink
    Draw-Arrow $g 900 255 1030 255 $script:Green 3
    Draw-CenteredText $g '*        1' 915 220 105 30 16 $script:Ink
    Draw-Arrow $g 1330 255 1430 255 $script:Green 3
    Draw-CenteredText $g '1        *' 1340 220 80 30 16 $script:Ink
    Draw-Arrow $g 260 345 260 500 $script:Green 3
    Draw-CenteredText $g '1 : *' 270 400 70 30 16 $script:Ink
    Draw-Arrow $g 730 345 730 500 $script:Green 3
    Draw-CenteredText $g '* : 0..1' 740 400 90 30 16 $script:Ink
    Draw-Arrow $g 1330 590 1430 590 $script:Green 3
    Draw-CenteredText $g '1        *' 1340 555 80 30 16 $script:Ink
    Draw-Arrow $g 560 310 430 820 $script:Line 2
    Draw-Arrow $g 900 330 1180 790 $script:Line 2
    Save-Canvas $c '03_modelo_dominio.png'
}

# Diagramas generales
New-UseCaseDiagram '01_casos_uso_general.png' 'Casos de uso generales del sistema' @('Visitante', 'Cliente', 'Recepcionista') @('Administrador', 'Servicios Google', 'Proveedor IA') @(
    'Consultar canchas y disponibilidad', 'Autenticarse y administrar perfil',
    'Crear y cancelar reserva', 'Gestionar atención presencial',
    'Administrar usuarios y recursos', 'Consultar panel, calendario y reportes',
    'Conversar con el asistente', 'Sincronizar reserva con calendario'
)
New-ArchitectureDiagram
New-DomainDiagram

# Sprint 1
New-UseCaseDiagram '04_s1_casos_uso.png' 'Sprint 1 · Portal, autenticación y roles' @('Visitante', 'Cliente') @('Administrador', 'Google Identity') @('Explorar la portada', 'Registrarse', 'Iniciar/cerrar sesión', 'Recuperar contraseña', 'Gestionar perfil', 'Autenticarse con Google')
New-ActivityDiagram '05_s1_actividad_autenticacion.png' 'Sprint 1 · Actividad de autenticación' @('Usuario', 'Aplicación', 'Base de datos') @('Abre inicio de sesión', 'Valida credenciales o inicia OAuth', 'Comprueba usuario activo y rol', 'Crea sesión autenticada', 'Redirige al panel correspondiente') @(0,1,2,1,0)
New-SequenceDiagram '06_s1_secuencia_google.png' 'Sprint 1 · Inicio de sesión con Google' @('Usuario', 'Wally Web', 'Google OAuth', 'Base de datos') @('Usuario','Wally Web','Google OAuth','Wally Web','Base de datos','Wally Web') @('Wally Web','Google OAuth','Usuario','Base de datos','Wally Web','Usuario') @('Solicita continuar con Google','Redirige con estado seguro','Retorna código de autorización','Intercambia código y obtiene perfil','Busca o vincula cuenta social','Muestra panel según rol')

# Sprint 2
New-UseCaseDiagram '07_s2_casos_uso.png' 'Sprint 2 · Recursos deportivos y disponibilidad' @('Visitante', 'Cliente') @('Administrador') @('Consultar catálogo de canchas', 'Ver detalle de cancha', 'Consultar disponibilidad', 'Gestionar canchas', 'Gestionar horarios', 'Gestionar balones')
New-ActivityDiagram '08_s2_actividad_disponibilidad.png' 'Sprint 2 · Consulta de disponibilidad' @('Usuario', 'Aplicación', 'Base de datos') @('Selecciona fecha y cancha', 'Consulta horarios activos', 'Cruza reservas activas del día', 'Marca franjas libres u ocupadas', 'Presenta opciones reservables') @(0,1,2,1,0)
New-SequenceDiagram '09_s2_secuencia_disponibilidad.png' 'Sprint 2 · Consulta de disponibilidad' @('Usuario', 'Vista', 'Controlador', 'BD') @('Usuario','Vista','Controlador','BD','Controlador','Vista') @('Vista','Controlador','BD','Controlador','Vista','Usuario') @('Selecciona filtros','Solicita disponibilidad','Consulta cancha, horarios y reservas','Retorna franjas calculadas','Renderiza estado de cada horario','Muestra opciones disponibles')

# Sprint 3
New-UseCaseDiagram '10_s3_casos_uso.png' 'Sprint 3 · Reservas del cliente' @('Cliente') @('Administrador', 'Google Calendar') @('Crear reserva', 'Elegir balón propio/económico/premium', 'Consultar mis reservas', 'Cancelar reserva futura', 'Evitar doble reserva', 'Programar sincronización externa')
New-ActivityDiagram '11_s3_actividad_reserva.png' 'Sprint 3 · Creación segura de reserva' @('Cliente', 'Aplicación', 'Base de datos') @('Selecciona cancha, fecha, horario y balón', 'Valida reglas y datos', 'Inicia transacción y bloquea recursos', 'Verifica disponibilidad vigente', 'Guarda reserva o informa conflicto', 'Confirma operación al cliente') @(0,1,2,2,1,0)
New-SequenceDiagram '12_s3_secuencia_reserva.png' 'Sprint 3 · Registro transaccional de reserva' @('Cliente', 'ReservaController', 'BD', 'Cola') @('Cliente','ReservaController','ReservaController','BD','BD','ReservaController','ReservaController') @('ReservaController','ReservaController','BD','BD','ReservaController','Cola','Cliente') @('Envía solicitud','Valida reglas de negocio','BEGIN + bloqueo pesimista','Comprueba restricción única','Confirma reserva o revierte','Despacha sincronización tras commit','Confirma reserva o responde 409')

# Sprint 4
New-UseCaseDiagram '13_s4_casos_uso.png' 'Sprint 4 · Operación de recepción' @('Recepcionista') @('Cliente') @('Consultar agenda diaria', 'Buscar/autocompletar cliente', 'Crear reserva presencial', 'Cambiar estado de reserva', 'Registrar check-in', 'Registrar check-out')
New-ActivityDiagram '14_s4_actividad_atencion.png' 'Sprint 4 · Atención presencial' @('Recepcionista', 'Aplicación', 'Cliente') @('Busca o selecciona cliente activo', 'Consulta disponibilidad y registra reserva', 'Cliente llega a la instalación', 'Verifica condiciones y registra check-in', 'Finaliza el turno y registra check-out', 'Actualiza agenda y estado final') @(0,1,2,0,0,1)
New-SequenceDiagram '15_s4_secuencia_checkin.png' 'Sprint 4 · Check-in y check-out' @('Recepcionista', 'Panel recepción', 'ReservaService', 'BD') @('Recepcionista','Panel recepción','ReservaService','BD','ReservaService','Recepcionista') @('Panel recepción','ReservaService','BD','ReservaService','Panel recepción','Panel recepción') @('Selecciona reserva del día','Solicita cambio de estado','Valida transición y permisos','Actualiza estado y marcas de tiempo','Retorna resultado','Visualiza agenda actualizada')

# Sprint 5
New-UseCaseDiagram '16_s5_casos_uso.png' 'Sprint 5 · Administración y análisis' @('Administrador') @('Sistema') @('Gestionar usuarios y roles', 'Gestionar canchas/horarios/balones', 'Gestionar reservas', 'Consultar dashboard', 'Usar calendario administrativo', 'Filtrar y exportar reporte CSV', 'Configurar apariencia')
New-ActivityDiagram '17_s5_actividad_reporte.png' 'Sprint 5 · Generación de reportes' @('Administrador', 'Aplicación', 'Base de datos') @('Define rango y filtros', 'Valida parámetros', 'Consulta reservas e indicadores', 'Agrupa resultados', 'Presenta tabla y métricas', 'Exporta CSV si se solicita') @(0,1,2,1,1,0)
New-SequenceDiagram '18_s5_secuencia_dashboard.png' 'Sprint 5 · Dashboard y reportes' @('Administrador', 'Dashboard', 'Controlador', 'BD') @('Administrador','Dashboard','Controlador','BD','Controlador','Dashboard') @('Dashboard','Controlador','BD','Controlador','Dashboard','Administrador') @('Solicita período/filtros','Envía consulta','Agrega reservas, ingresos y ocupación','Retorna conjuntos resumidos','Renderiza gráficos y tabla','Muestra indicadores o descarga CSV')

# Sprint 6
New-UseCaseDiagram '19_s6_casos_uso.png' 'Sprint 6 · Asistencia, integraciones y calidad' @('Visitante', 'Cliente') @('Administrador', 'Google Calendar', 'Proveedor IA') @('Consultar al chatbot', 'Mantener contexto conversacional', 'Revisar historial de conversaciones', 'Conectar calendario', 'Sincronizar eventos en cola', 'Continuar con NLP local ante fallos', 'Ejecutar pruebas automatizadas')
New-ActivityDiagram '20_s6_actividad_chatbot.png' 'Sprint 6 · Respuesta del chatbot' @('Usuario', 'Chatbot', 'Servicios') @('Envía consulta', 'Normaliza texto y recupera contexto', 'Resuelve intención local y consulta datos', 'Usa proveedor IA solo si está configurado', 'Aplica respuesta local si falla el proveedor', 'Guarda mensajes y responde') @(0,1,2,2,1,0)
New-SequenceDiagram '21_s6_secuencia_calendar.png' 'Sprint 6 · Sincronización con Google Calendar' @('Reserva', 'Evento dominio', 'Cola', 'Google API', 'BD') @('Reserva','Evento dominio','Evento dominio','Cola','Google API','Cola','BD') @('Evento dominio','Cola','Reserva','Google API','Cola','BD','Reserva') @('Emite cambio confirmado','Despacha job después del commit','Procesa trabajo asíncrono','Crea/actualiza/elimina evento','Retorna identificador o error','Registra estado de sincronización','No bloquea la operación principal')

Write-Output "Diagramas generados en: $outputDirectory"

