import assert from 'node:assert/strict';
import { calendarViewForViewport } from '../../resources/js/reservation-calendar.js';

assert.equal(calendarViewForViewport('cliente', 390), 'dayGridMonth');
assert.equal(calendarViewForViewport('cliente', 1440), 'dayGridMonth');
assert.equal(calendarViewForViewport('administrador', 390), 'listWeek');
assert.equal(calendarViewForViewport('recepcionista', 720), 'listWeek');
assert.equal(calendarViewForViewport('administrador', 1024), 'dayGridMonth');

console.log('reservation-calendar responsive view tests: ok');
