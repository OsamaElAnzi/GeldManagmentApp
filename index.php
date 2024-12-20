<?php

session_start();
require 'functions.php';
include 'back-end.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeldManagmentApp</title>
    <link type="image/x-icon" rel="icon" href="./foto/profits.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Orbitron&family=Oswald:wght@200..700&family=Playwrite+DE+VA+Guides&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Teko:wght@393&family=VT323&display=swap" rel="stylesheet">
    <style>
        .ibm-plex-mono-thin {
            font-family: "IBM Plex Mono", serif;
            font-weight: 600;
            font-style: italic;
        }

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

<body class="ibm-plex-mono-thin <?= $darkModeEnabled ? 'bg-dark text-white' : 'bg-light text-dark'; ?>">
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card <?= $darkModeEnabled ? 'bg-secondary text-white' : 'bg-light text-dark'; ?> shadow">
                    <div class="card-body">
                        <h2 class="card-title">Inkomen</h2>
                        <h2 class="card-title bg-success d-flex justify-content-center p-2 rounded"><?= totaleInkomsten() ?></h2>
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
                        <p>Bedrag: €<?= $bedrag ? number_format($bedrag, 2, ',', '.') : 0; ?>,-</p>
                        <p>Spaardoel: €<?= $spaardoel ? number_format($spaardoel, 2) : 0; ?>,-</p>
                        <p>Nog te gaan: €<?= number_format(nogTeGaanVoorDoelBehaling(), 2, ',', '.'); ?>,-</p>
                        <p>Hoeveelheid brieven:<?= getHoeveelheidBrieven(); ?>
                            <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                Biljetten
                            </button>
                            <!-- modal -->
                        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="col">
                                            Totaal: <?= getHoeveelheidBrieven(); ?>
                                        </div>
                                        <div class="row text-dark">
                                            <div class="col">
                                            €500
                                                <br />
                                                <?= biljet500() ?>
                                            </div>
                                            <div class="col">
                                            €200
                                                <br />
                                                <?= biljet200() ?>
                                            </div>
                                            <div class="col">
                                            €100
                                                <br />
                                                <?= biljet100() ?>
                                            </div>
                                            <div class="col">
                                            €50
                                                <br />
                                                <?= biljet50() ?>
                                            </div>
                                            <div class="col">
                                            €20
                                                <br />
                                                <?= biljet20() ?>
                                            </div>
                                            <div class="col">
                                            €10
                                                <br />
                                                <?= biljet10() ?>
                                            </div>
                                            <div class="col">
                                            €5
                                                <br />
                                                <?= biljet5() ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </p>
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
                                    maxlength="8" pattern="^\d+(\.\d{1,2})?$" required>
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
                        <h2 class="card-title bg-danger d-flex justify-content-center p-2 rounded"><?= totaleUitgaven() ?></h2>
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