<?php
if (!isLoggedIn()) {
    redirect('/login');
}
$current_page = 'sale';
$currencySymbol = h(getAppSetting('currency_symbol', '€'));

$content = '
<div class="p-4">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Nuova Vendita</h1>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <div id="sale-items" class="space-y-3 mb-4">
            <!-- Items will be added here -->
        </div>

        <button id="add-item-btn" class="w-full bg-blue-500 text-white py-3 px-4 rounded-md hover:bg-blue-600 text-lg font-medium">
            + Aggiungi Prodotto
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <div class="flex justify-between items-center mb-3">
            <span class="text-lg font-semibold">Totale:</span>
            <span id="total-amount" class="text-2xl font-bold text-green-600">' . $currencySymbol . '0.00</span>
        </div>

        <div class="space-y-3 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sconto</label>
                <div class="flex space-x-2">
                    <div class="flex-1 relative">
                        <div id="discount-type-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                            <span id="discount-type-text">Nessuno</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <input type="hidden" id="discount-type" value="">
                        <div id="discount-type-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden">
                            <div class="py-1">
                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="">Nessuno</div>
                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="percentage">Percentuale</div>
                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="fixed">Valore fisso</div>
                            </div>
                        </div>
                    </div>
                    <input type="number" id="discount-value" step="0.01" min="0" placeholder="0.00"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md" disabled>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pagamento</label>
                <div class="relative">
                    <div id="payment-method-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                        <span id="payment-method-text">Contanti</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <input type="hidden" id="payment-method" value="cash">
                    <div id="payment-method-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden">
                        <div class="py-1">
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="cash">Contanti</div>
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="card">Carta</div>
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="mixed">Misto</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button id="complete-sale-btn" class="w-full bg-green-600 text-white py-4 px-4 rounded-md hover:bg-green-700 text-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Conferma Vendita
        </button>
    </div>

    <!-- Receipt section -->
    <div id="receipt-section" class="bg-white rounded-lg shadow-md p-4 hidden">
        <h2 class="text-lg font-semibold mb-4">Ricevuta</h2>
        <div id="receipt-content"></div>
        <div class="flex space-x-3 mt-4">
            <button id="print-receipt-btn" class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700">
                Stampa
            </button>
            <button id="new-sale-btn" class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-md hover:bg-gray-700">
                Nuova Vendita
            </button>
        </div>
    </div>
</div>

<!-- Product search modal -->
<div id="product-modal" class="fixed inset-x-0 bottom-0 hidden z-50 p-2">
    <div class="w-full bg-white rounded-t-xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <input type="text" id="product-search" placeholder="Cerca prodotto..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg">
            </div>
            <div id="product-list" class="overflow-y-auto max-h-80">
                <!-- Products will be loaded here -->
            </div>
            <div class="p-4 border-t">
                <button id="close-modal" class="w-full bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600">
                    Annulla
                </button>
            </div>
    </div>
</div>

<!-- Quantity keypad panel -->
<div id="qty-keypad-modal" class="fixed inset-x-0 bottom-0 hidden z-[60] p-2">
    <div class="w-full bg-white rounded-t-xl shadow-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b bg-gray-50">
            <div id="qty-product-name" class="font-semibold text-gray-900">Quantità</div>
            <div id="qty-product-unit" class="text-sm text-gray-600">Inserisci quantità</div>
        </div>

        <div class="p-4">
            <div id="qty-display" class="w-full text-right text-3xl font-bold px-3 py-3 border border-gray-300 rounded-md mb-3">0</div>

            <div class="grid grid-cols-3 gap-2 mb-3">
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="7">7</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="8">8</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="9">9</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="4">4</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="5">5</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="6">6</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="1">1</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="2">2</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="3">3</button>
                <button type="button" id="qty-decimal-btn" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key=".">.</button>
                <button type="button" class="qty-key-btn bg-gray-100 py-3 rounded-md text-lg font-semibold" data-keypad-key="0">0</button>
                <button type="button" class="qty-key-btn bg-red-100 text-red-700 py-3 rounded-md text-lg font-semibold" data-keypad-key="backspace">⌫</button>
            </div>

            <div class="grid grid-cols-3 gap-2 mb-4">
                <button type="button" id="qty-quick-1" class="qty-quick-btn bg-blue-50 text-blue-700 py-2 rounded-md" data-keypad-quick="0.25">0.25</button>
                <button type="button" id="qty-quick-2" class="qty-quick-btn bg-blue-50 text-blue-700 py-2 rounded-md" data-keypad-quick="0.50">0.50</button>
                <button type="button" id="qty-quick-3" class="qty-quick-btn bg-blue-50 text-blue-700 py-2 rounded-md" data-keypad-quick="1">1</button>
            </div>

            <div class="flex space-x-3">
                <button id="qty-cancel-btn" type="button" class="flex-1 bg-gray-500 text-white py-3 rounded-md hover:bg-gray-600">Annulla</button>
                <button id="qty-confirm-btn" type="button" class="flex-1 bg-green-600 text-white py-3 rounded-md hover:bg-green-700">Conferma</button>
            </div>
        </div>
    </div>
