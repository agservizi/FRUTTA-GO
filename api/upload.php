<?php
// api/upload.php - Upload immagini prodotti

$method = $_SERVER['REQUEST_METHOD'];

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/bootstrap.php';

if (!isLoggedIn()) {
    errorResponse('Non autorizzato', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Metodo non supportato', 405);
}

if (!isset($_FILES['image'])) {
    errorResponse('Nessuna immagine fornita');
}

$file = $_FILES['image'];
$productId = $_POST['product_id'] ?? 0;
$storeId = getCurrentStoreId();

// Valida file
$validation = validateImageFile($file);
if ($validation !== true) {
    errorResponse($validation);
}

try {
    // Genera nome file unico
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . $productId . '_' . time() . '.' . $extension;

    // Ridimensiona immagine (thumb e full)
    $resizedImage = resizeImage($file, 800, 800); // Full size
    if (!$resizedImage) {
        errorResponse('Errore nell\'elaborazione immagine');
    }

    // Upload su S3
    $imageUrl = uploadImageToS3([
        'tmp_name' => $resizedImage,
        'name' => $filename,
        'type' => $file['type']
    ], $filename);

    if (!$imageUrl) {
        errorResponse('Errore upload immagine');
    }

    // Aggiorna database
    $stmt = getDB()->prepare("UPDATE products SET image_url = ? WHERE id = ? AND store_id = ?");
    $stmt->execute([$imageUrl, $productId, $storeId]);

    // Pulisci file temporaneo
    unlink($resizedImage);

    successResponse(['image_url' => $imageUrl], 'Immagine caricata');

} catch (Exception $e) {
    logError("Upload error: " . $e->getMessage());
    errorResponse('Errore interno', 500);
}
?>