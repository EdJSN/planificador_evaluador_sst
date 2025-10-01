export default function setupTableToggle(toggleBtnId, tableBodyId) {
  const toggleBtn = document.getElementById(toggleBtnId);
  const cardBodyTable = document.getElementById(tableBodyId);
  if (!toggleBtn || !cardBodyTable) return;

  // Clave única por página y por tabla (evita choques si hay varias)
  const STORAGE_KEY = `tableToggle:${location.pathname}:${tableBodyId}`;

  // Aplica estado y actualiza el botón
  const applyState = (expanded) => {
    cardBodyTable.classList.toggle('body-table-double', expanded);
    cardBodyTable.classList.toggle('body-table', !expanded);

    toggleBtn.innerHTML = expanded
      ? '<i class="fa fa-compress me-2" aria-hidden="true"></i>Ver menos'
      : '<i class="fa fa-search me-2" aria-hidden="true"></i>Ver más';
  };

  // 1) Estado inicial: toma lo guardado; si no hay, usa lo que tenga la DOM
  const saved = localStorage.getItem(STORAGE_KEY);
  const initialExpanded = saved === null
    ? cardBodyTable.classList.contains('body-table-double')
    : saved === '1';

  applyState(initialExpanded);

  // 2) Toggle y persistencia
  toggleBtn.addEventListener('click', (e) => {
    e.preventDefault();
    const nowExpanded = !cardBodyTable.classList.contains('body-table-double');
    applyState(nowExpanded);
    localStorage.setItem(STORAGE_KEY, nowExpanded ? '1' : '0');
  });
}
