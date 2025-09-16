// resources/js/modules/employees/audienceCounter.js

function getSelectedAudienceIds(selectEl) {
    if (!selectEl) return [];
    if (selectEl.tomselect) {
        return (selectEl.tomselect.items || []).map(v => v.toString());
    }
    return Array.from(selectEl.selectedOptions).map(opt => opt.value.toString());
}

/**
 * Llamar API para obtener el conteo de empleados y actualizar #number_participants
 */
async function updateNumberParticipantsFromAudiences(root = document) {
    try {
        const select = root.querySelector('#audiences');
        const npInput = root.querySelector('#number_participants');
        if (!select || !npInput) return;

        const ids = getSelectedAudienceIds(select);

        if (!ids.length) {
            npInput.value = '';
            return;
        }

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const token = tokenMeta ? tokenMeta.getAttribute('content') : '';

        const res = await fetch(window.countByAudiencesUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                audience_ids: ids.map(Number),
                mode: 'any', // "any" = al menos una etiqueta. Cambia a "all" si quieres intersección
            }),
        });

        if (!res.ok) {
            console.warn('No se pudo obtener el conteo de empleados por audiencias.');
            return;
        }

        const data = await res.json();
        if (data && typeof data.count === 'number') {
            npInput.value = data.count;
        }
    } catch (err) {
        console.error('Error actualizando number_participants:', err);
    }
}

/**
 * Inicializa listeners en los selects de audiencias
 */
export function setupAudienceCounter(root = document) {
    const audiencesSelect = root.querySelector('#audiences');
    if (audiencesSelect) {
        audiencesSelect.addEventListener('change', () => updateNumberParticipantsFromAudiences(root));
    }
}
