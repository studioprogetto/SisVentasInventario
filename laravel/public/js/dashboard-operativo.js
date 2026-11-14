(() => {
    const root = document.querySelector('.studio-dashboard');
    const mode = root.querySelector('#executive-chart-mode');
    if (!window.Chart) { mode.disabled = true; return; }
    const points = JSON.parse(root.getAttribute('data-sales_series'));
    const sparks = JSON.parse(root.getAttribute('data-spark_series'));
    const payments = JSON.parse(root.getAttribute('data-payments'));
    const health = JSON.parse(root.getAttribute('data-inventory_health'));
    const colors = ['#10b981', '#f59e0b', '#fb7185', '#fef3c7', '#38bdf8', '#a78bfa'];
    const animation = matchMedia('(prefers-reduced-motion: reduce)').matches ? false : {duration: 500};
    const basic = {responsive: true, maintainAspectRatio: false, animation, plugins: {legend: {display: false}}};
    const main = new Chart(root.querySelector('#executive-sales'), {
        type: 'line',
        data: {labels: points.map(p => p.label), datasets: [{label: 'Ingresos (PEN)', data: points.map(p => p.value), borderColor: '#5387d9', backgroundColor: '#5387d933', borderWidth: 2, pointBackgroundColor: '#10b981', pointRadius: points.length > 60 ? 0 : 2, tension: .35, fill: true, borderRadius: 5}, {label:'Importe sin moneda registrada', hidden:!JSON.parse(root.dataset.legacy).some(point=>point.value !== 0), data:JSON.parse(root.dataset.legacy).map(p=>p.value), borderColor:'#d68b13', backgroundColor:'#d68b1333', tension:.3, borderDash:[5,3]}]},
        options: {...basic, plugins: {legend: {display: false}, tooltip: {callbacks: {label: c => c.dataset.label + ': ' + Number(c.parsed.y).toLocaleString('es-PE', {minimumFractionDigits: 2})}}}, scales: {x: {grid: {display: false}, ticks: {color: '#94a3b8', maxTicksLimit: 7, maxRotation: 0, font: {size: 9}}}, y: {beginAtZero: true, grid: {color: '#94a3b81a'}, ticks: {color: '#94a3b8', font: {size: 9}, callback: v => v}}}}
    });
    const original = main.data.datasets.map(series => [...series.data]);
    const cumulative = root.querySelector('#income-cumulative');
    cumulative.addEventListener('click', () => {
        const enabled = cumulative.getAttribute('aria-pressed') !== 'true';
        main.data.datasets.forEach((series, index) => {
            let cents = 0;
            series.data = original[index].map(value => {
                cents += Math.round(value * 100);
                return enabled ? cents / 100 : value;
            });
        });
        cumulative.setAttribute('aria-pressed', String(enabled));
        cumulative.textContent = enabled ? 'Ver por día' : 'Ver acumulado';
        main.update();
    });
    mode.addEventListener('click', () => {
        const bars = main.config.type === 'line';
        main.config.type = bars ? 'bar' : 'line';
        main.data.datasets[0].backgroundColor = bars ? '#5387d9cc' : '#5387d933';
        main.update();
        mode.setAttribute('aria-pressed', String(bars));
    });
    sparks.forEach((series, i) => new Chart(root.querySelector('#executive-spark-' + i), {
        type: 'line', data: {labels: series.map(p => p.label), datasets: [{data: series.map(p => p.value), borderColor: ['#f59e0b', '#10b981', '#fef3c7'][i], borderWidth: 2, pointRadius: 0, tension: .35}]},
        options: {...basic, plugins: {legend: {display: false}, tooltip: {enabled: false}}, scales: {x: {display: false}, y: {display: false, beginAtZero: true}}}
    }));
    new Chart(root.querySelector('#executive-payments'), {
        type: 'doughnut', data: {labels: payments.map(p => p.metodo_pago + ' / ' + (p.moneda_codigo || 'Sin documentar')), datasets: [{data: payments.map(p => Number(p.operaciones)), backgroundColor: payments.map((_, i) => colors[i % colors.length]), borderColor: '#0f172a', borderWidth: 3}]}, options: {...basic, cutout: '74%'}
    });
    new Chart(root.querySelector('#executive-health'), {
        type: 'bar', data: {labels: ['Sobre mínimo', 'Por reponer', 'Cero / negativo'], datasets: [{data: [Number(health.suficiente || 0), Number(health.reposicion || 0), Number(health.agotados || 0)], backgroundColor: ['#10b981', '#f59e0b', '#fb7185'], borderRadius: 5, barThickness: 12}]},
        options: {...basic, indexAxis: 'y', scales: {x: {display: false, beginAtZero: true}, y: {grid: {display: false}, ticks: {color: '#94a3b8', font: {size: 10}}}}}
    });
})();