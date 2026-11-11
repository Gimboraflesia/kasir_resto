const formatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

document.querySelectorAll('[data-profit-calculator]').forEach((form) => {
    const costInput = form.querySelector('[data-cost-input]');
    const profitInput = form.querySelector('[data-profit-input]');
    const pricePreview = form.querySelector('[data-price-preview]');
    const profitPreview = form.querySelector('[data-profit-preview]');
    if (!costInput || !profitInput || !pricePreview || !profitPreview) return;

    const update = () => {
        const cost = Number.parseInt(costInput.value, 10) || 0;
        const percent = Number.parseInt(profitInput.value, 10) || 0;
        const profit = Math.floor((cost * percent + 50) / 100);
        pricePreview.textContent = formatter.format(cost + profit);
        profitPreview.textContent = formatter.format(profit);
    };

    costInput.addEventListener('input', update);
    profitInput.addEventListener('input', update);
    update();
});
