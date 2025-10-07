// resources/js/modules/check/finalizeActions.js
export function setupFinalizeActions() {
  const finalizeForm = document.getElementById("finalizeForm");
  const hiddenActivityIds = document.getElementById("finalizeActivityIds");
  if (!finalizeForm) return;

  finalizeForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Rellenar el hidden con los IDs activos visibles
    if (hiddenActivityIds) {
      hiddenActivityIds.value =
        Array.isArray(window.activeActivities) && window.activeActivities.length
          ? window.activeActivities.join(",")
          : "";
    }

    const formData = new FormData(finalizeForm);

    // --- CAMBIO ÚNICO ---
    // Si no hay firma nueva válida (data:image/png;base64,...) eliminamos el campo
    // para que el backend pueda reutilizar la firma previa si existe.
    const sigFieldName = "facilitator_signature";
    const sig = formData.get(sigFieldName);
    if (!sig || typeof sig !== "string" || !sig.startsWith("data:image/png;base64,")) {
      formData.delete(sigFieldName);
    }
    // --- FIN CAMBIO ÚNICO ---

    try {
      const response = await fetch(finalizeForm.action, {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
          Accept: "application/json",
        },
        body: formData,
      });

      const data = await response.json();

      if (response.ok && data.success) {
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

        // Redirigir a /check
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
