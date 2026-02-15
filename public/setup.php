<?php
// setup.php - Setup iniziale dell'applicazione
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crea database se non esiste
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);

    // Esegui schema
    $sql = file_get_contents(__DIR__ . '/../database.sql');
    $pdo->exec($sql);

    echo "Setup completato! Database creato e popolato.";
    echo "<br><a href='index.php'>Vai all'app</a>";
} catch (PDOException $e) {
    die("Errore setup: " . $e->getMessage());
}
?>