</div>
';
?>

<script>
let saleItems = [];
let products = [];
let pendingProduct = null;
let editingItemId = null;
let keypadValue = '';
const CURRENCY = (window.appSettings && window.appSettings.currency_symbol) ? window.appSettings.currency_symbol : '€';

function money(value) {
    return `${CURRENCY}${parseFloat(value || 0).toFixed(2)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    setupEventListeners();
});

async function loadProducts() {
    try {
        const response = await fetch('/public/api.php?action=products');
        const result = await response.json();
        if (result.success) {
            products = result.data;
        }
    } catch (error) {
        showToast('Errore caricamento prodotti', 'error');
    }
}

function setupEventListeners() {
    document.getElementById('add-item-btn').addEventListener('click', showProductModal);
    document.getElementById('close-modal').addEventListener('click', hideProductModal);
    document.getElementById('product-search').addEventListener('input', filterProducts);
    document.getElementById('discount-type').addEventListener('change', toggleDiscountInput);
    document.getElementById('discount-value').addEventListener('input', updateTotal);
    document.getElementById('complete-sale-btn').addEventListener('click', completeSale);
    document.getElementById('qty-cancel-btn').addEventListener('click', hideQtyKeypad);
    document.getElementById('qty-confirm-btn').addEventListener('click', confirmQtySelection);

    document.querySelectorAll('[data-keypad-key]').forEach(btn => {
        btn.addEventListener('click', () => handleKeypadInput(btn.dataset.keypadKey));
    });

    document.querySelectorAll('[data-keypad-quick]').forEach(btn => {
        btn.addEventListener('click', () => setQuickQty(btn.dataset.keypadQuick));
    });

    // Custom dropdown event listeners
    setupCustomDropdowns();
}

function setupCustomDropdowns() {
    // Discount type dropdown
    const discountDisplay = document.getElementById('discount-type-display');
    const discountDropdown = document.getElementById('discount-type-dropdown');
    const discountSelect = document.getElementById('discount-type');
    const discountText = document.getElementById('discount-type-text');

    discountDisplay.addEventListener('click', () => {
        discountDropdown.classList.toggle('hidden');
    });

    discountDropdown.addEventListener('click', (e) => {
        if (e.target.dataset.value !== undefined) {
            const value = e.target.dataset.value;
            const text = e.target.textContent;

            discountSelect.value = value;
            discountText.textContent = text;
            discountDropdown.classList.add('hidden');

            // Trigger change event for discount type
            discountSelect.dispatchEvent(new Event('change'));
        }
    });

    // Payment method dropdown
    const paymentDisplay = document.getElementById('payment-method-display');
    const paymentDropdown = document.getElementById('payment-method-dropdown');
    const paymentSelect = document.getElementById('payment-method');
    const paymentText = document.getElementById('payment-method-text');

    paymentDisplay.addEventListener('click', () => {
        paymentDropdown.classList.toggle('hidden');
    });

    paymentDropdown.addEventListener('click', (e) => {
        if (e.target.dataset.value !== undefined) {
            const value = e.target.dataset.value;
            const text = e.target.textContent;

            paymentSelect.value = value;
            paymentText.textContent = text;
            paymentDropdown.classList.add('hidden');
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!discountDisplay.contains(e.target) && !discountDropdown.contains(e.target)) {
            discountDropdown.classList.add('hidden');
        }
        if (!paymentDisplay.contains(e.target) && !paymentDropdown.contains(e.target)) {
            paymentDropdown.classList.add('hidden');
        }
    });
}

function showProductModal() {
    document.getElementById('product-modal').classList.remove('hidden');
    document.getElementById('product-search').focus();
    renderProductList(products);
}

function hideProductModal() {
    document.getElementById('product-modal').classList.add('hidden');
    document.getElementById('product-search').value = '';
}

function filterProducts(e) {
    const query = e.target.value.toLowerCase();
    const filtered = products.filter(p => p.name.toLowerCase().includes(query));
    renderProductList(filtered);
}

function renderProductList(productsToShow) {
    const container = document.getElementById('product-list');
    container.innerHTML = productsToShow.map(p => `
        <div class="p-3 border-b hover:bg-gray-50 cursor-pointer" onclick="selectProduct(${p.id})">
            <div class="font-medium">${p.name}</div>
            <div class="text-sm text-gray-600">${money(p.price_sale)}/${p.unit_type}</div>
        </div>
    `).join('');
}

function selectProduct(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    hideProductModal();
    openQtyKeypad(product);
}

function addSaleItem(product, qtyOverride = null) {
    const qty = qtyOverride !== null ? qtyOverride : (product.unit_type === 'pz' ? 1 : 0.1);
    const item = {
        id: Date.now(),
        product_id: product.id,
        name: product.name,
        unit_type: product.unit_type,
        unit_price: product.price_sale,
        qty: qty,
        line_total: qty * product.price_sale
    };

    saleItems.push(item);
    updateSaleItems();
    updateTotal();
}

function updateSaleItems() {
    const container = document.getElementById('sale-items');
    container.innerHTML = saleItems.map(item => `
        <div class="bg-gray-50 p-3 rounded-md">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <div class="font-medium">${item.name}</div>
                    <div class="text-sm text-gray-600">${money(item.unit_price)}/${item.unit_type}</div>
                </div>
                <button onclick="removeItem(${item.id})" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="editItemQty(${item.id})" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm text-left bg-white hover:bg-gray-50">
                    ${item.qty} ${item.unit_type}
                </button>
                <div class="flex-1 text-right font-medium"><span id="line-total-${item.id}">${money(item.qty * item.unit_price)}</span></div>
            </div>
        </div>
    `).join('');

    document.getElementById('complete-sale-btn').disabled = saleItems.length === 0;
}

function updateQty(itemId, qty) {
    const item = saleItems.find(i => i.id == itemId);
    if (item) {
        item.qty = parseFloat(qty) || 0;
        item.line_total = item.qty * item.unit_price;
        document.getElementById(`line-total-${itemId}`).textContent = money(item.line_total);
        updateSaleItems();
        updateTotal();
    }
}

function editItemQty(itemId) {
    const item = saleItems.find(i => i.id == itemId);
    if (!item) return;

    openQtyKeypad(item, itemId, item.qty);
}

function openQtyKeypad(product, itemId = null, currentQty = null) {
    pendingProduct = product;
    editingItemId = itemId;

    document.getElementById('qty-product-name').textContent = product.name;
    document.getElementById('qty-product-unit').textContent = product.unit_type === 'pz'
        ? 'Inserisci quantità (pezzi)'
        : 'Inserisci quantità in kg (es. 0.250 = 250g)';

    keypadValue = currentQty !== null ? String(currentQty) : (product.unit_type === 'pz' ? '1' : '');
    updateKeypadDisplay();
    updateQuickButtons();

    const decimalBtn = document.getElementById('qty-decimal-btn');
    const isPieces = product.unit_type === 'pz';
    decimalBtn.disabled = isPieces;
    decimalBtn.classList.toggle('opacity-40', isPieces);

    document.getElementById('qty-keypad-modal').classList.remove('hidden');
}

function hideQtyKeypad() {
    document.getElementById('qty-keypad-modal').classList.add('hidden');
    pendingProduct = null;
    editingItemId = null;
    keypadValue = '';
}

function updateKeypadDisplay() {
    document.getElementById('qty-display').textContent = keypadValue || '0';
}

function updateQuickButtons() {
    const isPieces = pendingProduct && pendingProduct.unit_type === 'pz';
    const quickValues = isPieces ? ['1', '2', '3'] : ['0.25', '0.50', '1'];

    document.getElementById('qty-quick-1').textContent = quickValues[0];
    document.getElementById('qty-quick-1').dataset.keypadQuick = quickValues[0];
    document.getElementById('qty-quick-2').textContent = quickValues[1];
    document.getElementById('qty-quick-2').dataset.keypadQuick = quickValues[1];
    document.getElementById('qty-quick-3').textContent = quickValues[2];
    document.getElementById('qty-quick-3').dataset.keypadQuick = quickValues[2];
}

function handleKeypadInput(key) {
    if (!pendingProduct) return;

    const isPieces = pendingProduct.unit_type === 'pz';

    if (key === 'backspace') {
        keypadValue = keypadValue.slice(0, -1);
    } else if (key === '.') {
        if (!isPieces && !keypadValue.includes('.')) {
            keypadValue = keypadValue ? `${keypadValue}.` : '0.';
        }
    } else {
        if (isPieces) {
            keypadValue = `${keypadValue}${key}`.replace(/\D/g, '');
            keypadValue = keypadValue.replace(/^0+(\d)/, '$1');
        } else {
            keypadValue = `${keypadValue}${key}`;
        }
    }

    updateKeypadDisplay();
}

function setQuickQty(value) {
    keypadValue = String(value);
    updateKeypadDisplay();
}

function confirmQtySelection() {
    if (!pendingProduct) return;

    let qty = parseFloat(keypadValue);
    if (pendingProduct.unit_type === 'pz') {
        qty = Math.max(1, Math.round(qty || 1));
    } else {
        qty = Math.max(0.01, qty || 0.1);
    }

    if (editingItemId !== null) {
        updateQty(editingItemId, qty);
    } else {
        addSaleItem(pendingProduct, qty);
    }

    hideQtyKeypad();
}

function removeItem(itemId) {
    saleItems = saleItems.filter(i => i.id != itemId);
    updateSaleItems();
    updateTotal();
}

function toggleDiscountInput() {
    const type = document.getElementById('discount-type').value;
    const input = document.getElementById('discount-value');
    input.disabled = !type;
    if (!type) input.value = '';
    updateTotal();
}

function updateTotal() {
    let total = saleItems.reduce((sum, item) => sum + item.line_total, 0);

    const discountType = document.getElementById('discount-type').value;
    const discountValue = parseFloat(document.getElementById('discount-value').value) || 0;

    if (discountType === 'percentage' && discountValue > 0) {
        total -= total * (discountValue / 100);
    } else if (discountType === 'fixed' && discountValue > 0) {
        total -= discountValue;
    }

    total = Math.max(0, total);
    document.getElementById('total-amount').textContent = money(total);
}

async function completeSale() {
    if (saleItems.length === 0) return;

    const discountType = document.getElementById('discount-type').value;
    const discountValue = parseFloat(document.getElementById('discount-value').value) || null;
    const paymentMethod = document.getElementById('payment-method').value;

    const data = {
        items: saleItems.map(item => ({
            product_id: item.product_id,
            qty: item.qty,
            unit_price: item.unit_price,
            unit_type: item.unit_type,
            line_total: item.line_total
        })),
        discount_type: discountType || null,
        discount_value: discountValue,
        payment_method: paymentMethod
    };

    try {
        const response = await fetch('/public/api.php?action=sales', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('Vendita completata!', 'success');
            saleItems = [];
            updateSaleItems();
            updateTotal();
            document.getElementById('discount-type').value = '';
            document.getElementById('discount-value').value = '';
            document.getElementById('payment-method').value = 'cash';

            // Mostra ricevuta
            showReceipt(result.data.id);
        } else {
            showToast(result.error || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function showReceipt(saleId) {
    try {
        const response = await fetch(`/public/api.php?action=receipt&sale_id=${saleId}`);
        const html = await response.text();

        document.getElementById('receipt-content').innerHTML = html;
        document.getElementById('receipt-section').classList.remove('hidden');

        // Scroll to receipt
        document.getElementById('receipt-section').scrollIntoView({ behavior: 'smooth' });

        // Setup buttons
        document.getElementById('print-receipt-btn').onclick = () => printReceipt();
        document.getElementById('new-sale-btn').onclick = () => hideReceipt();
    } catch (error) {
        showToast('Errore caricamento ricevuta', 'error');
    }
}

function hideReceipt() {
    document.getElementById('receipt-section').classList.add('hidden');
    document.getElementById('receipt-content').innerHTML = '';
}

function printReceipt() {
    const receiptHTML = document.getElementById('receipt-content').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ricevuta</title>
            <style>
                body { font-family: monospace; font-size: 12px; max-width: 300px; margin: 0 auto; }
                .receipt-container { background: white; padding: 10px; }
                .header { text-align: center; margin-bottom: 10px; }
                .item { display: flex; justify-content: space-between; margin: 5px 0; }
                .total { border-top: 1px solid #000; padding-top: 5px; font-weight: bold; }
                .footer { text-align: center; margin-top: 10px; font-size: 10px; }
                @media print { body { max-width: none; } }
            </style>
        </head>
        <body>
            ${receiptHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php
include TEMPLATES_DIR . 'partials/layout.php';
?>