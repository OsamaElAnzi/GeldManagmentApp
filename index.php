<?php
session_start();

require 'functions.php';
setupDatabase();

$spaardoel = getSpaarDoel();
$bedrag = getBedrag();
$datum = getDatum();
$condition = true;

if (isset($_GET['mode'])) {
    $_SESSION['mode'] = htmlspecialchars($_GET['mode']);
}

$mode = isset($_SESSION['mode']) ? $_SESSION['mode'] : 'day';

$darkModeEnabled = ($mode === 'dark');
$dayModeEnabled = ($mode === 'day');

if (isset($_GET['bedrag']) && isset($_GET['type'])) {
    $bedragNew = $_GET['bedrag'];
    $type = $_GET['type'];
    $bedragOutput = doelNaAanpassing($bedragNew, $type);
}
if (isset($_GET['bedragInvoeren']) && is_numeric($_GET['bedragInvoeren'])) {
    if (isset($_GET['INKOMEN'])) {
        $bedragInvoeren = $_GET['bedragInvoeren'];
        $soort_biljetten = $_GET['soort_biljetten'];
        $bedrag = doelAanpassen($bedragInvoeren, 'INKOMEN');
        voegToeAanInkomenLijst($datum, $bedragInvoeren, $soort_biljetten);
        header("Location:http://localhost/GeldManagmentApp/");
    } elseif (isset($_GET['UITGAVEN'])) {
        $bedragInvoeren = $_GET['bedragInvoeren'];
        $bedrag = doelAanpassen($bedragInvoeren, 'UITGAVEN');
        voegToeAanUitgavenLijst($datum, $bedragInvoeren, $soort_biljetten);
        header("Location:http://localhost/GeldManagmentApp/");
    }
} elseif (isset($_GET['SPAARDOEL']) && is_numeric($_GET['SPAARDOEL'])) {
    $spaardoel = (float) $_GET['SPAARDOEL'];
    updateSpaarDoel($spaardoel);
} elseif (isset($_GET['RESET-KNOP'])) {
    $bedrag = resetDoel();
    $condition = false;
}

$progress = ($spaardoel > 0) ? min(($bedrag / $spaardoel) * 100, 100) : 0;

$inkomen_page = isset($_GET['inkomen_page']) ? (int) $_GET['inkomen_page'] : 1;
$inkomen_limit = 10;
$inkomen_offset = ($inkomen_page - 1) * $inkomen_limit;
$inkomen_condition = '1';
$inkomen_total = countInkomenRows($inkomen_condition);
$inkomen_total_pages = ceil($inkomen_total / $inkomen_limit);
$inkomen_lijst = getInkomenLijst($inkomen_condition, $inkomen_limit, $inkomen_offset);

