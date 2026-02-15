<?php
if (!isLoggedIn()) {
    redirect('/login');
}
$current_page = 'inventory';
$currencySymbol = h(getAppSetting('currency_symbol', '€'));

$content = '
<div class="p-4">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Magazzino</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <button id="stock-btn" class="bg-blue-500 text-white p-4 rounded-lg shadow-md hover:bg-blue-600 text-center">
            <h3 class="text-lg font-semibold">Giacenze</h3>
            <p class="text-sm opacity-90">Visualizza stock attuale</p>
        </button>

        <button id="movements-btn" class="bg-green-500 text-white p-4 rounded-lg shadow-md hover:bg-green-600 text-center">
            <h3 class="text-lg font-semibold">Movimenti</h3>
            <p class="text-sm opacity-90">Storico movimenti</p>
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <h2 class="text-lg font-semibold mb-4">Nuovo Movimento</h2>

        <form id="movement-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                <div class="relative">
                    <div id="movement-type-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                        <span id="movement-type-text">Carico</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <input type="hidden" name="type" id="movement-type" value="in">
                    <div id="movement-type-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden">
                        <div class="py-1">
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="in">Carico</div>
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="out">Scarico</div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prodotto *</label>
                <input type="text" id="movement-product-search" placeholder="Cerca prodotto..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md" autocomplete="off">
                <input type="hidden" name="product_id" id="movement-product" value="">
                <div id="movement-product-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden max-h-56 overflow-y-auto"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantità *</label>
                <input type="number" name="qty" id="movement-qty" step="0.1" min="0.1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unità</label>
                <div class="relative">
                    <div id="movement-unit-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                        <span id="movement-unit-text">Kg</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <input type="hidden" name="unit_type" id="movement-unit" value="kg">
                    <div id="movement-unit-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden">
                        <div class="py-1">
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="kg">Kg</div>
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="pz">Pezzo</div>
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="cassetta">Cassetta</div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="cost-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">Costo totale (' . $currencySymbol . ')</label>
                <input type="number" name="cost_total" id="movement-cost" step="0.01" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg">
            </div>

            <div id="reason-field" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo scarico</label>
                <div class="relative">
                    <div id="movement-reason-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                        <span id="movement-reason-text">Vendita</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <input type="hidden" name="reason" id="movement-reason" value="vendita">
                    <div id="movement-reason-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden">
                        <div class="py-1">
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="vendita">Vendita</div>
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="spreco">Spreco</div>
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="rettifica">Rettifica</div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                <textarea name="note" id="movement-note" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg"></textarea>
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700 text-lg font-medium">
                Registra Movimento
            </button>
        </form>
    </div>

    <div id="content-area">
        <!-- Stock or movements will be shown here -->
    </div>
</div>

<script>
let products = [];
let currentView = \'stock\';
const LOW_STOCK_THRESHOLD = parseFloat((window.appSettings && window.appSettings.low_stock_threshold) ? window.appSettings.low_stock_threshold : \'5\');

document.addEventListener(\'DOMContentLoaded\', () => {
    loadProducts();
    setupEventListeners();
    showStock();
});

