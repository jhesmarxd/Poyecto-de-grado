# -*- coding: utf-8 -*-
"""
Playwright Automation Runner para Casos de Prueba del Sprint 2:
- TC-S2-008: Registrar una nueva cancha deportiva en el catálogo
- TC-S2-009: Validar unicidad de nombre y precio mínimo en registro de canchas
- TC-S2-010: Registrar un balón deportivo para alquiler
- TC-S2-011: Registrar excepción de cierre por mantenimiento en una cancha
- TC-S2-012: Generar franjas horarias consecutivas semanales
- TC-S2-013: Verificar exclusión de fechas con cierre en la consulta de horarios
- TC-S2-014: Impedir creación de franja con hora de inicio mayor a la de fin

Opciones:
  --case <TC_ID>    Ejecutar solo un caso específico (ej. TC-S2-008)
  --headed          Abrir ventana visual del navegador (por defecto headless)
  --pause           Pausar en el punto de captura para tomar la foto manualmente
"""

import os
import sys
import argparse
from datetime import datetime, timedelta
from playwright.sync_api import sync_playwright

BASE_URL = "http://127.0.0.1:8000"
OUTPUT_DIR = os.path.abspath("evidencias/sprint2")

os.makedirs(OUTPUT_DIR, exist_ok=True)

# Crear imagen de prueba temporal para uploads si no existe
SAMPLE_IMG = os.path.join(OUTPUT_DIR, "cancha_prueba.png")
if not os.path.exists(SAMPLE_IMG):
    with open(SAMPLE_IMG, "wb") as f:
        f.write(b'\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82')

def login_as_admin(page):
    if "/admin" in page.url and "/login" not in page.url:
        return
    page.goto(f"{BASE_URL}/admin", wait_until="networkidle")
    if "/admin" in page.url and "/login" not in page.url:
        return
    if page.locator('input[name="email"]').is_visible():
        print("  -> Iniciando sesión como Administrador (admin@wally.com)...", flush=True)
        page.fill('input[name="email"]', 'admin@wally.com')
        page.fill('input[name="password"]', 'Admin123!')
        page.click('button[type="submit"]')
        page.wait_for_load_state("networkidle")

def handle_pause(page, pause_flag, message=""):
    if pause_flag:
        print(f"\n  [PAUSA INTERACTIVA] {message}", flush=True)
        print("  Presiona ENTER en esta consola para continuar...", flush=True)
        input()

# ==============================================================================
# CASOS DE PRUEBA
# ==============================================================================

