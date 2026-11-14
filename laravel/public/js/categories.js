(() => {
    const page = document.querySelector('.categories-page');
    if (!page) return;
    const name = page.querySelector('#cat-name');
    const publish = page.querySelector('#cat-publish');
    if (name && publish) {
        const preview = () => {
            page.querySelector('#cat-preview-name').textContent = name.value.trim() || 'Nueva categoría';
            const badge = page.querySelector('#cat-preview-status');
            badge.textContent = publish.checked ? 'Publicada' : 'Sin publicar';
            badge.classList.toggle('is-active', publish.checked);
        };
        name.addEventListener('input', preview);
        publish.addEventListener('change', preview);
        preview();
    }
    const dialog = page.querySelector('#cat-edit-dialog');
    const body = page.querySelector('#cat-dialog-body');
    let source = null;
    let trigger = null;
    if (typeof dialog.showModal === 'function') {
        const open = details => {
            source = details;
            trigger = details.querySelector('summary');
            details.open = false;
            body.append(details.querySelector('.cat-edit-content'));
            dialog.showModal();
            body.querySelector('input[name="nombre"]').focus();
        };
        page.querySelectorAll('.cat-edit').forEach(details => {
            details.querySelector('summary').addEventListener('click', event => {
                event.preventDefault();
                open(details);
            });
        });
        dialog.addEventListener('close', () => {
            if (source) source.append(body.firstElementChild);
            source = null;
            if (trigger) trigger.focus();
        });
        page.querySelector('#cat-close-dialog').addEventListener('click', () => dialog.close());
        const invalidEdit = page.querySelector('.cat-edit[open]');
        if (invalidEdit) open(invalidEdit);
    }
    page.querySelectorAll('.cat-state-form').forEach(form => {
        form.addEventListener('submit', event => {
            const action = form.querySelector('input[name="activo"]').value === '0' ? 'Desactivar' : 'Activar';
            if (!window.confirm(action + ' categoría "' + form.dataset.categoryName + '"? La publicación no se modifica.')) event.preventDefault();
        });
    });
})();
