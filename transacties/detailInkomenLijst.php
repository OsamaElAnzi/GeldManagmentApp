<?php

// Controleer of het bestand functions.php bestaat
if (!file_exists('C:\xampp\htdocs\GeldManagmentApp\functions.php')) {
    echo 'Het bestand functions.php is niet gevonden.';
    exit; // Beëindig de scriptuitvoering netjes
} 

require 'C:\xampp\htdocs\GeldManagmentApp\functions.php';

// Controleer of de ID-parameter aanwezig is en valideer deze
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // Converteer naar integer om SQL-injectie te voorkomen
} else {
    echo 'Geen geldig ID opgegeven.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Inkomen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
    <?php detailInkomen($id) ?>
</html>
