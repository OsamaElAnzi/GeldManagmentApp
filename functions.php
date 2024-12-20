<?php

function conn()
{
    $host = 'localhost';
    $port = '3306'; //Check altijd of je de goede port hebt draaien anders krijg je een error
    $dbname = 'rekening';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    return new PDO($dsn, $user, $pass, $opt);
}

function setupDatabase()
{
    $pdo = conn();

    $createbedragenTable = 'CREATE TABLE IF NOT EXISTS bedragen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bedrag DECIMAL(15, 2) NOT NULL DEFAULT 0,
        hoeveelheidbrieven VARCHAR(255) NULL,
        spaardoel DECIMAL(15, 2) NOT NULL DEFAULT 0
    )';
    $pdo->exec($createbedragenTable);

    $query = $pdo->query('SELECT COUNT(*) as count FROM bedragen');
    $row = $query->fetch();

    if ($row['count'] == 0) {
        $insertQuery = 'INSERT INTO bedragen (bedrag, spaardoel) VALUES (0, 0)';
        $pdo->exec($insertQuery);
    }
    $createInkomenTable = 'CREATE TABLE IF NOT EXISTS inkomenlijst (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bedrag DECIMAL(10, 2) NOT NULL,
        soort_biljetten VARCHAR(255) NULL,
        aantalBiljettenInkomen INT NULL,
        datum DATE NOT NULL
    )';
    $pdo->exec($createInkomenTable);

    $createUitgavenTable = 'CREATE TABLE IF NOT EXISTS uitgavenlijst (
        id INT AUTO_INCREMENT PRIMARY KEY,
        datum DATE NOT NULL,
        soort_biljetten VARCHAR(255) NULL,
        aantalBiljettenUitgaven INT NULL,
        bedrag DECIMAL(10, 2) NOT NULL
    )';
    $pdo->exec($createUitgavenTable);
}

function getBedrag()
{
    $pdo = conn();
    $query = 'SELECT bedrag FROM bedragen LIMIT 1';
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    return (float)$result['bedrag'];
}
function getHoeveelheidBrieven()
{
    $pdo = conn();

    try {
        $brievenHoeveelheidInkomen = biljettenTellerInkomen();
        $brievenHoeveelheidUitgaven = biljettenTellerUitgaven();

        $verschil = $brievenHoeveelheidInkomen - $brievenHoeveelheidUitgaven;

        $queryInsert = "UPDATE bedragen SET hoeveelheidbrieven = :hoeveelheid WHERE id = 1";
        $stmt = $pdo->prepare($queryInsert);
        $stmt->execute(['hoeveelheid' => $verschil]);

        $query = $pdo->query('SELECT hoeveelheidbrieven FROM bedragen LIMIT 1');
        $result = $query->fetch();

        return $result ? $result['hoeveelheidbrieven'] : 0;
    } catch (Exception $e) {
        echo '<div class="alert alert-danger" role="alert">Er is een fout opgetreden: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return 0;
    }
}


function biljettenTellerInkomen()
{
    $pdo = conn();

    try {
        $stmt = $pdo->query('SELECT soort_biljetten, bedrag FROM inkomenlijst');
        $resultaten = $stmt->fetchAll();

        if (!$resultaten) {
            return 0;
        }

        $biljettenTelling = [];

        foreach ($resultaten as $row) {
            $soort = (int)$row['soort_biljetten'];
            $bedrag = (int)$row['bedrag'];

            if ($soort > 0) {
                $aantalBiljetten = (int)($bedrag / $soort);
                if (!isset($biljettenTelling[$soort])) {
                    $biljettenTelling[$soort] = 0;
                }

                $biljettenTelling[$soort] += $aantalBiljetten;
            } else {
                return 0;
            }
        }

        return array_sum($biljettenTelling);
    } catch (Exception $e) {
        echo '<div class="alert alert-danger" role="alert">Er is een fout opgetreden: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return 0;
    }
}

function biljettenTellerUitgaven()
{
    $pdo = conn();

    try {
        $stmt = $pdo->query('SELECT soort_biljetten, bedrag FROM uitgavenlijst');
        $resultaten = $stmt->fetchAll();

        if (!$resultaten) {
            return 0;
        }

        $biljettenTelling = [];

        foreach ($resultaten as $row) {
            $soort = (int)$row['soort_biljetten'];
            $bedrag = (int)$row['bedrag'];

            if ($soort > 0) {
                $aantalBiljetten = (int)($bedrag / $soort);

                if (!isset($biljettenTelling[$soort])) {
                    $biljettenTelling[$soort] = 0;
                }

                $biljettenTelling[$soort] += $aantalBiljetten;
            } else {
                return 0;
            }
        }

        return array_sum($biljettenTelling);
    } catch (Exception $e) {
        echo '<div class="alert alert-danger" role="alert">Er is een fout opgetreden: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return 0;
    }
}

