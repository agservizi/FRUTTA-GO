<?php
if (!isLoggedIn()) {
    redirect('/login');
}
$current_page = 'reports';

$content = '
<div class="p-4 max-w-full overflow-x-hidden">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Report</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <button id="daily-btn" class="bg-blue-500 text-white p-4 rounded-lg shadow-md hover:bg-blue-600 text-center">
            <h3 class="text-lg font-semibold">Report Giornaliero</h3>
            <p class="text-sm opacity-90">Vendite di oggi</p>
        </button>

        <button id="monthly-btn" class="bg-green-500 text-white p-4 rounded-lg shadow-md hover:bg-green-600 text-center">
            <h3 class="text-lg font-semibold">Report Mensile</h3>
            <p class="text-sm opacity-90">Vendite del mese</p>
        </button>
    </div>

    <div id="report-content" class="bg-white rounded-lg shadow-md p-4 max-w-full overflow-x-hidden">
        <!-- Report content will be loaded here -->
    </div>
</div>

<script>
let currentReport = \'daily\';
const CURRENCY = (window.appSettings && window.appSettings.currency_symbol) ? window.appSettings.currency_symbol : \'€\';

function money(value) {
    return `${CURRENCY}${parseFloat(value || 0).toFixed(2)}`;
}

function percent(value) {
    return `${parseFloat(value || 0).toFixed(2)}%`;
}

