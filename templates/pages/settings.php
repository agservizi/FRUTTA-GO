<?php
if (!isLoggedIn()) {
    redirect('/login');
}
if (!hasPermission('settings')) {
    redirect('/dashboard');
}
$current_page = 'settings';

$content = '
<div class="p-4">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Impostazioni</h1>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <h2 class="text-lg font-semibold mb-4">Settaggi Generali</h2>
        <form id="settings-form" class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome attività</label>
                <input type="text" id="store-name" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Es. Frutta Go">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valuta</label>
                    <input type="text" id="currency-symbol" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="€">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IVA (%)</label>
                    <input type="number" id="vat-rate" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="4">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Soglia basso stock</label>
                <input type="number" id="low-stock-threshold" step="1" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="5">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Footer ricevuta</label>
                <textarea id="receipt-footer" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Messaggio in fondo alla ricevuta"></textarea>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700">Salva Impostazioni</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4">
        <h2 class="text-lg font-semibold mb-4">Categorie</h2>

        <form id="category-form" class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-4">
            <input type="hidden" id="category-id" value="">
            <input type="text" id="category-name" class="px-3 py-2 border border-gray-300 rounded-md" placeholder="Nome categoria" required>
            <input type="number" id="category-order" class="px-3 py-2 border border-gray-300 rounded-md" placeholder="Ordine" min="0" value="0">
            <button type="submit" class="md:col-span-3 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Salva Categoria</button>
        </form>

        <div id="categories-list" class="space-y-2"></div>
    </div>
</div>

<div id="delete-category-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Conferma eliminazione</h2>
            </div>
            <div class="p-4">
                <p id="delete-category-text" class="text-gray-700">Eliminare questa categoria?</p>
            </div>
            <div class="p-4 pt-0 flex space-x-3">
                <button type="button" id="delete-category-cancel-btn" class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600">Annulla</button>
                <button type="button" id="delete-category-confirm-btn" class="flex-1 bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">Elimina</button>
            </div>
        </div>
    </div>
</div>
';

ob_start();
?>
<script>
let categories = [];
let pendingDeleteCategoryId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    loadCategories();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('settings-form').addEventListener('submit', saveSettings);
    document.getElementById('category-form').addEventListener('submit', saveCategory);
    document.getElementById('delete-category-cancel-btn').addEventListener('click', hideDeleteCategoryModal);
    document.getElementById('delete-category-confirm-btn').addEventListener('click', confirmDeleteCategory);
    document.getElementById('delete-category-modal').addEventListener('click', (e) => {
        if (e.target.id === 'delete-category-modal') {
            hideDeleteCategoryModal();
        }
    });
}

async function loadSettings() {
    try {
        const response = await fetch('/public/api.php?action=settings');
        const result = await response.json();
        if (!result.success) return;

        const settings = result.data?.settings || {};
        document.getElementById('store-name').value = settings.store_name || '';
        document.getElementById('currency-symbol').value = settings.currency_symbol || '€';
        document.getElementById('vat-rate').value = settings.vat_rate || '4';
        document.getElementById('low-stock-threshold').value = settings.low_stock_threshold || '5';
        document.getElementById('receipt-footer').value = settings.receipt_footer || '';
    } catch (error) {
        showToast('Errore caricamento impostazioni', 'error');
    }
}

async function saveSettings(e) {
    e.preventDefault();

    const data = {
        store_name: document.getElementById('store-name').value.trim(),
        currency_symbol: document.getElementById('currency-symbol').value.trim(),
        vat_rate: document.getElementById('vat-rate').value.trim(),
        low_stock_threshold: document.getElementById('low-stock-threshold').value.trim(),
        receipt_footer: document.getElementById('receipt-footer').value.trim()
    };

    try {
        const response = await fetch('/public/api.php?action=settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('Impostazioni salvate', 'success');
        } else {
            showToast(result.error || 'Errore salvataggio', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function loadCategories() {
    try {
        const response = await fetch('/public/api.php?action=categories');
        const result = await response.json();
        if (!result.success) return;

        categories = result.data || [];
        renderCategories();
    } catch (error) {
        showToast('Errore caricamento categorie', 'error');
    }
}

function renderCategories() {
    const container = document.getElementById('categories-list');

    if (categories.length === 0) {
        container.innerHTML = '<div class="text-sm text-gray-500">Nessuna categoria presente.</div>';
        return;
    }

    container.innerHTML = categories.map(c => `
        <div class="p-3 bg-gray-50 rounded-md border border-gray-200 flex justify-between items-center">
            <div>
                <div class="font-medium">${c.name}</div>
                <div class="text-sm text-gray-600">Ordine: ${c.sort_order}</div>
            </div>
            <div class="flex space-x-2">
                <button onclick="editCategory(${c.id})" class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded-md">Modifica</button>
                <button onclick="deleteCategory(${c.id})" class="text-sm px-3 py-1 bg-red-100 text-red-700 rounded-md">Elimina</button>
            </div>
        </div>
    `).join('');
}

function editCategory(id) {
    const category = categories.find(c => c.id == id);
    if (!category) return;

    document.getElementById('category-id').value = category.id;
    document.getElementById('category-name').value = category.name;
    document.getElementById('category-order').value = category.sort_order || 0;
}

async function saveCategory(e) {
    e.preventDefault();

    const id = document.getElementById('category-id').value;
    const name = document.getElementById('category-name').value.trim();
    const sort_order = parseInt(document.getElementById('category-order').value || '0', 10);

    if (!name) {
        showToast('Nome categoria richiesto', 'error');
        return;
    }

    const method = id ? 'PUT' : 'POST';
    const url = id ? `/public/api.php?action=categories&id=${id}` : '/public/api.php?action=categories';

    try {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, sort_order })
        });

        const result = await response.json();
        if (result.success) {
            showToast(id ? 'Categoria aggiornata' : 'Categoria creata', 'success');
            resetCategoryForm();
            loadCategories();
        } else {
            showToast(result.error || 'Errore salvataggio categoria', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

function resetCategoryForm() {
    document.getElementById('category-id').value = '';
    document.getElementById('category-name').value = '';
    document.getElementById('category-order').value = '0';
}

async function deleteCategory(id) {
    const category = categories.find(c => c.id == id);
    showDeleteCategoryModal(id, category ? category.name : null);
}

function showDeleteCategoryModal(id, name = null) {
    pendingDeleteCategoryId = id;
    const text = name
        ? `Eliminare la categoria "${name}"?`
        : 'Eliminare questa categoria?';
    document.getElementById('delete-category-text').textContent = text;
    document.getElementById('delete-category-modal').classList.remove('hidden');
}

function hideDeleteCategoryModal() {
    pendingDeleteCategoryId = null;
    document.getElementById('delete-category-modal').classList.add('hidden');
}

async function confirmDeleteCategory() {
    if (!pendingDeleteCategoryId) {
        return;
    }

    const id = pendingDeleteCategoryId;
    hideDeleteCategoryModal();

    try {
        const response = await fetch(`/public/api.php?action=categories&id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (result.success) {
            showToast('Categoria eliminata', 'success');
            loadCategories();
        } else {
            showToast(result.error || 'Errore eliminazione', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}
</script>
<?php
$page_script = ob_get_clean();
include TEMPLATES_DIR . 'partials/layout.php';
?>