def run_tc_s2_008(page, pause=False):
    print("\n=======================================================", flush=True)
    print("EJECUTANDO TC-S2-008: Registrar nueva cancha en el catálogo", flush=True)
    print("=======================================================", flush=True)
    login_as_admin(page)
    
    # 1. Navegar a Gestión de canchas
    print("  1. Navegando a Gestión de canchas...", flush=True)
    page.goto(f"{BASE_URL}/admin/canchas", wait_until="networkidle")
    
    # 2. Hacer clic en + Nueva cancha
    print("  2. Clic en '+ Nueva cancha'...", flush=True)
    page.goto(f"{BASE_URL}/admin/canchas/crear", wait_until="networkidle")
    
    # 3. Llenar formulario
    print("  3. Rellenando campos del formulario...", flush=True)
    timestamp = datetime.now().strftime("%H%M%S")
    nombre_cancha = f"Cancha 3 - Sintética CP {timestamp}"
    page.fill('#nombre', nombre_cancha)
    page.fill('#ubicacion', 'Complejo Wally - Sector Este')
    page.fill('#precio_por_hora', '35.00')
    page.select_option('#estado', 'disponible')
    page.select_option('#tipo_recinto', 'cerrada')
    page.fill('#color_malla', 'Azul')
    page.fill('#altura_malla', '2.43 m')
    page.fill('#tipo_piso', 'Parquet flotante')
    page.fill('#descripcion', 'Cancha sintética profesional creada para evidencia de prueba CP.')
    
    if os.path.exists(SAMPLE_IMG):
        page.set_input_files('#imagen', SAMPLE_IMG)
        
    shot1 = os.path.join(OUTPUT_DIR, "TC-S2-008_1_formulario_completado.png")
    page.screenshot(path=shot1, full_page=True)
    print(f"  [Evidencia 1 guardada]: {shot1}", flush=True)
    
    handle_pause(page, pause, "Formulario completado. Listo para guardar.")
    
    # 4. Guardar cancha (dispara diálogo SweetAlert2 con nuevos iconos y estilo)
    print("  4. Clic en 'Guardar cancha'...", flush=True)
    page.click('button:has-text("Guardar cancha")')
    page.wait_for_selector(".swal2-popup", timeout=5000)
    page.wait_for_timeout(600)
    
    shot2 = os.path.join(OUTPUT_DIR, "TC-S2-008_2_cancha_creada.png")
    page.screenshot(path=shot2, full_page=True)
    print(f"  [Evidencia 2 guardada (Modal con nuevos estilos)]: {shot2}", flush=True)
    
    # Confirmar modal para persistir la cancha y capturar el catálogo actualizado
    swal_btn = page.locator('.swal2-confirm')
    if swal_btn.is_visible():
        print("  5. Confirmando guardado en SweetAlert...", flush=True)
        swal_btn.click()
        page.wait_for_load_state("networkidle")
        page.wait_for_timeout(1000)
        shot3 = os.path.join(OUTPUT_DIR, "TC-S2-008_3_cancha_guardada_catalogo.png")
        page.screenshot(path=shot3, full_page=True)
        print(f"  [Evidencia 3 guardada (Catálogo general)]: {shot3}", flush=True)
        
    print("  [OK] TC-S2-008 COMPLETADO EXITOSAMENTE.", flush=True)

def run_tc_s2_009(page, pause=False):
    print("\n=======================================================", flush=True)
    print("EJECUTANDO TC-S2-009: Validar nombre duplicado y precio negativo", flush=True)
    print("=======================================================", flush=True)
    login_as_admin(page)
    
    print("  1. Navegando al formulario de nueva cancha...", flush=True)
    page.goto(f"{BASE_URL}/admin/canchas/crear", wait_until="networkidle")
    
    print("  2. Rellenando con datos inválidos (nombre duplicado 'Cancha 1' y precio -15)...", flush=True)
    page.fill('#nombre', 'Cancha 1') # Ya existe
    # Deshabilitar validación HTML5 para que el formulario se envíe y Laravel valide en servidor
    page.evaluate("document.querySelector('form').setAttribute('novalidate', 'novalidate')")
    page.evaluate("document.getElementById('precio_por_hora').type = 'text'")
    page.fill('#precio_por_hora', '-15.00')
    page.fill('#ubicacion', 'Sector Central')
    page.select_option('#estado', 'disponible')
    page.select_option('#tipo_recinto', 'cerrada')
    
    handle_pause(page, pause, "Datos inválidos ingresados. Listo para enviar.")
    
    print("  3. Presionando 'Guardar cancha'...", flush=True)
    page.click('button:has-text("Guardar cancha")')
    page.wait_for_selector(".swal2-popup", timeout=5000)
    page.wait_for_timeout(600)
    
    swal_btn = page.locator('.swal2-confirm')
    if swal_btn.is_visible():
        swal_btn.click()
        page.wait_for_load_state("networkidle")
        page.wait_for_timeout(1000)
    
    shot = os.path.join(OUTPUT_DIR, "TC-S2-009_mensajes_validacion.png")
    page.screenshot(path=shot, full_page=True)
    print(f"  [Evidencia guardada]: {shot}", flush=True)
    print("  [OK] TC-S2-009 COMPLETADO EXITOSAMENTE.", flush=True)

