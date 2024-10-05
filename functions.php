<?php

// Database connection function
function conn() {
    $host = 'localhost';
    $port = '3306';
    $dbname = 'REKENING'; 
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

// Setup the database and create necessary tables
function setupDatabase() {
    $pdo = conn();
    
    // Create BEDRAGEN table
    $createBedragenTable = 'CREATE TABLE IF NOT EXISTS BEDRAGEN (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bedrag DECIMAL(10, 2) NOT NULL DEFAULT 0,
        spaardoel DECIMAL(10, 2) NOT NULL DEFAULT 0
    )';
    $pdo->exec($createBedragenTable);

    // Check if there's no entry and insert default
    $query = $pdo->query('SELECT COUNT(*) as count FROM BEDRAGEN');
    $row = $query->fetch();

    if ($row['count'] == 0) {
        $insertQuery = 'INSERT INTO BEDRAGEN (bedrag, spaardoel) VALUES (0, 0)';
        $pdo->exec($insertQuery);
    }

    // Create INKOMEN table
    $createInkomenTable = 'CREATE TABLE IF NOT EXISTS inkomen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bedrag DECIMAL(10, 2) NOT NULL,
        datum DATE NOT NULL
    )';
    $pdo->exec($createInkomenTable);

    // Create UITGAVEN table
    $createUitgavenTable = 'CREATE TABLE IF NOT EXISTS uitgavenlijst (
        id INT AUTO_INCREMENT PRIMARY KEY,
        datum DATE NOT NULL,
        bedrag DECIMAL(10, 2) NOT NULL
    )';
    $pdo->exec($createUitgavenTable);
}

// Get the current balance
function getBedrag() {
    $pdo = conn();
    $query = $pdo->query('SELECT bedrag FROM BEDRAGEN LIMIT 1');
    $result = $query->fetch();
    return $result ? (float)$result['bedrag'] : 0;
}

// Get the savings goal
function getSpaarDoel() {
    $pdo = conn();
    $query = $pdo->query('SELECT spaardoel FROM BEDRAGEN LIMIT 1');
    $result = $query->fetch();
    return $result ? (float)$result['spaardoel'] : 0;
}

// Get the current date
function getDatum() {
    $pdo = conn();
    $query = $pdo->query('SELECT CURDATE() AS datum');
    $result = $query->fetch();
    return $result['datum'];
}

// Adjust the current balance based on income or expenses
function doelAanpassen($bedragInvoeren, $type) {
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

// Update the savings goal
function updateSpaarDoel($spaardoel) {
    $pdo = conn();
    $updateQuery = 'UPDATE BEDRAGEN SET spaardoel = :spaardoel LIMIT 1';
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['spaardoel' => $spaardoel]);
}

// Reset all values to zero
function resetDoel() {
    if (isset($_GET['RESET-KNOP'])) { 
        $pdo = conn();
        $resetQueryBedragen = 'UPDATE BEDRAGEN SET bedrag = 0, spaardoel = 0';
        $resetQueryInkomenLijst = 'DELETE FROM inkomen';
        $resetQueryUitgavenLijst = 'DELETE FROM uitgavenlijst'; 
        $pdo->exec($resetQueryUitgavenLijst);
        $pdo->exec($resetQueryInkomenLijst);
        $pdo->exec($resetQueryBedragen);
        
        return 0; 
    }
    return getBedrag();
}

// Calculate the remaining amount to reach the savings goal
function nogTeGaanVoorDoelBehaling() {
    $huidigBedrag = getBedrag();
    $spaardoel = getSpaarDoel();
    return $spaardoel - $huidigBedrag;
}

// Add to the income list
function voegToeAanInkomenLijst($datum, $bedragInvoeren) {
    $pdo = conn();
    $query = 'INSERT INTO inkomen (datum, bedrag) VALUES (:datum, :bedrag)';
    $stmt = $pdo->prepare($query);
    $stmt->execute(['datum' => $datum, 'bedrag' => $bedragInvoeren]);
}

// Get the income list with pagination
function getInkomenLijst($condition, $limit, $offset) {
    $pdo = conn();
    $query = "SELECT bedrag, datum FROM inkomen WHERE $condition LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Display the income list
function displayInkomenLijst($inkomen_lijst) {
    foreach ($inkomen_lijst as $item) {
        echo '<div class="item">';
        echo '<p>€' . number_format($item['bedrag'], 2) . '</p>';
        echo '<p>' . htmlspecialchars($item['datum']) . '</p>';
        echo '</div>';
    }
}

// Get the total number of income rows
function countInkomenRows($condition) {
    $pdo = conn();
    $sql = "SELECT COUNT(*) as total FROM inkomen WHERE $condition";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['total'];
}

// Display pagination controls
function displayPagination($current_page, $total_pages) {
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

// Initialize the database
setupDatabase();

