import os
from scratch_classify_check import modules_def

def count_lines(filepath):
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
    
    total = len(lines)
    blank = 0
    comment = 0
    code = 0
    
    ext = os.path.splitext(filepath)[1].lower()
    is_php_or_js = ext in ['.php', '.js', '.scss', '.css']
    is_blade = '.blade.php' in filepath
    
    in_block_comment = False
    in_blade_comment = False
    
    for line in lines:
        stripped = line.strip()
        if not stripped:
            blank += 1
            continue
        
        # Check comments
        if is_blade:
            if '{{--' in stripped and '--}}' in stripped:
                comment += 1
                continue
            if '{{--' in stripped:
                in_blade_comment = True
                comment += 1
                continue
            if in_blade_comment:
                comment += 1
                if '--}}' in stripped:
                    in_blade_comment = False
                continue
        
        if is_php_or_js:
            if stripped.startswith('//') or stripped.startswith('#'):
                comment += 1
                continue
            if stripped.startswith('/*') and stripped.endswith('*/'):
                comment += 1
                continue
            if stripped.startswith('/*'):
                in_block_comment = True
                comment += 1
                continue
            if in_block_comment:
                comment += 1
                if '*/' in stripped:
                    in_block_comment = False
                continue
        
        code += 1
        
    return total, blank, comment, code

grand_total = 0
grand_blank = 0
grand_comment = 0
grand_code = 0

module_stats = []

for mod_name, files in modules_def.items():
    m_tot, m_blk, m_cmt, m_cod = 0, 0, 0, 0
    for f in files:
        tot, blk, cmt, cod = count_lines(os.path.normpath(f))
        m_tot += tot
        m_blk += blk
        m_cmt += cmt
        m_cod += cod
    
    grand_total += m_tot
    grand_blank += m_blk
    grand_comment += m_cmt
    grand_code += m_cod
    
    module_stats.append({
        'module': mod_name,
        'files': len(files),
        'total': m_tot,
        'blank': m_blk,
        'comment': m_cmt,
        'sloc': m_cod
    })

print("=== RESULTADOS DE TAMANO FUNCIONAL ===")
for s in module_stats:
    pct = (s['sloc'] / grand_code) * 100.0
    print(f"{s['module']}: {s['files']} archivos | Total: {s['total']} | Blank: {s['blank']} | Comentarios: {s['comment']} | SLOC: {s['sloc']} ({pct:.2f}%)")

print("-----------------------------------------")
print(f"TOTAL SISTEMA:")
print(f"Archivos Funcionales Unicos: 254")
print(f"Total Lineas Fisicas: {grand_total}")
print(f"Lineas en Blanco: {grand_blank}")
print(f"Lineas de Comentarios: {grand_comment}")
print(f"SLOC (Lineas de Codigo Fuente Reales): {grand_code}")
print(f"KSLOC (Miles de Lineas de Codigo): {grand_code / 1000.0:.3f} KSLOC")

