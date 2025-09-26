// resources/js/modules/check/finalizeActions.js
export function setupFinalizeActions() {
  const finalizeForm = document.getElementById("finalizeForm");
  const hiddenActivityIds = document.getElementById("finalizeActivityIds");
  if (!finalizeForm) return;

  finalizeForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Rellenar el hidden UNA sola vez con lo que está activo en la vista
    if (hiddenActivityIds) {
      hiddenActivityIds.value =
        Array.isArray(window.activeActivities) && window.activeActivities.length
          ? window.activeActivities.join(",")
          : "";
    }

    const formData = new FormData(finalizeForm);

    try {
      const response = await fetch(finalizeForm.action, {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
          "Accept": "application/json",
        },
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        alert(data.message);

        // Cerrar modal
        const modalElement = document.getElementById("confirm-finalize-modal");
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();

        // Limpiar UI actual
        const table = document.getElementById("attendanceTable");
        if (table) table.querySelector("tbody").innerHTML = "";

        const activitiesTitle = document.getElementById("activeActivitiesTitle");
        if (activitiesTitle) activitiesTitle.textContent = "Control de asistencia activo";

        if (hiddenActivityIds) hiddenActivityIds.value = "";
        window.activeActivities = [];

        // Redirigir a /check SIN query params para no resembrar IDs por historial
        window.location.replace("/check");
        return;
      } else {
        alert(data.message || "No se pudo finalizar.");
      }
    } catch (error) {
      console.error("Error en finalizeForm:", error);
      alert("Ocurrió un error inesperado. Intenta de nuevo.");
    }
  });
}
