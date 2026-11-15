
(() => {
    const root = document.querySelector('.report-center');
    const search = root.querySelector('#report-search');
    const area = root.querySelector('#report-area');
    const filter = () => {
        let visible = 0;
        root.querySelectorAll('.rc-card').forEach(card => {
            card.hidden = !(area.value === 'all' || card.dataset.area === area.value) || !SearchUI.matches(card.textContent,search.value);
            if (!card.hidden) visible++;
        });
        root.querySelector('#report-empty').hidden = visible > 0;
    };
    let timer;
    search.addEventListener('input', () => {clearTimeout(timer); timer = setTimeout(filter,300);});
    area.addEventListener('change', filter);
})();
