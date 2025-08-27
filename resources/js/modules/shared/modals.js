// Inicializa y expone los modales para edición y eliminación
let editActivityModal;
let confirmDeleteActivityModal;
let confirmDeleteEmployeeModal;
let createEmployeeModal;

export function initModals() {
    // Referencias de los modales principales
    const editElement = document.getElementById('editActivityModal');
    const confirmElement = document.getElementById('confirmDeleteActivityModal');
    const confirmEmployeeElement = document.getElementById('confirmDeleteEmployeeModal');
    const createEmployeeElement = document.getElementById('createEmployeeModal');
    const loginModalEl = document.getElementById('loginModal');
    const resetModalEl = document.getElementById('resetPasswordModal');

    // Instanciación condicional de modales
    if (editElement) editActivityModal = new bootstrap.Modal(editElement);
    if (confirmElement) confirmDeleteActivityModal = new bootstrap.Modal(confirmElement);
    if (confirmEmployeeElement) confirmDeleteEmployeeModal = new bootstrap.Modal(confirmEmployeeElement);
    if (createEmployeeElement) createEmployeeModal = new bootstrap.Modal(createEmployeeElement);

    // Corrección de residuos visuales tras cerrar modales
    document.addEventListener('hidden.bs.modal', () => {
        if (document.querySelectorAll('.modal.show').length === 0) {
            document.body.classList.remove('modal-open');
            document.body.style = '';
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        }
    });

    // Manejo modal login <-> recuperación de contraseña
    if (loginModalEl && resetModalEl) {
        const loginModal = new bootstrap.Modal(loginModalEl);
        const resetModal = new bootstrap.Modal(resetModalEl);

        document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (resetModalEl.classList.contains('show')) {
                    resetModal.hide();
                    setTimeout(() => loginModal.show(), 300);
                } else {
                    loginModal.show();
                }
            });
        });

        document.querySelectorAll('[data-bs-target="#resetPasswordModal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (loginModalEl.classList.contains('show')) {
                    loginModal.hide();
                    setTimeout(() => resetModal.show(), 300);
                } else {
                    resetModal.show();
                }
            });
        });

        // Mostrar automáticamente el modal de recuperación si hay mensaje de sesión
        if (resetModalEl.dataset.show === "true") {
            resetModal.show();
        }
    }
}

// Accesores públicos
export function getEditModal() {
    return editActivityModal;
}
export function getDeleteModal() {
    return confirmDeleteActivityModal;
}
export function getDeleteEmployeeModal() {
    return confirmDeleteEmployeeModal;
}
export function getCreateEmployeeModal() {
    return createEmployeeModal;
}
