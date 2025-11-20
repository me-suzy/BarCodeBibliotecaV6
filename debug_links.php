<?php
$ALEPH_SERVER = "83.146.133.42";
$ALEPH_PORT = "8991";
$ALEPH_BASE_URL = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/F";

$cota = 'IV-4659';

echo "<h1>Debug Link-uri Aleph pentru: {$cota}</h1>";
echo "<hr>";

// Inițializare sesiune
$init_url = "{$ALEPH_BASE_URL}?func=file&file_name=find-b";
$session_response = file_get_contents($init_url);
preg_match('/\/F\/([A-Z0-9\-]+)\?/', $session_response, $matches);
$session_id = $matches[1];

echo "<p><strong>Session ID:</strong> {$session_id}</p>";

// Căutare
$search_url = "{$ALEPH_BASE_URL}/{$session_id}?func=find-b&request=" . urlencode($cota) . "&find_code=LOC&adjacent=N&local_base=RAI01";
$search_response = file_get_contents($search_url);

echo "<p><strong>URL căutare:</strong> <a href='{$search_url}' target='_blank'>Vezi în Aleph</a></p>";
echo "<hr>";

// Salvează pentru analiză
file_put_contents('debug_full.html', $search_response);
echo "<p>✅ HTML salvat în: <code>debug_full.html</code></p>";

// Testează MULTIPLE pattern-uri
echo "<h2>🔍 Testare Pattern-uri Regex:</h2>";

$patterns = [
    'Pattern 1 (orice A HREF)' => '/<A\s+HREF=([^\s>]+)>/i',
    'Pattern 2 (cu spații la final)' => '/<A\s+HREF=([^\s]+)\s*>/i',
    'Pattern 3 (func=item-global)' => '/<A\s+HREF=([^>]*func=item-global[^>]*)>/i',
    'Pattern 4 (cu ACAD)' => '/<A\s+HREF=([^>]*func=item-global[^>]*sub_library=ACAD[^>]*)>/i',
    'Pattern 5 (case insensitive cu spații)' => '/<a\s+href=([^>\s]+)/i',
];

foreach ($patterns as $name => $pattern) {
    echo "<h3>{$name}</h3>";
    preg_match_all($pattern, $search_response, $matches);
    
    echo "<p><strong>Găsite:</strong> " . count($matches[1]) . " link-uri</p>";
    
    if (count($matches[1]) > 0) {
        echo "<ul>";
        foreach (array_slice($matches[1], 0, 5) as $i => $link) {
            $link_clean = htmlspecialchars(trim($link));
            
            // Verifică dacă conține func=item-global
            $has_item = strpos($link, 'func=item-global') !== false ? '✅ item-global' : '❌';
            $has_acad = strpos($link, 'sub_library=ACAD') !== false ? '✅ ACAD' : '❌';
            
            echo "<li>{$has_item} {$has_acad}<br><code>{$link_clean}</code></li>";
        }
        echo "</ul>";
    }
    echo "<hr>";
}

// Caută MANUAL în HTML după "func=item-global"
echo "<h2>📋 Căutare Manuală în HTML:</h2>";

$pos = strpos($search_response, 'func=item-global');
if ($pos !== false) {
    echo "<p>✅ Găsit 'func=item-global' la poziția: {$pos}</p>";
    
    // Extrage 500 caractere în jurul acestei poziții
    $start = max(0, $pos - 200);
    $fragment = substr($search_response, $start, 500);
    
    echo "<h3>Fragment HTML:</h3>";
    echo "<textarea style='width:100%; height:200px;'>";
    echo htmlspecialchars($fragment);
    echo "</textarea>";
} else {
    echo "<p>❌ NU s-a găsit 'func=item-global' în HTML!</p>";
}

// Caută după "Biblioteca Academiei"
echo "<h2>🏛️ Căutare după 'Biblioteca Academiei':</h2>";

$pos2 = strpos($search_response, 'Biblioteca Academiei');
if ($pos2 !== false) {
    echo "<p>✅ Găsit la poziția: {$pos2}</p>";
    
    $start2 = max(0, $pos2 - 300);
    $fragment2 = substr($search_response, $start2, 600);
    
    echo "<h3>Fragment HTML:</h3>";
    echo "<textarea style='width:100%; height:200px;'>";
    echo htmlspecialchars($fragment2);
    echo "</textarea>";
} else {
    echo "<p>❌ NU s-a găsit 'Biblioteca Academiei' în HTML!</p>";
}
?>
