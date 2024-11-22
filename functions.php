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

    $createBedragenTable = 'CREATE TABLE IF NOT EXISTS BEDRAGEN (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bedrag DECIMAL(15, 2) NOT NULL DEFAULT 0,
        spaardoel DECIMAL(15, 2) NOT NULL DEFAULT 0
    )';
    $pdo->exec($createBedragenTable);

    $query = $pdo->query('SELECT COUNT(*) as count FROM BEDRAGEN');
    $row = $query->fetch();

    if ($row['count'] == 0) {
        $insertQuery = 'INSERT INTO BEDRAGEN (bedrag, spaardoel) VALUES (0, 0)';
        $pdo->exec($insertQuery);
    }
    $createInkomenTable = 'CREATE TABLE IF NOT EXISTS inkomenlijst (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bedrag DECIMAL(10, 2) NOT NULL,
        datum DATE NOT NULL
    )';
    $pdo->exec($createInkomenTable);

    $createUitgavenTable = 'CREATE TABLE IF NOT EXISTS uitgavenlijst (
        id INT AUTO_INCREMENT PRIMARY KEY,
        datum DATE NOT NULL,
        bedrag DECIMAL(10, 2) NOT NULL
    )';
    $pdo->exec($createUitgavenTable);
}

function getBedrag()
{
    $pdo = conn();
    $query = $pdo->query('SELECT bedrag FROM BEDRAGEN LIMIT 1');
    $result = $query->fetch();
    return $result ? (float)$result['bedrag'] : 0;
}

function getSpaarDoel()
{
    $pdo = conn();
    $query = $pdo->query('SELECT spaardoel FROM BEDRAGEN LIMIT 1');
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

    $updateQuery = 'UPDATE BEDRAGEN SET bedrag = :bedrag LIMIT 1';
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['bedrag' => $nieuwBedrag]);

    return $nieuwBedrag;
}
function doelNaAanpassing($bedragNew, $type)
{
    $pdo = conn();
    $huidigBedrag = getBedrag();
    if ($type === 'NEW-INKOMEN') {
        $huidigBedrag =+ (float)$bedragNew;
        $updateQuery = 'UPDATE BEDRAGEN SET bedrag = :bedrag LIMIT 1';
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute(['bedrag' => $huidigBedrag]);
        header("Location:http://localhost/GeldManagmentApp/");
    } elseif ($type === 'NEW-UITGAVEN') {
        $huidigBedrag =- $huidigBedrag - (float)$bedragNew;
        $updateQuery = 'UPDATE BEDRAGEN SET bedrag = :bedrag LIMIT 1';
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute(['bedrag' => $huidigBedrag]);
        header("Location:http://localhost/GeldManagmentApp/");
    }
}
function updateSpaarDoel($spaardoel)
{
    $pdo = conn();
    $updateQuery = 'UPDATE BEDRAGEN SET spaardoel = :spaardoel LIMIT 1';
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['spaardoel' => $spaardoel]);
}
function resetDoel()
{
    if (isset($_GET['RESET-KNOP'])) {
        $pdo = conn();
        $resetQueryBedragen = 'UPDATE BEDRAGEN SET bedrag = 0, spaardoel = 0';
        $resetQueryInkomenLijst = 'DELETE FROM inkomenlijst';
        $resetQueryUitgavenLijst = 'DELETE FROM uitgavenlijst';
        $pdo->exec($resetQueryUitgavenLijst);
        $pdo->exec($resetQueryInkomenLijst);
        $pdo->exec($resetQueryBedragen);

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

function voegToeAanInkomenLijst($datum, $bedragInvoeren)
{
    $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
    $pdo = conn();
    $query = 'INSERT INTO inkomenlijst (datum, bedrag) VALUES (:datum, :bedrag)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedragInvoeren]);
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
function voegToeAanUitgavenLijst($datum, $bedragInvoeren)
{
    $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
    $pdo = conn();
    $query = 'INSERT INTO uitgavenlijst (datum, bedrag) VALUES (:datum, :bedrag)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedragInvoeren]);
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

    if ($item) {
        echo <<<HTML
<body class="d-flex justify-content-center align-items-center flex-column" style="min-height: 100vh; background-color: #f4f4f9;">
    <h1>Detailpagina inkomen</h1><br />
    <form style="width: 22rem;" action="" method="post">
        <input type="hidden" name="id" value="{$item['id']}">

        <div class="form-outline mb-4">
            <input type="text" id="bedrag" name="bedrag" class="form-control" value="{$item['bedrag']}" required />
            <label class="form-label" for="bedrag">Bedrag</label>
        </div>

        <div class="form-outline mb-4">
            <input type="text" id="datum" name="datum" class="form-control" value="{$item['datum']}" required />
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
    } else {
        echo '<div class="alert alert-danger" role="alert">Item niet gevonden</div>';
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

        $query = "UPDATE inkomenlijst SET bedrag = :bedrag, datum = :datum WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'bedrag' => $bedrag,
            'datum' => $datum,
            'id' => $id
        ]);
        header("Location: http://localhost/GeldManagmentApp/?bedrag=" . $bedrag);
        echo '<div class="alert alert-success" role="alert">Inkomen succesvol aangepast!</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">Vul alle velden in!</div>';
    }
}

function verwijderDetailInkomen($id)
{
    $pdo = conn();

    $query = "DELETE FROM inkomenlijst WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);

    header('Location: http://localhost/GeldManagmentApp/');
    exit();
}
//voor uitgaven
function detailUitgaven($id)
{
    $pdo = conn();
    $query = "SELECT * FROM uitgavenlijst WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();

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
            <input type="text" id="datum" name="datum" class="form-control" value="{$item['datum']}" required />
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

        $query = "UPDATE uitgavenlijst SET bedrag = :bedrag, datum = :datum WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'bedrag' => $bedrag,
            'datum' => $datum,
            'id' => $id
        ]);
        header("Location: http://localhost/GeldManagmentApp/?bedrag=" . $bedrag);
        exit();
    } else {
        echo '<div class="alert alert-danger" role="alert">Vul alle velden in!</div>';
    }
}
function verwijderDetailUitgaven($id)
{
    $pdo = conn();

    $query = "DELETE FROM uitgavenlijst WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);

    header('Location: http://localhost/GeldManagmentApp/');
    exit();
}

setupDatabase();
