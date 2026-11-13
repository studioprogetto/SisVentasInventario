'use strict';

document.addEventListener('DOMContentLoaded', function () {
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
});
