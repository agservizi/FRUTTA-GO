<?php
// index.php nella root - bootstrap resiliente per deploy shared hosting

if (file_exists(__DIR__ . '/public/index.php')) {
	require __DIR__ . '/public/index.php';
	exit;
}

http_response_code(500);
echo 'Entry point non trovato';
?>