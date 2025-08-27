import { getCreateEmployeeModal } from '../shared/modals';
import { assignFormListener } from '../shared/formHandlers';
import SignaturePad from 'signature_pad';

let signaturePad = null;

// Opciones de creación de empleado
export function setupEmployeeCreate() {
    const createBtn = document.getElementById('createEmployeeBtn');
    const createForm = document.getElementById('createEmployeeForm');
    const methodInput = document.getElementById('editFormMethod');
    const idInput = document.getElementById('editEmployeeId');

    if (createBtn) {
        createBtn.addEventListener('click', () => {
            if (createForm) {
                createForm.reset();
                createForm.action = '/employees';
                if (methodInput) methodInput.value = 'POST';
                if (idInput) idInput.value = '';
            }
            getCreateEmployeeModal()?.show();
        });
    }

    if (createForm) assignFormListener(createForm);
}

// Opciones de edición de empleado
export function setupEmployeeEdit() {
    const editButtons = document.querySelectorAll('.editEmployeeBtn');
    const form = document.getElementById('createEmployeeForm');
    const methodInput = document.getElementById('editFormMethod');
    const idInput = document.getElementById('editEmployeeId');
    const modal = getCreateEmployeeModal();

    if (!editButtons || !form || !modal) return;

    editButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const row = btn.closest('tr');
            const data = row?.dataset;
            if (!data?.id) return alert('No se encontraron los datos del empleado.');

            form.reset();
            for (const key in data) {
                const input = form.querySelector(`#${key}`);
                if (input) input.value = data[key];
            }

            form.action = `/employees/${data.id}`;
            if (methodInput) methodInput.value = 'PUT';
            if (idInput) idInput.value = data.id;

            modal.show();
        });
    });
}

// Opciones de eliminación de empleado

import { getDeleteEmployeeModal } from '../shared/modals';

export function setupEmployeeDelete() {
    const deleteButtons = document.querySelectorAll('.deleteEmployeeBtn');
    const deleteForm = document.getElementById('deleteEmployeeForm');
    const deleteIdInput = document.getElementById('deleteEmployeeId');
    const passwordInput = document.getElementById('passwordConfirmation');

    assignFormListener(deleteForm);

    if (!deleteButtons || !deleteForm || !deleteIdInput || !passwordInput) return;

    deleteButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const row = btn.closest('tr');
            const employeeId = row?.dataset?.id;
            if (!employeeId) return alert('No se pudo obtener el ID del empleado.');

            deleteForm.action = `/employees/${employeeId}`;
            deleteIdInput.value = employeeId;
            passwordInput.value = '';
            passwordInput.classList.remove('is-invalid');
            getDeleteEmployeeModal()?.show();
        });
    });
}
