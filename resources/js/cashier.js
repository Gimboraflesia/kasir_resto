const menuGrid = document.querySelector('[data-menu-grid]');

if (menuGrid) {
    const cart = new Map();
    const money = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const orderLines = document.querySelector('[data-order-lines]');
    const itemFields = document.querySelector('[data-checkout-items]');
    const cashInput = document.querySelector('[data-cash-input]');
    const cashFields = document.querySelector('[data-cash-fields]');
    const qrisNote = document.querySelector('[data-qris-note]');
    const paymentOptions = [...document.querySelectorAll('[data-payment-method]')];
    const checkoutButton = document.querySelector('[data-checkout-button]');
    const checkoutHint = document.querySelector('[data-checkout-hint]');
    const searchInput = document.querySelector('[data-menu-search]');
    let activeCategory = 'all';
    const selectedPaymentMethod = () => paymentOptions.find((option) => option.checked)?.value || 'cash';
    const cartQuantity = () => [...cart.values()].reduce((sum, item) => sum + item.quantity, 0);
    const cartTotal = () => [...cart.values()].reduce((sum, item) => sum + item.price * item.quantity, 0);

    function renderCart() {
        const entries = [...cart.values()]; const total = cartTotal(); const count = cartQuantity();
        const method = selectedPaymentMethod(); const isCash = method === 'cash'; const isManual = method === 'qris_manual';
        const cash = Number.parseInt(cashInput.value, 10) || 0;
        document.querySelector('[data-order-count]').textContent = `${count} item${count === 1 ? '' : ''}`;
        document.querySelector('[data-subtotal]').textContent = money.format(total);
        document.querySelector('[data-grand-total]').textContent = money.format(total);
        document.querySelector('[data-change-amount]').textContent = money.format(Math.max(0, cash - total));
        document.querySelector('[data-clear-cart]').disabled = count === 0;
        document.querySelector('[data-cash-preset="exact"]').disabled = count === 0;
        orderLines.replaceChildren(); itemFields.replaceChildren();
        if (!entries.length) {
            const empty = document.createElement('div'); empty.className = 'cart-empty';
            empty.innerHTML = '<span class="cart-empty-mark">＋</span><strong>Belum ada menu</strong><span>Pilih menu untuk memulai pesanan.</span>'; orderLines.append(empty);
        } else entries.forEach((item, index) => {
            const row = document.createElement('div'); row.className = 'cart-line';
            const description = document.createElement('div'); description.className = 'cart-line-info';
            const name = document.createElement('strong'); name.textContent = item.name;
            const unitPrice = document.createElement('span'); unitPrice.textContent = money.format(item.price); description.append(name, unitPrice);
            const controls = document.createElement('div'); controls.className = 'cart-line-controls';
            const decrement = document.createElement('button'); decrement.type = 'button'; decrement.className = 'quantity-control'; decrement.textContent = '−'; decrement.setAttribute('aria-label', `Kurangi ${item.name}`); decrement.addEventListener('click', () => changeQuantity(item.id, -1));
            const quantity = document.createElement('span'); quantity.className = 'cart-line-quantity'; quantity.textContent = item.quantity;
            const increment = document.createElement('button'); increment.type = 'button'; increment.className = 'quantity-control'; increment.textContent = '+'; increment.setAttribute('aria-label', `Tambah ${item.name}`); increment.disabled = item.quantity >= 99 || (item.stock !== null && item.quantity >= item.stock); increment.addEventListener('click', () => changeQuantity(item.id, 1)); controls.append(decrement, quantity, increment);
            const lineTotal = document.createElement('strong'); lineTotal.className = 'cart-line-total'; lineTotal.textContent = money.format(item.price * item.quantity); row.append(description, controls, lineTotal); orderLines.append(row);
            const productField = document.createElement('input'); productField.type = 'hidden'; productField.name = `items[${index}][product_id]`; productField.value = item.id;
            const quantityField = document.createElement('input'); quantityField.type = 'hidden'; quantityField.name = `items[${index}][quantity]`; quantityField.value = item.quantity; itemFields.append(productField, quantityField);
        });
        document.querySelectorAll('[data-cart-count]').forEach((badge) => { const quantity = cart.get(Number(badge.dataset.cartCount))?.quantity || 0; badge.hidden = quantity === 0; badge.textContent = quantity; });
        checkoutButton.disabled = !(count > 0 && (!isCash || cash >= total)); cashFields.hidden = !isCash; cashInput.required = isCash; qrisNote.hidden = isCash;
        checkoutButton.querySelector('span').textContent = isCash ? 'Konfirmasi pembayaran tunai' : isManual ? 'Konfirmasi QRIS manual' : 'Buat pembayaran QRIS';
        checkoutHint.textContent = count === 0 ? 'Pilih menu untuk mulai membuat pesanan.' : isCash && cash < total ? `Kurang ${money.format(total - cash)} dari total pembayaran.` : isCash ? 'Pesanan dan nominal tunai siap dikonfirmasi.' : isManual ? 'Kasir akan mencatat konfirmasi QRIS secara manual.' : 'Pelanggan membayar lewat QRIS Midtrans Sandbox.';
    }
    function changeQuantity(id, amount) { const item = cart.get(id); if (!item) return; const quantity = item.quantity + amount; if (quantity < 1) cart.delete(id); else if (quantity <= 99 && (item.stock === null || quantity <= item.stock)) item.quantity = quantity; renderCart(); }
    menuGrid.addEventListener('click', (event) => { const button = event.target.closest('[data-product-add]'); if (!button || button.disabled) return; const id = Number(button.dataset.productId); const item = cart.get(id); const stock = button.dataset.productStock === '' ? null : Number(button.dataset.productStock); if (item) item.quantity = Math.min(99, stock === null ? 99 : stock, item.quantity + 1); else cart.set(id, { id, name: button.dataset.productName, price: Number(button.dataset.productPrice), stock, quantity: 1 }); renderCart(); });
    document.querySelectorAll('[data-category-filter]').forEach((button) => button.addEventListener('click', () => { activeCategory = button.dataset.categoryFilter; document.querySelectorAll('[data-category-filter]').forEach((chip) => { const selected = chip === button; chip.classList.toggle('is-selected', selected); chip.setAttribute('aria-pressed', String(selected)); }); filterMenu(); }));
    function filterMenu() { const term = searchInput.value.trim().toLocaleLowerCase('id-ID'); let visible = 0; menuGrid.querySelectorAll('[data-product-add]').forEach((button) => { const matchesCategory = activeCategory === 'all' || button.dataset.categoryId === activeCategory; const matchesSearch = button.dataset.productName.toLocaleLowerCase('id-ID').includes(term); button.hidden = !(matchesCategory && matchesSearch); if (!button.hidden) visible += 1; }); document.querySelector('[data-no-results]').hidden = visible !== 0; }
    searchInput.addEventListener('input', filterMenu); cashInput.addEventListener('input', renderCart); paymentOptions.forEach((option) => option.addEventListener('change', renderCart));
    document.querySelector('[data-clear-cart]').addEventListener('click', () => { cart.clear(); renderCart(); });
    document.querySelectorAll('[data-cash-preset]').forEach((button) => button.addEventListener('click', () => { cashInput.value = button.dataset.cashPreset === 'exact' ? cartTotal() : Number(button.dataset.cashPreset); renderCart(); }));
    document.querySelector('[data-checkout-form]').addEventListener('submit', (event) => { if (checkoutButton.disabled || cart.size === 0) { event.preventDefault(); return; } checkoutButton.disabled = true; checkoutButton.querySelector('span').textContent = 'Memproses pembayaran…'; });
    renderCart();
}
