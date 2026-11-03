const MINUTE_MS = 60 * 1000;

export function getReservationCountdownState(nowMs, startMs, endMs) {
    if (![nowMs, startMs, endMs].every(Number.isFinite) || endMs <= startMs) {
        return null;
    }

    if (nowMs >= startMs) {
        return {
            phase: 'finished',
            status: 'Buscando la siguiente reserva',
            caption: 'Actualizando',
            remainingMs: 0,
        };
    }

    const remainingMs = startMs - nowMs;

    if (remainingMs <= 10 * MINUTE_MS) {
        return { phase: 'ten', status: 'Comienza en menos de 10 minutos', caption: 'Comienza en', remainingMs };
    }

    if (remainingMs <= 30 * MINUTE_MS) {
        return { phase: 'thirty', status: 'Comienza en menos de 30 minutos', caption: 'Comienza en', remainingMs };
    }

    if (remainingMs < 60 * MINUTE_MS) {
        return { phase: 'hour', status: 'Comienza en menos de 1 hora', caption: 'Comienza en', remainingMs };
    }

    return { phase: 'upcoming', status: 'Reserva próxima', caption: 'Comienza en', remainingMs };
}

export function formatReservationCountdown(milliseconds) {
    const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return { days, hours, minutes, seconds, showSeconds: milliseconds < 60 * MINUTE_MS };
}

const initializedCountdowns = new WeakSet();

function initializeCountdown(element) {
    if (initializedCountdowns.has(element)) return;
    initializedCountdowns.add(element);
    const startMs = Number(element.dataset.startAt);
    const endMs = Number(element.dataset.endAt);
    const serverNowMs = Number(element.dataset.serverNow);

    if (![startMs, endMs, serverNowMs].every(Number.isFinite)) {
        return;
    }

    const clockOffsetMs = serverNowMs - Date.now();
    const status = element.querySelector('[data-countdown-status]');
    const caption = element.querySelector('[data-countdown-caption]');
    const days = element.querySelector('[data-countdown-days]');
    const hours = element.querySelector('[data-countdown-hours]');
    const minutes = element.querySelector('[data-countdown-minutes]');
    const seconds = element.querySelector('[data-countdown-seconds]');
    const secondsUnit = element.querySelector('[data-countdown-seconds-unit]');
    const animation = element.querySelector('[data-countdown-animation]');
    let intervalId = null;
    let reloading = false;

    const render = () => {
        const state = getReservationCountdownState(Date.now() + clockOffsetMs, startMs, endMs);

        if (!state) {
            return null;
        }

        element.dataset.phase = state.phase;
        status.textContent = state.status;
        caption.textContent = state.caption;
        const remaining = formatReservationCountdown(state.remainingMs);
        days.textContent = String(remaining.days).padStart(2, '0');
        hours.textContent = String(remaining.hours).padStart(2, '0');
        minutes.textContent = String(remaining.minutes).padStart(2, '0');
        seconds.textContent = String(remaining.seconds).padStart(2, '0');
        secondsUnit.hidden = !remaining.showSeconds;
        if (animation) {
            animation.src = state.phase === 'active'
                ? animation.dataset.activeSrc
                : animation.dataset.upcomingSrc;
        }

        if (state.phase === 'finished' && intervalId !== null) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
        if (state.phase === 'finished' && !reloading) {
            reloading = true;
            window.location.reload();
        }

        return state.phase;
    };

    const initialPhase = render();

    if (initialPhase !== 'finished') {
        intervalId = window.setInterval(render, 1000);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            render();
        }
    });

    window.addEventListener('pageshow', render);
}

export function initReservationCountdowns(root = document) {
    root.querySelectorAll('[data-reservation-countdown]').forEach(initializeCountdown);
}
