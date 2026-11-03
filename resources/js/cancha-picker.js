export function initCanchaPickers() {
    document.querySelectorAll('[data-cancha-picker]').forEach((picker) => {
        const label = picker.querySelector('[data-cancha-picker-label]');
        if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            picker.addEventListener('pointerenter', () => { picker.open = true; });
            picker.addEventListener('pointerleave', () => { picker.open = false; });
        }
        picker.addEventListener('change', (event) => {
            if (event.target.matches('input[name="cancha_id"]')) {
                label.textContent = event.target.dataset.canchaName;
                picker.open = false;
            }
        });
        picker.querySelectorAll('input[name="cancha_id"]').forEach((input) => {
            input.addEventListener('invalid', () => { picker.open = true; });
        });
        document.addEventListener('click', (event) => {
            if (!picker.contains(event.target)) picker.open = false;
        });
    });
}
