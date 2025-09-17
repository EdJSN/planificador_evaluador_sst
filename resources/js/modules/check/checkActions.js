import axios from "axios";

export function setupCheckAttendance() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  // === Auxiliar: obtener los activity_ids del grupo ===
  function getActiveActivityIds() {
    // 1) Preferimos la variable global que pusiste en Blade
    if (Array.isArray(window.activeActivities) && window.activeActivities.length > 0) {
      return window.activeActivities.map(n => Number(n)).filter(Boolean);
    }
    // 2) Fallback: si tienes un hidden con CSV (p.ej. finalizeActivityIds), lo tomamos de ahí
    const hidden = document.getElementById("finalizeActivityIds");
    if (hidden && hidden.value) {
      return hidden.value
        .split(",")
        .map(s => parseInt(s.trim(), 10))
        .filter(n => !Number.isNaN(n));
    }
    // 3) Si no hay nada, devolvemos arreglo vacío (el backend lo rechazará)
    return [];
  }

  // === Guardado inmediato al cambiar un radio ===
  document.querySelectorAll(".attend-radio").forEach((radio) => {
    radio.addEventListener("change", async (e) => {
      const attendanceId = e.target.dataset.attendanceId;
      const value = e.target.value === "1";
      // Opcional (recomendado): replicar al grupo también en el cambio inmediato
      const activityIds = getActiveActivityIds();

      try {
        await axios.post(
          "/check/attendance/update",
          {
            attendance_id: Number(attendanceId),
            attend: value,
            // Envíalo si quieres que el backend replique el cambio en todas las actividades del grupo:
            activity_ids: activityIds, // <-- clave para replicar por grupo (si tu update lo soporta)
          },
          {
            headers: {
              "X-CSRF-TOKEN": csrfToken,
              Accept: "application/json",
            },
          }
        );

        console.log(`Asistencia ${attendanceId} guardada: ${value}`);
      } catch (error) {
        console.error("Error al guardar asistencia", error);
        alert("Error: No se pudo guardar la asistencia. Intenta de nuevo.");
      }
    });
  });

  // === Botón Guardar → mandar todo en lote ===
  const saveBtn = document.getElementById("saveSignatureBtn");
  if (saveBtn) {
    saveBtn.addEventListener("click", async () => {
      // 1) Recolectar un registro representativo por empleado (el checked por fila)
      const attendances = [];
      document.querySelectorAll("tr[data-attendance-id]").forEach((row) => {
        const checked = row.querySelector(".attend-radio:checked");
        if (checked) {
          attendances.push({
            id: Number(checked.dataset.attendanceId),
            attend: checked.value === "1",
          });
        }
      });

      if (attendances.length === 0) {
        alert("No hay cambios para guardar.");
        return;
      }

      // 2) IDs de actividades del grupo (OBLIGATORIO para actualizar todas)
      const activityIds = getActiveActivityIds();
      if (!Array.isArray(activityIds) || activityIds.length === 0) {
        alert(
          "No se encontraron actividades activas. Refresca la página o verifica window.activeActivities."
        );
        return;
      }

      // 3) Payload: aquí incluimos attendances + activity_ids
      const payload = {
        attendances: attendances,
        activity_ids: activityIds,
      };

      try {
        const res = await axios.post("/check/attendance/bulk-update", payload, {
          headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
          },
        });

        alert(res.data.message || "Asistencias guardadas correctamente.");
      } catch (err) {
        console.error("Error al guardar en lote", err);
        alert("Error: No se pudieron guardar las asistencias en lote.");
      }
    });
  }
}
