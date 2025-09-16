// resources/js/modules/planner/exportListModal.js
import TomSelect from "tom-select";

export function setupExportListModal() {
  // Elementos del modal
  const form = document.getElementById("exportListForm");
  const tipoIndividual = document.getElementById("tipo_individual");
  const tipoGrupo = document.getElementById("tipo_grupo");

  const actividadIndividual = document.getElementById("actividad_individual");
  const actividadGrupo = document.getElementById("actividad_grupo");

  const selectIndividual = document.getElementById("activity_id_individual");
  const selectGrupo = document.getElementById("activity_ids_group");

  // Si no existe el modal, no hacemos nada
  if (
    !form ||
    !tipoIndividual ||
    !tipoGrupo ||
    !actividadIndividual ||
    !actividadGrupo ||
    !selectIndividual ||
    !selectGrupo
  ) {
    return;
  }

  // Inicializar TomSelects del modal (evitar doble-init)
  const tsIndividual =
    selectIndividual.tomselect ||
    new TomSelect(selectIndividual, {
      placeholder: "Seleccione una actividad",
      maxItems: 1,
      persist: false,
      onInitialize() {
        this.control_input.placeholder = this.settings.placeholder;
      },
    });

  const tsGrupo =
    selectGrupo.tomselect ||
    new TomSelect(selectGrupo, {
      plugins: ["remove_button"],
      placeholder: "Seleccione una o varias actividades",
      maxItems: null,
      persist: false,
      onInitialize() {
        this.control_input.placeholder = this.settings.placeholder;
      },
    });

  function toggleActividad() {
    if (tipoIndividual.checked) {
      actividadIndividual.classList.remove("d-none");
      actividadGrupo.classList.add("d-none");
      selectIndividual.removeAttribute("disabled");
      selectIndividual.setAttribute("name", "activity_id");
      selectGrupo.setAttribute("disabled", "disabled");
      tsGrupo.clear();
    } else {
      actividadGrupo.classList.remove("d-none");
      actividadIndividual.classList.add("d-none");
      selectGrupo.removeAttribute("disabled");
      selectGrupo.setAttribute("name", "activity_ids[]");
      selectIndividual.setAttribute("disabled", "disabled");
      tsIndividual.clear();
    }
  }

  // Estado inicial + listeners
  toggleActividad();
  tipoIndividual.addEventListener("change", toggleActividad);
  tipoGrupo.addEventListener("change", toggleActividad);

  // SUBMIT: export -> prepare -> redirect
  const csrf = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      // 1) Exportar
      const fd = new FormData(form);
      const exportRes = await fetch(form.action, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrf },
        body: fd,
      });
      if (!exportRes.ok) throw new Error(await exportRes.text());
      const exportJson = await exportRes.json();
      if (
        !exportJson?.success ||
        !Array.isArray(exportJson.activity_ids) ||
        exportJson.activity_ids.length === 0
      ) {
        throw new Error("Respuesta de export inválida.");
      }

      // 2) Preparar asistencias
      const prepRes = await fetch("/check/prepare", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf,
        },
        body: JSON.stringify({ activity_ids: exportJson.activity_ids }),
      });
      if (!prepRes.ok) throw new Error(await prepRes.text());
      const prepJson = await prepRes.json();
      if (!prepJson?.success) throw new Error(prepJson?.message || "Error en prepare");

      // 3) Redirigir a /check
      const idsParam = exportJson.activity_ids.join(",");
      window.location.href = `/check?activity_ids=${encodeURIComponent(idsParam)}`;
    } catch (err) {
      console.error(err);
      alert("No se pudo completar la exportación/preparación. Revisa consola.");
    }
  });
}
