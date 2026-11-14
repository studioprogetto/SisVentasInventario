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


document.addEventListener('DOMContentLoaded', function () {
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
});
