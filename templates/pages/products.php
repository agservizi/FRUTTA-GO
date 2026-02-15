<?php
if (!isLoggedIn()) {
    redirect('/login');
}
$current_page = 'products';
$currencySymbol = h(getAppSetting('currency_symbol', '€'));

$content = '
<div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Prodotti</h1>
        <button id="add-product-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
            + Nuovo Prodotto
        </button>
    </div>

    <div class="mb-4">
        <input type="text" id="search-products" placeholder="Cerca prodotti..."
               class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg">
    </div>

    <div id="products-list" class="space-y-3">
        <!-- Products will be loaded here -->
    </div>
</div>

<!-- Product modal -->
<div id="product-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-end justify-center min-h-screen p-4">
        <div class="bg-white rounded-t-lg shadow-xl w-full max-w-md">
            <div class="p-4 border-b">
                <h2 id="modal-title" class="text-lg font-semibold">Nuovo Prodotto</h2>
            </div>
            <form id="product-form" class="p-4 space-y-4">
                <input type="hidden" name="id" id="product-id">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="name" id="product-name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <div class="relative">
                        <div id="category-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                            <span id="category-text">Seleziona categoria</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <input type="hidden" name="category_id" id="product-category" value="">
                        <div id="category-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden max-h-48 overflow-y-auto">
                            <div class="py-1">
                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="">Seleziona categoria</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unità *</label>
                    <div class="relative">
                        <div id="unit-display" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer flex items-center justify-between">
                            <span id="unit-text">Kg</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <input type="hidden" name="unit_type" id="product-unit" value="kg">
                        <div id="unit-dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden">
                            <div class="py-1">
                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="kg">Kg</div>
                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="pz">Pezzo</div>
                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="cassetta">Cassetta</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prezzo vendita (' . $currencySymbol . ') *</label>
                    <input type="number" name="price_sale" id="product-price" step="0.01" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Costo (' . $currencySymbol . ')</label>
                    <input type="number" name="price_cost" id="product-cost" step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-lg">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_favorite" id="product-favorite" class="mr-2">
                    <label for="product-favorite" class="text-sm font-medium text-gray-700">Preferito</label>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" id="cancel-btn" class="flex-1 bg-gray-500 text-white py-3 px-4 rounded-md hover:bg-gray-600">
                        Annulla
                    </button>
                    <button type="submit" class="flex-1 bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700">
                        Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
';
?>

<script>
let products = [];
let categories = [];
const CURRENCY = (window.appSettings && window.appSettings.currency_symbol) ? window.appSettings.currency_symbol : '€';

