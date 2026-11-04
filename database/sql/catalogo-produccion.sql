-- Catálogo operativo de Wally. Idempotente, sin usuarios ni reservas de demostración.
BEGIN;

INSERT INTO wally.canchas
    (nombre, descripcion, ubicacion, precio_por_hora, estado, imagen, color_malla, altura_malla, tipo_piso, tipo_recinto, created_at, updated_at)
SELECT v.*, NOW(), NOW()
FROM (VALUES
    ('Cancha 1', 'Cancha cerrada (techada). Ideal para jugar todo el día sin sol ni lluvia.', 'Complejo Wally · Zona Norte', 25.00, 'disponible', 'cancha-1.jpeg', 'Verde', '2 m', 'Cemento pulido', 'cerrada'),
    ('Cancha 2', 'Cancha cerrada (techada). Confort y seguridad en cada partido.', 'Complejo Wally · Zona Sur', 25.00, 'disponible', 'cancha-2.jpeg', 'Negra', '1.80 m', 'Sintético', 'cerrada')
) AS v(nombre, descripcion, ubicacion, precio_por_hora, estado, imagen, color_malla, altura_malla, tipo_piso, tipo_recinto)
WHERE NOT EXISTS (SELECT 1 FROM wally.canchas c WHERE c.nombre = v.nombre);

INSERT INTO wally.balones
    (tipo, marca, nombre, color, calidad, imagen, precio_por_hora, destacado, descripcion, created_at, updated_at)
SELECT v.*, NOW(), NOW()
FROM (VALUES
    ('economico', 'Asatex', '1', 'Amarillo/verde', 'Estándar', 'Balon economico Asatex 1.png', 25.00, true, 'Balón económico que la dueña presta sin costo extra. Ideal para partidos de práctica.'),
    ('economico', 'Asatex', '2', 'Amarillo/verde', 'Estándar', 'Balon economico Asatex 2.png', 25.00, false, 'Segundo balón Asatex disponible para prestar en tus partidos.'),
    ('economico', 'Veloz', '1', 'Blanco/azul', 'Estándar', 'Balon economico Veloz 1.png', 25.00, false, 'Balón Veloz económico, listo para el juego de todos los días.'),
    ('economico', 'Veloz', '2', 'Blanco/azul', 'Estándar', 'Balon economico Veloz 2.png', 25.00, false, 'Segunda unidad Veloz disponible para prestar.'),
    ('premium', 'Mikasa', 'V200W', 'Blanco/azul', 'Profesional', 'Balon premiun Mikasa V200W Original.png', 30.00, true, 'Balón oficial de competición. Agarre y rendimiento de primer nivel.'),
    ('premium', 'Mikasa', 'V330W', 'Blanco/azul', 'Alta', 'Balon premiun Mikasa V330W Orginal.png', 30.00, false, 'Balón Mikasa de alta calidad, excelente para partidos exigentes.'),
    ('premium', 'Mikasa', 'V330W 2', 'Blanco/azul', 'Alta', 'Balon premiun Mikasa V330W Orginal.png', 30.00, false, 'Segunda unidad Mikasa V330W disponible como balón premium.'),
    ('premium', 'Molten', '5500', 'Blanco/rojo', 'Profesional', 'Balon Premiun Molten 5500 Original.png', 30.00, false, 'Balón Molten 5500 de competición, máximo control y durabilidad.')
) AS v(tipo, marca, nombre, color, calidad, imagen, precio_por_hora, destacado, descripcion)
WHERE NOT EXISTS (
    SELECT 1 FROM wally.balones b WHERE b.tipo = v.tipo AND b.marca = v.marca AND b.nombre = v.nombre
);

UPDATE wally.configuraciones_reserva
SET duraciones_permitidas = '[60,90,120,150,180]'::json, minutos_franja = 30, updated_at = NOW();

INSERT INTO wally.horarios
    (cancha_id, dia_semana, hora_inicio, hora_fin, disponible, created_at, updated_at)
SELECT c.id, d.dia, t.inicio::time, (t.inicio + INTERVAL '30 minutes')::time, true, NOW(), NOW()
FROM wally.canchas c
CROSS JOIN generate_series(1, 6) AS d(dia)
CROSS JOIN generate_series(TIMESTAMP '2000-01-01 08:00', TIMESTAMP '2000-01-01 21:30', INTERVAL '30 minutes') AS t(inicio)
WHERE c.nombre IN ('Cancha 1', 'Cancha 2')
ON CONFLICT (cancha_id, dia_semana, hora_inicio) DO NOTHING;

COMMIT;

SELECT (SELECT COUNT(*) FROM wally.canchas) AS canchas,
       (SELECT COUNT(*) FROM wally.balones) AS balones,
       (SELECT COUNT(*) FROM wally.horarios) AS horarios,
       (SELECT COUNT(*) FROM wally.reservas) AS reservas;
