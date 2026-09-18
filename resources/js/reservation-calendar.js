const MOBILE_BREAKPOINT = 720;

function textRow(label, value) {
    const row = document.createElement('div');
    row.className = 'calendar-detail-row';
    const term = document.createElement('span');
    term.className = 'calendar-detail-label';
    term.textContent = label;
    const description = document.createElement('span');
    description.className = 'calendar-detail-value';
    description.textContent = value || '—';
    row.append(term, description);
    return row;
}

function safeGoogleUrl(value) {
    if (!value) return null;
    try {
        const url = new URL(value);
        const allowedHosts = ['calendar.google.com', 'www.google.com'];
        return url.protocol === 'https:' && allowedHosts.includes(url.hostname) ? url.href : null;
    } catch {
        return null;
    }
}

function detailContent(role, props) {
    const content = document.createElement('div');
    content.className = 'calendar-detail';
    const common = [
        ['Cancha', props.cancha],
        ['Fecha', props.fecha],
        ['Horario', `${props.hora_inicio || '—'}–${props.hora_fin || '—'}`],
        ['Estado', props.estado],
    ];
    const rows = role === 'administrador'
        ? [
            ['Cliente', props.cliente], ['Correo', props.correo], ['Teléfono', props.telefono],
            ...common, ['Balón', props.balon], ['Importe', props.monto],
            ['Observaciones', props.observaciones], ['Check-in', props.check_in],
            ['Check-out', props.check_out], ['Google Calendar', props.google_estado],
        ]
        : role === 'recepcionista'
            ? [
                ['Cliente', props.cliente], ['Teléfono', props.telefono], ...common,
                ['Balón', props.balon], ['Check-in', props.check_in], ['Check-out', props.check_out],
            ]
            : common;
    rows.forEach(([label, value]) => content.append(textRow(label, value)));

    if (role === 'administrador') {
        const url = safeGoogleUrl(props.google_url);
        if (url) {
            const link = document.createElement('a');
            link.className = 'calendar-external-link';
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = 'Abrir evento en Google Calendar';
            content.append(link);
        }
    }
    return content;
}

function calendarView() {
    return window.innerWidth <= MOBILE_BREAKPOINT ? 'listWeek' : 'dayGridMonth';
}

function initCalendar(root) {
    const target = root.querySelector('[data-calendar-target]');
    const loading = root.querySelector('[data-calendar-loading]');
    const empty = root.querySelector('[data-calendar-empty]');
    const role = root.dataset.role;
    const eventsUrl = root.dataset.eventsUrl;
    let lastMobileState = window.innerWidth <= MOBILE_BREAKPOINT;
    if (!target || !eventsUrl || !window.FullCalendar) return;

    const setLoading = (active) => {
        root.classList.toggle('is-loading', active);
        if (loading) loading.hidden = !active;
    };

    const calendar = new window.FullCalendar.Calendar(target, {
        plugins: [window.FullCalendar.luxonPlugin],
        locale: window.FullCalendar.esLocale,
        timeZone: root.dataset.timeZone || 'America/La_Paz',
        initialView: calendarView(),
        height: 'auto',
        nowIndicator: true,
        navLinks: true,
        dayMaxEvents: true,
        eventDisplay: 'block',
        displayEventTime: role === 'cliente',
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Agenda' },
        events(fetchInfo, success, failure) {
            const params = new URLSearchParams({ start: fetchInfo.startStr, end: fetchInfo.endStr });
            root.querySelectorAll('[data-calendar-filter]').forEach((filter) => {
                if (filter.value) params.set(filter.dataset.calendarFilter, filter.value);
            });
            setLoading(true);
            fetch(`${eventsUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then((events) => {
                    if (empty) empty.hidden = events.length !== 0;
                    success(events);
                })
                .catch((error) => {
                    if (empty) empty.hidden = true;
                    failure(error);
                    window.Swal?.fire({
                        icon: 'error', title: 'No se pudo cargar el calendario',
                        text: 'Intenta nuevamente o revisa tu conexión.', confirmButtonText: 'Cerrar',
                    });
                })
                .finally(() => setLoading(false));
        },
        eventClick(info) {
            info.jsEvent.preventDefault();
            window.Swal?.fire({
                title: role === 'cliente' ? info.event.extendedProps.cancha : 'Detalle de la reserva',
                html: detailContent(role, info.event.extendedProps),
                confirmButtonText: 'Cerrar',
                customClass: { popup: 'calendar-detail-dialog' },
            });
        },
    });

    root.querySelectorAll('[data-calendar-filter]').forEach((filter) => {
        filter.addEventListener('change', () => calendar.refetchEvents());
    });
    let resizeTimer;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            const mobile = window.innerWidth <= MOBILE_BREAKPOINT;
            if (mobile !== lastMobileState) {
                lastMobileState = mobile;
                calendar.changeView(mobile ? 'listWeek' : 'dayGridMonth');
            }
        }, 180);
    });
    calendar.render();
}

export function initReservationCalendars() {
    document.querySelectorAll('[data-reservation-calendar]').forEach(initCalendar);
}
