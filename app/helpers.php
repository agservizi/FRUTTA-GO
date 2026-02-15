<?php
// helpers.php - Funzioni helper

// Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Error response
function errorResponse($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

// Success response
function successResponse($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data) $response['data'] = $data;
    jsonResponse($response);
}

// Log error
function logError($message) {
    $logFile = STORAGE_DIR . 'logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Upload immagine a S3
function uploadImageToS3($file, $filename) {
    try {
        $s3 = getS3Client();
        $result = $s3->putObject([
            'Bucket' => S3_BUCKET,
            'Key' => $filename,
            'SourceFile' => $file['tmp_name'],
            'ACL' => 'public-read',
        ]);
        return $result['ObjectURL'];
    } catch (Exception $e) {
        logError("Upload S3 error: " . $e->getMessage());
        return false;
    }
}

// Ridimensiona immagine
function resizeImage($file, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) return false;

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];

    // Calcola nuove dimensioni
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);

    // Crea immagine
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            return false;
    }

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Salva temporaneamente
    $tempFile = tempnam(sys_get_temp_dir(), 'resized_');
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dstImage, $tempFile, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($dstImage, $tempFile, 9);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dstImage, $tempFile, 90);
            break;
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return $tempFile;
}

// Valida file immagine
function validateImageFile($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        return 'Tipo file non supportato. Usa JPG, PNG o WebP.';
    }

    if ($file['size'] > $maxSize) {
        return 'File troppo grande. Max 5MB.';
    }

    return true;
}
?>