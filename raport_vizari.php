<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'config.php';
require_once 'auth_check.php';

$an_curent = date('Y');

// Total cititori
$stmt = $pdo->query("SELECT COUNT(*) as total FROM cititori");
$total_cititori = $stmt->fetch()['total'];

// Cititori vizați pentru anul curent
$stmt = $pdo->prepare("SELECT COUNT(*) as vizati FROM cititori WHERE YEAR(ultima_vizare) = ?");
$stmt->execute([$an_curent]);
$total_vizati = $stmt->fetch()['vizati'];

// Cititori nevizați
$total_nevizati = $total_cititori - $total_vizati;

// Procent vizare
$procent_vizare = $total_cititori > 0 ? round(($total_vizati / $total_cititori) * 100, 2) : 0;

// Vizări pe luni (pentru anul curent)
$sql = "SELECT MONTH(ultima_vizare) as luna, COUNT(*) as numar
        FROM cititori
        WHERE YEAR(ultima_vizare) = ?
        GROUP BY MONTH(ultima_vizare)
        ORDER BY luna";

$stmt = $pdo->prepare($sql);
$stmt->execute([$an_curent]);
$vizari_pe_luni = $stmt->fetchAll(PDO::FETCH_ASSOC);

$luni_romana = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Raport Vizări Permise - <?= $an_curent ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">📊 Raport Vizări Permise - <?= $an_curent ?></h1>

        <!-- Statistici generale -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total Cititori</h5>
                        <h2 class="text-primary"><?= $total_cititori ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Vizați <?= $an_curent ?></h5>
                        <h2 class="text-success"><?= $total_vizati ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Nevizați</h5>
                        <h2 class="text-danger"><?= $total_nevizati ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Procent Vizare</h5>
                        <h2 class="<?= $procent_vizare >= 80 ? 'text-success' : 'text-warning' ?>">
                            <?= $procent_vizare ?>%
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bară de progres -->
        <div class="card mb-4">
            <div class="card-body">
                <h5>Progres Vizare <?= $an_curent ?></h5>
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar <?= $procent_vizare >= 80 ? 'bg-success' : 'bg-warning' ?>" 
                         style="width: <?= $procent_vizare ?>%">
                        <?= $procent_vizare ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafic vizări pe luni -->
        <div class="card mb-4">
            <div class="card-body">
                <h5>Vizări pe Luni - <?= $an_curent ?></h5>
                <canvas id="chartVizari"></canvas>
            </div>
        </div>

        <!-- Tabel vizări pe luni -->
        <div class="card">
            <div class="card-body">
                <h5>Detalii pe Luni</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Luna</th>
                            <th>Număr Vizări</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vizari_pe_luni as $vizare): ?>
                        <tr>
                            <td><?= $luni_romana[$vizare['luna']] ?></td>
                            <td><strong><?= $vizare['numar'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">← Înapoi la Scanare</a>
            <a href="lista_nevizati.php" class="btn btn-warning">⚠️ Vezi Lista Nevizați</a>
        </div>
    </div>

    <script>
        // Date pentru grafic
        const luni = <?= json_encode(array_column($vizari_pe_luni, 'luna')) ?>;
        const numarVizari = <?= json_encode(array_column($vizari_pe_luni, 'numar')) ?>;

        const luniNumere = <?= json_encode($luni_romana) ?>;
        const labelLuni = luni.map(l => luniNumere[l]);

        const ctx = document.getElementById('chartVizari');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelLuni,
                datasets: [{
                    label: 'Număr Vizări',
                    data: numarVizari,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>