def run_tc_s2_010(page, pause=False):
    print("\n=======================================================", flush=True)
    print("EJECUTANDO TC-S2-010: Registrar un balón deportivo para alquiler", flush=True)
    print("=======================================================", flush=True)
    login_as_admin(page)
    
    print("  1. Navegando al módulo de Balones...", flush=True)
    page.goto(f"{BASE_URL}/admin/balones", wait_until="networkidle")
    
    print("  2. Clic en '+ Nuevo balón'...", flush=True)
    page.goto(f"{BASE_URL}/admin/balones/nuevo", wait_until="networkidle")
    
    print("  3. Rellenando formulario de balón...", flush=True)
    page.select_option('#tipo', 'premium')
        
    timestamp = datetime.now().strftime("%H%M%S")
    page.fill('#marca', 'Mikasa')
    page.fill('#nombre', f"V200W Profesional CP {timestamp}")
    page.fill('#color', 'Amarillo / Azul')
    page.fill('#calidad', 'Profesional')
    page.fill('#precio_por_hora', '5.00')
    page.fill('#descripcion', 'Balón reglamentario de alta visibilidad para partidos de campeonato.')
    
    shot1 = os.path.join(OUTPUT_DIR, "TC-S2-010_1_formulario_balon.png")
    page.screenshot(path=shot1, full_page=True)
    print(f"  [Evidencia 1 guardada]: {shot1}", flush=True)
    
    handle_pause(page, pause, "Formulario de balón completado.")
    
    print("  4. Clic en 'Guardar balón' (abre diálogo SweetAlert2 con nuevos iconos y estilo)...", flush=True)
    page.click('button:has-text("Guardar balón")')
    page.wait_for_selector(".swal2-popup", timeout=5000)
    page.wait_for_timeout(600)
    
    shot2 = os.path.join(OUTPUT_DIR, "TC-S2-010_2_balon_creado.png")
    page.screenshot(path=shot2, full_page=True)
    print(f"  [Evidencia 2 guardada (Modal con nuevos estilos)]: {shot2}", flush=True)
    
    swal_btn = page.locator('.swal2-confirm')
    if swal_btn.is_visible():
        print("  5. Confirmando guardado en SweetAlert...", flush=True)
        swal_btn.click()
        page.wait_for_load_state("networkidle")
        page.wait_for_timeout(1000)
        shot3 = os.path.join(OUTPUT_DIR, "TC-S2-010_3_balon_guardado_catalogo.png")
        page.screenshot(path=shot3, full_page=True)
        print(f"  [Evidencia 3 guardada (Catálogo con balón creado)]: {shot3}", flush=True)
        
    print("  [OK] TC-S2-010 COMPLETADO EXITOSAMENTE.", flush=True)

def run_tc_s2_011(page, pause=False):
    print("\n=======================================================", flush=True)
    print("EJECUTANDO TC-S2-011: Registrar excepción de cierre por mantenimiento", flush=True)
    print("=======================================================", flush=True)
    login_as_admin(page)
    
    print("  1. Navegando a Franjas reservables de Cancha 1...", flush=True)
    page.goto(f"{BASE_URL}/admin/horarios?cancha_id=1", wait_until="networkidle")
    
    today = datetime.now()
    days_ahead = (0 - today.weekday()) % 7
    if days_ahead == 0:
        days_ahead = 7
    future_monday = (today + timedelta(days=days_ahead + 7)).strftime("%Y-%m-%d")
    
    print(f"  2. Rellenando sección Cierres por fecha para el día {future_monday}...", flush=True)
    page.fill('#fecha_cierre', future_monday)
    page.fill('input[name="motivo"]', 'Mantenimiento preventivo de red y suelo CP')
    
    handle_pause(page, pause, "Formulario de cierre completado. Listo para presionar Registrar cierre.")
    
    print("  3. Presionando 'Registrar cierre'...", flush=True)
    page.click('button:has-text("Registrar cierre")')
    page.wait_for_selector(".swal2-popup", timeout=5000)
    page.wait_for_timeout(600)
    
    # Modal SweetAlert con nuevos iconos y estilos
    shot1 = os.path.join(OUTPUT_DIR, "TC-S2-011_1_confirmacion_cierre.png")
    page.screenshot(path=shot1, full_page=True)
    print(f"  [Evidencia 1 guardada (Modal nuevo)]: {shot1}", flush=True)
    
    swal_btn = page.locator('.swal2-confirm')
    if swal_btn.is_visible():
        print("  4. Confirmando en ventana SweetAlert...", flush=True)
        swal_btn.click()
        page.wait_for_load_state("networkidle")
        page.wait_for_timeout(1000)
        
    shot2 = os.path.join(OUTPUT_DIR, "TC-S2-011_2_cierre_registrado.png")
    page.screenshot(path=shot2, full_page=True)
    print(f"  [Evidencia 2 guardada]: {shot2}", flush=True)
    print("  [OK] TC-S2-011 COMPLETADO EXITOSAMENTE.", flush=True)

