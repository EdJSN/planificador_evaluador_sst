import { assignFormListener } from '../shared/formHandlers';

export function setupSettingsUsers() {
  const form = document.getElementById('createUserForm');
  assignFormListener(form);
}

export function setupSettingsActions() {
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form');
    if (!form || !form.id.startsWith('editUserForm') && !form.id.startsWith('deleteUserForm')) return;

    e.preventDefault();

    const token = document.querySelector('meta[name="csrf-token"]').content;

    try {
      const res = await fetch(form.action, {
        method: form.method || 'POST',
        headers: { 'X-CSRF-TOKEN': token },
        body: new FormData(form),
      });

      const data = await res.json();

      if (!res.ok) {
        // Error de validación
        if (res.status === 422 && data.errors) {
          const firstError = Object.values(data.errors)[0]?.[0] || 'Error de validación.';
          alert(firstError);
        } else {
          alert(data.message || 'Ocurrió un error en la operación.');
        }
        return;
      }

      // Éxito
      alert(data.message || 'Operación completada.');
      location.reload();

    } catch (err) {
      alert('Error de conexión con el servidor.');
      console.error(err);
    }
  });
}

