import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";

export function setupExportListToggle() {
    const tipoIndividual = document.getElementById("tipo_individual");
    const tipoGrupo = document.getElementById("tipo_grupo");

    const actividadIndividual = document.getElementById("actividad_individual");
    const actividadGrupo = document.getElementById("actividad_grupo");

    const selectIndividual = document.getElementById("activity_id_individual");
    const selectGrupo = document.getElementById("activity_ids_group");

    // Instancias de TomSelect
    let tsIndividual = null;
    let tsGrupo = null;

    if (!tipoIndividual || !tipoGrupo || !actividadIndividual || !actividadGrupo) {
        return; // no estamos en la vista
    }

    function initTomSelect() {
        if (selectIndividual && !tsIndividual) {
            tsIndividual = new TomSelect(selectIndividual, {
                placeholder: "Seleccione una actividad",
                maxItems: 1,
                persist: false,
                onInitialize: function () {
                    this.control_input.placeholder = this.settings.placeholder;
                },
            });
        }

        if (selectGrupo && !tsGrupo) {
            tsGrupo = new TomSelect(selectGrupo, {
                plugins: ["remove_button"],
                placeholder: "Seleccione una o varias actividades",
                maxItems: null,
                persist: false,
                onInitialize: function () {
                    this.control_input.placeholder = this.settings.placeholder;
                },
            });
        }
    }

    function toggleActividad() {
        if (tipoIndividual.checked) {
            actividadIndividual.classList.remove("d-none");
            actividadGrupo.classList.add("d-none");

            selectIndividual.removeAttribute("disabled");
            selectIndividual.setAttribute("name", "activity_id");

            selectGrupo.setAttribute("disabled", "disabled");
        } else if (tipoGrupo.checked) {
            actividadGrupo.classList.remove("d-none");
            actividadIndividual.classList.add("d-none");

            selectGrupo.removeAttribute("disabled");
            selectGrupo.setAttribute("name", "activity_ids[]");

            selectIndividual.setAttribute("disabled", "disabled");
        }
    }

    // Inicializar TomSelect al cargar
    initTomSelect();
    // Mostrar la opción correcta según el radio
    toggleActividad();

    // Listeners
    tipoIndividual.addEventListener("change", toggleActividad);
    tipoGrupo.addEventListener("change", toggleActividad);
}