$uitgaven_page = isset($_GET['uitgaven_page']) ? (int) $_GET['uitgaven_page'] : 1;
$uitgaven_limit = 10;
$uitgaven_offset = ($uitgaven_page - 1) * $uitgaven_limit;
$uitgaven_condition = '1';
$uitgaven_total = countUitgavenRows($uitgaven_condition);
$uitgaven_total_pages = ceil($uitgaven_total / $uitgaven_limit);
$uitgaven_lijst = getUitgavenLijst($uitgaven_condition, $uitgaven_limit, $uitgaven_offset);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doel Aanpassen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        .circle-container {
            width: 150px;
            height: 150px;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .circle-fill {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            position: relative;
        }
    </style>
</head>

<body class="<?= $darkModeEnabled ? 'bg-dark text-white' : 'bg-light text-dark'; ?>">
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card <?= $darkModeEnabled ? 'bg-secondary text-white' : 'bg-light text-dark'; ?> shadow">
                    <div class="card-body">
                        <h2 class="card-title">Inkomen</h2>
                        <h2 class="card-title bg-success d-flex justify-content-center"><?= totaleInkomsten() ?></h2>
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between">
                                <strong>info</strong>
                                <strong>Bedrag</strong>
                                <strong>Datum</strong>
                            </div>
                            <?php displayInkomenLijst($inkomen_lijst); ?>
                        </div>
                        <div class="mt-3">
                            <?php displayInkomenPagination($inkomen_page, $inkomen_total_pages); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card <?= $darkModeEnabled ? 'bg-secondary text-white' : 'bg-light text-dark'; ?> shadow">
                    <div class="card-body text-center d-flex align-item-center flex-column">
                        <h2>Doel</h2>
                        <p>Bedrag: €<?= $bedrag ? number_format($bedrag, 2, ',', '.'): 0; ?>,-</p>
                        <p>Spaardoel: €<?= number_format($spaardoel, 2); ?>,-</p>
                        <p>Nog te gaan: €<?= number_format(nogTeGaanVoorDoelBehaling(), 2, ',', '.'); ?>,-</p>
                        <p>Hoeveelheid brieven:<?= getHoeveelheidBrieven(); ?></p>
                        <div class="circle-container d-flex justify-content-center align-items-center mb-3 w-100">
                            <div class="circle-fill"
                                style="background: conic-gradient(green <?= $progress ?>%, lightgrey 0%);">
                                <h3><?= number_format($progress, 0) . '%' ?></h3>
                            </div>
                        </div>

                        <form action="" method="GET" class="mb-3">
                            <input type="hidden" name="mode" value="<?= $mode; ?>">
                            <div class="input-group">
                                <input type="text" class="form-control" name="SPAARDOEL" placeholder="Spaardoel" required>
                                <button type="submit" class="btn btn-primary" name="AANPASSEN">Aanpassen</button>
                            </div>
                        </form>

                        <form action="" method="GET">
                            <input type="hidden" name="mode" value="<?= $mode; ?>">
                            <div class="input-group mb-3">
                                <span class="input-group-text">€</span>
                                <input type="text" name="bedragInvoeren" class="form-control" placeholder="BEDRAG"
                                    maxlength="8" required>
                            </div>
                            <select class="form-select mb-3" aria-label="Default select example" name="soort_biljetten">
                                <option value="geen bilject gekozen">Soort biljetten</option>
                                <option value="5">€5,-</option>
                                <option value="10">€10,-</option>
                                <option value="20">€20,-</option>
                                <option value="50">€50,-</option>
                                <option value="100">€100,-</option>
                                <option value="200">€200,-</option>
                                <option value="500">€500,-</option>
                            </select>
                            <div class="d-flex gap-2">
                                <button type="submit" name="INKOMEN" class="btn btn-success">INKOMEN</button>
                                <button type="submit" name="UITGAVEN" class="btn btn-danger">UITGAVEN</button>
                            </div>
                        </form>
                        <form action="" method="GET" class="mt-3">
                            <input type="hidden" name="mode" value="<?= $mode; ?>">
                            <button type="submit" name="RESET-KNOP" class="btn btn-warning">Reset</button>
                        </form>

                        <form action="" method="GET" class="mt-3">
                            <button type="submit" name="mode" value="dark" class="btn btn-dark">Dark Mode</button>
                            <button type="submit" name="mode" value="day" class="btn btn-light">Day Mode</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card <?= $darkModeEnabled ? 'bg-secondary text-white' : 'bg-light text-dark'; ?> shadow">
                    <div class="card-body">
                        <h2 class="card-title">Uitgaven</h2>
                        <h2 class="card-title bg-danger d-flex justify-content-center"><?= totaleUitgaven() ?></h2>
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between">
                                <strong>info</strong>
                                <strong>Bedrag</strong>
                                <strong>Datum</strong>
                            </div>
                            <?php displayUitgavenLijst($uitgaven_lijst); ?>
                        </div>
                        <div class="mt-3">
                            <?php displayUitgavenPagination($uitgaven_page, $uitgaven_total_pages); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>