function getSpaarDoel()
{
    $pdo = conn();
    $query = $pdo->query('SELECT spaardoel FROM bedragen LIMIT 1');
    $result = $query->fetch();
    return $result ? (float)$result['spaardoel'] : 0;
}

function getDatum()
{
    $pdo = conn();
    $query = $pdo->query('SELECT CURDATE() AS datum');
    $result = $query->fetch();
    return $result['datum'];
}

function doelAanpassen($bedragInvoeren, $type)
{
    $pdo = conn();
    $huidigBedrag = getBedrag();
    $nieuwBedrag = ($type === 'INKOMEN')
        ? $huidigBedrag + (float)$bedragInvoeren
        : $huidigBedrag - (float)$bedragInvoeren;

    $updateQuery = 'UPDATE bedragen SET bedrag = :bedrag LIMIT 1';
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['bedrag' => $nieuwBedrag]);

    return $nieuwBedrag;
}
function doelNaAanpassing($bedragNew, $type)
{
    $pdo = conn();
    $huidigBedrag = getBedrag();
    if ($type === 'NEW-INKOMEN') {
        $huidigBedrag = +(float)$bedragNew;
        $updateQuery = 'UPDATE bedragen SET bedrag = :bedrag LIMIT 1';
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute(['bedrag' => $huidigBedrag]);
    } elseif ($type === 'NEW-UITGAVEN') {
        $huidigBedrag = -$huidigBedrag - (float)$bedragNew;
        $updateQuery = 'UPDATE bedragen SET bedrag = :bedrag LIMIT 1';
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute(['bedrag' => $huidigBedrag]);
    }
}
function updateSpaarDoel($spaardoel)
{
    $pdo = conn();
    $updateQuery = 'UPDATE bedragen SET spaardoel = :spaardoel LIMIT 1';
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['spaardoel' => $spaardoel]);
}
function resetDoel()
{
    if (isset($_GET['RESET-KNOP'])) {
        $pdo = conn();
        $resetQuerybedragen = 'UPDATE bedragen SET bedrag = 0, spaardoel = 0';
        $resetQueryInkomenLijst = 'DELETE FROM inkomenlijst';
        $resetQueryUitgavenLijst = 'DELETE FROM uitgavenlijst';
        $pdo->exec($resetQueryUitgavenLijst);
        $pdo->exec($resetQueryInkomenLijst);
        $pdo->exec($resetQuerybedragen);

        return 0;
    }
    return getBedrag();
}

function nogTeGaanVoorDoelBehaling()
{
    $huidigBedrag = getBedrag();
    $spaardoel = getSpaarDoel();
    return $spaardoel - $huidigBedrag;
}

