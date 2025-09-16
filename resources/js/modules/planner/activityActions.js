import { assignFormListener } from '../shared/formHandlers';
import { getSelectedRow } from '../shared/tableSelection';
import { getEditModal, getDeleteModal } from '../shared/modals';
import { setupAudienceCounter } from '../employees/audienceCounter';

export function initPlannerActions() {
    const editButton = document.getElementById('selectBtn');
    const deleteButton = document.getElementById('deleteActivityButton');
    const editModalElement = document.getElementById('editActivityModal');
    const deleteActivityForm = document.getElementById('deleteActivityForm');
    const deleteActivityIdInput = document.getElementById('deleteActivityId');
    const passwordInput = document.getElementById('passwordConfirmation');
    const modalTitle = document.getElementById('editActivityModalLabel');
    const createForm = document.getElementById('createActivityForm');

    assignFormListener(createForm);
    assignFormListener(deleteActivityForm);

    if (editModalElement) {
        editModalElement.addEventListener('shown.bs.modal', () => {
            const editForm = document.getElementById('editActivityForm');
            assignFormListener(editForm);
        });
    }

    if (editButton) {
        editButton.addEventListener('click', (e) => {
            e.preventDefault();
            const row = getSelectedRow();
            if (!row) return alert('Selecciona una actividad para editar.');
            const data = row.dataset;
            const form = document.getElementById('editActivityForm');
            if (!form) return alert('No se encontró el formulario de edición.');

            form.reset();
            for (const key in data) {
                const input = form.querySelector(`#${key}`);

                if (key === 'id') {
                    const idInput = form.querySelector('#editActivityId');
                    if (idInput) idInput.value = data[key] ?? '';
                }
                else if (input) {
                    if (input.tagName === 'SELECT' && input.multiple) {
                        const selectedValues = (data[key] || '').split(',').map(v => v.trim());

                        // Si está inicializado con TomSelect
                        if (input.tomselect) {
                            input.tomselect.setValue(selectedValues, true); // true = silencioso, sin disparar eventos extra
                        } else {
                            // Fallback por si no tiene TomSelect
                            [...input.options].forEach(opt => {
                                opt.selected = selectedValues.includes(opt.value.toString());
                            });
                        }
                    }
                    else if (input.tagName === 'SELECT' || input.tagName === 'TEXTAREA') {
                        // select simple o textarea
                        input.value = data[key] ?? '';
                    }
                    else if (input.type === 'date') {
                        // Fechas (ya vienen en formato YYYY-MM-DD desde el Blade)
                        input.value = data[key]
                            ? new Date(data[key]).toISOString().split('T')[0]
                            : '';
                    }
                    else if (input.type === 'time') {
                        // Horas (ya vienen en formato HH:mm desde el Blade)
                        input.value = data[key] || '';
                    }
                    else {
                        // Campos de texto u otros
                        input.value = data[key] ?? '';
                    }
                }
            }

            form.action = `/planner/${data.id}`;
            const methodInput = form.querySelector('#editFormMethod');
            if (methodInput) methodInput.value = 'PUT';
            if (modalTitle) modalTitle.textContent = `Editar Actividad (ID: ${data.id})`;

            getEditModal()?.show();
        });
    }

    if (deleteButton) {
        deleteButton.addEventListener('click', () => {
            const row = getSelectedRow();
            if (!row) return alert('Selecciona una actividad para eliminar.');

            deleteActivityIdInput.value = row.dataset.id;
            passwordInput.value = '';
            passwordInput.classList.remove('is-invalid');
            deleteActivityForm.action = `/planner/${row.dataset.id}`;
            getDeleteModal()?.show();
        });
    }

    if (editModalElement) {
        editModalElement.addEventListener('shown.bs.modal', () => {
            const editForm = document.getElementById('editActivityForm');
            if (editForm) {
                setupAudienceCounter(editForm);
            }
        });
    }
}
