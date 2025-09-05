export function setupFacilitatorSignature() {
    const modalEl  = document.getElementById('confirm-finalize-modal'); 
    const canvas   = document.getElementById('facilitatorSignatureCanvas');
    const input    = document.getElementById('facilitatorSignatureInput');
    const clearBtn = document.getElementById('clearFacilitatorSignatureBtn');

    if (!modalEl || !canvas || !input ) return;

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

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: clientX - rect.left, y: clientY - rect.top };
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
        input.value = canvas.toDataURL('image/png'); // guarda base64
    }

    clearBtn.addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        input.value = '';
    });

    // Resize dinámico
    window.addEventListener('resize', resizeCanvas);

    // Eventos de dibujo
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseout', end);
    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); });
    canvas.addEventListener('touchmove',  (e) => { e.preventDefault(); move(e);  });
    canvas.addEventListener('touchend',   (e) => { e.preventDefault(); end();    });

    // Al abrir modal => preparar canvas
    modalEl.addEventListener('shown.bs.modal', () => {
        resizeCanvas();
    });
}
