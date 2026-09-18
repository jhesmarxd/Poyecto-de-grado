const confirmedForms = new WeakSet();

const assets = {
    create: '/img/iconos/calendario%20reserva%20animacion.gif',
    edit: '/img/iconos/actualizando%20.gif',
    delete: '/img/iconos/gestion-de-residuos.gif',
    loading: '/img/iconos/cargando.gif',
    success: '/img/iconos/animacion%20de%20carga%20balon%20voley%20.gif',
};

function actionFor(form) {
    if (form.dataset.confirmAction) return form.dataset.confirmAction;

    const override = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
    const method = override || (form.method || 'GET').toUpperCase();
    const target = `${form.action} ${form.textContent}`.toLowerCase();

    if (method === 'DELETE' || /eliminar|cancelar|desvincular/.test(target)) return 'delete';
    if (['PUT', 'PATCH'].includes(method) || /estado|checkin|checkout|reintentar|sincronizar/.test(target)) return 'edit';

    return 'create';
}

function copyFor(action) {
    const copies = {
        create: {
            title: '¿Confirmar creación?',
            text: 'Revisa los datos antes de guardar el nuevo registro.',
            confirm: 'Sí, crear',
        },
        edit: {
            title: '¿Confirmar cambios?',
            text: 'Se guardarán los cambios realizados.',
            confirm: 'Sí, actualizar',
        },
        delete: {
            title: '¿Confirmar esta acción?',
            text: 'Esta acción puede liberar o eliminar información relacionada.',
            confirm: 'Sí, continuar',
        },
    };

    return copies[action] || copies.edit;
}

async function confirmForm(form, options = {}) {
    const action = options.action || actionFor(form);
    const copy = copyFor(action);
    const result = await window.Swal.fire({
        title: options.title || form.dataset.confirmTitle || copy.title,
        text: options.text || form.dataset.confirmText || copy.text,
        imageUrl: assets[action] || assets.edit,
        imageWidth: 92,
        imageHeight: 92,
        imageAlt: '',
        showCancelButton: true,
        confirmButtonText: options.confirmText || copy.confirm,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: action === 'delete' ? '#dc3545' : '#005547',
        reverseButtons: true,
        focusCancel: action === 'delete',
    });

    if (!result.isConfirmed) return false;

    window.Swal.fire({
        title: 'Procesando…',
        text: 'Espera un momento.',
        imageUrl: assets.loading,
        imageWidth: 82,
        imageHeight: 82,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
    });

    return true;
}

export function initCrudDialogs(root = document) {
    root.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (confirmedForms.has(form)) {
            confirmedForms.delete(form);
            return;
        }

        const method = (form.method || 'GET').toUpperCase();
        const inContent = form.closest('main.content');
        if (method === 'GET' || !inContent || form.dataset.noConfirm !== undefined || form.id === 'chatbot-form') return;

        event.preventDefault();
        const submitter = event.submitter;
        if (await confirmForm(form)) {
            confirmedForms.add(form);
            form.requestSubmit(submitter || undefined);
        }
    });

    const feedback = root.querySelector('[data-crud-status]');
    if (feedback?.dataset.crudStatus) {
        window.Swal.fire({
            title: 'Operación completada',
            text: feedback.dataset.crudStatus,
            imageUrl: assets.success,
            imageWidth: 92,
            imageHeight: 92,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#005547',
        });
    }

    window.WallyCrud = {
        confirmForm: async (formOrId, options = {}) => {
            const form = typeof formOrId === 'string' ? document.getElementById(formOrId) : formOrId;
            if (!form || !await confirmForm(form, options)) return;
            form.submit();
        },
    };
}
