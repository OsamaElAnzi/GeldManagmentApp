<?php
require 'functions.php';
setupDatabase();

$spaardoel = getSpaarDoel();
$bedrag = getBedrag();
$datum = getDatum();
$condition = true;

$darkModeEnabled = false;
$dayModeEnabled = true;

if (isset($_GET['mode'])) {
    if ($_GET['mode'] == 'dark') {
        $darkModeEnabled = true;
        $dayModeEnabled = false;
    } else {
        $darkModeEnabled = false;
        $dayModeEnabled = true;
    }
}

if (isset($_GET['bedragInvoeren']) && is_numeric($_GET['bedragInvoeren'])) {
    if (isset($_GET['INKOMEN'])) {
        $bedragInvoeren = $_GET['bedragInvoeren'];
        $bedrag = doelAanpassen($bedragInvoeren, 'INKOMEN');
        voegToeAanInkomenLijst($datum, $bedragInvoeren);
    } elseif (isset($_GET['UITGAVEN'])) {
        $bedragInvoeren = $_GET['bedragInvoeren'];
        $bedrag = doelAanpassen($bedragInvoeren, 'UITGAVEN');
        voegToeAanUitgavenLijst($datum, $bedragInvoeren);
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
        .circle-fill {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: lightgrey;
            display: flex;
            justify-content: center;
            align-items: center;
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
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between">
                                <strong>Bedrag</strong>
                                <strong>Datum</strong>
                            </div>
                            <!-- probleem met table als in er moet beschrijving colomn erbij zelfde geld voor uitgaven -->
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
                    <div class="card-body text-center">
                        <h2>Doel</h2>
                        <p>Bedrag: €<?= number_format($bedrag, 2, ',' , '.') ?>,-</p>
                        <p>Spaardoel: €<?= number_format($spaardoel, 2); ?>,-</p>
                        <p>Nog te gaan: €<?= number_format(nogTeGaanVoorDoelBehaling(), 2, ',' , '.'); ?>,-</p>
                        <!-- circle heeft moeite om in het midden te zijn -->
                        <div class="circle-fill mb-3"
                            style="background: conic-gradient(green <?= $progress ?>%, lightgrey 0%);">
                            <h3><?= number_format($progress, 0) . '%' ?></h3>
                        </div>

                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="SPAARDOEL" placeholder="Spaardoel" required>
                                <button type="submit" class="btn btn-primary" name="AANPASSEN">Aanpassen</button>
                            </div>
                        </form>

                        <form action="" method="GET">
                            <div class="input-group mb-3">
                                <span class="input-group-text">€</span>
                                <input type="text" name="bedragInvoeren" class="form-control" placeholder="BEDRAG"
                                    pattern="[0-9]*" maxlength="8" required>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="INKOMEN" class="btn btn-success">INKOMEN</button>
                                <button type="submit" name="UITGAVEN" class="btn btn-danger">UITGAVEN</button>
                            </div>
                        </form>
                        <form action="" method="GET" class="mt-3">
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
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between">
                                <strong>Bedrag</strong>
                                <strong>Datum</strong>
                            </div>
                            <!-- moet nog een link aan toevoegevn die je verwijst naar de beschrijfing van de tranactie -->
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
