'use strict';

function initViewControls() {
    const buttons = Array.from(document.querySelectorAll('[data-inventory-view]'));
    const grid = document.querySelector('[data-products-grid]');
    const table = document.querySelector('[data-products-table]');
    if (!buttons.length || !grid || !table) return;

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            const showTable = button.dataset.inventoryView === 'table';
            grid.hidden = showTable;
            table.hidden = !showTable;
            buttons.forEach(function (control) {
                const active = control === button;
                control.classList.toggle('is-active', active);
                control.setAttribute('aria-pressed', String(active));
            });
        });
    });
}

function initProductDialogs() {
    const openers = document.querySelectorAll('[data-open-product-modal]');
    const dialogs = document.querySelectorAll('.product-modal');
    if (!openers.length || !dialogs.length) return;

    const closeDialog = function (dialog) {
        if (typeof dialog.close === 'function') dialog.close();
        else dialog.removeAttribute('open');
    };

    openers.forEach(function (button) {
        button.addEventListener('click', function () {
            const dialog = document.getElementById(button.dataset.openProductModal);
            if (!dialog) return;
            if (typeof dialog.showModal === 'function') dialog.showModal();
            else dialog.setAttribute('open', 'open');
            dialog.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
        });
    });

    dialogs.forEach(function (dialog) {
        dialog.querySelectorAll('[data-close-product-modal]').forEach(function (button) {
            button.addEventListener('click', function () { closeDialog(dialog); });
        });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) closeDialog(dialog);
        });
        dialog.addEventListener('cancel', function () { closeDialog(dialog); });
    });
}
document.addEventListener('DOMContentLoaded', () => {
    initViewControls();
    initProductDialogs();
    let pending;
    let timer;
    function formUrl(form) {
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form));
        return url;
    }
    async function load(url) {
        clearTimeout(timer);
        pending?.abort();
        const request = new AbortController();
        pending = request;
        try {
            const response = await fetch(url, {signal:request.signal});
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = html.querySelector('.inventory-page');
            if (!response.ok || !next) throw new Error('Vista no disponible');
            const current = document.querySelector('.inventory-page');
            const focus = current.contains(document.activeElement) && document.activeElement.id;
            const table = current.querySelector('[data-inventory-view="table"]').getAttribute('aria-pressed') === 'true';
            current.querySelectorAll('.inventory-product-card').forEach(card => card.vanillaTilt?.destroy());
            current.replaceWith(next);
            initViewControls();
            initProductDialogs();
            if (table) next.querySelector('[data-inventory-view="table"]').click();
            if (focus) document.getElementById(focus)?.focus({preventScroll:true});
            history.replaceState(null, '', url);
            document.dispatchEvent(new Event('inventory:updated'));
        } catch (error) {
            if (!request.signal.aborted) location.assign(url);
        }
    }
    document.addEventListener('submit', event => {
        if (!event.target.matches('.inventory-filter-form')) return;
        event.preventDefault();
        load(formUrl(event.target));
    });
    document.addEventListener('input', event => {
        if (!event.target.matches('#inventory-search')) return;
        pending?.abort();
        clearTimeout(timer);
        timer = setTimeout(() => load(formUrl(event.target.form)), 300);
    });
    document.addEventListener('change', event => {
        if (event.target.matches('.inventory-filter-form select, .inventory-filter-form input[type=checkbox]')) load(formUrl(event.target.form));
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('.inventory-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        load(link.href);
    });
});
