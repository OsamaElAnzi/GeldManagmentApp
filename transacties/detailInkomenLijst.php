<?php

if (!file_exists('C:\xampp\htdocs\GeldManagmentApp\functions.php')) {
    echo 'Het bestand functions.php is niet gevonden.';
    exit;
}

require 'C:\xampp\htdocs\GeldManagmentApp\functions.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    echo '<div class="alert alert-danger" role="alert">Item niet gevonden</div>';
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
<body style="background-color: #f4f4f9; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <div class="container">
        <div class="mb-4 text-center">
            <a href="http://localhost/GeldManagmentApp/" class="btn btn-danger">Terug</a>
        </div>

        <div class="card p-4 shadow-sm">
            <?php
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                detailInkomen($id);
            } else {
                echo '<div class="alert alert-danger" role="alert">Geen ID opgegeven!</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>

