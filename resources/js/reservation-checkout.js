const phonePattern = /^(?:\+591)?[67][0-9]{7}$/;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
}

function detail(label, value) {
    return `<div class="booking-confirmation__row"><dt>${label}</dt><dd>${escapeHtml(value || '—')}</dd></div>`;
}

async function responseData(response) {
    const data = await response.json().catch(() => ({}));
    if (response.ok) return data;
    const firstError = Object.values(data.errors || {})[0];
    const message = Array.isArray(firstError) ? firstError[0] : data.message;
    const error = new Error(response.status === 409
        ? 'El horario o balón seleccionado acaba de ocuparse. Vuelve a disponibilidad y elige otro.'
        : (message || 'No se pudo completar la solicitud. Inténtalo de nuevo.'));
    error.status = response.status;
    throw error;
}

export function initReservationCheckout(root = document) {
    const form = root.querySelector('[data-reservation-checkout]');
    if (!form || !window.Swal) return;

    const button = form.querySelector('button[type="submit"]');
    let busy = false;

    async function requestPhone() {
        const result = await window.Swal.fire({
            title: 'Registra tu celular',
            text: 'Lo necesitamos para confirmar tu reserva. Quedará guardado en tu perfil.',
            input: 'tel',
            inputLabel: 'Número de celular',
            inputValue: form.dataset.phone || '',
            inputAttributes: { autocomplete: 'tel', inputmode: 'tel', maxlength: '12' },
            showCancelButton: true,
            confirmButtonText: 'Guardar y continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#008C95',
            inputValidator: value => phonePattern.test(value.trim())
                ? undefined : 'Ingresa 8 dígitos que comiencen con 6 o 7. También puedes usar +591.',
        });
        if (!result.isConfirmed) return false;

        const telefono = result.value.trim();
        const response = await fetch(form.dataset.phoneUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
            },
            body: new URLSearchParams({ telefono }),
            credentials: 'same-origin',
        });
        const data = await responseData(response);
        form.dataset.phone = data.telefono;
        return true;
    }

    function summary() {
        const selected = form.querySelector('input[name="balon_seleccion"]:checked');
        const price = Number(selected?.dataset.precio || 0);
        const minutes = Number(form.dataset.duration || 0);
        const cost = (price * minutes / 60).toFixed(2);
        const ball = selected?.dataset.tipo === 'propio'
            ? 'Balón propio' : `Balón ${selected?.dataset.marca || 'del recinto'}`;

        return `<dl class="booking-confirmation">`
            + detail('Cancha', form.dataset.court)
            + detail('Características', form.dataset.features)
            + detail('Fecha', form.dataset.date)
            + detail('Horario', `${form.dataset.start} – ${form.dataset.end}`)
            + detail('Duración', `${minutes} minutos`)
            + detail('Balón', ball)
            + detail('Costo total', `Bs. ${cost}`)
            + detail('Titular', form.dataset.customer)
            + detail('Celular', form.dataset.phone)
            + detail('Correo', form.dataset.email)
            + `</dl>`;
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        button.disabled = true;

        try {
            if (!phonePattern.test(form.dataset.phone || '') && !await requestPhone()) return;

            const confirmation = await window.Swal.fire({
                title: 'Confirma tu reserva',
                html: summary(),
                width: 'min(92vw, 620px)',
                showCancelButton: true,
                confirmButtonText: 'Confirmar reserva',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#008C95',
                focusCancel: true,
            });
            if (!confirmation.isConfirmed) return;

            window.Swal.fire({
                title: 'Estamos realizando tu reserva...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => window.Swal.showLoading(),
            });

            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            await responseData(response);

            await window.Swal.fire({
                icon: 'success',
                title: '¡Tu reserva se realizó correctamente!',
                timer: 1700,
                showConfirmButton: false,
            });
            window.location.assign(form.dataset.listUrl);
        } catch (error) {
            const conflict = error.status === 409;
            const result = await window.Swal.fire({
                icon: 'error', title: conflict ? 'Horario no disponible' : 'No se pudo realizar la reserva',
                text: error.message,
                showCancelButton: conflict,
                confirmButtonText: conflict ? 'Elegir otro horario' : 'Entendido',
                cancelButtonText: 'Revisar selección',
            });
            if (conflict && result.isConfirmed) {
                window.location.assign(form.querySelector('[data-availability-url]').value);
            }
        } finally {
            busy = false;
            button.disabled = false;
        }
    });
}
