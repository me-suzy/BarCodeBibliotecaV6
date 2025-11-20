<?php
// config.php - Configurare conexiune bază de date

// Setează encoding-ul intern PHP la UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

$host = 'localhost';
$dbname = 'biblioteca';
$username = 'root';
$password = '';

try {
    // 🔥 IMPORTANT: Setează charset=utf8mb4 în DSN
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Forțează encoding UTF-8 pentru toată sesiunea MySQL
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET character_set_connection=utf8mb4");
    $pdo->exec("SET character_set_results=utf8mb4");
    $pdo->exec("SET collation_connection=utf8mb4_unicode_ci");
    
} catch(PDOException $e) {
    die("Eroare conexiune: " . $e->getMessage());
}