function voegToeAanInkomenLijst($datum, $bedragInvoeren, $soort_biljetten)
{
    $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
    $pdo = conn();
    $query = 'INSERT INTO inkomenlijst (datum, bedrag, soort_biljetten) VALUES (:datum, :bedrag, :soort_biljetten)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedragInvoeren, 'soort_biljetten' => $soort_biljetten]);
    header("Location:http://localhost/GeldManagmentApp/?mode=$mode");
}
function getInkomenLijst($condition, $limit, $offset)
{
    $pdo = conn();
    $query = "SELECT id, bedrag, datum FROM inkomenlijst WHERE $condition LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function displayInkomenLijst($inkomen_lijst)
{
    echo '<div class="list-group">';
    foreach ($inkomen_lijst as $item) {
        //moet darkmode variable meegeven zodat darkmode werkt voor inkomen
        echo '<div class="list-group-item d-flex justify-content-between align-items-center">';
        echo '<a href="transacties/detailInkomenLijst.php?id=' . htmlspecialchars($item['id']) . '" class="text-decoration-none text-primary">Details</a>';
        echo '<span class="badge bg-success">€+' . number_format($item['bedrag'], 2, ',', '.') . '</span>';
        echo '<span>' . htmlspecialchars($item['datum']) . '</span>';
        echo '</div>';
    }
    echo '</div>';
}



function countInkomenRows($condition)
{
    $pdo = conn();
    $sql = "SELECT COUNT(*) as total FROM inkomenlijst WHERE $condition";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['total'];
}

function displayPagination($current_page, $total_pages)
{
    if ($total_pages > 1) {
        echo "<div class='pagination'>";
        if ($current_page > 1) {
            echo "<a href='?page=" . ($current_page - 1) . "' class='prev'>Vorige</a>";
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $current_page) ? 'active' : '';
            echo "<a href='?page=$i' class='$active_class'>$i</a> ";
        }
        if ($current_page < $total_pages) {
            echo "<a href='?page=" . ($current_page + 1) . "' class='next'>Volgende</a>";
        }
        echo "</div>";
    }
}
function voegToeAanUitgavenLijst($datum, $bedragInvoeren, $soort_biljetten)
{
    $pdo = conn();
    $query = 'INSERT INTO uitgavenlijst (soort_biljetten ,datum, bedrag) VALUES (:soort_biljetten, :datum, :bedrag)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedragInvoeren, 'soort_biljetten' => $soort_biljetten]);
    header("Location: http://localhost/GeldManagmentApp/");
}
function getUitgavenLijst($condition, $limit, $offset)
{
    $pdo = conn();
    $query = "SELECT id, bedrag, datum FROM uitgavenlijst WHERE $condition LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
function displayUitgavenLijst($uitgaven_lijst)
{
    echo '<div class="list-group">';
    foreach ($uitgaven_lijst as $item) {
        //moet darkmode variable meegeven zodat darkmode werkt voor uitgaven
        echo '<div class="list-group-item d-flex justify-content-between align-items-center">';
        echo '<a href="transacties/detailUitgavenLijst.php?id=' . htmlspecialchars($item['id']) . '" class="text-decoration-none text-primary">Details</a>';
        echo '<span class="badge bg-danger">€-' . number_format($item['bedrag'], 2, ',', '.') . '</span>';
        echo '<span>' . htmlspecialchars($item['datum']) . '</span>';
        echo '</div>';
    }
    echo '</div>';
}


function countUitgavenRows($condition)
{
    $pdo = conn();
    $sql = "SELECT COUNT(*) as total FROM uitgavenlijst WHERE $condition";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['total'];
}

function displayInkomenPagination($current_page, $total_pages)
{
    if ($total_pages > 1) {
        echo "<nav aria-label='Page navigation'>";
        echo "<ul class='pagination justify-content-center'>";

        $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';

        if ($current_page > 1) {
            echo "<li class='page-item'>";
            echo "<a class='page-link' href='?inkomen_page=" . ($current_page - 1) . "&mode=$mode' aria-label='Previous'>";
            echo "<img src='foto/arrow_back_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Previous'>";
            echo "</a>";
            echo "</li>";
        } else {
            echo "<li class='page-item disabled'>";
            echo "<span class='page-link'>";
            echo "<img src='foto/arrow_back_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Previous'>";
            echo "</span>";
            echo "</li>";
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $current_page) ? 'active' : '';
            echo "<li class='page-item $active_class'>";
            echo "<a class='page-link' href='?inkomen_page=$i&mode=$mode'>$i</a>";
            echo "</li>";
        }
        if ($current_page < $total_pages) {
            echo "<li class='page-item'>";
            echo "<a class='page-link' href='?inkomen_page=" . ($current_page + 1) . "&mode=$mode' aria-label='Next'>";
            echo "<img src='foto/arrow_right_alt_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Next'>";
            echo "</a>";
            echo "</li>";
        } else {
            echo "<li class='page-item disabled'>";
            echo "<span class='page-link'>";
            echo "<img src='foto/arrow_right_alt_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Next'>";
            echo "</span>";
            echo "</li>";
        }

        echo "</ul>";
        echo "</nav>";
    }
}


