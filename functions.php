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
    $pdo = conn();
    $query = 'INSERT INTO inkomenlijst (datum, bedrag) VALUES (:datum, :bedrag)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedragInvoeren]);
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
    foreach ($inkomen_lijst as $item) {
        echo '<div class="item">';
        echo '<a href="transacties\detailInkomenLijst.php?">test</a>';// moet nog een verbeterde rationele database maken voordat dit gaat lukken
        echo '<p>€' . number_format($item['bedrag'], 2).'</p>';
        echo '<p>' . htmlspecialchars($item['datum']) . '</p>';
        echo '</div>';
    }
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
    $pdo = conn();
    $query = 'INSERT INTO uitgavenlijst (datum, bedrag) VALUES (:datum, :bedrag)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedragInvoeren]);
}
function getUitgavenLijst($condition, $limit, $offset)
{
    $pdo = conn();
    $query = "SELECT bedrag, datum FROM uitgavenlijst WHERE $condition LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
function displayUitgavenLijst($uitgaven_lijst)
{
    foreach ($uitgaven_lijst as $item) {
        echo '<div class="item">';
        echo '<p>' . $item['datum'] . '</p>';
        echo '<p>-' . number_format($item['bedrag'], 2) . '</p>';
        echo '</div>';
    }
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
        echo "<div class='pagination'>";
        if ($current_page > 1) {
            $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
            echo "<a href='?inkomen_page=" . ($current_page - 1) . "&mode=$mode' class='prev'><img width='20px' src='foto\arrow_back_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' alt='foto'></a>";
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
            $active_class = ($i == $current_page) ? 'active' : '';
            echo "<a href='?inkomen_page=$i&mode=$mode' class='$active_class'>$i</a> ";
        }
        if ($current_page < $total_pages) {
            $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
            echo "<a href='?inkomen_page=" . ($current_page + 1) . "&mode=$mode' class='next'><img width='20px' src='foto\arrow_right_alt_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' alt='foto'></a>";
        }

        echo "</div>";
    }
}

function displayUitgavenPagination($current_page, $total_pages)
{
    if ($total_pages > 1) {
        echo "<div class='pagination'>";
        if ($current_page > 1) {
            $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
            echo "<a href='?uitgaven_page=" . ($current_page - 1) . "&mode=$mode' class='prev'><img width='20px' src='foto\arrow_back_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' alt='foto'></a>";
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
            $active_class = ($i == $current_page) ? 'active' : '';
            echo "<a href='?uitgaven_page=$i&mode=$mode' class='$active_class'>$i</a> ";
        }
        if ($current_page < $total_pages) {
            $mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : '';
            echo "<a href='?uitgaven_page=" . ($current_page + 1) . "&mode=$mode' class='next'><img width='20px' src='./foto/arrow_right_alt_24dp_5F6368_FILL0_wght400_GRAD0_opsz24.png' alt='foto'></a>";
        }

        echo "</div>";
    }
}


setupDatabase();