function money(value) {
    return `${CURRENCY}${parseFloat(value || 0).toFixed(2)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    loadCategories();
    setupEventListeners();
});

async function loadProducts() {
    try {
        const response = await fetch('/public/api.php?action=products');
        const result = await response.json();
        if (result.success) {
            products = result.data;
            renderProducts(products);
        }
    } catch (error) {
        showToast('Errore caricamento prodotti', 'error');
    }
}

async function loadCategories() {
    try {
        const response = await fetch('/public/api.php?action=categories');
        const result = await response.json();
        if (result.success) {
            categories = result.data;
            renderCategories();
        }
    } catch (error) {
        console.error('Errore caricamento categorie:', error);
    }
}

function setupEventListeners() {
    document.getElementById('add-product-btn').addEventListener('click', () => showProductModal());
    document.getElementById('cancel-btn').addEventListener('click', hideProductModal);
    document.getElementById('product-form').addEventListener('submit', saveProduct);
    document.getElementById('search-products').addEventListener('input', filterProducts);

    // Custom dropdown for category
    setupCategoryDropdown();
    setupUnitDropdown();
}

function setupCategoryDropdown() {
    const categoryDisplay = document.getElementById('category-display');
    const categoryDropdown = document.getElementById('category-dropdown');
    const categorySelect = document.getElementById('product-category');
    const categoryText = document.getElementById('category-text');

    categoryDisplay.addEventListener('click', () => {
        categoryDropdown.classList.toggle('hidden');
    });

    categoryDropdown.addEventListener('click', (e) => {
        if (e.target.dataset.value !== undefined) {
            const value = e.target.dataset.value;
            const text = e.target.textContent;

            categorySelect.value = value;
            categoryText.textContent = text;
            categoryDropdown.classList.add('hidden');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!categoryDisplay.contains(e.target) && !categoryDropdown.contains(e.target)) {
            categoryDropdown.classList.add('hidden');
        }
    });
}

function setupUnitDropdown() {
    const unitDisplay = document.getElementById('unit-display');
    const unitDropdown = document.getElementById('unit-dropdown');
    const unitInput = document.getElementById('product-unit');
    const unitText = document.getElementById('unit-text');

    unitDisplay.addEventListener('click', () => {
        unitDropdown.classList.toggle('hidden');
    });

    unitDropdown.addEventListener('click', (e) => {
        if (e.target.dataset.value !== undefined) {
            unitInput.value = e.target.dataset.value;
            unitText.textContent = e.target.textContent;
            unitDropdown.classList.add('hidden');
        }
    });

    document.addEventListener('click', (e) => {
        if (!unitDisplay.contains(e.target) && !unitDropdown.contains(e.target)) {
            unitDropdown.classList.add('hidden');
        }
    });
}

function renderCategories() {
    const dropdown = document.getElementById('category-dropdown');

    // Update custom dropdown
    const dropdownContent = document.createElement('div');
    dropdownContent.className = 'py-1';
    dropdownContent.innerHTML = '<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="">Seleziona categoria</div>' +
        categories.map(c => `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-value="${c.id}">${c.name}</div>`).join('');

    dropdown.innerHTML = '';
    dropdown.appendChild(dropdownContent);
}

function renderProducts(productsToShow) {
    const container = document.getElementById('products-list');
    container.innerHTML = productsToShow.map(p => `
        <div class="bg-white p-4 rounded-lg shadow-md">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <h3 class="text-lg font-semibold">${p.name}</h3>
                        ${p.is_favorite ? '<span class="text-yellow-500">★</span>' : ''}
                    </div>
                    <p class="text-gray-600">${p.category_name || 'Nessuna categoria'} • ${p.unit_type}</p>
                    <p class="text-green-600 font-medium">${money(p.price_sale)}</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="editProduct(${p.id})" class="text-blue-500 hover:text-blue-700 p-2" title="Modifica">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                        </svg>
                    </button>
                    <button onclick="deleteProduct(${p.id})" class="text-red-500 hover:text-red-700 p-2" title="Elimina">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function filterProducts(e) {
    const query = e.target.value.toLowerCase();
    const filtered = products.filter(p => p.name.toLowerCase().includes(query));
    renderProducts(filtered);
}

function showProductModal(product = null) {
    const modal = document.getElementById('product-modal');
    const form = document.getElementById('product-form');
    const title = document.getElementById('modal-title');

    if (product) {
        title.textContent = 'Modifica Prodotto';
        document.getElementById('product-id').value = product.id;
        document.getElementById('product-name').value = product.name;
        document.getElementById('product-category').value = product.category_id || '';
        document.getElementById('product-unit').value = product.unit_type;
        document.getElementById('category-text').textContent = product.category_name || 'Seleziona categoria';
        const unitLabels = { kg: 'Kg', pz: 'Pezzo', cassetta: 'Cassetta' };
        document.getElementById('unit-text').textContent = unitLabels[product.unit_type] || 'Kg';
        document.getElementById('product-price').value = product.price_sale;
        document.getElementById('product-cost').value = product.price_cost || '';
        document.getElementById('product-favorite').checked = product.is_favorite;
    } else {
        title.textContent = 'Nuovo Prodotto';
        form.reset();
        document.getElementById('product-id').value = '';
        document.getElementById('product-category').value = '';
        document.getElementById('product-unit').value = 'kg';
        document.getElementById('category-text').textContent = 'Seleziona categoria';
        document.getElementById('unit-text').textContent = 'Kg';
    }

    modal.classList.remove('hidden');
}

function hideProductModal() {
    document.getElementById('product-modal').classList.add('hidden');
}

function editProduct(productId) {
    const product = products.find(p => p.id == productId);
    if (product) {
        showProductModal(product);
    }
}

async function saveProduct(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.is_favorite = data.is_favorite ? 1 : 0;

    const isEdit = data.id;
    const method = isEdit ? 'PUT' : 'POST';
    const url = isEdit ? `/public/api.php?action=products&id=${data.id}` : '/public/api.php?action=products';

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast(result.message || 'Prodotto salvato', 'success');
            hideProductModal();
            loadProducts();
        } else {
            showToast(result.error || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function deleteProduct(productId) {
    if (!confirm('Sei sicuro di voler eliminare questo prodotto?')) return;

    try {
        const response = await fetch(`/public/api.php?action=products&id=${productId}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (result.success) {
            showToast('Prodotto eliminato con successo', 'success');
            loadProducts();
        } else {
            showToast(result.error || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}
</script>
<?php
include TEMPLATES_DIR . 'partials/layout.php';
?>