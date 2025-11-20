<?php
$cota = $_GET['cota'] ?? 'IV-4659';
$ALEPH_SERVER = "83.146.133.42";
$ALEPH_PORT = "8991";

// Încercăm toate tipurile de căutare
$coduri = ['CZU', 'SIG', 'COT', 'LOC', 'WRD', 'BAR'];

echo "<h1>Test căutare Aleph: {$cota}</h1>";

foreach ($coduri as $cod) {
    $url = "http://{$ALEPH_SERVER}:{$ALEPH_PORT}/F?func=find-b&request=" . urlencode($cota) . "&find_code={$cod}&local_base=RAI01";
    
    echo "<h3>Încercare cu cod: {$cod}</h3>";
    echo "<a href='{$url}' target='_blank'>Deschide în Aleph</a><br><br>";
    
    $response = @file_get_contents($url);
    
    if ($response) {
        if (strpos($response, 'Niciun rezultat') !== false) {
            echo "<span style='color: red;'>❌ Fără rezultate</span><br><br>";
        } else {
            echo "<span style='color: green;'>✅ GĂSIT!</span><br>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre><br><br>";
        }
    } else {
        echo "<span style='color: red;'>Eroare conexiune</span><br><br>";
    }
}
?>
```

**Accesează:**
```
http://localhost/test_aleph_direct.php?cota=IV-4659