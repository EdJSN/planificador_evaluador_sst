//Script para confirmación de desvinculación de actividad

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.unlink-activity');
    if (!btn) return;

    const url = btn.dataset.url;
    if (!url) return;

    if (!confirm('¿Quitar esta actividad del control?')) return;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        });

        const json = await res.json();

        if (!res.ok || !json.success) {
            alert(json.message || 'No se pudo desvincular.');
            return;
        }

        alert(json.message || 'Desvinculado.');
        location.reload();
    } catch (err) {
        console.error(err);
        alert('Error de red al intentar desvincular.');
    }
});