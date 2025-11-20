<?php
$ALEPH_SERVER = "83.146.133.42";
$ALEPH_PORT = "8991";
$ALEPH_BASE_URL = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/F";

$cota = 'IV-4659';

echo "<h1>🔍 Debug Pagina de Detalii - Carte: {$cota}</h1>";
echo "<hr>";

// Inițializare sesiune
$init_url = "{$ALEPH_BASE_URL}?func=file&file_name=find-b";
$session_response = file_get_contents($init_url);
preg_match('/\/F\/([A-Z0-9\-]+)\?/', $session_response, $matches);
$session_id = $matches[1];

// Căutare
$search_url = "{$ALEPH_BASE_URL}/{$session_id}?func=find-b&request=" . urlencode($cota) . "&find_code=LOC&adjacent=N&local_base=RAI01";
$search_response = file_get_contents($search_url);

// Extrage link
preg_match_all('/<A\s+HREF=([^>]*func=item-global[^>]*)>/i', $search_response, $all_links);

$detail_url = '';
foreach ($all_links[1] as $link) {
    if (strpos($link, 'sub_library=ACAD') !== false) {
        $detail_url = trim($link);
        break;
    }
}

if (empty($detail_url)) {
    $detail_url = trim($all_links[1][0]);
}

echo "<p><strong>URL detalii:</strong> <a href='{$detail_url}' target='_blank'>Deschide în browser</a></p>";
echo "<p><code>" . htmlspecialchars($detail_url) . "</code></p>";
echo "<hr>";

// Fetch detalii
$detail_html = file_get_contents($detail_url);

// Salvează pentru analiză
file_put_contents('debug_details_full.html', $detail_html);
echo "<p>✅ HTML salvat în: <code>debug_details_full.html</code></p>";
echo "<hr>";

// ==========================================
// EXTRAGERE DATE DIN FORMATUL ALEPH SPECIFIC
// ==========================================

$data = [
    'titlu' => '',
    'autor' => '',
    'autor_complet' => '',
    'isbn' => '',
    'anul' => '',
    'editie' => '',
    'sectiune' => ''
];

echo "<h2>🎯 Extragere date din formatul Aleph:</h2>";

// Pattern pentru formatul: "Author Nume, Prenume. Titlu / Autor Complet"
if (preg_match('/Author\s+([^.]+)\.\s+([^\/]+)\s*\/\s*(.+?)(?:<br>|$)/is', $detail_html, $matches)) {
    $data['autor'] = trim($matches[1]);
    $data['titlu'] = trim($matches[2]);
    $data['autor_complet'] = trim(strip_tags($matches[3]));
    
    echo "<p>✅ <strong>Autor:</strong> " . htmlspecialchars($data['autor']) . "</p>";
    echo "<p>✅ <strong>Titlu:</strong> " . htmlspecialchars($data['titlu']) . "</p>";
    echo "<p>✅ <strong>Autor complet:</strong> " . htmlspecialchars($data['autor_complet']) . "</p>";
} else {
    echo "<p style='color: red;'>❌ Nu s-a putut extrage autorul și titlul</p>";
}

// ISBN (dacă există)
if (preg_match('/ISBN[:\s]*([0-9\-Xx]+)/i', $detail_html, $matches)) {
    $data['isbn'] = trim($matches[1]);
    echo "<p>✅ <strong>ISBN:</strong> " . htmlspecialchars($data['isbn']) . "</p>";
}

// An publicare (poate fi în diferite formate)
if (preg_match('/\b(19|20)\d{2}\b/', $detail_html, $matches)) {
    $data['anul'] = $matches[0];
    echo "<p>✅ <strong>An:</strong> " . htmlspecialchars($data['anul']) . "</p>";
}

// Parse cu DOMDocument pentru date tabelară (colecție, cotă, etc.)
$dom = new DOMDocument();
@$dom->loadHTML(mb_convert_encoding($detail_html, 'HTML-ENTITIES', 'UTF-8'));
$tds = $dom->getElementsByTagName('td');

echo "<hr>";
echo "<h2>📋 Date suplimentare din tabel:</h2>";

$cota_gasita = '';
$colectie = '';
$biblioteca = '';

for ($i = 0; $i < $tds->length; $i++) {
    $td = $tds->item($i);
    $text = trim($td->textContent);
    
    // Cotă (format IV-XXXX)
    if (preg_match('/^IV-\d+$/', $text)) {
        $cota_gasita = $text;
        echo "<p>✅ <strong>Cotă:</strong> " . htmlspecialchars($text) . "</p>";
    }
    
    // Colecție
    if (stripos($text, 'Cărți depozit') !== false || stripos($text, 'depozit') !== false) {
        $colectie = $text;
        echo "<p>✅ <strong>Colecție:</strong> " . htmlspecialchars($text) . "</p>";
    }
    
    // Bibliotecă
    if (stripos($text, 'Biblioteca Academiei') !== false) {
        $biblioteca = $text;
        echo "<p>✅ <strong>Bibliotecă:</strong> " . htmlspecialchars($text) . "</p>";
    }
}

echo "<hr>";
echo "<h2>📊 Rezultat Final - Date Extrase:</h2>";
echo "<pre>";
print_r([
    'titlu' => $data['titlu'],
    'autor' => $data['autor'],
    'autor_complet' => $data['autor_complet'],
    'isbn' => $data['isbn'],
    'anul' => $data['anul'],
    'cota' => $cota_gasita,
    'colectie' => $colectie,
    'biblioteca' => $biblioteca
]);
echo "</pre>";

if (empty($data['titlu'])) {
    echo "<p style='color: red; font-size: 1.5em;'>❌ EXTRAGEREA A EȘUAT!</p>";
} else {
    echo "<p style='color: green; font-size: 1.5em;'>✅ DATE EXTRASE CU SUCCES!</p>";
}

echo "<hr>";
echo "<h2>🔍 Fragment HTML sursa (pentru debug):</h2>";
$pos = strpos($detail_html, 'Author');
if ($pos !== false) {
    $fragment = substr($detail_html, $pos, 500);
    echo "<textarea style='width:100%; height:150px; font-family: monospace;'>";
    echo htmlspecialchars($fragment);
    echo "</textarea>";
}
?>