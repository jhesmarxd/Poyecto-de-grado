const confirmedForms = new WeakSet();

/**
 * Determines the CRUD action type ('create' | 'edit' | 'delete')
 * based on explicit dataset attributes, HTTP method overrides, submit button cues, and URL.
 */
function actionFor(form, submitter) {
    // 1. Explicit action override on submit button or form element
    if (submitter?.dataset?.confirmAction) return submitter.dataset.confirmAction;
    if (form.dataset.confirmAction) return form.dataset.confirmAction;

    // 2. HTTP method override via hidden _method input (standard Laravel RESTful forms)
    const override = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
    const method = override || (form.method || 'GET').toUpperCase();

    if (method === 'DELETE') return 'delete';

    // 3. Submitter button attributes and semantics (if submitted by clicking a specific button)
    if (submitter) {
        const submitText = (submitter.textContent || submitter.value || '').trim().toLowerCase();
        const submitClass = submitter.className || '';

        // Delete / remove / danger button
        if (
            /eliminar|borrar|baja|desvincular|remover/.test(submitText) ||
            submitClass.includes('btn-danger') ||
            submitClass.includes('btn--danger') ||
            submitClass.includes('text-danger')
        ) {
            return 'delete';
        }

        // Edit / update button
        if (/actualizar|editar|modificar/.test(submitText)) {
            return 'edit';
        }

        // Save / create / store button
        if (/guardar|crear|registrar|agregar|nuevo|publicar|aplicar/.test(submitText)) {
            return ['PUT', 'PATCH'].includes(method) ? 'edit' : 'create';
        }
    }

    // 4. HTTP method: PUT or PATCH indicates an update/edit operation
    if (['PUT', 'PATCH'].includes(method)) return 'edit';

    // 5. Action URL inspection
    const actionUrl = (form.getAttribute('action') || form.action || '').toLowerCase();
    if (/(eliminar|destroy|delete|baja)/.test(actionUrl)) return 'delete';
    if (/(editar|update|modificar)/.test(actionUrl)) return 'edit';
    if (/(crear|guardar|store|nuevo|registrar)/.test(actionUrl)) return 'create';

    // 6. State or operational action keywords
    if (/estado|checkin|checkout|reintentar|sincronizar/.test(actionUrl)) return 'edit';

    // 7. Default standard POST to 'create'
    return 'create';
}

/**
 * Provides standardized copy, native SweetAlert2 icon, and cohesive brand button colors.
 */
function copyFor(action) {
    const copies = {
        create: {
            title: '¿Confirmar registro?',
            text: 'Revisa los datos antes de guardar el nuevo registro.',
            confirm: 'Sí, guardar',
            icon: 'question',
            confirmButtonColor: '#008C95', // Wally Teal Brand Color
            cancelButtonColor: '#64748B',  // Slate Gray
        },
        edit: {
            title: '¿Guardar cambios?',
            text: 'Se actualizarán los datos con la información modificada.',
            confirm: 'Sí, actualizar',
            icon: 'question',
            confirmButtonColor: '#008C95', // Wally Teal Brand Color
            cancelButtonColor: '#64748B',  // Slate Gray
        },
        delete: {
            title: '¿Confirmar eliminación?',
            text: 'Esta acción no se puede deshacer y eliminará el registro.',
            confirm: 'Sí, eliminar',
            icon: 'warning',
            confirmButtonColor: '#EF4444', // Danger Red
            cancelButtonColor: '#64748B',  // Slate Gray
        },
    };

    return copies[action] || copies.create;
}

/**
 * Displays a clean, vector-animated SweetAlert2 confirmation dialog.
 */
async function confirmForm(form, options = {}) {
    const submitter = options.submitter;
    const action = options.action || actionFor(form, submitter);
    const copy = copyFor(action);

    const result = await window.Swal.fire({
        title: options.title || form.dataset.confirmTitle || copy.title,
        text: options.text || form.dataset.confirmText || copy.text,
        icon: options.icon || form.dataset.confirmIcon || copy.icon,
        showCancelButton: true,
        confirmButtonText: options.confirmText || copy.confirm,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: options.confirmButtonColor || copy.confirmButtonColor,
        cancelButtonColor: options.cancelButtonColor || copy.cancelButtonColor,
        reverseButtons: true,
        focusCancel: action === 'delete',
    });

    if (!result.isConfirmed) return false;

    window.Swal.fire({
        title: 'Procesando…',
        text: 'Por favor, espera un momento.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            window.Swal.showLoading();
        },
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
        if (await confirmForm(form, { submitter })) {
            confirmedForms.add(form);
            form.requestSubmit(submitter || undefined);
        }
    });

    const feedback = root.querySelector('[data-crud-status]');
    if (feedback?.dataset.crudStatus) {
        window.Swal.fire({
            icon: 'success',
            title: '¡Operación exitosa!',
            text: feedback.dataset.crudStatus,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#008C95',
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
