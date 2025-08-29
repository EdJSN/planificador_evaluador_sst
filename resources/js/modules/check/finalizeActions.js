export function setupFinalizeActions() {
    const finalizeForm = document.getElementById("finalizeForm");

    if (!finalizeForm) return;

    finalizeForm.addEventListener("submit", async (e) => {
        e.preventDefault();

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

                // Vaciar tabla
                const table = document.getElementById("attendanceTable");
                if (table) {
                    table.querySelector("tbody").innerHTML = "";
                }

                // Limpiar título de actividades
                const activitiesTitle = document.getElementById("activeActivitiesTitle");
                if (activitiesTitle) {
                    activitiesTitle.textContent = "Control de asistencia activo";
                }

            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error("Error en finalizeForm:", error);
            alert("Ocurrió un error inesperado. Intenta de nuevo.");
        }
    });
}
