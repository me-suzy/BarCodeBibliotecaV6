<?php
$html = file_get_contents('debug_aleph_search.html');

echo "<h1>Analiza HTML Aleph - Căutare link-uri</h1>";
echo "<hr>";

// Afișează primele 5000 caractere
echo "<h2>Primele 5000 caractere din HTML:</h2>";
echo "<textarea style='width: 100%; height: 300px;'>";
echo htmlspecialchars(substr($html, 0, 5000));
echo "</textarea>";

echo "<hr>";

// Caută toate link-urile
echo "<h2>Toate link-urile găsite în pagină:</h2>";
preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $html, $all_links);

echo "<p>Total link-uri: " . count($all_links[1]) . "</p>";

echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>#</th><th>Link (href)</th><th>Text link</th></tr>";

foreach ($all_links[1] as $i => $href) {
    $link_text = strip_tags($all_links[2][$i]);
    echo "<tr>";
    echo "<td>" . ($i + 1) . "</td>";
    echo "<td style='font-family: monospace; font-size: 0.9em;'>" . htmlspecialchars($href) . "</td>";
    echo "<td>" . htmlspecialchars(substr($link_text, 0, 100)) . "</td>";
    echo "</tr>";
    
    // Afișăm doar primele 20
    if ($i >= 19) {
        echo "<tr><td colspan='3'>... și altele</td></tr>";
        break;
    }
}

echo "</table>";

echo "<hr>";
echo "<h2>Caută pattern-uri specifice:</h2>";

// Pattern 1: func=item-global
preg_match_all('/href="([^"]*func=item-global[^"]*)"/i', $html, $item_links);
echo "<h3>Pattern: func=item-global</h3>";
echo "<p>Găsite: " . count($item_links[1]) . "</p>";
if (count($item_links[1]) > 0) {
    echo "<pre>" . htmlspecialchars($item_links[1][0]) . "</pre>";
}

// Pattern 2: func=full-set-set
preg_match_all('/href="([^"]*func=full-set-set[^"]*)"/i', $html, $full_links);
echo "<h3>Pattern: func=full-set-set</h3>";
echo "<p>Găsite: " . count($full_links[1]) . "</p>";
if (count($full_links[1]) > 0) {
    echo "<pre>" . htmlspecialchars($full_links[1][0]) . "</pre>";
}

// Pattern 3: Link-uri cu numere (1., 2., etc.)
preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>(\d+)\.<\/a>/i', $html, $numbered_links);
echo "<h3>Pattern: Link-uri numerotate (1., 2., etc.)</h3>";
echo "<p>Găsite: " . count($numbered_links[1]) . "</p>";
if (count($numbered_links[1]) > 0) {
    foreach ($numbered_links[1] as $i => $link) {
        echo "<p><strong>" . $numbered_links[2][$i] . ".</strong> " . htmlspecialchars($link) . "</p>";
        if ($i >= 2) break;
    }
}

// Pattern 4: Titluri de cărți (link-uri către detalii)
preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>[^<]*<\/a>/i', $html, $title_links);
echo "<h3>Posibile link-uri către titluri:</h3>";
echo "<p>Total: " . count($title_links[1]) . "</p>";

echo "<hr>";
echo "<h2>HTML complet (raw):</h2>";
echo "<textarea style='width: 100%; height: 400px;'>";
echo htmlspecialchars($html);
echo "</textarea>";
?>
