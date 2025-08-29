import axios from "axios";

export function setupCheckAttendance() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Guardado inmediato al cambiar un radio
    document.querySelectorAll(".attend-radio").forEach(radio => {
        radio.addEventListener("change", async (e) => {
            const attendanceId = e.target.dataset.attendanceId;
            const value = e.target.value === "1" ? true : false;

            try {
                await axios.post("/check/attendance/update", {
                    attendance_id: attendanceId,
                    attend: value,
                }, {
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });

                console.log(`Asistencia ${attendanceId} guardada: ${value}`);
            } catch (error) {
                console.error("Error al guardar asistencia", error);
                alert("Error: No se pudo guardar la asistencia. Intenta de nuevo.");
            }
        });
    });

    // Botón Guardar → mandar todo en lote (id de attendance representativo por empleado)
    const saveBtn = document.getElementById("saveSignatureBtn");
    if (saveBtn) {
        saveBtn.addEventListener("click", async () => {
            // Recolectar un registro representativo por empleado (el checked por fila)
            const attendances = [];
            // iteramos por cada fila que tenga data-attendance-id:
            document.querySelectorAll("tr[data-attendance-id]").forEach(row => {
                const checked = row.querySelector(".attend-radio:checked");
                if (checked) {
                    attendances.push({
                        id: checked.dataset.attendanceId,
                        attend: checked.value === "1" ? true : false
                    });
                }
            });

            if (attendances.length === 0) {
                alert("No hay cambios para guardar.");
                return;
            }

            try {
                const res = await axios.post("/check/attendance/bulk-update", {
                    attendances: attendances
                }, {
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });

                alert(res.data.message || "Asistencias guardadas correctamente.");
            } catch (err) {
                console.error("Error al guardar en lote", err);
                alert("Error: No se pudieron guardar las asistencias en lote.");
            }
        });
    }
}