document.addEventListener(\'DOMContentLoaded\', () => {
    loadDailyReport();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById(\'daily-btn\').addEventListener(\'click\', loadDailyReport);
    document.getElementById(\'monthly-btn\').addEventListener(\'click\', loadMonthlyReport);
}

async function loadDailyReport() {
    currentReport = \'daily\';
    updateButtons();

    try {
        const response = await fetch(\'/api.php/reports?type=daily\');
        const result = await response.json();
        if (result.success) {
            renderDailyReport(result.data);
        }
    } catch (error) {
        showToast(\'Errore caricamento report giornaliero\', \'error\');
    }
}

async function loadMonthlyReport() {
    currentReport = \'monthly\';
    updateButtons();

    try {
        const response = await fetch(\'/api.php/reports?type=monthly\');
        const result = await response.json();
        if (result.success) {
            renderMonthlyReport(result.data);
        }
    } catch (error) {
        showToast(\'Errore caricamento report mensile\', \'error\');
    }
}

function updateButtons() {
    const dailyBtn = document.getElementById(\'daily-btn\');
    const monthlyBtn = document.getElementById(\'monthly-btn\');

    if (currentReport === \'daily\') {
        dailyBtn.classList.add(\'bg-blue-600\');
        dailyBtn.classList.remove(\'bg-blue-500\');
        monthlyBtn.classList.remove(\'bg-green-600\');
        monthlyBtn.classList.add(\'bg-green-500\');
    } else {
        monthlyBtn.classList.add(\'bg-green-600\');
        monthlyBtn.classList.remove(\'bg-green-500\');
        dailyBtn.classList.remove(\'bg-blue-600\');
        dailyBtn.classList.add(\'bg-blue-500\');
    }
}

function renderDailyReport(data) {
    const container = document.getElementById(\'report-content\');
    container.innerHTML = `
        <h2 class="text-xl font-semibold mb-4">Report Giornaliero - ${new Date().toLocaleDateString()}</h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">${data.daily.sales_count}</div>
                <div class="text-sm text-blue-800">Vendite</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">${money(data.daily.total_revenue)}</div>
                <div class="text-sm text-green-800">Incasso Totale</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-red-600">${money(data.daily.total_cost)}</div>
                <div class="text-sm text-red-800">Costo Totale</div>
            </div>
            <div class="bg-emerald-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-emerald-600">${money(data.daily.total_profit)}</div>
                <div class="text-sm text-emerald-800">Guadagno</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">${percent(data.daily.margin_pct)}</div>
                <div class="text-sm text-purple-800">Margine %</div>
            </div>
        </div>

        <h3 class="text-lg font-semibold mb-3">Top 10 Prodotti</h3>
        <div class="space-y-2">
            ${data.top_products.map((p, i) => `
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md">
                    <div class="flex items-center space-x-3">
                        <span class="font-semibold text-gray-500 w-6">${i + 1}.</span>
                        <span class="font-medium">${p.name}</span>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold text-green-700">Ricavo: ${money(p.total_revenue)}</div>
                        <div class="text-sm text-red-600">Costo: ${money(p.total_cost)}</div>
                        <div class="text-sm text-emerald-700">Guadagno: ${money(p.total_profit)} (${percent(p.margin_pct)})</div>
                        <div class="text-sm text-gray-600">${p.total_qty} venduti</div>
                    </div>
                </div>
            `).join(\'\')}
        </div>

        <div class="mt-6">
            <button onclick="exportReport(\'daily\')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                Esporta CSV
            </button>
        </div>
    `;
}

function renderMonthlyReport(data) {
    const container = document.getElementById(\'report-content\');
    container.innerHTML = `
        <h2 class="text-xl font-semibold mb-4">Report Mensile - ${data.monthly.month}</h2>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">${data.monthly.sales_count}</div>
                <div class="text-sm text-blue-800">Vendite Totali</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">${money(data.monthly.total_revenue)}</div>
                <div class="text-sm text-green-800">Incasso Totale</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-red-600">${money(data.monthly.total_cost)}</div>
                <div class="text-sm text-red-800">Costo Totale</div>
            </div>
            <div class="bg-emerald-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-emerald-600">${money(data.monthly.total_profit)}</div>
                <div class="text-sm text-emerald-800">Guadagno</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">${percent(data.monthly.margin_pct)}</div>
                <div class="text-sm text-purple-800">Margine %</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">${money(data.monthly.best_day_revenue || 0)}</div>
                <div class="text-sm text-yellow-800">Miglior Giorno</div>
            </div>
        </div>

        <h3 class="text-lg font-semibold mb-3">Prodotti Più Venduti</h3>
        <div class="space-y-2">
            ${data.top_products.map((p, i) => `
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md">
                    <div class="flex items-center space-x-3">
                        <span class="font-semibold text-gray-500 w-6">${i + 1}.</span>
                        <span class="font-medium">${p.name}</span>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold text-green-700">Ricavo: ${money(p.total_revenue)}</div>
                        <div class="text-sm text-red-600">Costo: ${money(p.total_cost)}</div>
                        <div class="text-sm text-emerald-700">Guadagno: ${money(p.total_profit)} (${percent(p.margin_pct)})</div>
                        <div class="text-sm text-gray-600">${p.total_qty} venduti</div>
                    </div>
                </div>
            `).join(\'\')}
        </div>

        <h3 class="text-lg font-semibold mt-6 mb-3">Andamento Giornaliero (Mese)</h3>
        <div class="w-full max-w-full overflow-x-auto">
            <table class="w-full bg-white border border-gray-200 rounded-md table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left text-sm text-gray-700">
                        <th class="px-3 py-2 border-b">Data</th>
                        <th class="px-3 py-2 border-b">Vendite</th>
                        <th class="px-3 py-2 border-b">Ricavo</th>
                        <th class="px-3 py-2 border-b">Costo</th>
                        <th class="px-3 py-2 border-b">Guadagno</th>
                        <th class="px-3 py-2 border-b">Margine %</th>
                    </tr>
                </thead>
                <tbody>
                    ${(data.daily_breakdown || []).map(row => `
                        <tr class="text-sm border-b last:border-b-0">
                            <td class="px-3 py-2">${new Date(row.day).toLocaleDateString()}</td>
                            <td class="px-3 py-2">${row.sales_count}</td>
                            <td class="px-3 py-2 text-green-700">${money(row.total_revenue)}</td>
                            <td class="px-3 py-2 text-red-600">${money(row.total_cost)}</td>
                            <td class="px-3 py-2 text-emerald-700">${money(row.total_profit)}</td>
                            <td class="px-3 py-2">${percent(row.margin_pct)}</td>
                        </tr>
                    `).join(\'\') || `
                        <tr>
                            <td class="px-3 py-3 text-sm text-gray-500" colspan="6">Nessun dato disponibile per il mese corrente.</td>
                        </tr>
                    `}
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <button onclick="exportReport(\'monthly\')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                Esporta CSV
            </button>
        </div>
    `;
}

function exportReport(type) {
    // Simple CSV export - in a real app you might want to generate this server-side
    showToast(\'Funzione export non implementata\', \'info\');
}
</script>
';
include TEMPLATES_DIR . 'partials/layout.php';
?>