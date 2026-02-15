<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $appSettings = getAppSettings();
    $storeName = $appSettings['store_name'] ?? APP_NAME;
    ?>
    <title><?php echo h($storeName); ?></title>
    <link href="http://localhost:3001/css/main.css" rel="stylesheet">
    <script src="http://localhost:3001/js/main.js" defer></script>
    <script>
        window.appSettings = <?php echo json_encode([
            'store_name' => $storeName,
            'currency_symbol' => $appSettings['currency_symbol'] ?? '€',
            'vat_rate' => $appSettings['vat_rate'] ?? '4',
            'low_stock_threshold' => $appSettings['low_stock_threshold'] ?? '5',
            'receipt_footer' => $appSettings['receipt_footer'] ?? ''
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
</head>
<?php $bodyClasses = 'bg-gray-50 text-gray-900 overflow-x-hidden'; ?>
<?php if (!empty($current_page) && $current_page === 'inventory') { $bodyClasses .= ' inventory-no-scrollbar'; } ?>
<body class="<?php echo h($bodyClasses); ?>">
    <?php if (isLoggedIn()): ?>
    <header id="main-header" class="bg-transparent shadow-sm fixed top-0 left-0 right-0 z-40 px-4 py-0 flex justify-between items-center transition-colors duration-300">
        <img src="/assets/img/logo-fruttago.png" alt="Frutta Go" class="h-32">
        <button id="logout-btn" class="text-gray-600 hover:text-gray-900 p-2">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
            </svg>
        </button>
    </header>
    <nav class="bg-green-600 text-white fixed bottom-0 left-0 right-0 z-50">
        <div class="flex justify-around py-2">
            <a href="/dashboard" class="flex flex-col items-center p-2 <?php echo $current_page === 'dashboard' ? 'text-green-200' : ''; ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="/sale" class="flex flex-col items-center p-2 <?php echo $current_page === 'sale' ? 'text-green-200' : ''; ?>">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                </svg>
                <span class="text-xs mt-1">Vendi</span>
            </a>
            <a href="/inventory" class="flex flex-col items-center p-2 <?php echo $current_page === 'inventory' ? 'text-green-200' : ''; ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h.75M3.75 12h.75m-.75 5.25h.75M6.75 6.75h.75m-.75 5.25h.75m-.75 5.25h.75M9.75 6.75h.75m-.75 5.25h.75m-.75 5.25h.75M12.75 6.75h.75m-.75 5.25h.75M15.75 12h.75m-.75 5.25h.75M17.25 6.75h.75m-.75 5.25h.75m-.75 5.25h.75M19.5 6.75h.75m-.75 5.25h.75m-.75 5.25h.75"/>
                </svg>
                <span class="text-xs mt-1">Magazzino</span>
            </a>
            <a href="/purchases" class="flex flex-col items-center p-2 <?php echo $current_page === 'purchases' ? 'text-green-200' : ''; ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
                <span class="text-xs mt-1">Acquisti</span>
            </a>
            <a href="/reports" class="flex flex-col items-center p-2 <?php echo $current_page === 'reports' ? 'text-green-200' : ''; ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                <span class="text-xs mt-1">Report</span>
            </a>
        </div>
    </nav>
    <?php endif; ?>

    <main class="pt-36 pb-16 min-h-screen">
        <?php echo $content; ?>
    </main>

    <!-- Toast container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50"></div>

    <!-- Modal container -->
    <div id="modal-container"></div>

    <?php if (!empty($page_script)): ?>
        <?php echo $page_script; ?>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', async () => {
                    try {
                        const response = await fetch('/api.php/auth', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'logout' })
                        });
                        const result = await response.json();
                        if (result.success) {
                            window.location.href = '/login';
                        } else {
                            showToast('Errore logout', 'error');
                        }
                    } catch (error) {
                        showToast('Errore di connessione', 'error');
                    }
                });
            }
        });
    </script>
</body>
</html>