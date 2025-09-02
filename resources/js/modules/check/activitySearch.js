const form = document.querySelector("#searchActivityForm");
const input = document.querySelector("#searchInput");
const tableBody = document.querySelector("#activitiesResultsBody");

function escapeHtml(text) {
    if (!text && text !== 0) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
}

if (form && tableBody) {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const search = input.value.trim();
        const tokenInput = document.querySelector('input[name="_token"]');
        const token = tokenInput ? tokenInput.value : '';

        try {
            const response = await fetch("/check/search", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                },
                body: JSON.stringify({ searchInput: search }),
            });

            // Si el servidor devolvió HTML de error, mostrarlo para debug
            if (!response.ok) {
                const txt = await response.text();
                console.error("Respuesta no OK", response.status, txt);
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Error en el servidor (${response.status})</td></tr>`;
                return;
            }

            const activities = await response.json();

            // Limpiar tabla
            tableBody.innerHTML = "";

            if (!activities || activities.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center">No se encontraron actividades</td></tr>`;
                return;
            }

            // Insertar resultados (una fila por actividad)
            activities.forEach(act => {
                const topic = escapeHtml(act.topic ?? '');
                const dateFormatted = escapeHtml(act.estimated_date_formatted ?? '');
                const stateLabel = escapeHtml(act.states_label ?? act.states ?? '');

                const row = `
                    <tr>
                        <td>${topic}</td>
                        <td>${dateFormatted}</td>
                        <td>${stateLabel}</td>
                        <td class="text-center">
                            <button class="btn btnAzlo-dark" data-activity-id="${act.id}">
                                <i class="fa fa-print"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML("beforeend", row);
            });
        } catch (error) {
            console.error("Error en búsqueda:", error);
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Error en la búsqueda</td></tr>`;
        }
    });
}