function displayUitgavenPagination($current_page, $total_pages)
{
    if ($total_pages > 1) {
        echo "<nav aria-label='Uitgaven page navigation'>";
        echo "<ul class='pagination justify-content-center'>";

        $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';

        if ($current_page > 1) {
            echo "<li class='page-item'>";
            echo "<a class='page-link' href='?uitgaven_page=" . ($current_page - 1) . "&mode=$mode' aria-label='Previous'>";
            echo "<img src='./foto/arrow_back_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Previous'>";
            echo "</a>";
            echo "</li>";
        } else {
            echo "<li class='page-item disabled'>";
            echo "<span class='page-link'>";
            echo "<img src='./foto/arrow_back_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Previous'>";
            echo "</span>";
            echo "</li>";
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $current_page) ? 'active' : '';
            echo "<li class='page-item $active_class'>";
            echo "<a class='page-link' href='?uitgaven_page=$i&mode=$mode'>$i</a>";
            echo "</li>";
        }
        if ($current_page < $total_pages) {
            echo "<li class='page-item'>";
            echo "<a class='page-link' href='?uitgaven_page=" . ($current_page + 1) . "&mode=$mode' aria-label='Next'>";
            echo "<img src='./foto/arrow_right_alt_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Next'>";
            echo "</a>";
            echo "</li>";
        } else {
            echo "<li class='page-item disabled'>";
            echo "<span class='page-link'>";
            echo "<img src='./foto/arrow_right_alt_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' width='20' alt='Next'>";
            echo "</span>";
            echo "</li>";
        }

        echo "</ul>";
        echo "</nav>";
    }
}

// dit stukje is voor de detail pagina voor inkomen
function detailInkomen($id)
{
    $pdo = conn();
    $query = "SELECT * FROM inkomenlijst WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();

    if (!$item) {
        echo '<div class="alert alert-danger" role="alert">Item niet gevonden</div>';
        return;
    }

    $bedragInkomen = (float)$item['bedrag'];
    $soort_biljetten = (int)$item['soort_biljetten'];
    if ($soort_biljetten > 0) {
        $aantalBiljettenInkomen = (int)($bedragInkomen / $soort_biljetten);

        $updateQuery = "UPDATE inkomenlijst SET aantalBiljettenInkomen = :aantal WHERE id = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute(['aantal' => $aantalBiljettenInkomen, 'id' => $id]);
    } else {
        $aantalBiljettenInkomen = 0;
    }

    echo <<<HTML
<body class="d-flex justify-content-center align-items-center flex-column" style="min-height: 100vh; background-color: #f4f4f9;">
    <h1>Detailpagina inkomen</h1><br />
    <form style="width: 22rem;" action="" method="POST">
        <input type="hidden" name="id" value="{$item['id']}">

        <div class="form-outline mb-4">
            <input type="text" id="bedrag" name="bedrag" class="form-control" value="{$item['bedrag']}" required />
            <label class="form-label" for="bedrag">Bedrag</label>
        </div>
        <div class="form-outline mb-4">
            <input type="text" id="soort_biljetten" name="soort_biljetten" class="form-control" value="{$item['soort_biljetten']}" disabled />
            <label class="form-label" for="soort_biljetten">Soort biljet</label>
            <input type="text" id="hoeveelheid" name="hoeveelheid" class="form-control" value="{$aantalBiljettenInkomen}" disabled />
            <label class="form-label" for="soort_biljetten">Hoeveelheid</label>
        </div>
        <select class="form-select mb-3" aria-label="Default select example" name="soort_biljetten">
            <option value="5">€5,-</option>
            <option value="10">€10,-</option>
            <option value="20">€20,-</option>
            <option value="50">€50,-</option>
            <option value="100">€100,-</option>
            <option value="200">€200,-</option>
            <option value="500">€500,-</option>
        </select>
        <div class="form-outline mb-4">
            <input type="date" id="datum" name="datum" class="form-control" value="{$item['datum']}" required />
            <label class="form-label" for="datum">Datum</label>
        </div>

        <div class="form-check d-flex justify-content-center mb-4">
            <input class="form-check-input me-2" type="checkbox" value="1" id="terms" name="terms" required />
            <label class="form-check-label" for="terms">
                Je bent er van zeker dat je dit nauwkeurig hebt ingevoerd
            </label>
        </div>

        <button type="submit" name="aanpassen" class="btn btn-success btn-block mb-4">Aanpassen</button>
        <button type="submit" name="verwijderen" class="btn btn-danger btn-block mb-4">Verwijderen</button>
    </form>
</body>
HTML;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['aanpassen'])) {
            editDetailInkomen();
        } elseif (isset($_POST['verwijderen'])) {
            verwijderDetailInkomen($id);
        }
    }
}



