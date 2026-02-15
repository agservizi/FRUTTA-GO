<?php
if (!isLoggedIn()) {
    redirect('/login');
}
$current_page = 'purchases';
$currencySymbol = h(getAppSetting('currency_symbol', '€'));

$content = '
<div class="p-4">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Acquisti</h1>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold">Nuovo Acquisto</h2>
            <button id="toggle-supplier-form" class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded-md">+ Fornitore</button>
        </div>

        <form id="supplier-form" class="space-y-3 mb-4 hidden">
            <input type="text" id="supplier-name" placeholder="Nome fornitore *" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
            <input type="text" id="supplier-phone" placeholder="Telefono" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <input type="email" id="supplier-email" placeholder="Email" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Salva Fornitore</button>
        </form>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore</label>
            <div class="relative">
                <div id="purchase-supplier-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                    <span id="purchase-supplier-text">Seleziona fornitore (opzionale)</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <input type="hidden" id="purchase-supplier-id" value="">
                <div id="purchase-supplier-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden max-h-56 overflow-y-auto"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
            <div class="md:col-span-2 relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Prodotto</label>
                <input type="text" id="purchase-product-search" placeholder="Cerca prodotto..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md" autocomplete="off">
                <input type="hidden" id="purchase-product-id" value="">
                <input type="hidden" id="purchase-product-unit" value="kg">
                <div id="purchase-product-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden max-h-56 overflow-y-auto"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantità</label>
                <input type="number" id="purchase-qty" step="0.001" min="0.001" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="0.000">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Costo unitario (' . $currencySymbol . ')</label>
                <input type="number" id="purchase-unit-cost" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="0.00">
            </div>
        </div>

        <div class="mb-4">
            <button id="add-purchase-item-btn" class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700">Aggiungi Riga Acquisto</button>
        </div>

        <div id="purchase-items" class="space-y-2 mb-4"></div>

        <div class="flex justify-between items-center mb-4">
            <span class="text-lg font-semibold">Totale Acquisto</span>
            <span id="purchase-total" class="text-2xl font-bold text-green-600">' . $currencySymbol . '0.00</span>
        </div>

        <textarea id="purchase-note" rows="2" placeholder="Note acquisto..." class="w-full px-3 py-2 border border-gray-300 rounded-md mb-3"></textarea>

        <button id="save-purchase-btn" class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 disabled:opacity-50" disabled>
            Registra Acquisto
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4">
        <h2 class="text-lg font-semibold mb-3">Storico Acquisti</h2>
        <div id="purchases-history" class="space-y-2"></div>
    </div>
</div>
';
ob_start();
?>

<script>
let suppliers = [];
let products = [];
let purchaseItems = [];
const CURRENCY = (window.appSettings && window.appSettings.currency_symbol) ? window.appSettings.currency_symbol : '€';

