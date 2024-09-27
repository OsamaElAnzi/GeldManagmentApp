<?php
function conn() {
    $host = 'localhost';
    $port = '3306  ';
    $dbname = 'REKENING'; 
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset;port=$port;";
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    return new PDO($dsn, $user, $pass, $opt);
}
conn();

function setupDatabase() {
    $pdo = conn();
    
    $createTable = 'CREATE TABLE IF NOT EXISTS BEDRAGEN (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bedrag DECIMAL(10, 2),
        spaardoel DECIMAL(10, 2)
    )';
    $pdo->exec($createTable);

    $query = $pdo->query('SELECT COUNT(*) as count FROM BEDRAGEN');
    $row = $query->fetch();

    if ($row['count'] == 0) {
        $insertQuery = 'INSERT INTO BEDRAGEN (bedrag, spaardoel) VALUES (0, 0)';
        $pdo->exec($insertQuery);
    }
}
function addSpaardoelColumnIfNotExists() {
    $pdo = conn();
    
    $checkColumn = $pdo->query("SHOW COLUMNS FROM BEDRAGEN LIKE 'spaardoel'");
    $columnExists = $checkColumn->fetch();
    
    if (!$columnExists) {
        $alterTable = "ALTER TABLE BEDRAGEN ADD COLUMN spaardoel DECIMAL(10, 2)";
        $pdo->exec($alterTable);
    }
}
addSpaardoelColumnIfNotExists();
function getBedrag() {
    $pdo = conn();
    $query = $pdo->query('SELECT bedrag FROM BEDRAGEN LIMIT 1');
    $result = $query->fetch();
    return $result ? $result['bedrag'] : 0;
}
function getSpaarDoel() {
    $pdo = conn();
    $query = $pdo->query('SELECT spaardoel FROM BEDRAGEN LIMIT 1');
    $result = $query->fetch();
    return $result? $result['spaardoel'] : 0;
}
function getDatum() {
    $pdo = conn();
    $query = $pdo->query('SELECT CURDATE() AS datum');
    $result = $query->fetch();
    return $result['datum'];
}

function doelAanpassen($bedragInvoeren, $type) {
    $pdo = conn();
    $huidigBedrag = getBedrag();

    if ($type === 'INKOMEN') {
        $nieuwBedrag = $huidigBedrag + (float) $bedragInvoeren;

    } elseif ($type === 'UITGAVEN') {
        $nieuwBedrag = $huidigBedrag - (float) $bedragInvoeren;
    }
    $updateQuery = 'UPDATE BEDRAGEN SET bedrag = :bedrag LIMIT 1';
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['bedrag' => $nieuwBedrag]);

    return $nieuwBedrag;
}
function updateSpaarDoel($spaardoel) {
    $pdo = conn();
    $updateQuery = 'UPDATE BEDRAGEN SET spaardoel = :spaardoel LIMIT 1';
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['spaardoel' => $spaardoel]);
}


function resetDoel() {
    if (isset($_GET['RESET-KNOP'])) { 
        $pdo = conn();
        $resetQueryBedragen = 'UPDATE BEDRAGEN SET bedrag = 0, spaardoel = 0 ';
        $resetQueryInkomenLijst = 'DELETE FROM inkomenlijst'; 
        $pdo->exec($resetQueryInkomenLijst);
        $pdo->exec($resetQueryBedragen);
        
        return 0; 
    }
    return getBedrag();
}
function nogTeGaanVoorDoelBehaling() {
    $huidigBedrag = getBedrag();
    $spaardoel = getSpaarDoel();
    $verschil = $spaardoel - $huidigBedrag;
    return $verschil;
}
function maakTableInkomenLijst() {
    $pdo = conn();
    $query = 'CREATE TABLE IF NOT EXISTS inkomenlijst
    (
        id INT AUTO_INCREMENT PRIMARY KEY,
        datum DATETIME NOT NULL,
        bedrag DECIMAL(10, 2)
    )';
    $pdo->exec($query);
}
maakTableInkomenLijst();

function voegToeAanInkomenLijst($datum, $bedrag) {
    $pdo = conn();
    $query = 'INSERT INTO inkomenlijst (datum, bedrag) VALUES (:datum, :bedrag)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedrag]);
}
function getInkomenLijst() {
    $pdo = conn();
    $query = 'SELECT * FROM inkomenlijst ORDER BY datum DESC';
    $stmt = $pdo->query($query);
    return $stmt->fetchAll();
}
function displayInkomenLijst($condition) {
    echo '<div class="lijstInLijst">'; 
    if ($condition) {  // The if block is properly opened here
        foreach (getInkomenLijst() as $item) {
            echo '<div class="item">'; // Wrap each item in its own div
            echo '<h2>' . $item['datum'] . '</h2>';
            echo '<h2>+' . $item['bedrag'] . '</h2>';
            echo '</div>'; // Close the item div
        }
    } else {
        echo '<p>No income list to display.</p>';
    }
    echo '</div>';  // Close the outer div
}