function editDetailInkomen()
{
    $pdo = conn();
    $type = 'NEW-INKOMEN';
    if (isset($_POST['id'], $_POST['bedrag'], $_POST['datum'])) {
        $id = $_POST['id'];
        $bedrag = $_POST['bedrag'];
        $datum = $_POST['datum'];
        $soort_biljetten = $_POST['soort_biljetten'];

        $query = "UPDATE inkomenlijst SET bedrag = :bedrag, datum = :datum, soort_biljetten = :soort_biljetten WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'soort_biljetten' => $soort_biljetten,
            'bedrag' => $bedrag,
            'datum' => $datum,
            'id' => $id
        ]);
        header("Location: http://localhost/GeldManagmentApp/?bedrag=" . $bedrag . "&type=" . $type);
        echo '<div class="alert alert-danger" role="alert">Vul alle velden in!</div>';
    }
}

function verwijderDetailInkomen($id)
{
    $pdo = conn();

    try {
        $queryFetch = $pdo->prepare('SELECT bedrag FROM inkomenlijst WHERE id = :id');
        $queryFetch->execute(['id' => $id]);
        $bedragMin = $queryFetch->fetch();

        if (!$bedragMin) {
            echo '<div class="alert alert-danger" role="alert">Item niet gevonden!</div>';
            return;
        }

        $result = $bedragMin['bedrag'];

        $queryUpdate = $pdo->prepare('UPDATE bedragen SET bedrag = bedrag - :bedrag');
        $queryUpdate->execute(['bedrag' => $result]);

        $queryDelete = $pdo->prepare('DELETE FROM inkomenlijst WHERE id = :id');
        $queryDelete->execute(['id' => $id]);

        echo '<div class="alert alert-success" role="alert">Succesvol verwijderd!</div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "http://localhost/GeldManagmentApp/";
                }, 1500)
              </script>';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger" role="alert">Er is een fout opgetreden: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}


//voor uitgaven
function detailUitgaven($id)
{
    $pdo = conn();
    $query = "SELECT * FROM uitgavenlijst WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    $bedragUitgave = (float)$item['bedrag'];
    $soort_biljetten = (int)$item['soort_biljetten'];
    if ($soort_biljetten > 0) {
        $aantalBiljettenUitgaven = (int)($bedragUitgave / $soort_biljetten);

        $updateQuery = "UPDATE uitgavenlijst SET aantalBiljettenUitgaven = :aantal WHERE id = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute(['aantal' => $aantalBiljettenUitgaven, 'id' => $id]);
    } else {
        $aantalBiljettenUitgaven = 0;
    }
    if ($item) {
        echo <<<HTML
<body class="d-flex justify-content-center align-items-center flex-column" style="min-height: 100vh; background-color: #f4f4f9;">
    <h1>Detailpagina uitgave</h1><br />
    <form style="width: 22rem;" action="" method="post">
        <input type="hidden" name="id" value="{$item['id']}">

        <div class="form-outline mb-4">
            <input type="text" id="bedrag" name="bedrag" class="form-control" value="{$item['bedrag']}" required />
            <label class="form-label" for="bedrag">Bedrag</label>
        </div>
        <div class="form-outline mb-4">
            <input type="text" id="soort_biljetten" name="soort_biljetten" class="form-control" value="{$item['soort_biljetten']}" disabled />
            <label class="form-label" for="soort_biljetten">Soort biljet</label>
        </div>
        <select class="form-select mb-3" aria-label="Default select example" name="soort_biljetten">
            <option value="5">€5,-</option>
            <option value="10">€10,-</option>
            <option value="20">€20,-</option>
            <option value="50">€50,-</option>
            <option value="100">€100,-</option>
            <option value="200">€200,-</option>
            <option value="500">€500,-</option>
        </select>
        <div class="form-outline mb-4">
            <input type="date" id="datum" name="datum" class="form-control" value="{$item['datum']}" required />
            <label class="form-label" for="datum">Datum</label>
        </div>

        <div class="form-check d-flex justify-content-center mb-4">
            <input class="form-check-input me-2" type="checkbox" value="1" id="terms" name="terms" required />
            <label class="form-check-label" for="terms">
                Je bent er van zeker dat je dit nauwkeurig hebt ingevoerd
            </label>
        </div>

        <button type="submit" name="aanpassen" class="btn btn-success btn-block mb-4">Aanpassen</button>
        <button type="submit" name="verwijderen" class="btn btn-danger btn-block mb-4">Verwijderen</button>
    </form>
</body>
HTML;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['aanpassen'])) {
                editDetailUitgaven();
            } elseif (isset($_POST['verwijderen'])) {
                verwijderDetailUitgaven($id);
            }
        }
    } else {
        echo '<div class="alert alert-danger" role="alert">Item niet gevonden</div>';
    }
}
function editDetailUitgaven()
{
    $pdo = conn();
    $type = 'NEW-UITGAVEN';

    if (isset($_POST['id'], $_POST['bedrag'], $_POST['datum'])) {
        $id = $_POST['id'];
        $bedrag = $_POST['bedrag'];
        $datum = $_POST['datum'];
        $soort_biljetten = $_POST['soort_biljetten'];

        $query = "UPDATE uitgavenlijst SET bedrag = :bedrag, datum = :datum, soort_biljetten = :soort_biljetten WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'soort_biljetten' => $soort_biljetten,
            'bedrag' => $bedrag,
            'datum' => $datum,
            'id' => $id
        ]);

        header("Location: http://localhost/GeldManagmentApp/?bedrag=" . $bedrag . "&type=" . $type);
        exit();
    } else {
        echo '<div class="alert alert-danger" role="alert">Vul alle velden in!</div>';
    }
}