function money(value) {
    return `${CURRENCY}${parseFloat(value || 0).toFixed(2)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    loadSuppliers();
    loadProducts();
    loadPurchases();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('toggle-supplier-form').addEventListener('click', () => {
        document.getElementById('supplier-form').classList.toggle('hidden');
    });

    document.getElementById('supplier-form').addEventListener('submit', saveSupplier);
    document.getElementById('add-purchase-item-btn').addEventListener('click', addPurchaseItem);
    document.getElementById('save-purchase-btn').addEventListener('click', savePurchase);

    setupDropdown('purchase-supplier-display', 'purchase-supplier-dropdown', 'purchase-supplier-id', 'purchase-supplier-text');
    
    // Setup product search
    document.getElementById('purchase-product-search').addEventListener('input', filterPurchaseProducts);
    document.getElementById('purchase-product-search').addEventListener('focus', showPurchaseProductDropdown);
    document.addEventListener('click', hidePurchaseProductDropdown);
    
    // Handle product selection from dropdown
    document.getElementById('purchase-product-dropdown').addEventListener('click', (e) => {
        const option = e.target.closest('[data-value]');
        if (!option) return;
        handleProductSelected(option.dataset.value);
    });
}

function setupDropdown(displayId, dropdownId, inputId, textId, onSelect = null) {
    const display = document.getElementById(displayId);
    const dropdown = document.getElementById(dropdownId);
    const input = document.getElementById(inputId);
    const text = document.getElementById(textId);

    display.addEventListener('click', () => {
        dropdown.classList.toggle('hidden');
    });

    dropdown.addEventListener('click', (e) => {
        const option = e.target.closest('[data-value]');
        if (!option) return;

        input.value = option.dataset.value;
        text.textContent = option.dataset.label || option.textContent;
        dropdown.classList.add('hidden');

        if (onSelect) {
            onSelect(option.dataset.value);
        }
    });

    document.addEventListener('click', (e) => {
        if (!display.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

async function loadSuppliers() {
    try {
        const response = await fetch('/public/api.php?action=suppliers');
        const result = await response.json();
        if (!result.success) return;

        suppliers = result.data?.suppliers || [];
        renderSuppliersDropdown();
    } catch (error) {
        showToast('Errore caricamento fornitori', 'error');
    }
}

function renderSuppliersDropdown() {
    const dropdown = document.getElementById('purchase-supplier-dropdown');
    const content = document.createElement('div');
    content.className = 'py-1';
    content.innerHTML = '<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="" data-label="Seleziona fornitore (opzionale)">Seleziona fornitore (opzionale)</div>' +
        suppliers.map(s => `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="${s.id}" data-label="${s.name}">${s.name}</div>`).join('');

    dropdown.innerHTML = '';
    dropdown.appendChild(content);
}

async function loadProducts() {
    try {
        const response = await fetch('/public/api.php?action=products');
        const result = await response.json();
        if (!result.success) return;

        products = result.data || [];
        renderProductsDropdown();
    } catch (error) {
        showToast('Errore caricamento prodotti', 'error');
    }
}

function renderProductsDropdown(filterQuery = '') {
    const dropdown = document.getElementById('purchase-product-dropdown');
    const filteredProducts = filterQuery ? 
        products.filter(p => p.name.toLowerCase().includes(filterQuery.toLowerCase())) : 
        products.slice(0, 10); // Show first 10 if no filter

    const content = document.createElement('div');
    content.className = 'py-1';
    
    if (filteredProducts.length === 0) {
        content.innerHTML = '<div class="px-3 py-2 text-gray-500">Nessun prodotto trovato</div>';
    } else {
        content.innerHTML = filteredProducts.map(p => 
            `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="${p.id}" data-label="${p.name}">${p.name}</div>`
        ).join('');
    }

    dropdown.innerHTML = '';
    dropdown.appendChild(content);
}

function filterPurchaseProducts(e) {
    const query = e.target.value;
    renderProductsDropdown(query);
    document.getElementById('purchase-product-dropdown').classList.remove('hidden');
}

function showPurchaseProductDropdown() {
    renderProductsDropdown(document.getElementById('purchase-product-search').value);
    document.getElementById('purchase-product-dropdown').classList.remove('hidden');
}

function hidePurchaseProductDropdown(e) {
    const searchInput = document.getElementById('purchase-product-search');
    const dropdown = document.getElementById('purchase-product-dropdown');
    
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
}

function handleProductSelected(productId) {
    const product = products.find(p => String(p.id) === String(productId));
    if (!product) return;

    document.getElementById('purchase-product-search').value = product.name;
    document.getElementById('purchase-product-id').value = product.id;
    document.getElementById('purchase-product-unit').value = product.unit_type;
    document.getElementById('purchase-unit-cost').value = product.price_cost || '';
    document.getElementById('purchase-product-dropdown').classList.add('hidden');
    document.getElementById('purchase-qty').focus();
}

async function saveSupplier(e) {
    e.preventDefault();

    const data = {
        name: document.getElementById('supplier-name').value.trim(),
        phone: document.getElementById('supplier-phone').value.trim(),
        email: document.getElementById('supplier-email').value.trim()
    };

    if (!data.name) {
        showToast('Nome fornitore richiesto', 'error');
        return;
    }

    try {
        const response = await fetch('/public/api.php?action=suppliers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('Fornitore creato', 'success');
            document.getElementById('supplier-form').reset();
            document.getElementById('supplier-form').classList.add('hidden');
            await loadSuppliers();
        } else {
            showToast(result.error || 'Errore creazione fornitore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

function addPurchaseItem() {
    const productId = document.getElementById('purchase-product-id').value;
    const qty = parseFloat(document.getElementById('purchase-qty').value || 0);
    const unitCost = parseFloat(document.getElementById('purchase-unit-cost').value || 0);

    if (!productId || qty <= 0 || unitCost < 0) {
        showToast('Seleziona prodotto e inserisci quantità/costo validi', 'error');
        return;
    }

    const product = products.find(p => String(p.id) === String(productId));
    if (!product) return;

    const unitType = document.getElementById('purchase-product-unit').value || product.unit_type;

    purchaseItems.push({
        id: Date.now() + Math.random(),
        product_id: product.id,
        product_name: product.name,
        qty,
        unit_type: unitType,
        unit_cost: unitCost,
        line_total: qty * unitCost
    });

    renderPurchaseItems();
    resetItemInputs();
}

function resetItemInputs() {
    document.getElementById('purchase-product-id').value = '';
    document.getElementById('purchase-product-search').value = '';
    document.getElementById('purchase-qty').value = '';
    document.getElementById('purchase-unit-cost').value = '';
}

function renderPurchaseItems() {
    const container = document.getElementById('purchase-items');
    const total = purchaseItems.reduce((sum, item) => sum + item.line_total, 0);

    container.innerHTML = purchaseItems.map(item => `
        <div class="p-3 bg-gray-50 rounded-md border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <div class="font-medium">${item.product_name}</div>
                    <div class="text-sm text-gray-600">${item.qty} ${item.unit_type} × ${money(item.unit_cost)}</div>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-green-700">${money(item.line_total)}</div>
                    <button onclick="removePurchaseItem(${item.id})" class="text-sm text-red-500 hover:text-red-700">Rimuovi</button>
                </div>
            </div>
        </div>
    `).join('');

    document.getElementById('purchase-total').textContent = money(total);
    document.getElementById('save-purchase-btn').disabled = purchaseItems.length === 0;
}

function removePurchaseItem(itemId) {
    purchaseItems = purchaseItems.filter(i => i.id !== itemId);
    renderPurchaseItems();
}

async function savePurchase() {
    if (purchaseItems.length === 0) {
        showToast('Aggiungi almeno una riga acquisto', 'error');
        return;
    }

    const data = {
        supplier_id: document.getElementById('purchase-supplier-id').value || null,
        note: document.getElementById('purchase-note').value.trim(),
        items: purchaseItems.map(item => ({
            product_id: item.product_id,
            qty: item.qty,
            unit_type: item.unit_type,
            unit_cost: item.unit_cost
        }))
    };

    try {
        const response = await fetch('/public/api.php?action=purchases', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('Acquisto registrato con successo', 'success');
            purchaseItems = [];
            renderPurchaseItems();
            document.getElementById('purchase-note').value = '';
            document.getElementById('purchase-supplier-id').value = '';
            document.getElementById('purchase-supplier-text').textContent = 'Seleziona fornitore (opzionale)';
            await loadPurchases();
            await loadProducts();
        } else {
            showToast(result.error || 'Errore registrazione acquisto', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function loadPurchases() {
    try {
        const response = await fetch('/public/api.php?action=purchases');
        const result = await response.json();
        if (!result.success) return;

        renderPurchasesHistory(result.data?.purchases || []);
    } catch (error) {
        showToast('Errore caricamento storico acquisti', 'error');
    }
}

function renderPurchasesHistory(purchases) {
    const container = document.getElementById('purchases-history');

    if (purchases.length === 0) {
        container.innerHTML = '<div class="text-sm text-gray-500">Nessun acquisto registrato.</div>';
        return;
    }

    container.innerHTML = purchases.map(p => `
        <div class="p-3 bg-gray-50 rounded-md border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <div class="font-medium">${p.supplier_name || 'Fornitore non specificato'}</div>
                    <div class="text-sm text-gray-600">${new Date(p.created_at).toLocaleString()} • ${p.items_count} righe</div>
                    ${p.note ? `<div class="text-sm text-gray-500 mt-1">${p.note}</div>` : ''}
                </div>
                <div class="text-right">
                    <div class="font-semibold text-green-700">${money(p.total)}</div>
                    <div class="text-xs text-gray-500">${p.user_name}</div>
                </div>
            </div>
        </div>
    `).join('');
}
</script>

<?php
$page_script = ob_get_clean();
include TEMPLATES_DIR . 'partials/layout.php';
?>