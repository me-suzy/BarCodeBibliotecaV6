<?php
$cota = 'IV-4659';
$ALEPH_SERVER = "83.146.133.42";
$ALEPH_PORT = "8991";
$ALEPH_BASE_URL = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/F";

echo "<h1>Test DEBUG WLB: {$cota}</h1>";

// PASUL 1: Inițializează sesiune
$init_url = "{$ALEPH_BASE_URL}?func=file&file_name=find-b";
$session_response = file_get_contents($init_url);

preg_match('/\/F\/([A-Z0-9\-]+)\?/', $session_response, $matches);
$session_id = isset($matches[1]) ? $matches[1] : '';

echo "<h3>Session ID: {$session_id}</h3>";

// PASUL 2: Căutare cu WLB
$search_url = "{$ALEPH_BASE_URL}/{$session_id}?func=find-b&request=" . urlencode($cota) . "&find_code=WLB&adjacent=N&local_base=RAI01";

echo "<p><a href='{$search_url}' target='_blank'>Deschide în Aleph</a></p>";

$search_response = file_get_contents($search_url);

// Salvează răspunsul pentru analiză
file_put_contents('debug_search_response.html', $search_response);

echo "<h3>Răspuns salvat în: debug_search_response.html</h3>";

// Verifică dacă sunt rezultate
if (strpos($search_response, 'Niciun rezultat') !== false || 
    strpos($search_response, 'No results') !== false) {
    echo "<span style='color: red; font-size: 1.5em;'>❌ Niciun rezultat găsit</span>";
} else {
    echo "<span style='color: green; font-size: 1.5em;'>✅ Rezultate găsite!</span>";
}

echo "<hr>";
echo "<h3>Căutare link-uri în răspuns:</h3>";

// Încercăm MULTIPLE regex-uri pentru link-uri
$patterns = [
    '/<a href="([^"]+)"[^>]*>\d+\.<\/a>/',  // Pattern original
    '/<a[^>]+href="([^"]+)"[^>]*>/',        // Orice link
    '/href="([^"]*func=item[^"]*)"/i',      // Link-uri func=item
    '/href="([^"]*doc_number[^"]*)"/i',     // Link-uri cu doc_number
];

foreach ($patterns as $i => $pattern) {
    echo "<h4>Pattern " . ($i+1) . ":</h4>";
    preg_match_all($pattern, $search_response, $pattern_matches);
    echo "Găsite: " . count($pattern_matches[1]) . " link-uri<br>";
    
    if (count($pattern_matches[1]) > 0) {
        echo "<ul>";
        foreach (array_slice($pattern_matches[1], 0, 3) as $link) {
            echo "<li>" . htmlspecialchars($link) . "</li>";
        }
        echo "</ul>";
    }
}

echo "<hr>";
echo "<h3>Primele 2000 caractere din răspuns:</h3>";
echo "<textarea style='width: 100%; height: 300px;'>";
echo htmlspecialchars(substr($search_response, 0, 2000));
echo "</textarea>";
?>