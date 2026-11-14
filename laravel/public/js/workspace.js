document.addEventListener('alpine:init', () => {
    Alpine.data('moduleSearch', () => ({
        init() {
            const search = document.querySelector('[data-module-search]');
            const results = document.querySelector('[data-module-results]');
            function filterModules() {
                const normalize = value => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                const query = normalize(search.value);
                results.replaceChildren();
                results.hidden = !query;
                if (!query) return;
                for (const item of document.querySelectorAll('#main-nav .nav-link')) {
                    if (!normalize(item.textContent).includes(query)) continue;
                    const link = document.createElement('a');
                    link.href = item.href;
                    link.textContent = item.textContent.trim();
                    results.append(link);
                }
                if (!results.children.length) results.textContent = 'No se encontraron módulos.';
            }
            search.addEventListener('input', filterModules);
            search.addEventListener('focus', filterModules);
            search.parentElement.parentElement.addEventListener('focusout', event => {
                if (!event.currentTarget.contains(event.relatedTarget)) results.hidden = true;
            });
            search.addEventListener('keydown', event => {
                if (event.key === 'Escape') results.hidden = true;
                if (event.key === 'ArrowDown') results.querySelector('a')?.focus();
            });
        }
    }));
});
const tiltMedia = matchMedia('(hover:hover) and (pointer:fine) and (prefers-reduced-motion:no-preference)');
function updateTilt() {
    document.querySelectorAll('[data-agency-kpi], .inventory-product-card, .executive-card.kpi').forEach(card => {
        if (!tiltMedia.matches) card.vanillaTilt?.destroy();
        else if (!card.vanillaTilt) window.VanillaTilt?.init(card, {max:3, scale:1, speed:240, gyroscope:false});
    });
}
updateTilt();
tiltMedia.addEventListener('change', updateTilt);
document.addEventListener('inventory:updated', updateTilt);
document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-open-create]');
    if (trigger) document.getElementById(trigger.dataset.openCreate)?.showModal();
    if (event.target.closest('[data-close-create]')) event.target.closest('dialog').close();
});
document.querySelectorAll('.create-dialog').forEach(dialog => {
    dialog.querySelector('form')?.addEventListener('formdata', event => event.formData.set('_create_dialog', dialog.id));
    if (dialog.hasAttribute('data-reopen')) dialog.showModal();
});
