import Chart from 'chart.js/auto';

const canvas = document.querySelector('#sales-chart');
const source = document.querySelector('#sales-chart-data');

if (canvas && source) {
    const series = JSON.parse(source.textContent);
    const formatMoney = (value) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(value);

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: series.daily.labels,
            datasets: [{
                label: 'Pendapatan',
                data: series.daily.values,
                borderColor: '#35533f',
                backgroundColor: 'rgba(77, 111, 82, .08)',
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#35533f',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                fill: true,
                tension: .28,
            }],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (context) => ` ${formatMoney(context.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#81857b', font: { size: 10 } } },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#eef0ea', drawTicks: false },
                    ticks: {
                        color: '#8a8d85',
                        padding: 10,
                        maxTicksLimit: 5,
                        font: { size: 10 },
                        callback: (value) => value >= 1000000 ? `Rp ${(value / 1000000).toLocaleString('id-ID')} jt` : value >= 1000 ? `Rp ${(value / 1000).toLocaleString('id-ID')} rb` : `Rp ${value}`,
                    },
                },
            },
        },
    });

    document.querySelectorAll('[data-chart-period]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.chartPeriod;
            document.querySelectorAll('[data-chart-period]').forEach((tab) => {
                const selected = tab === button;
                tab.classList.toggle('is-active', selected);
                tab.setAttribute('aria-pressed', String(selected));
            });
            chart.data.labels = series[key].labels;
            chart.data.datasets[0].data = series[key].values;
            chart.update();
        });
    });
}
