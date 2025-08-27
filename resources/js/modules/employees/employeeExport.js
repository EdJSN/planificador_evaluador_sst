import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";

export function setupEmployeeExport() {
  const openBtn = document.getElementById('openExportModalBtn');
  const form = document.getElementById('exportListForm');
  const modalElement = document.getElementById('exportListModal');

  if (!openBtn || !form || !modalElement) return;

  const modal = new bootstrap.Modal(modalElement);
  let isSelectInitialized = false;

  // Abrir modal al hacer clic en el botón 
  openBtn.addEventListener('click', () => {
    modal.show();

    // Inicializar Tom Select la primera vez
    if (!isSelectInitialized) {
      const selectElement = document.getElementById('activity_id', 'areaRol');
      if (selectElement) {
        new TomSelect(selectElement, {
          plugins: ['remove_button'], 
          placeholder: "Seleccione una o varias actividades",
          maxItems: null,
          persist: false,
          onInitialize: function () {
            this.control_input.placeholder = this.settings.placeholder;
          },
        });
        isSelectInitialized = true;
      } else {
        console.error("No se encontró #activity_id en el DOM");
      }
    }
  });

  // Manejar envío del formulario 
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    try {
      const response = await fetch(form.action || '/check/prepare', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN':
            document.querySelector('meta[name="csrf-token"]').content
        }
      });

      if (!response.ok) throw new Error('Error al preparar la exportación');

      const data = await response.json();

      alert(data.message);
      modal.hide();

    } catch (error) {
      console.error(error);
      alert('Hubo un problema al exportar');
    }
  });
}
