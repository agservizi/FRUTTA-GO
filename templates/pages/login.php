<?php
$current_page = 'login';
$storeName = h(getAppSetting('store_name', APP_NAME));
$content = '
<div class="min-h-screen flex items-center justify-center bg-green-50 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-green-600">' . $storeName . '</h1>
            <p class="text-gray-600 mt-2">Accedi al gestionale</p>
        </div>

        <form id="login-form" class="space-y-6">
            <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" required
                       class="mt-1 block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 text-lg">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 text-lg">
            </div>

            <button type="submit"
                    class="w-full bg-green-600 text-white py-4 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 text-lg font-medium">
                Accedi
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Username: admin, Password: admin123
            </p>
        </div>
    </div>
</div>
';
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Rimuovi il token CSRF dai dati da inviare
        const data = Object.fromEntries(formData);
        delete data.csrf_token;

        try {
            const response = await fetch('/api.php/auth', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                window.location.href = '/dashboard';
            } else {
                showToast(result.error || 'Errore login', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
});
</script>

<?php
include TEMPLATES_DIR . 'partials/layout.php';
?>