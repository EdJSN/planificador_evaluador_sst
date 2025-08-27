export default function setupTableToggle(toggleBtnId, tableBodyId) {
    const toggleBtn = document.getElementById(toggleBtnId);
    const cardBodyTable = document.getElementById(tableBodyId);

    if (toggleBtn && cardBodyTable) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            cardBodyTable.classList.toggle('body-table-double');
            cardBodyTable.classList.toggle('body-table');

            toggleBtn.innerHTML = cardBodyTable.classList.contains('body-table-double')
                ? '<i class="fa fa-compress me-2" aria-hidden="true"></i>Ver menos'
                : '<i class="fa fa-search me-2" aria-hidden="true"></i>Ver más';
        });
    }
}