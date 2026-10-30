// ============================================================
// Interacciones y animaciones de las páginas de autenticación
// (login / registro). Solo se carga en esas vistas.
// ============================================================

// Shell 2026 (toggle de tema también en las vistas de auth)
import { initShellBehaviors } from './shell';

document.addEventListener('DOMContentLoaded', () => {
    initShellBehaviors();
    // --- Mostrar / ocultar contraseña ---
    document.querySelectorAll('.password-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('is-visible', show);
            btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });

    // --- Validación interactiva de campos incompletos o vacíos ---
    const authForms = document.querySelectorAll('.auth-login-form, .auth-register-form');
    authForms.forEach((form) => {
        const requiredInputs = form.querySelectorAll('input[required]');

        requiredInputs.forEach((input) => {
            input.addEventListener('invalid', (e) => {
                if (input.validity.valueMissing) {
                    const label = input.closest('.auth-field')?.querySelector('label')?.textContent?.trim() || 'este campo';
                    input.setCustomValidity(`El campo ${label.toLowerCase()} no puede estar vacío.`);
                } else if (input.validity.typeMismatch && input.type === 'email') {
                    input.setCustomValidity('Por favor ingrese un correo electrónico válido (ejemplo: usuario@correo.com).');
                } else {
                    input.setCustomValidity('');
                }
            });

            input.addEventListener('input', () => {
                input.setCustomValidity('');
                if (input.classList.contains('is-invalid') && input.value.trim() !== '') {
                    input.classList.remove('is-invalid');
                    const feedback = input.closest('.auth-field')?.querySelector('.invalid-feedback');
                    if (feedback) feedback.style.display = 'none';
                }
            });
        });
    });

    // --- Efecto 3D sutil en la tarjeta (mouse) ---
    const card = document.querySelector('.auth-card');
    if (card && window.matchMedia('(pointer: fine)').matches) {
        // Al terminar la animación de entrada, liberamos el transform
        // para que el efecto 3D pueda aplicarse por JS.
        card.addEventListener('animationend', () => {
            card.classList.add('no-anim');
        });

        let raf = null;
        card.addEventListener('mousemove', (e) => {
            if (raf) return;
            raf = requestAnimationFrame(() => {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width - 0.5;
                const y = (e.clientY - rect.top) / rect.height - 0.5;
                card.style.transform = `perspective(900px) rotateY(${x * 4}deg) rotateX(${-y * 4}deg)`;
                raf = null;
            });
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    }
});
