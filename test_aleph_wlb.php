<?php
$cota = 'IV-4659';
$url = "http://65.176.121.45:8991/F?func=find-b&request=" . urlencode($cota) . "&find_code=WLB&local_base=RAI01";

echo "<h1>Test WLB: {$cota}</h1>";
echo "<a href='{$url}' target='_blank'>Deschide în Aleph</a><br><br>";

$response = file_get_contents($url);

if (strpos($response, 'Niciun rezultat') !== false) {
    echo "<span style='color: red;'>❌ Fără rezultate</span>";
} else {
    echo "<span style='color: green;'>✅ GĂSIT!</span>";
    
    // Extrage link-uri
    preg_match_all('/<a href="([^"]+)"[^>]*>\d+\.<\/a>/', $response, $matches);
    
    echo "<h3>Link-uri găsite: " . count($matches[1]) . "</h3>";
    
    foreach ($matches[1] as $i => $link) {
        echo "<br><strong>Rezultat " . ($i+1) . ":</strong><br>";
        echo "<a href='http://65.176.121.45:8991" . $link . "' target='_blank'>Vezi detalii</a>";
    }
}
?>