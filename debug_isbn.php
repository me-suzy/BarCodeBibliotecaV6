<?php
$ALEPH_SERVER = "83.146.133.42";
$ALEPH_PORT = "8991";
$ALEPH_BASE_URL = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/F";

$cota = 'IV-4659';

echo "<h1>🔍 Debug ISBN pentru: {$cota}</h1>";
echo "<hr>";

// 1. Inițializare sesiune
$init_url = "{$ALEPH_BASE_URL}?func=file&file_name=find-b";
$session_response = file_get_contents($init_url);
preg_match('/\/F\/([A-Z0-9\-]+)\?/', $session_response, $matches);
$session_id = $matches[1];

// 2. Căutare
$search_url = "{$ALEPH_BASE_URL}/{$session_id}?func=find-b&request=" . urlencode($cota) . "&find_code=LOC&adjacent=N&local_base=RAI01";
$search_response = file_get_contents($search_url);

// 3. Extrage link către pagina COMPLETĂ (func=full-set-set)
preg_match('/<A\s+HREF=([^>]*func=full-set-set[^>]*)>/i', $search_response, $full_match);
$full_detail_url = isset($full_match[1]) ? trim($full_match[1]) : '';

if (empty($full_detail_url)) {
    echo "<p style='color: red;'>❌ Nu s-a găsit link către pagina completă!</p>";
    exit;
}

echo "<p><strong>✅ URL pagina completă:</strong></p>";
echo "<p><a href='{$full_detail_url}' target='_blank'>Deschide în browser</a></p>";
echo "<p><code>" . htmlspecialchars($full_detail_url) . "</code></p>";
echo "<hr>";

// 4. Fetch pagina completă
$full_html = file_get_contents($full_detail_url);

// Salvează HTML
file_put_contents('debug_isbn_full.html', $full_html);
echo "<p>✅ HTML salvat în: <code>debug_isbn_full.html</code></p>";
echo "<hr>";

// 5. Caută ISBN în HTML brut
echo "<h2>🔍 Căutare ISBN în HTML brut:</h2>";
if (preg_match('/ISBN.*?(\d[\d\-Xx]+)/is', $full_html, $isbn_raw)) {
    echo "<p>✅ <strong>ISBN găsit (raw):</strong> " . htmlspecialchars($isbn_raw[1]) . "</p>";
    echo "<p><strong>Context:</strong> <code>" . htmlspecialchars($isbn_raw[0]) . "</code></p>";
} else {
    echo "<p style='color: red;'>❌ ISBN nu a fost găsit în HTML brut</p>";
}

// Verifică dacă există textul "978-973-1888-42-2" undeva
if (strpos($full_html, '978-973-1888-42-2') !== false) {
    echo "<p>✅ String-ul '978-973-1888-42-2' EXISTĂ în HTML!</p>";
    $pos = strpos($full_html, '978-973-1888-42-2');
    $fragment = substr($full_html, max(0, $pos - 200), 400);
    echo "<h3>Fragment HTML în jurul ISBN-ului:</h3>";
    echo "<textarea style='width:100%; height:150px;'>" . htmlspecialchars($fragment) . "</textarea>";
} else {
    echo "<p style='color: red;'>❌ String-ul '978-973-1888-42-2' NU există în HTML</p>";
}

echo "<hr>";

// 6. Parse cu DOMDocument și afișează TOATE TD-urile
echo "<h2>📋 Toate TD-urile din pagina completă:</h2>";

$dom = new DOMDocument();
@$dom->loadHTML(mb_convert_encoding($full_html, 'HTML-ENTITIES', 'UTF-8'));
$tds = $dom->getElementsByTagName('td');

echo "<p><strong>Total TD-uri găsite:</strong> {$tds->length}</p>";

echo "<table border='1' cellpadding='8' style='width:100%; border-collapse: collapse; font-size: 14px;'>";
echo "<tr style='background: #333; color: white;'>";
echo "<th>#</th><th>Class</th><th>Text Content</th><th>Lungime</th></tr>";

for ($i = 0; $i < $tds->length && $i < 100; $i++) {
    $td = $tds->item($i);
    $text = trim($td->textContent);
    $class = $td->getAttribute('class');
    
    // Evidențiază rândurile cu ISBN
    $highlight = '';
    if (stripos($text, 'isbn') !== false) {
        $highlight = 'background: #4caf50; color: white; font-weight: bold;';
    } else if (stripos($text, '978') !== false || stripos($text, '979') !== false) {
        $highlight = 'background: #ff9800; color: white; font-weight: bold;';
    } else if (stripos($text, 'autor') !== false) {
        $highlight = 'background: #2196f3; color: white;';
    } else if (stripos($text, 'titlu') !== false) {
        $highlight = 'background: #9c27b0; color: white;';
    }
    
    $text_display = htmlspecialchars(substr($text, 0, 150));
    if (strlen($text) > 150) $text_display .= '...';
    
    echo "<tr style='{$highlight}'>";
    echo "<td>{$i}</td>";
    echo "<td>" . htmlspecialchars($class) . "</td>";
    echo "<td>{$text_display}</td>";
    echo "<td>" . strlen($text) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";

// 7. Metodă alternativă: caută pattern-uri specifice
echo "<h2>🎯 Testare metode de extragere ISBN:</h2>";

// Metodă 1: Regex direct în HTML
if (preg_match('/ISBN[:\s]*([0-9\-Xx]{10,17})/i', $full_html, $m1)) {
    echo "<p>✅ <strong>Metodă 1 (regex HTML):</strong> " . htmlspecialchars($m1[1]) . "</p>";
} else {
    echo "<p>❌ Metodă 1 (regex HTML): Nu a găsit</p>";
}

// Metodă 2: Caută direct pattern ISBN-13
if (preg_match('/978[\-\s]?[\d\-]{10,}/', $full_html, $m2)) {
    echo "<p>✅ <strong>Metodă 2 (pattern 978):</strong> " . htmlspecialchars($m2[0]) . "</p>";
} else {
    echo "<p>❌ Metodă 2 (pattern 978): Nu a găsit</p>";
}

// Metodă 3: Parse etichetă-valoare din TD-uri
echo "<p><strong>Metodă 3 (parsing TD-uri structurate):</strong></p>";
$found_isbn = false;
for ($i = 0; $i < $tds->length - 1; $i++) {
    $label = trim($tds->item($i)->textContent);
    $value = trim($tds->item($i + 1)->textContent);
    
    if (stripos($label, 'ISBN') !== false) {
        echo "<p>✅ Găsit la poziția {$i}: <strong>Label:</strong> \"" . htmlspecialchars($label) . "\" → <strong>Value:</strong> \"" . htmlspecialchars($value) . "\"</p>";
        $found_isbn = true;
    }
}

if (!$found_isbn) {
    echo "<p>❌ Nu s-a găsit pereche ISBN în TD-uri</p>";
}

echo "<hr>";

// 8. Afișează primele 50 de linii din HTML pentru analiză manuală
echo "<h2>📄 Primele linii din HTML (pentru analiză manuală):</h2>";
$lines = explode("\n", $full_html);
echo "<pre style='background: #f5f5f5; padding: 15px; overflow-x: auto; max-height: 400px;'>";
for ($i = 0; $i < min(100, count($lines)); $i++) {
    $line = htmlspecialchars($lines[$i]);
    // Evidențiază linia cu ISBN
    if (stripos($line, 'isbn') !== false || stripos($line, '978') !== false) {
        echo "<span style='background: yellow;'>{$line}</span>\n";
    } else {
        echo "{$line}\n";
    }
}
echo "</pre>";

?>
