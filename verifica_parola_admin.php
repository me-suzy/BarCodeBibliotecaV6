<?php
/**
 * Endpoint pentru verificarea parolei admin
 * Verifică parola utilizatorului cu ID 1 din baza de date
 */

session_start();
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// Verifică dacă este request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mesaj' => 'Metodă nepermisă']);
    exit;
}

// Obține parola trimisă
$parola_trimisa = $_POST['parola'] ?? '';

if (empty($parola_trimisa)) {
    echo json_encode(['success' => false, 'mesaj' => 'Parolă lipsă']);
    exit;
}

try {
    // Obține parola hash-uită pentru utilizatorul cu ID 1
    $stmt = $pdo->prepare("SELECT password_hash FROM utilizatori WHERE id = 1 AND activ = TRUE");
    $stmt->execute();
    $utilizator = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$utilizator) {
        echo json_encode(['success' => false, 'mesaj' => 'Utilizatorul admin nu există sau este dezactivat']);
        exit;
    }
    
    // Verifică parola folosind password_verify()
    if (password_verify($parola_trimisa, $utilizator['password_hash'])) {
        // Parola corectă - salvează în sesiune că utilizatorul are acces admin
        $_SESSION['admin_access_verified'] = true;
        $_SESSION['admin_access_time'] = time();
        
        echo json_encode([
            'success' => true, 
            'mesaj' => 'Parolă corectă'
        ]);
    } else {
        // Parolă greșită
        echo json_encode([
            'success' => false, 
            'mesaj' => 'Parolă administrativă incorectă!'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Eroare verificare parolă admin: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'mesaj' => 'Eroare la verificarea parolei'
    ]);
}

