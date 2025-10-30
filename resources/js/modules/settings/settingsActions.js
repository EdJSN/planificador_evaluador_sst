// resources/js/modules/settings/settingsActions.js
import { assignFormListener } from '../shared/formHandlers';

export function setupSettingsUsers() {
  const form = document.getElementById('createUserForm');
  if (form) assignFormListener(form);
}

export function setupSettingsActions() {
  // 👇 Agregamos el patrón de los formularios de edición reales: editUserModal…Form
  const forms = document.querySelectorAll(
    'form[id^="editUserForm"], form[id^="editUserModal"][id$="Form"], form[id^="deleteUserForm"]'
  );

  forms.forEach((form) => {
    if (form.dataset.settingsBound === '1') return;
    form.dataset.settingsBound = '1';

    form.addEventListener(
      'submit',
      async (e) => {
        e.preventDefault();
        e.stopPropagation();

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
          alert('Error: token CSRF no encontrado.');
          return;
        }

        try {
          const res = await fetch(form.action, {
            method: form.method || 'POST', // POST + _method=PUT/DELETE viene en el FormData
            headers: { 'X-CSRF-TOKEN': token },
            body: new FormData(form),
          });

          let data = null;
          try { data = await res.json(); } catch { /* puede no haber JSON (204, etc.) */ }

          if (!res.ok) {
            const msg =
              (data && data.errors && Object.values(data.errors)[0]?.[0]) ||
              (data && data.message) ||
              'Ocurrió un error.';
            alert(msg);
            return;
          }

          alert((data && data.message) || 'Operación completada.');

          // Cierra el modal si el form está dentro de uno
          const modalEl = form.closest('.modal');
          if (modalEl && window.bootstrap?.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
          }

          setTimeout(() => window.location.reload(), 400);
        } catch (err) {
          console.error('Error en fetch:', err);
          alert('Error de conexión con el servidor.');
        }
      },
      { capture: true }
    );
  });
}
