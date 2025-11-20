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

// Obține numărul total de cărți
$total_records = $pdo->query("SELECT COUNT(*) FROM carti")->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Obține cărțile pentru pagina curentă
$stmt = $pdo->prepare("
    SELECT
        id,
        cod_bare,
        titlu,
        autor,
        isbn,
        cota,
        raft,
        nivel,
        pozitie,
        locatie_completa,
        sectiune,
        data_adaugare
    FROM carti
    ORDER BY data_adaugare DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$carti = $stmt->fetchAll();

// Calculează pagina anterioară și următoare
$prev_page = $page > 1 ? $page - 1 : null;
$next_page = $page < $total_pages ? $page + 1 : null;

// Funcție pentru generarea link-urilor de paginare
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
                            <th>Dată adăugare</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($carti as $carte): ?>
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
                                <td><?php echo date('d.m.Y H:i', strtotime($carte['data_adaugare'])); ?></td>
                                <td><a href="editare_carte.php?id=<?php echo $carte['id']; ?>" class="action-btn">✏️ Modifica</a></td>
                            </tr>
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
                    // Afișează maxim 5 pagini în jurul paginii curente
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
                <div class="no-data">📭 Nu există cărți în bibliotecă</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
