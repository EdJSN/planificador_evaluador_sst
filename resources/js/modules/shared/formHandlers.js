// Asigna listeners a formularios y gestiona su envío con fetch y validación

export function assignFormListener(form) {
    if (!form || form.hasAttribute('data-listener-assigned')) return;

    form.setAttribute('data-listener-assigned', 'true');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return alert('Token CSRF no encontrado.');

        const url = form.action || '/'; // Usa action del form o '/' por defecto

        // Validación especial para contraseña en eliminación
        if (form.id === 'deleteActivityForm') {
            const passwordInput = form.querySelector('#passwordConfirmation');
            if (passwordInput && passwordInput.value.trim() === '') {
                passwordInput.classList.add('is-invalid');
                return alert('La contraseña es requerida.');
            } else {
                passwordInput.classList.remove('is-invalid');
            }
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                }
            });

            const data = await response.json();

            if (response.ok) {
                const messages = {
                    deleteActivityForm: 'Actividad eliminada exitosamente.',
                    createActivityForm: 'Actividad creada exitosamente.',
                    editActivityForm: 'Actividad actualizada exitosamente.',
                    createEmployeeForm: 'Empleado registrado exitosamente.',
                    editEmployeeForm: 'Empleado actualizado exitosamente.',
                    deleteEmployeeForm: 'Empleado eliminado exitosamente.',
                };
                alert(messages[form.id] || 'Operación completada.');
                form.reset();
                window.location.reload();
            } else {
                // Si hay errores de validación
                if (data.errors) {
                    let mensaje = 'Se encontraron errores:\n';
                    for (const campo in data.errors) {
                        mensaje += `- ${data.errors[campo].join(', ')}\n`;
                    }
                    alert(mensaje);
                } else {
                    alert(data.message || 'Ocurrió un error en el servidor.');
                }
            }
        } catch (error) {
            console.error('Error al enviar formulario:', error);
            alert('Error inesperado al procesar el formulario.');
        }
    });
}