def run_tc_s2_012(page, pause=False):
    print("\n=======================================================", flush=True)
    print("EJECUTANDO TC-S2-012: Generar franjas horarias consecutivas", flush=True)
    print("=======================================================", flush=True)
    login_as_admin(page)
    
    print("  1. Navegando a Franjas reservables de Cancha 1...", flush=True)
    page.goto(f"{BASE_URL}/admin/horarios?cancha_id=1", wait_until="networkidle")
    
    print("  2. Configurando franjas masivas (Lunes a Viernes, 14:00 a 22:00, 60 min)...", flush=True)
    for d in [1, 2, 3, 4, 5]:
        checkbox = page.locator(f'input[name="dias[]"][value="{d}"]')
        if checkbox.count() > 0:
            checkbox.check()
            
    today_str = datetime.now().strftime("%Y-%m-%d")
    month_ahead_str = (datetime.now() + timedelta(days=30)).strftime("%Y-%m-%d")
    
    page.fill('#vigente_desde', today_str)
    page.fill('#vigente_hasta', month_ahead_str)
    page.fill('#hora_inicio_masiva', '14:00')
    page.fill('#hora_fin_masiva', '22:00')
    page.select_option('#duracion', '60')
    
    shot1 = os.path.join(OUTPUT_DIR, "TC-S2-012_1_configuracion_masiva.png")
    page.screenshot(path=shot1, full_page=True)
    print(f"  [Evidencia 1 guardada]: {shot1}", flush=True)
    
    handle_pause(page, pause, "Configuración masiva lista. Presionando Aplicar...")
    
    print("  3. Presionando 'Aplicar configuración'...", flush=True)
    page.click('button:has-text("Aplicar configuración")')
    page.wait_for_selector(".swal2-popup", timeout=5000)
    page.wait_for_timeout(600)
    
    shot_modal = os.path.join(OUTPUT_DIR, "TC-S2-012_modal_confirmacion.png")
    page.screenshot(path=shot_modal, full_page=True)
    print(f"  [Evidencia Modal guardada]: {shot_modal}", flush=True)
    
    swal_btn = page.locator('.swal2-confirm')
    if swal_btn.is_visible():
        print("  4. Confirmando acción en diálogo modal...", flush=True)
        swal_btn.click()
        page.wait_for_load_state("networkidle")
        page.wait_for_timeout(1500)
        
    shot2 = os.path.join(OUTPUT_DIR, "TC-S2-012_2_franjas_generadas.png")
    page.screenshot(path=shot2, full_page=True)
    print(f"  [Evidencia 2 guardada]: {shot2}", flush=True)
    print("  [OK] TC-S2-012 COMPLETADO EXITOSAMENTE.", flush=True)

def run_tc_s2_013(page, pause=False):
    print("\n=======================================================", flush=True)
    print("EJECUTANDO TC-S2-013: Exclusión de fecha cerrada en disponibilidad", flush=True)
    print("=======================================================", flush=True)
    
    today = datetime.now()
    days_ahead = (0 - today.weekday()) % 7
    if days_ahead == 0:
        days_ahead = 7
    future_monday = (today + timedelta(days=days_ahead + 7)).strftime("%Y-%m-%d")
    
    print(f"  1. Navegando a consulta pública de disponibilidad...", flush=True)
    page.goto(f"{BASE_URL}/disponibilidad", wait_until="networkidle")
    
    print(f"  2. Seleccionando Cancha 1 y fecha cerrada ({future_monday})...", flush=True)
    page.select_option('#cancha_id', '1')
    page.fill('#fecha', future_monday)
    
    handle_pause(page, pause, "Datos seleccionados. Listo para consultar horarios.")
    
    print("  3. Presionando 'Consultar horarios'...", flush=True)
    page.click('.availability-submit')
    page.wait_for_load_state("networkidle")
    page.wait_for_timeout(800)
    
    shot = os.path.join(OUTPUT_DIR, "TC-S2-013_fecha_cerrada_disponibilidad.png")
    page.screenshot(path=shot, full_page=True)
    print(f"  [Evidencia guardada]: {shot}", flush=True)
    print("  [OK] TC-S2-013 COMPLETADO EXITOSAMENTE.", flush=True)

