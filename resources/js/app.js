import './bootstrap';

const reportPeriod = document.querySelector('[data-report-period]');
if (reportPeriod) {
    const customDates = document.querySelector('[data-report-custom]');
    const syncCustomDates = () => { customDates.hidden = reportPeriod.value !== 'custom'; };
    reportPeriod.addEventListener('change', syncCustomDates);
    syncCustomDates();
}

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.querySelector('#password');
        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        button.textContent = showing ? 'Sembunyikan' : 'Lihat';
        button.setAttribute('aria-label', showing ? 'Sembunyikan password' : 'Tampilkan password');
    });
});
