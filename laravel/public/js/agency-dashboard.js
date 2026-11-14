const canvas = document.querySelector('#agency-revenue');
const colors = () => getComputedStyle(document.documentElement);
const token = name => colors().getPropertyValue(name).trim();
const chart = new Chart(canvas, {
    type: 'bar',
    data: {
        labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
        datasets: [{ label: 'Ingresos (S/)', data: [3900, 5200, 4100, 5250], borderRadius: 9, maxBarThickness: 64 }]
    },
    options: { maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } }
});
function refreshPalette() {
    chart.data.datasets[0].backgroundColor = token('--accent');
    for (const axis of ['x', 'y']) chart.options.scales[axis].ticks.color = token('--muted');
    chart.options.scales.y.grid.color = token('--line');
    chart.update('none');
}
refreshPalette();
new MutationObserver(refreshPalette).observe(document.documentElement, { attributes:true, attributeFilter:['data-theme'] });
