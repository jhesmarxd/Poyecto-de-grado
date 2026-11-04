import './bootstrap';

// Bootstrap 5
import 'bootstrap';

// jQuery global (requerido por DataTables)
import $ from 'jquery';
window.$ = window.jQuery = $;

// DataTables con estilo Bootstrap 5
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';

// SweetAlert2
import Swal from 'sweetalert2';
window.Swal = Swal;

// FullCalendar (calendario de reservas) — en v6 el CSS va embebido en el bundle
import { Calendar } from 'fullcalendar';
import esLocale from '@fullcalendar/core/locales/es';
import luxonPlugin from '@fullcalendar/luxon3';
window.FullCalendar = { Calendar, esLocale, luxonPlugin };

// Chart.js (gráficos del dashboard)
import Chart from 'chart.js/auto';
window.Chart = Chart;

// Widget de chat del chatbot
import './chatbot';

// Shell 2026 (tema, dropdowns, nav-groups, drawer)
import { initShellBehaviors } from './shell';
import { initSequentialRowNumbers } from './table-numbering';
import { initReservationCalendars } from './reservation-calendar';
import { initReservationCountdowns } from './reservation-countdown';
import { initCrudDialogs } from './crud-dialogs';
import { initCanchaPickers } from './cancha-picker';
import { initReservationCheckout } from './reservation-checkout';
import { initAvailabilitySelection } from './availability-selection';

document.addEventListener('DOMContentLoaded', () => {
    initShellBehaviors();
    initSequentialRowNumbers();
    initReservationCalendars();
    initReservationCountdowns();
    initCrudDialogs();
    initCanchaPickers();
    initReservationCheckout();
    initAvailabilitySelection();
});
