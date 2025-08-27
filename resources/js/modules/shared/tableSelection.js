// Controla la selección de filas en la tabla de actividades

let selectedRow = null;

export function setupRowSelection(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.addEventListener('click', (event) => {
        const row = event.target.closest('tr');
        if (!row?.dataset.id) return;

        if (selectedRow) selectedRow.classList.remove('table-primary');
        selectedRow = row;
        selectedRow.classList.add('table-primary');
    });
}

export function getSelectedRow() {
    return selectedRow;
}
