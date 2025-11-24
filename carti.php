<?php
// carti.php - Lista tuturor cărților cu paginare
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'config.php';
require_once 'auth_check.php';

// Configurare paginare
$records_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Asigură că pagina e cel puțin 1
$offset = ($page - 1) * $records_per_page;

// Filtru pentru status
$filtru_status = isset($_GET['status']) ? $_GET['status'] : 'toate';
$valid_statuses = ['toate', 'disponibile', 'imprumutate', 'returnate_recent'];
if (!in_array($filtru_status, $valid_statuses)) {
    $filtru_status = 'toate';
}

// Construiește WHERE clause pentru filtru
$where_clause = '';
$join_clause = '';
$params = [];

if ($filtru_status === 'disponibile') {
    // Cărți care NU sunt împrumutate (nu au împrumuturi active)
    $where_clause = "WHERE NOT EXISTS (
        SELECT 1 FROM imprumuturi i 
        WHERE i.cod_carte = c.cod_bare 
        AND i.data_returnare IS NULL
    )";
} elseif ($filtru_status === 'imprumutate') {
    // Cărți care SUNT împrumutate (au împrumuturi active)
    $join_clause = "INNER JOIN imprumuturi i ON c.cod_bare = i.cod_carte";
    $where_clause = "WHERE i.data_returnare IS NULL";
} elseif ($filtru_status === 'returnate_recent') {
    // Cărți returnate în ultimele 30 de zile
    $join_clause = "INNER JOIN imprumuturi i ON c.cod_bare = i.cod_carte";
    $where_clause = "WHERE i.data_returnare IS NOT NULL 
        AND i.data_returnare >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Obține numărul total de cărți (cu filtru)
$count_query = "SELECT COUNT(DISTINCT c.id) FROM carti c $join_clause $where_clause";
$total_records = $pdo->query($count_query)->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Obține cărțile pentru pagina curentă (cu status împrumut)
$query_carti = "
    SELECT DISTINCT
        c.id,
        c.cod_bare,
        c.titlu,
        c.autor,
        c.isbn,
        c.cota,
        c.raft,
        c.nivel,
        c.pozitie,
        c.locatie_completa,
        c.sectiune,
        c.data_adaugare,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM imprumuturi i 
                WHERE i.cod_carte = c.cod_bare 
                AND i.data_returnare IS NULL
            ) THEN 'imprumutata'
            ELSE 'disponibila'
        END as status_carte,
        (SELECT MAX(i.data_returnare) FROM imprumuturi i WHERE i.cod_carte = c.cod_bare) as ultima_returnare
    FROM carti c
    $join_clause
    $where_clause
    ORDER BY c.data_adaugare DESC
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($query_carti);
$stmt->bindValue(1, $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$carti = $stmt->fetchAll();

// Calculează pagina anterioară și următoare
$prev_page = $page > 1 ? $page - 1 : null;
$next_page = $page < $total_pages ? $page + 1 : null;

// Funcție pentru generarea link-urilor de paginare
function generatePaginationLink($page_num, $current_page, $filtru_status) {
    $active_class = ($page_num == $current_page) ? 'active' : '';
    $status_param = $filtru_status !== 'toate' ? "&status=$filtru_status" : '';
    return "<a href=\"?page=$page_num$status_param\" class=\"$active_class\">$page_num</a>";
}

// Calculează statistici pentru fiecare filtru
$stats = [
    'toate' => $pdo->query("SELECT COUNT(*) FROM carti")->fetchColumn(),
    'disponibile' => $pdo->query("SELECT COUNT(*) FROM carti c WHERE NOT EXISTS (SELECT 1 FROM imprumuturi i WHERE i.cod_carte = c.cod_bare AND i.data_returnare IS NULL)")->fetchColumn(),
    'imprumutate' => $pdo->query("SELECT COUNT(DISTINCT cod_carte) FROM imprumuturi WHERE data_returnare IS NULL")->fetchColumn(),
    'returnate_recent' => $pdo->query("SELECT COUNT(DISTINCT cod_carte) FROM imprumuturi WHERE data_returnare IS NOT NULL AND data_returnare >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toate cărțile - Sistem Bibliotecă</title>
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

        tr:hover {
            background: #f8f9fa;
        }

        .location-info {
            font-size: 0.9em;
            color: #666;
            font-style: italic;
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

        .book-author {
            color: #666;
        }

        .app-footer {
            text-align: right;
            padding: 30px 40px;
            margin-top: 40px;
            background: transparent;
        }

        .app-footer p {
            display: inline-block;
            margin: 0;
            padding: 13px 26px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(13px);
            border-radius: 22px;
            color: white;
            font-weight: 400;
            font-size: 0.9em;
            box-shadow: 0 0 18px rgba(196, 181, 253, 0.15),
                        0 4px 16px rgba(0, 0, 0, 0.1),
                        inset 0 1px 1px rgba(255, 255, 255, 0.2);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            transition: all 0.45s ease;
            position: relative;
        }

        .app-footer p::before {
            content: '💡';
            margin-right: 10px;
            font-size: 1.15em;
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.6));
        }

        .app-footer p:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.08));
            box-shadow: 0 0 35px rgba(196, 181, 253, 0.3),
                        0 8px 24px rgba(0, 0, 0, 0.15),
                        inset 0 1px 1px rgba(255, 255, 255, 0.3);
            transform: translateY(-3px) scale(1.01);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .filters {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-disponibila {
            background: #d4edda;
            color: #155724;
        }

        .status-imprumutata {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Toate cărțile din bibliotecă</h1>
            <div class="header-buttons">
                <a href="index.php" class="home-btn">🏠 Acasă</a>
                <a href="index.php" class="back-btn">← Înapoi la scanare</a>
            </div>
        </div>

        <div class="stats">
            <h2>Total: <?php echo number_format($total_records); ?> cărți</h2>
            <p>Afișate <?php echo $records_per_page; ?> înregistrări pe pagină</p>
        </div>

        <!-- Filtre -->
        <div class="filters">
            <h3 style="margin-bottom: 15px; color: #667eea;">🔍 Filtrează după status:</h3>
            <div class="filter-buttons">
                <a href="?status=toate" class="filter-btn <?php echo $filtru_status === 'toate' ? 'active' : ''; ?>">
                    📚 Toate (<?php echo number_format($stats['toate']); ?>)
                </a>
                <a href="?status=disponibile" class="filter-btn <?php echo $filtru_status === 'disponibile' ? 'active' : ''; ?>">
                    ✅ Disponibile (<?php echo number_format($stats['disponibile']); ?>)
                </a>
                <a href="?status=imprumutate" class="filter-btn <?php echo $filtru_status === 'imprumutate' ? 'active' : ''; ?>">
                    📖 Împrumutate (<?php echo number_format($stats['imprumutate']); ?>)
                </a>
                <a href="?status=returnate_recent" class="filter-btn <?php echo $filtru_status === 'returnate_recent' ? 'active' : ''; ?>">
                    🔄 Returnate recent (<?php echo number_format($stats['returnate_recent']); ?>)
                </a>
                <a href="istoric_imprumuturi.php" class="filter-btn" style="background: #17a2b8; border-color: #17a2b8; color: white;">
                    📋 Istoric complet
                </a>
            </div>
        </div>

        <div class="content">
            <?php if (count($carti) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cod</th>
                            <th>Titlu</th>
                            <th>Autor</th>
                            <th>ISBN</th>
                            <th>Cota</th>
                            <th>Locație</th>
                            <th>Status</th>
                            <th>Dată adăugare</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($carti as $carte): 
                            $status = $carte['status_carte'] ?? 'disponibila';
                            $status_text = $status === 'imprumutata' ? 'Împrumutată' : 'Disponibilă';
                            $status_class = $status === 'imprumutata' ? 'status-imprumutata' : 'status-disponibila';
                        ?>
                            <tr>
                                <td><span class="book-code"><?php echo htmlspecialchars($carte['cod_bare']); ?></span></td>
                                <td><span class="book-title"><?php echo htmlspecialchars($carte['titlu']); ?></span></td>
                                <td><span class="book-author"><?php echo htmlspecialchars($carte['autor'] ?: '-'); ?></span></td>
                                <td><?php echo htmlspecialchars($carte['isbn'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($carte['cota'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($carte['locatie_completa']): ?>
                                        <span class="location-info"><?php echo htmlspecialchars($carte['locatie_completa']); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <?php if ($filtru_status === 'returnate_recent' && $carte['ultima_returnare']): ?>
                                        <br><small style="color: #666; font-size: 0.85em;">
                                            Returnată: <?php echo date('d.m.Y', strtotime($carte['ultima_returnare'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($carte['data_adaugare'])); ?></td>
                                <td><a href="editare_carte.php?id=<?php echo $carte['id']; ?>" class="action-btn">✏️ Modifica</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginare -->
                <div class="pagination">
                    <?php 
                    $status_param = $filtru_status !== 'toate' ? "&status=$filtru_status" : '';
                    ?>
                    <?php if ($prev_page): ?>
                        <a href="?page=<?php echo $prev_page; ?><?php echo $status_param; ?>">&laquo; Anterior</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; Anterior</span>
                    <?php endif; ?>

                    <?php
                    // Afișează maxim 5 pagini în jurul paginii curente
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo generatePaginationLink(1, $page, $filtru_status);
                        if ($start_page > 2) echo '<span>...</span>';
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        echo generatePaginationLink($i, $page, $filtru_status);
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span>...</span>';
                        echo generatePaginationLink($total_pages, $page, $filtru_status);
                    }
                    ?>

                    <?php if ($next_page): ?>
                        <a href="?page=<?php echo $next_page; ?><?php echo $status_param; ?>">Următor &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Următor &raquo;</span>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="no-data">📭 Nu există cărți în bibliotecă</div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="app-footer">
            <p>Dezvoltare web: Neculai Ioan Fantanaru</p>
        </div>
    </div>
</body>
</html>
