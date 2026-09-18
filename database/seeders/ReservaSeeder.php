<?php

namespace Database\Seeders;

use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    /**
     * Crea reservas de ejemplo con clientes reales en distintos estados.
     * Idempotente: puede ejecutarse varias veces sin duplicar registros.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@wally.com')->first();
        $recepcion = User::where('email', 'recepcion@wally.com')->first();

        $clientes = User::where('rol', User::ROL_CLIENTE)->get();

        if ($clientes->isEmpty()) {
            $this->command?->warn('ReservaSeeder: no hay clientes. Ejecuta UserSeeder primero.');

            return;
        }

        $cancha1 = Cancha::where('nombre', 'Cancha 1')->first();
        $cancha2 = Cancha::where('nombre', 'Cancha 2')->first();

        if (! $cancha1 || ! $cancha2) {
            $this->command?->warn('ReservaSeeder: faltan canchas. Ejecuta CanchaSeeder primero.');

            return;
        }

        // Balones de referencia
        $balonEconomico = \App\Models\Balon::economico();
        $balonPremium = \App\Models\Balon::premium();

        // ── Reservas distribuidas entre los clientes reales ──
        // [cliente índice, cancha, días adelante, franja, estado, tipo_balon]
        $plantillas = [
            // Juan Pablo García - 2 reservas
            [0, $cancha1, 2, '18:00:00', Reserva::ESTADO_CONFIRMADA, Reserva::BALON_PRESTADO, $balonEconomico],
            [0, $cancha2, 5, '19:00:00', Reserva::ESTADO_PENDIENTE, Reserva::BALON_PROPIO, null],

            // Andrés Felipe López - 2 reservas
            [1, $cancha1, 3, '20:00:00', Reserva::ESTADO_PENDIENTE, Reserva::BALON_PREMIUM, $balonPremium],
            [1, $cancha2, 7, '17:00:00', Reserva::ESTADO_CONFIRMADA, Reserva::BALON_PRESTADO, $balonEconomico],

            // Valentina Ruiz - 1 reserva
            [2, $cancha2, 4, '19:00:00', Reserva::ESTADO_CONFIRMADA, Reserva::BALON_PROPIO, null],

            // Diego Fernando Morán - 2 reservas
            [3, $cancha1, 6, '21:00:00', Reserva::ESTADO_CANCELADA, Reserva::BALON_PRESTADO, $balonEconomico],
            [3, $cancha1, 8, '18:00:00', Reserva::ESTADO_PENDIENTE, Reserva::BALON_PREMIUM, $balonPremium],

            // Camila Andrea Rojas - 1 reserva
            [4, $cancha2, 10, '20:00:00', Reserva::ESTADO_COMPLETADA, Reserva::BALON_PRESTADO, $balonEconomico],

            // Santiago Herrera - 2 reservas
            [5, $cancha1, 3, '19:00:00', Reserva::ESTADO_CONFIRMADA, Reserva::BALON_PROPIO, null],
            [5, $cancha2, 6, '21:00:00', Reserva::ESTADO_PENDIENTE, Reserva::BALON_PREMIUM, $balonPremium],

            // Laura Sofía Vargas - 1 reserva
            [6, $cancha1, 4, '20:00:00', Reserva::ESTADO_PENDIENTE, Reserva::BALON_PRESTADO, $balonEconomico],

            // Mateo Alejandro Díaz - 2 reservas
            [7, $cancha2, 2, '18:00:00', Reserva::ESTADO_CONFIRMADA, Reserva::BALON_PRESTADO, $balonEconomico],
            [7, $cancha1, 9, '19:00:00', Reserva::ESTADO_CANCELADA, Reserva::BALON_PROPIO, null],
        ];

        foreach ($plantillas as [$idx, $cancha, $dias, $horaInicio, $estado, $tipoBalon, $balon]) {
            $cliente = $clientes[$idx] ?? null;
            if (! $cliente) {
                continue;
            }

            $fecha = now()->addDays($dias)->toDateString();
            $diaSemana = (int) now()->addDays($dias)->dayOfWeekIso;

            $horario = $cancha->horarios()
                ->where('dia_semana', $diaSemana)
                ->where('hora_inicio', $horaInicio)
                ->first();

            if (! $horario) {
                continue;
            }

            Reserva::updateOrCreate(
                [
                    'user_id' => $cliente->id,
                    'cancha_id' => $cancha->id,
                    'horario_id' => $horario->id,
                    'fecha' => $fecha,
                ],
                [
                    'monto_total' => Reserva::calcularMonto((float) $cancha->precio_por_hora, $balon?->id),
                    'tipo_balon' => $tipoBalon,
                    'balon_id' => $balon?->id,
                    'estado' => $estado,
                    'observaciones' => "Reserva de {$cliente->name}",
                ]
            );
        }

        // ── Reserva del recepcionista ──
        if ($recepcion) {
            $fechaRecep = now()->addDays(11)->toDateString();
            $diaRecep = (int) now()->addDays(11)->dayOfWeekIso;
            $horarioRecep = $cancha1->horarios()
                ->where('dia_semana', $diaRecep)
                ->where('hora_inicio', '18:00:00')
                ->first();

            if ($horarioRecep) {
                Reserva::updateOrCreate(
                    [
                        'user_id' => $recepcion->id,
                        'cancha_id' => $cancha1->id,
                        'horario_id' => $horarioRecep->id,
                        'fecha' => $fechaRecep,
                    ],
                    [
                        'monto_total' => Reserva::calcularMonto((float) $cancha1->precio_por_hora, $balonPremium?->id),
                        'tipo_balon' => Reserva::BALON_PREMIUM,
                        'balon_id' => $balonPremium?->id,
                        'estado' => Reserva::ESTADO_PENDIENTE,
                        'observaciones' => 'Reserva de María Elena Torres (recepcionista)',
                    ]
                );
            }
        }

        // ── Reserva del admin ──
        if ($admin) {
            $fechaAdmin = now()->addDays(12)->toDateString();
            $diaAdmin = (int) now()->addDays(12)->dayOfWeekIso;
            $horarioAdmin = $cancha2->horarios()
                ->where('dia_semana', $diaAdmin)
                ->where('hora_inicio', '20:00:00')
                ->first();

            if ($horarioAdmin) {
                Reserva::updateOrCreate(
                    [
                        'user_id' => $admin->id,
                        'cancha_id' => $cancha2->id,
                        'horario_id' => $horarioAdmin->id,
                        'fecha' => $fechaAdmin,
                    ],
                    [
                        'monto_total' => Reserva::calcularMonto((float) $cancha2->precio_por_hora, $balonPremium?->id),
                        'tipo_balon' => Reserva::BALON_PREMIUM,
                        'balon_id' => $balonPremium?->id,
                        'estado' => Reserva::ESTADO_PENDIENTE,
                        'observaciones' => 'Reserva de Carlos Mendoza (admin)',
                    ]
                );
            }
        }
    }
}