def run_tc_s2_014(page, pause=False):
    print("\n=======================================================", flush=True)
    print("EJECUTANDO TC-S2-014: Impedir franja con hora inicio > fin", flush=True)
    print("=======================================================", flush=True)
    login_as_admin(page)
    
    print("  1. Navegando a creación de franja individual...", flush=True)
    page.goto(f"{BASE_URL}/admin/canchas/1/horarios/crear", wait_until="networkidle")
    
    print("  2. Configurando hora de inicio 21:00 y fin 18:00...", flush=True)
    page.select_option('#dia_semana', '6') # Sábado
    page.fill('#hora_inicio', '21:00')
    page.fill('#hora_fin', '18:00')
    
    handle_pause(page, pause, "Horas invertidas ingresadas. Listo para guardar.")
    
    print("  3. Presionando 'Guardar horario'...", flush=True)
    page.click('button:has-text("Guardar horario")')
    page.wait_for_selector(".swal2-popup", timeout=5000)
    page.wait_for_timeout(600)
    
    swal_btn = page.locator('.swal2-confirm')
    if swal_btn.is_visible():
        swal_btn.click()
        page.wait_for_load_state("networkidle")
        page.wait_for_timeout(1000)
        
    shot = os.path.join(OUTPUT_DIR, "TC-S2-014_error_coherencia_horaria.png")
    page.screenshot(path=shot, full_page=True)
    print(f"  [Evidencia guardada]: {shot}", flush=True)
    print("  [OK] TC-S2-014 COMPLETADO EXITOSAMENTE.", flush=True)


def main():
    parser = argparse.ArgumentParser(description="Ejecutor Playwright de Casos de Prueba del Sprint 2")
    parser.add_argument("--case", type=str, default=None, help="Código del caso a ejecutar (ej: TC-S2-008)")
    parser.add_argument("--headed", action="store_true", help="Abrir ventana visual del navegador")
    parser.add_argument("--pause", action="store_true", help="Pausar en consola para permitir interacción manual")
    args = parser.parse_args()

    cases = {
        "TC-S2-008": run_tc_s2_008,
        "TC-S2-009": run_tc_s2_009,
        "TC-S2-010": run_tc_s2_010,
        "TC-S2-011": run_tc_s2_011,
        "TC-S2-012": run_tc_s2_012,
        "TC-S2-013": run_tc_s2_013,
        "TC-S2-014": run_tc_s2_014,
    }

    selected_cases = [args.case] if args.case else list(cases.keys())

    print(f"Iniciando Playwright (Headed: {args.headed}, Pause: {args.pause})...", flush=True)
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=not args.headed, slow_mo=300 if args.headed else 0)
        context = browser.new_context(viewport={"width": 1280, "height": 800})
        page = context.new_page()

        for c_id in selected_cases:
            if c_id in cases:
                try:
                    cases[c_id](page, pause=args.pause)
                except Exception as ex:
                    print(f"  [ERROR en {c_id}]: {ex}", flush=True)
            else:
                print(f"Caso {c_id} no reconocido. Opciones: {list(cases.keys())}", flush=True)

        browser.close()

    print(f"\n=======================================================", flush=True)
    print(f"TODAS LAS EVIDENCIAS GUARDADAS EN: {OUTPUT_DIR}", flush=True)
    print("=======================================================", flush=True)

if __name__ == "__main__":
    main()
