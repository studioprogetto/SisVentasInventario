(() => {
    function draw(element, url) {
        element.replaceChildren();
        if (!url) return;
        const code = qrcode(0, 'M');
        code.addData(url);
        code.make();
        const image = new Image();
        image.alt = 'Código QR para escanear';
        image.src = code.createDataURL(6, 24);
        element.append(image);
    }

    document.querySelectorAll('[data-static-qr]').forEach(el => draw(el, el.dataset.staticQr));
    document.querySelector('[data-print-static]')?.addEventListener('click', () => window.print());
    document.querySelector('[data-print-receipt]')?.addEventListener('click', () => window.print());
    const panel = document.querySelector('[data-publication]');
    if (!panel) return;
    const status = panel.querySelector('[data-status]');
    const image = panel.querySelector('[data-code]');
    const share = document.querySelector('[data-ticket-share]');
    let previous;
    let lastPublication;
    let expires;
    let label;
    function clear(message) {
        image.replaceChildren();
        if (share) {
            share.hidden = true;
            share.removeAttribute('href');
        }
        previous = null;
        expires = null;
        status.textContent = message;
    }
    function countdown() {
        if (!expires) return;
        const seconds = Math.ceil((expires - Date.now()) / 1000);
        if (seconds <= 0) clear('QR vencido.');
        else status.textContent = label + ' · ' + seconds + ' s';
    }
    async function poll() {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);
        try {
            const response = await fetch(panel.dataset.publication, {cache:'no-store', headers:{Accept:'application/json'}, signal:controller.signal});
            if (!response.ok) throw new Error();
            const state = await response.json();
            if (lastPublication !== undefined && lastPublication !== state.url && document.querySelector('.delivery-queue')) {
                location.reload();
                return;
            }
            lastPublication = state.url;
            if (!state.url) {
                clear(state.status || 'En espera de una entrega.');
            } else {
                if (state.url !== previous) draw(image,state.url);
                previous = state.url;
                if (share) {
                    share.href = 'https://wa.me/?text=' + encodeURIComponent('Tu ticket de compra: ' + state.url);
                    share.hidden = false;
                }
                label = 'Ticket #' + state.ticket;
                expires = state.remaining === null ? null : Date.now() + state.remaining * 1000;
                status.textContent = label + ' · Disponible para escanear';
                countdown();
            }
        } catch { clear('Sin conexión con caja. Reconectando…'); }
        finally { clearTimeout(timeout); }
        setTimeout(poll, 1500);
    }
    setInterval(countdown, 250);
    poll();
})();
