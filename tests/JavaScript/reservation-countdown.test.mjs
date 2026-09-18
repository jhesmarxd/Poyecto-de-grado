import assert from 'node:assert/strict';
import {
    formatReservationCountdown,
    getReservationCountdownState,
} from '../../resources/js/reservation-countdown.js';

const start = Date.UTC(2026, 8, 15, 18, 0, 0);
const end = Date.UTC(2026, 8, 15, 19, 0, 0);

assert.equal(getReservationCountdownState(start - 2 * 60 * 60 * 1000, start, end).phase, 'upcoming');
assert.equal(getReservationCountdownState(start - 59 * 60 * 1000, start, end).phase, 'hour');
assert.equal(getReservationCountdownState(start - 29 * 60 * 1000, start, end).phase, 'thirty');
assert.equal(getReservationCountdownState(start - 9 * 60 * 1000, start, end).phase, 'ten');

const active = getReservationCountdownState(start + 15 * 60 * 1000, start, end);
assert.equal(active.phase, 'active');
assert.equal(active.caption, 'Finaliza en');
assert.equal(formatReservationCountdown(active.remainingMs), '00:45:00');

assert.deepEqual(getReservationCountdownState(end, start, end), {
    phase: 'finished',
    status: 'Reserva finalizada',
    caption: 'Tiempo cumplido',
    remainingMs: 0,
});

assert.equal(formatReservationCountdown(49 * 60 * 60 * 1000 + 2000), '49:00:02');
assert.equal(getReservationCountdownState(start, end, start), null);

console.log('Temporizador: fases, formato, reserva en curso y finalización verificados.');
