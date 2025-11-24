<?php
// cititori.php - Lista tuturor cititorilor cu paginare
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'config.php';
require_once 'auth_check.php';

// Configurare paginare
$records_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Asigură că pagina e cel puțin 1
$offset = ($page - 1) * $records_per_page;

// Obține numărul total de cititori
$total_records = $pdo->query("SELECT COUNT(*) FROM cititori")->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Obține cititorii pentru pagina curentă
$stmt = $pdo->prepare("
    SELECT
        id,
        cod_bare,
        nume,
        prenume,
        telefon,
        email,
        data_inregistrare
    FROM cititori
    ORDER BY data_inregistrare DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$cititori = $stmt->fetchAll();

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
    <title>T toți cititorii - Sistem Bibliotecă</title>
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

        .reader-code {
            font-weight: bold;
            color: #667eea;
        }

        .reader-name {
            font-weight: 600;
            color: #333;
        }

        .contact-info {
            color: #666;
        }

        .contact-info a {
            color: #667eea;
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Toți cititorii înregistrați</h1>
		<div class="header-buttons">
			<a href="raport_prezenta.php" class="home-btn" style="background: #ffc107;">📈 Prezență</a>
			<a href="index.php" class="home-btn">🏠 Acasă</a>
			<a href="index.php" class="back-btn">← Înapoi la scanare</a>
		</div>
        </div>

        <div class="stats">
            <h2>Total: <?php echo number_format($total_records); ?> cititori</h2>
            <p>Afișate <?php echo $records_per_page; ?> înregistrări pe pagină</p>
        </div>

        <div class="content">
            <?php if (count($cititori) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cod</th>
                            <th>Nume</th>
                            <th>Prenume</th>
                            <th>Telefon</th>
                            <th>Email</th>
                            <th>Dată înregistrare</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cititori as $cititor): ?>
                            <tr>
                                <td><span class="reader-code"><?php echo htmlspecialchars($cititor['cod_bare']); ?></span></td>
                                <td><span class="reader-name"><?php echo htmlspecialchars($cititor['nume']); ?></span></td>
                                <td><?php echo htmlspecialchars($cititor['prenume']); ?></td>
                                <td class="contact-info">
                                    <?php if ($cititor['telefon']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($cititor['telefon']); ?>">
                                            <?php echo htmlspecialchars($cititor['telefon']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="contact-info">
                                    <?php if ($cititor['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($cititor['email']); ?>">
                                            <?php echo htmlspecialchars($cititor['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($cititor['data_inregistrare'])); ?></td>
                                <td><a href="editare_cititor.php?id=<?php echo $cititor['id']; ?>" class="action-btn">✏️ Modifica</a></td>
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
                <div class="no-data">📭 Nu există cititori înregistrați</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="app-footer">
        <p>Dezvoltare web: Neculai Ioan Fantanaru</p>
    </div>
</body>
</html>
