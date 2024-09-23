<?php
function conn() {
    $host = 'localhost';
    $port = '3306';
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
        $resetQuery = 'UPDATE BEDRAGEN SET bedrag = 0, spaardoel = 0 LIMIT 1';
        $pdo->exec($resetQuery);
        
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
function darkMode() {
    $bg = '<style>.main-content{background-color:black}</style>';
    return $bg;
}