async function loadProducts() {
    try {
        const response = await fetch(\'/public/api.php?action=products\');
        const result = await response.json();
        if (result.success) {
            products = result.data;
            renderProductSelect();
        }
    } catch (error) {
        showToast(\'Errore caricamento prodotti\', \'error\');
    }
}

function setupEventListeners() {
    document.getElementById(\'stock-btn\').addEventListener(\'click\', showStock);
    document.getElementById(\'movements-btn\').addEventListener(\'click\', showMovements);
    document.getElementById(\'movement-form\').addEventListener(\'submit\', saveMovement);    
    // Setup product search
    document.getElementById(\'movement-product-search\').addEventListener(\'input\', filterMovementProducts);
    document.getElementById(\'movement-product-search\').addEventListener(\'focus\', showMovementProductDropdown);
    document.addEventListener(\'click\', hideMovementProductDropdown);
    
    // Handle product selection from dropdown
    document.getElementById(\'movement-product-dropdown\').addEventListener(\'click\', function(e) {
        var option = e.target.closest(\'[data-value]\');
        if (!option) return;
        handleMovementProductSelected(option.getAttribute(\'data-value\'));
    });
    setupCustomDropdowns();
}

function renderProductSelect(filterQuery) {
    var dropdown = document.getElementById(\'movement-product-dropdown\');
    var filteredProducts = products.slice(0, 10);
    if (filterQuery) {
        filteredProducts = products.filter(function(p) { return p.name.toLowerCase().includes(filterQuery.toLowerCase()); });
    }
    
    var dropdownContent = document.createElement(\'div\');
    dropdownContent.className = \'py-1\';
    
    if (filteredProducts.length === 0) {
        dropdownContent.innerHTML = \'<div class="px-3 py-2 text-gray-500">Nessun prodotto trovato</div>\';
    } else {
        var html = \'\';
        for (var i = 0; i < filteredProducts.length; i++) {
            var p = filteredProducts[i];
            html += \'<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="\' + p.id + \'" data-unit="\' + p.unit_type + \'">\' + p.name + \'</div>\';
        }
        dropdownContent.innerHTML = html;
    }

    dropdown.innerHTML = \'\';
    dropdown.appendChild(dropdownContent);
}

function filterMovementProducts(e) {
    const query = e.target.value;
    renderProductSelect(query);
    document.getElementById(\'movement-product-dropdown\').classList.remove(\'hidden\');
}

function showMovementProductDropdown() {
    renderProductSelect(document.getElementById(\'movement-product-search\').value);
    document.getElementById(\'movement-product-dropdown\').classList.remove(\'hidden\');
}

function hideMovementProductDropdown(e) {
    const searchInput = document.getElementById(\'movement-product-search\');
    const dropdown = document.getElementById(\'movement-product-dropdown\');
    
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add(\'hidden\');
    }
}

function handleMovementProductSelected(productId) {
    var product = products.find(function(p) { return String(p.id) == String(productId); });
    if (product == null) return;

    document.getElementById(\'movement-product-search\').value = product.name;
    document.getElementById(\'movement-product\').value = product.id;
    document.getElementById(\'movement-product-dropdown\').classList.add(\'hidden\');
    updateUnitType();
    document.getElementById(\'movement-qty\').focus();
}

function setupDropdown(displayId, dropdownId, inputId, textId, onChange = null) {
    const display = document.getElementById(displayId);
    const dropdown = document.getElementById(dropdownId);
    const input = document.getElementById(inputId);
    const text = document.getElementById(textId);

    display.addEventListener(\'click\', () => {
        dropdown.classList.toggle(\'hidden\');
    });

    dropdown.addEventListener(\'click\', (e) => {
        if (e.target.dataset.value !== undefined) {
            input.value = e.target.dataset.value;
            text.textContent = e.target.textContent;
            dropdown.classList.add(\'hidden\');

            if (onChange) {
                onChange(e.target);
            }
        }
    });

    document.addEventListener(\'click\', (e) => {
        if (!display.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add(\'hidden\');
        }
    });
}

function setupCustomDropdowns() {
    setupDropdown(\'movement-type-display\', \'movement-type-dropdown\', \'movement-type\', \'movement-type-text\', toggleFields);

    setupDropdown(\'movement-unit-display\', \'movement-unit-dropdown\', \'movement-unit\', \'movement-unit-text\');
    setupDropdown(\'movement-reason-display\', \'movement-reason-dropdown\', \'movement-reason\', \'movement-reason-text\');
}

function toggleFields() {
    const type = document.getElementById(\'movement-type\').value;
    const costField = document.getElementById(\'cost-field\');
    const reasonField = document.getElementById(\'reason-field\');

    if (type === \'in\') {
        costField.classList.remove(\'hidden\');
        reasonField.classList.add(\'hidden\');
    } else {
        costField.classList.add(\'hidden\');
        reasonField.classList.remove(\'hidden\');
    }
}

function updateUnitType() {
    const productId = document.getElementById(\'movement-product\').value;
    const product = products.find(p => String(p.id) === String(productId));
    const unitType = product ? product.unit_type : \'kg\';
    document.getElementById(\'movement-unit\').value = unitType;
    const unitLabels = { kg: \'Kg\', pz: \'Pezzo\', cassetta: \'Cassetta\' };
    document.getElementById(\'movement-unit-text\').textContent = unitLabels[unitType] || \'Kg\';
}

async function showStock() {
    currentView = \'stock\';
    document.getElementById(\'stock-btn\').classList.add(\'bg-blue-600\');
    document.getElementById(\'stock-btn\').classList.remove(\'bg-blue-500\');
    document.getElementById(\'movements-btn\').classList.remove(\'bg-green-600\');
    document.getElementById(\'movements-btn\').classList.add(\'bg-green-500\');

    try {
        const response = await fetch(\'/public/api.php?action=inventory\');
        const result = await response.json();
        if (result.success) {
            renderStock(result.data.stock);
        }
    } catch (error) {
        showToast(\'Errore caricamento giacenze\', \'error\');
    }
}

async function showMovements() {
    currentView = \'movements\';
    document.getElementById(\'movements-btn\').classList.add(\'bg-green-600\');
    document.getElementById(\'movements-btn\').classList.remove(\'bg-green-500\');
    document.getElementById(\'stock-btn\').classList.remove(\'bg-blue-600\');
    document.getElementById(\'stock-btn\').classList.add(\'bg-blue-500\');

    try {
        const response = await fetch(\'/public/api.php?action=inventory\');
        const result = await response.json();
        if (result.success) {
            renderMovements(result.data.movements);
        }
    } catch (error) {
        showToast(\'Errore caricamento movimenti\', \'error\');
    }
}

function renderStock(stock) {
    const container = document.getElementById(\'content-area\');
    container.innerHTML = `
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-lg font-semibold mb-4">Giacenze Attuali</h2>
            <div class="space-y-3">
                ${stock.map(s => {
                    const stockValue = parseFloat(s.stock || 0);
                    const isLowStock = stockValue <= LOW_STOCK_THRESHOLD;
                    const stockClass = isLowStock ? \'text-red-600\' : \'text-green-600\';
                    return `
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md">
                        <div>
                            <div class="font-medium">${s.name}</div>
                            <div class="text-sm text-gray-600">${s.unit_type}${isLowStock ? \' • Sotto soglia\' : \'\'}</div>
                        </div>
                        <div class="text-lg font-semibold ${stockClass}">${s.stock}</div>
                    </div>
                    `;
                }).join(\'\')}
            </div>
        </div>
    `;
}

function renderMovements(movements) {
    const container = document.getElementById(\'content-area\');
    container.innerHTML = `
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-lg font-semibold mb-4">Movimenti Recenti</h2>
            <div class="space-y-3">
                ${movements.map(m => `
                    <div class="p-3 bg-gray-50 rounded-md">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-medium">${m.product_name}</div>
                                <div class="text-sm text-gray-600">${m.user_name} • ${new Date(m.created_at).toLocaleString()}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold ${m.type === \'in\' ? \'text-green-600\' : \'text-red-600\'}">
                                    ${m.type === \'in\' ? \'+\' : \'-\'}${m.qty} ${m.unit_type}
                                </div>
                                <div class="text-sm text-gray-600">${m.reason || \'\'}</div>
                            </div>
                        </div>
                        ${m.note ? `<div class="text-sm text-gray-500">${m.note}</div>` : \'\'}
                    </div>
                `).join(\'\')}
            </div>
        </div>
    `;
}

async function saveMovement(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch(\'/public/api.php?action=inventory\', {
            method: \'POST\',
            headers: { \'Content-Type\': \'application/json\' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast(result.message || \'Movimento registrato\', \'success\');
            e.target.reset();
            document.getElementById(\'movement-type\').value = \'in\';
            document.getElementById(\'movement-type-text\').textContent = \'Carico\';
            document.getElementById(\'movement-product\').value = \'\';
            document.getElementById(\'movement-product-search\').value = \'\';
            document.getElementById(\'movement-unit\').value = \'kg\';
            document.getElementById(\'movement-unit-text\').textContent = \'Kg\';
            document.getElementById(\'movement-reason\').value = \'vendita\';
            document.getElementById(\'movement-reason-text\').textContent = \'Vendita\';
            toggleFields();
            if (currentView === \'stock\') {
                showStock();
            } else {
                showMovements();
            }
        } else {
            showToast(result.error || \'Errore\', \'error\');
        }
    } catch (error) {
        showToast(\'Errore di connessione\', \'error\');
    }
}
</script>
';
include TEMPLATES_DIR . 'partials/layout.php';
?>