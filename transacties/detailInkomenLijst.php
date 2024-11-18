<?php

if (!file_exists('C:\xampp\htdocs\GeldManagmentApp\functions.php')) {
    die('Het bestand functions.php is niet gevonden.');
} else {
    require 'C:\xampp\htdocs\GeldManagmentApp\functions.php';
    $id = $_GET['id'];
    detailInkomen($id);
}




