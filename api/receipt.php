<?php
// api/receipt.php - Generazione ricevute

$method = $_SERVER['REQUEST_METHOD'];

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/bootstrap.php';

if (!isLoggedIn()) {
    errorResponse('Non autorizzato', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Metodo non supportato', 405);
}

$saleId = $_GET['sale_id'] ?? 0;
if (!$saleId) {
    errorResponse('ID vendita richiesto');
}

try {
    $storeId = getCurrentStoreId();
    // Recupera dati vendita
    $stmt = getDB()->prepare("
        SELECT s.*, u.name as user_name
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND s.store_id = ?
    ");
    $stmt->execute([$saleId, $storeId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        errorResponse('Vendita non trovata', 404);
    }

    // Recupera righe vendita
    $stmt = getDB()->prepare("
        SELECT si.*, p.name as product_name
        FROM sale_items si
        JOIN products p ON si.product_id = p.id AND p.store_id = si.store_id
        WHERE si.sale_id = ? AND si.store_id = ?
        ORDER BY si.id
    ");
    $stmt->execute([$saleId, $storeId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Genera HTML ricevuta
    $html = generateReceiptHTML($sale, $items);

    // Restituisci HTML
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;

} catch (Exception $e) {
    logError("Receipt generation error: " . $e->getMessage());
    errorResponse('Errore generazione ricevuta', 500);
}

function generateReceiptHTML($sale, $items) {
    $storeName = getAppSetting('store_name', APP_NAME);
    $currencySymbol = getAppSetting('currency_symbol', '€');
    $receiptFooter = getAppSetting('receipt_footer', 'Grazie per il vostro acquisto!');
    $date = date('d/m/Y H:i', strtotime($sale['created_at']));
    $total = number_format($sale['total'], 2, ',', '.');

    $html = '
<div class="receipt-container" style="font-family: monospace; font-size: 12px; max-width: 300px; margin: 0 auto; background: white; padding: 10px; border: 1px solid #ccc;">
    <div class="header" style="text-align: center; margin-bottom: 10px;">
        <h1 style="margin: 0; font-size: 16px;">' . h(strtoupper($storeName)) . '</h1>
        <p style="margin: 5px 0;">Ricevuta N. ' . $sale['id'] . '</p>
        <p style="margin: 5px 0;">Data: ' . $date . '</p>
        <p style="margin: 5px 0;">Operatore: ' . h($sale['user_name']) . '</p>
    </div>

    <div class="items">';

    foreach ($items as $item) {
        $qty = number_format($item['qty'], 2, ',', '');
        $price = number_format($item['unit_price'], 2, ',', '');
        $lineTotal = number_format($item['line_total'], 2, ',', '');
        $html .= '
        <div class="item" style="display: flex; justify-content: space-between; margin: 5px 0;">
            <span>' . h($item['product_name']) . ' (' . $qty . ')</span>
            <span>' . h($currencySymbol) . $lineTotal . '</span>
        </div>';
    }

    $html .= '
    </div>

    <div class="total" style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold;">
        <div class="item" style="display: flex; justify-content: space-between; margin: 5px 0;">
            <span>TOTALE</span>
            <span>' . h($currencySymbol) . $total . '</span>
        </div>
    </div>

    <div class="footer" style="text-align: center; margin-top: 10px; font-size: 10px;">
        <p style="margin: 0;">' . h($receiptFooter) . '</p>
    </div>
</div>';

    return $html;
}
?>