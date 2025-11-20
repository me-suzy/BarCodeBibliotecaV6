<?php
// imprumuturi.php - Lista împrumuturilor active cu paginare și grupare pe cititori
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'config.php';
require_once 'auth_check.php';

// Configurare paginare
$records_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $records_per_page;

// Obține numărul total de împrumuturi active (doar cele nerețurnate)
$total_records = $pdo->query("SELECT COUNT(*) FROM imprumuturi WHERE data_returnare IS NULL")->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Obține împrumuturile active GRUPATE pe cititori (doar cele nerețurnate)
$stmt = $pdo->prepare("
    SELECT
        i.id,
        i.cod_cititor,
        i.cod_carte,
        i.data_imprumut,
        c.titlu,
        c.autor,
        c.locatie_completa,
        cit.nume,
        cit.prenume,
        cit.telefon,
        cit.email,
        DATEDIFF(NOW(), i.data_imprumut) as zile_imprumut
    FROM imprumuturi i
    JOIN carti c ON i.cod_carte = c.cod_bare
    JOIN cititori cit ON i.cod_cititor = cit.cod_bare
    WHERE i.data_returnare IS NULL
    ORDER BY cit.nume, cit.prenume, i.data_imprumut DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$imprumuturi = $stmt->fetchAll();

// Grupează împrumuturile pe cititori
$imprumuturi_grupate = [];
foreach ($imprumuturi as $imp) {
    $cod_cititor = $imp['cod_cititor'];
    if (!isset($imprumuturi_grupate[$cod_cititor])) {
        $imprumuturi_grupate[$cod_cititor] = [
            'cititor' => [
                'cod_bare' => $imp['cod_cititor'],
                'nume' => $imp['nume'],
                'prenume' => $imp['prenume'],
                'telefon' => $imp['telefon'],
                'email' => $imp['email']
            ],
            'carti' => []
        ];
    }
    $imprumuturi_grupate[$cod_cititor]['carti'][] = $imp;
}

$prev_page = $page > 1 ? $page - 1 : null;
$next_page = $page < $total_pages ? $page + 1 : null;

function generatePaginationLink($page_num, $current_page) {
    $active_class = ($page_num == $current_page) ? 'active' : '';
    return "<a href=\"?page=$page_num\" class=\"$active_class\">$page_num</a>";
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Împrumuturi active - Sistem Bibliotecă</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 2em;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .home-btn, .back-btn {
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            font-weight: 600;
        }

        .home-btn {
            background: #28a745;
        }

        .home-btn:hover {
            background: #218838;
        }

        .back-btn {
            background: #667eea;
        }

        .back-btn:hover {
            background: #764ba2;
        }

        .stats {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .stats h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        /* Stiluri pentru gruparea cititorilor */
        .cititor-group {
            border-top: 3px solid #667eea;
        }

        .cititor-row {
            background: #f0f4ff;
            font-weight: 600;
        }

        .cititor-info {
            vertical-align: top;
            background: #f0f4ff;
            border-right: 2px solid #667eea;
        }

        .carte-row {
            background: white;
        }

        .carte-row:hover {
            background: #f8f9fa;
        }

        .empty-cell {
            background: #f0f4ff;
            border-right: 2px solid #667eea;
        }

        .action-btn {
            padding: 6px 12px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background 0.3s;
        }

        .action-btn:hover {
            background: #218838;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #667eea;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.2em;
        }

        .book-code {
            font-weight: bold;
            color: #667eea;
        }

        .book-title {
            font-weight: 600;
            color: #333;
        }

        .reader-name {
            font-weight: 600;
            color: #28a745;
            font-size: 1.1em;
        }

        .reader-code {
            color: #667eea;
            font-size: 0.9em;
            font-weight: 600;
        }

        .location-info {
            font-size: 0.9em;
            color: #666;
            font-style: italic;
        }

        .contact-info {
            color: #666;
            font-size: 0.95em;
        }

        .contact-info a {
            color: #667eea;
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .loan-date {
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📖 Împrumuturi active</h1>
            <div class="header-buttons">
                <a href="index.php" class="home-btn">🏠 Acasă</a>
                <a href="index.php" class="back-btn">← Înapoi la scanare</a>
            </div>
        </div>

        <div class="stats">
            <h2>Total: <?php echo number_format($total_records); ?> împrumuturi active</h2>
            <p>Afișate <?php echo $records_per_page; ?> înregistrări pe pagină</p>
        </div>

        <div class="content">
            <?php if (count($imprumuturi_grupate) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cititor</th>
                            <th>Contact</th>
                            <th>Carte</th>
                            <th>Autor</th>
                            <th>Locație</th>
                            <th>Data împrumut</th>
                            <th>Zile</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imprumuturi_grupate as $cod_cititor => $data): ?>
                            <?php $cititor = $data['cititor']; ?>
                            <?php $carti = $data['carti']; ?>
                            <?php $nr_carti = count($carti); ?>
                            
                            <?php foreach ($carti as $index => $carte): ?>
                                <tr class="<?php echo $index === 0 ? 'cititor-group' : ''; ?>">
                                    <?php if ($index === 0): ?>
                                        <!-- Informații cititor - doar pe primul rând -->
                                        <td class="cititor-info" rowspan="<?php echo $nr_carti; ?>">
                                            <div class="reader-name">
                                                <?php echo htmlspecialchars($cititor['nume'] . ' ' . $cititor['prenume']); ?>
                                            </div>
                                            <div class="reader-code"><?php echo htmlspecialchars($cititor['cod_bare']); ?></div>
                                            <div style="margin-top: 8px; font-weight: 600; color: #667eea; font-size: 1.1em;">
                                                📚 <?php echo $nr_carti; ?> <?php echo $nr_carti == 1 ? 'Carte' : 'Cărți'; ?>
                                            </div>
                                        </td>
                                        <td class="cititor-info contact-info" rowspan="<?php echo $nr_carti; ?>">
                                            <?php if ($cititor['telefon']): ?>
                                                <a href="tel:<?php echo htmlspecialchars($cititor['telefon']); ?>">
                                                    📞 <?php echo htmlspecialchars($cititor['telefon']); ?>
                                                </a><br>
                                            <?php endif; ?>
                                            <?php if ($cititor['email']): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($cititor['email']); ?>">
                                                    ✉️ <?php echo htmlspecialchars($cititor['email']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <!-- Informații carte -->
                                    <td class="carte-row">
                                        <div class="book-title"><?php echo htmlspecialchars($carte['titlu']); ?></div>
                                        <div class="book-code"><?php echo htmlspecialchars($carte['cod_carte']); ?></div>
                                    </td>
                                    <td class="carte-row"><?php echo htmlspecialchars($carte['autor'] ?: '-'); ?></td>
                                    <td class="carte-row">
                                        <?php if ($carte['locatie_completa']): ?>
                                            <span class="location-info"><?php echo htmlspecialchars($carte['locatie_completa']); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="carte-row">
                                        <div><?php echo date('d.m.Y', strtotime($carte['data_imprumut'])); ?></div>
                                        <div class="loan-date"><?php echo date('H:i', strtotime($carte['data_imprumut'])); ?></div>
                                    </td>
                                    <td class="carte-row"><?php echo $carte['zile_imprumut']; ?> zile</td>
                                    <td class="carte-row">
                                        <?php
                                        if ($carte['zile_imprumut'] > 30) {
                                            echo '<span class="badge badge-danger">Întârziere!</span>';
                                        } elseif ($carte['zile_imprumut'] > 14) {
                                            echo '<span class="badge badge-warning">Atenție</span>';
                                        } else {
                                            echo '<span class="badge badge-success">OK</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="carte-row">
                                        <a href="editare_imprumut.php?id=<?php echo $carte['id']; ?>" class="action-btn">✏️ Modifică</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginare -->
                <div class="pagination">
                    <?php if ($prev_page): ?>
                        <a href="?page=<?php echo $prev_page; ?>">&laquo; Anterior</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; Anterior</span>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo generatePaginationLink(1, $page);
                        if ($start_page > 2) echo '<span>...</span>';
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        echo generatePaginationLink($i, $page);
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span>...</span>';
                        echo generatePaginationLink($total_pages, $page);
                    }
                    ?>

                    <?php if ($next_page): ?>
                        <a href="?page=<?php echo $next_page; ?>">Următor &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Următor &raquo;</span>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="no-data">🔭 Nu există împrumuturi active</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>