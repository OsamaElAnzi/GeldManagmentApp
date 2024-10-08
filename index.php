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
$inkomen_limit = 22;
$inkomen_offset = ($inkomen_page - 1) * $inkomen_limit;

$inkomen_condition = '1';
$inkomen_total = countInkomenRows($inkomen_condition);
$inkomen_total_pages = ceil($inkomen_total / $inkomen_limit);

$inkomen_lijst = getInkomenLijst($inkomen_condition, $inkomen_limit, $inkomen_offset);
$uitgaven_page = isset($_GET['uitgaven_page']) ? (int) $_GET['uitgaven_page'] : 1;
$uitgaven_limit = 22;
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
    <title>Doel%20Aanpassen</title>
    <link rel="stylesheet" href="style.css?v=1.0" type="text/css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .main-content {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            flex: 1;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .circle-fill {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: lightgrey;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        input[type="text"],
        button {
            padding: 10px;
            font-size: 1rem;
            border-radius: 5px;
            border: 1px solid gray;
        }

        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        <?php if ($darkModeEnabled): ?>
            body,
            html {
                background-color: #121212;
                color: white;
            }

            .main-content {
                background-color: #1e1e1e;
                color: white;
            }

            input,
            button {
                background-color: #333;
                color: white;
            }

            button:hover {
                background-color: #555;
            }

            .main-content:hover {
                -webkit-box-shadow: 0px 0px 84px -28px rgba(91, 100, 185, 1);
                -moz-box-shadow: 0px 0px 84px -28px rgba(91, 100, 185, 1);
                box-shadow: 0px 0px 84px -28px rgba(91, 100, 185, 1);
            }

        <?php else: ?>
            body,
            html {
                background-color: white;
                color: black;
            }

            .main-content,
            .main-content>div {
                background-color: #f5f5f5;
                color: black;
            }

            input,
            button {
                background-color: white;
                color: black;
            }

            button:hover {
                background-color: #ddd;
            }

        <?php endif; ?>
    </style>
</head>
<!-- ColorZilla extention kan voor een error zorgen als je de html checkt naar netheid -->
<body>
    <div class="container">
        <div class="main-content">
            <div class="inkomen-lijst">
                <h1>Inkomen</h1>
                <div class="lijst">
                    <div class="title">
                        <h2>Bedrag</h2>
                        <h2>Datum</h2>
                    </div>
                    <?php displayInkomenLijst($inkomen_lijst); ?>
                </div>
                <?php displayInkomenPagination($inkomen_page, $inkomen_total_pages); ?>
            </div>
        </div>
        <div class="main-content">
            <h1>Doel</h1>
            <div class="info-van-bezit">
                <p>Bedrag: €<?= number_format($bedrag, 2, ',' , '.') ?>,-</p>
                <p>Spaardoel: €<?= number_format($spaardoel, 2); ?>,-</p>
                <p>Nog te gaan: €<?= number_format(nogTeGaanVoorDoelBehaling(), 2, ',' , '.'); ?>,-</p>
            </div>
            <div class="circle">
                <div class="circle-fill" style="background: conic-gradient(green <?= $progress ?>%, lightgrey 0%);">
                    <h1><?= number_format($progress, 0) . '%' ?></h1>
                </div>
            </div>
            <div class="wijzigings-blok">
                <form action="" method="GET" autocomplete="off">
                    <input type="hidden" name="mode" value="<?= htmlspecialchars($darkModeEnabled ? 'dark' : 'day') ?>">
                    <div class="input-van-bedrag">
                        <p>€</p>
                        <input type="text" pattern=[0-9]* name="bedragInvoeren" placeholder="BEDRAG" maxlength="8" required>
                    </div>
                    <div class="knoppen">
                        <button type="submit" class="INKOMEN" name="INKOMEN">INKOMEN</button>
                        <button type="submit" class="UITGAVEN" name="UITGAVEN">UITGAVEN</button>
                    </div>
                </form>
            </div>
            <div class="doel-aanpassen">
                <form action="" method="GET" autocomplete="off">
                    <input type="hidden" name="mode" value="<?= htmlspecialchars($darkModeEnabled ? 'dark' : 'day') ?>">
                    <input type="text" class="input-Aanpassen" name="SPAARDOEL" placeholder="Spaardoel">
                    <button type="submit" class="AANPASSEN" name="AANPASSEN">Aanpassen</button>
                </form>
            </div>
            <div class="doel-reset">
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="<?= htmlspecialchars($darkModeEnabled ? 'dark' : 'day') ?>">
                    <button type="submit" name="RESET-KNOP" class="RESET-KNOP">Reset</button>
                </form>
            </div>
            <div class="dark-day-mode">
                <form action="" method="GET">
                    <input type="hidden" name="inkomen_page" value="<?= htmlspecialchars($inkomen_page) ?>">
                    <input type="hidden" name="uitgaven_page" value="<?= htmlspecialchars($uitgaven_page) ?>">
                    <button type="submit" name="mode" value="dark" class="KNOPDARKMODE">Dark Mode</button>
                    <button type="submit" name="mode" value="day" class="KNOPDAYMODE">Day Mode</button>
                </form>
            </div>
        </div>
        <div class="main-content">
            <div class="uitgaven-lijst">
                <h1>Uitgaven</h1>
                <div class="lijst">
                    <div class="title">
                        <h2>Bedrag</h2>
                        <h2>Datum</h2>
                    </div>
                    <?php displayUitgavenLijst($uitgaven_lijst); ?>
                </div>
                <?php displayUitgavenPagination($uitgaven_page, $uitgaven_total_pages); ?>
            </div>
        </div>

</body>

</html>