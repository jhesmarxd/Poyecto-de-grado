export function initAvailabilitySelection(root = document) {
    const form = root.querySelector('[data-availability-selection]');
    if (!form) return;

    const slots = [...form.querySelectorAll('[data-slot]')];
    const allowed = (form.dataset.durations || '').split(',').map(Number);
    const summary = form.querySelector('[data-selection-summary]');
    const reserve = form.querySelector('[data-reserve]');
    let selected = [];

    function minutes(value) {
        const [hour, minute] = value.split(':').map(Number);
        return hour * 60 + minute;
    }

    function render() {
        slots.forEach(slot => {
            const active = selected.includes(slot);
            slot.classList.toggle('is-selected', active);
            slot.setAttribute('aria-pressed', String(active));
        });
        const first = selected[0];
        const last = selected.at(-1);
        const duration = first ? minutes(last.dataset.end) - minutes(first.dataset.start) : 0;
        const valid = allowed.includes(duration);
        form.querySelector('[data-start-id]').value = valid ? first.dataset.id : '';
        form.querySelector('[data-end-time]').value = valid ? last.dataset.end : '';
        reserve.disabled = !valid;
        summary.textContent = first
            ? `${first.dataset.start}–${last.dataset.end} · ${duration} minutos${valid ? '' : ' (elige entre 60 y 180 minutos)'}`
            : 'Selecciona al menos 60 minutos.';
    }

    slots.forEach(slot => slot.addEventListener('click', () => {
        if (slot.disabled) return;
        if (selected.includes(slot)) {
            selected = selected.slice(0, selected.indexOf(slot));
        } else if (!selected.length) {
            selected = [slot];
        } else {
            const last = selected.at(-1);
            selected = minutes(last.dataset.end) === minutes(slot.dataset.start)
                && minutes(slot.dataset.end) - minutes(selected[0].dataset.start) <= 180
                ? [...selected, slot] : [slot];
        }
        render();
    }));
    render();
}
