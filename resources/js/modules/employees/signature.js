document.addEventListener('DOMContentLoaded', () => {
  if (!window.location.pathname.includes('/employees')) return;

  const modalEl     = document.getElementById('createEmployeeModal');
  const canvas      = document.getElementById('signatureCanvas');
  const input       = document.getElementById('signatureInput');
  const clearBtn    = document.getElementById('clearSignatureBtn');
  const methodInput = document.getElementById('editFormMethod');
  const idInput     = document.getElementById('editEmployeeId');

  if (!modalEl || !canvas || !input || !clearBtn) return;

  const ctx = canvas.getContext('2d');
  let drawing = false;

  function resizeCanvas() {
    const dataUrl = input.value || null;
    canvas.width  = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.lineWidth = 2;
    ctx.lineJoin  = 'round';
    ctx.lineCap   = 'round';
    if (dataUrl) drawFromDataUrl(dataUrl);
  }

  function drawFromDataUrl(dataUrl) {
    const img = new Image();
    img.onload = () => ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    img.src = dataUrl;
  }

  function start(e) {
    drawing = true;
    ctx.beginPath();
    const { x, y } = getPos(e);
    ctx.moveTo(x, y);
  }
  function move(e) {
    if (!drawing) return;
    const { x, y } = getPos(e);
    ctx.lineTo(x, y);
    ctx.stroke();
  }
  function end() {
    if (!drawing) return;
    drawing = false;
    ctx.closePath();
    input.value = canvas.toDataURL('image/png'); // guarda base64 en hidden
  }
  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: clientX - rect.left, y: clientY - rect.top };
  }

  clearBtn.addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    input.value = ''; // => en update preserva firma anterior
  });

  // Redimensiona sin perder lo dibujado (manteniendo hidden)
  window.addEventListener('resize', resizeCanvas);

  // Eventos de dibujo (mouse/touch)
  canvas.addEventListener('mousedown', start);
  canvas.addEventListener('mousemove', move);
  canvas.addEventListener('mouseup', end);
  canvas.addEventListener('mouseout', end);
  canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); });
  canvas.addEventListener('touchmove',  (e) => { e.preventDefault(); move(e);  });
  canvas.addEventListener('touchend',   (e) => { e.preventDefault(); end();    });

  // Al mostrar el modal, preparar canvas y, si es edición, precargar firma
  modalEl.addEventListener('shown.bs.modal', () => {
    resizeCanvas();

    const isEdit = (methodInput && methodInput.value.toUpperCase() === 'PUT');
    if (!isEdit) {
      // Crear => limpiar todo
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      input.value = '';
      return;
    }

    const employeeId = idInput?.value;
    if (!employeeId) return;

    // Precargar firma desde backend (privado -> data_url)
    fetch('/employees/signature/show', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ employee_id: employeeId }),
      credentials: 'same-origin'
    })
      .then(r => r.ok ? r.json() : Promise.reject(r.status))
      .then(({ data_url }) => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (data_url) {
          drawFromDataUrl(data_url);
          input.value = data_url; // si el usuario NO dibuja, se mantiene
        } else {
          input.value = ''; // no hay firma previa
        }
      })
      .catch(err => console.error('Error cargando firma:', err));
  });
});
