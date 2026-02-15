<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/bootstrap.php';

try {
    $pdo = getDB();

    // Leggi il file SQL e rimuovi le righe problematiche
    $sql = file_get_contents(__DIR__ . '/database.sql');
    $sql = preg_replace('/^--.*$/m', '', $sql); // Rimuovi commenti
    $sql = preg_replace('/CREATE DATABASE.*$/m', '', $sql);
    $sql = preg_replace('/USE.*$/m', '', $sql);
    $sql = preg_replace('/CREATE INDEX.*$/m', '', $sql); // Rimuovi indici per ora

    // Dividi in istruzioni separate
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            echo "Eseguendo: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }

    echo "Schema database creato con successo!\n";

} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
?>