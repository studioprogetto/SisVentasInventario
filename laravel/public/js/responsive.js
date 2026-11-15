(() => {
    const compact = matchMedia('(max-width:767px), (min-width:768px) and (max-width:1023px) and (orientation:portrait)');
    document.addEventListener('alpine:init', () => {
        Alpine.data('recordRow', () => ({
            open: false,
            toggle() { this.open = !this.open; },
            get expanded() { return this.open ? 'true' : 'false'; }
        }));
        function prepareTables() {
            if (!compact.matches) return;
            document.querySelectorAll('#content table:not(.pos-cart):not(.ticket-table)').forEach(table => {
                const labels = Array.from(table.tHead?.rows[0]?.cells || [], cell => cell.textContent.trim());
                if (!labels.length) return;
                table.classList.add('adaptive-list');
                for (const body of table.tBodies) for (const row of body.rows) {
                    if (row.hasAttribute('x-data') || row.cells.length < 2) continue;
                    const cells = Array.from(row.cells);
                    const primary = labels.findIndex(label => /^(nombre|producto|cliente|proveedor|archivo)$/i.test(label));
                    const value = labels.findIndex(label => /^(estado|importe|total|monto|stock)$/i.test(label));
                    const toggle = document.createElement('button');
                    toggle.type = 'button';
                    toggle.className = 'record-toggle';
                    toggle.setAttribute('x-on:click', 'toggle');
                    toggle.setAttribute('x-bind:aria-expanded', 'expanded');
                    for (const index of new Set([Math.max(0, primary), value])) {
                        if (index < 0) continue;
                        const span = document.createElement('span');
                        span.textContent = (cells[index].querySelector('strong') || cells[index]).textContent.trim();
                        toggle.append(span);
                    }
                    cells.forEach((cell,index) => cell.dataset.field = labels[index] || '');
                    const heading = row.insertCell(0);
                    heading.className = 'record-heading';
                    heading.append(toggle);
                    row.setAttribute('x-bind:data-expanded','expanded');
                    row.setAttribute('x-data','recordRow');
                }
            });
        }
        prepareTables();
        compact.addEventListener('change',prepareTables);
        document.addEventListener('inventory:updated',prepareTables);
        document.addEventListener('search:updated',prepareTables);
    });
    const cart = document.querySelector('#checkout-sheet');
    if (!cart) return;
    const layout = cart.parentElement;
    const form = layout.closest('form');
    const sheet = document.createElement('dialog');
    sheet.className = 'cart-sheet';
    sheet.setAttribute('aria-label','Carrito de venta');
    form.append(sheet);
    const opener = form.querySelector('[data-show-cart]');
    const mobile = matchMedia('(max-width:1023px)');
    function syncCart() {
        if (mobile.matches) sheet.append(cart);
        else { sheet.close(); layout.append(cart); }
    }
    syncCart();
    mobile.addEventListener('change', syncCart);
    opener.addEventListener('click', () => { sheet.showModal(); opener.setAttribute('aria-expanded','true'); });
    cart.querySelector('[data-dismiss-cart]').addEventListener('click', () => sheet.close());
    sheet.addEventListener('close', () => opener.setAttribute('aria-expanded','false'));
    const total = cart.querySelector('[data-pos-total]');
    const dock = form.querySelector('[data-dock-total]');
    new MutationObserver(() => { dock.textContent = total.textContent; }).observe(total,{childList:true,subtree:true,characterData:true});
})();
