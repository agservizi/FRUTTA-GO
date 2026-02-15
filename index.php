<?php
// index.php nella root - Reindirizza a public/ o mostra un messaggio
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Location: public/');
exit;
?>