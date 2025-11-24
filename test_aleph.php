<?php
echo "<h3>Test conectivitate Aleph</h3>";

// Test 1: allow_url_fopen
echo "<p><strong>1. allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? '✅ Activat' : '❌ Dezactivat') . "</p>";

// Test 2: Încearcă să acceseze Aleph
$aleph_url = "http://65.176.121.45:8991/F";
echo "<p><strong>2. Test conectare Aleph:</strong></p>";

$context = stream_context_create([
    'http' => [
        'timeout' => 5
    ]
]);

$result = @file_get_contents($aleph_url, false, $context);

if ($result !== false) {
    echo "<p style='color: green;'>✅ Conexiune reușită! Lungime răspuns: " . strlen($result) . " bytes</p>";
} else {
    echo "<p style='color: red;'>❌ Conexiune eșuată!</p>";
    echo "<p><strong>Eroare:</strong> " . error_get_last()['message'] . "</p>";
}

// Test 3: Verifică cURL
echo "<p><strong>3. cURL disponibil:</strong> " . (function_exists('curl_init') ? '✅ Da' : '❌ Nu') . "</p>";

// Test 4: Încearcă cu cURL
if (function_exists('curl_init')) {
    $ch = curl_init($aleph_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $curl_result = curl_exec($ch);
    $curl_error = curl_error($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($curl_result !== false) {
        echo "<p style='color: green;'>✅ cURL funcționează! HTTP Code: {$curl_info['http_code']}</p>";
    } else {
        echo "<p style='color: red;'>❌ cURL eșuat: {$curl_error}</p>";
    }
}

// Test 5: Testează direct URL-ul în browser
echo "<hr>";
echo "<p><strong>5. Test manual în browser:</strong></p>";
echo "<p><a href='http://65.176.121.45:8991/F' target='_blank'>Deschide Aleph în browser →</a></p>";
echo "<p><small>Dacă se deschide în browser dar nu funcționează în PHP, problema e la configurația PHP.</small></p>";
?>