function verwijderDetailUitgaven($id)
{
    $pdo = conn();

    try {
        $queryFetch = $pdo->prepare('SELECT bedrag FROM uitgavenlijst WHERE id = :id');
        $queryFetch->execute(['id' => $id]);
        $bedragMin = $queryFetch->fetch();

        if (!$bedragMin) {
            echo '<div class="alert alert-danger" role="alert">Item niet gevonden!</div>';
            return;
        }

        $result = $bedragMin['bedrag'];

        $queryUpdate = $pdo->prepare('UPDATE bedragen SET bedrag = bedrag + :bedrag');
        $queryUpdate->execute(['bedrag' => $result]);
        $queryDelete = $pdo->prepare('DELETE FROM uitgavenlijst WHERE id = :id');
        $queryDelete->execute(['id' => $id]);

        echo '<div class="alert alert-success" role="alert">Succesvol verwijderd!</div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "http://localhost/GeldManagmentApp/";
                }, 1500)
              </script>';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger" role="alert">Er is een fout opgetreden: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

function totaleInkomsten()
{
    try {
        $pdo = conn();
        $query = "SELECT SUM(bedrag) as totaal FROM inkomenlijst";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['totaal'] !== null) {
            return "€" . number_format($result['totaal'], 2, ',', '.');
        } else {
            return "Nog geen inkomsten.";
        }
    } catch (PDOException $e) {
        return "Fout bij ophalen van inkomsten: " . $e->getMessage();
    }
}

function totaleUitgaven()
{
    try {
        $pdo = conn();
        $query = "SELECT SUM(bedrag) as totaal FROM uitgavenlijst";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['totaal'] !== null) {
            return "€" . number_format($result['totaal'], 2, ',', '.');
        } else {
            return "Nog geen uitgaven.";
        }
    } catch (PDOException $e) {
        return "Fout bij ophalen van uitgaven: " . $e->getMessage();
    }
}
function biljet500() {
    $pdo = conn();
    $query = "SELECT SUM(aantalBiljettenInkomen) as total FROM inkomenlijst WHERE soort_biljetten = 500";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}
function biljet200() {
    $pdo = conn();
    $query = "SELECT SUM(aantalBiljettenInkomen) as total FROM inkomenlijst WHERE soort_biljetten = 200";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}
function biljet100() {
    $pdo = conn();
    $query = "SELECT SUM(aantalBiljettenInkomen) as total FROM inkomenlijst WHERE soort_biljetten = 100";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}
function biljet50() {
    $pdo = conn();
    $query = "SELECT SUM(aantalBiljettenInkomen) as total FROM inkomenlijst WHERE soort_biljetten = 50";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}
function biljet20() {
    $pdo = conn();
    $query = "SELECT SUM(aantalBiljettenInkomen) as total FROM inkomenlijst WHERE soort_biljetten = 20";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}
function biljet10() {
    $pdo = conn();
    $query = "SELECT SUM(aantalBiljettenInkomen) as total FROM inkomenlijst WHERE soort_biljetten = 10";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}
function biljet5() {
    $pdo = conn();
    $query = "SELECT SUM(aantalBiljettenInkomen) as total FROM inkomenlijst WHERE soort_biljetten = 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}
setupDatabase();
