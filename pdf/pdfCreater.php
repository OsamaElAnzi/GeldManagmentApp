<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

use Dompdf\Dompdf;

// Load the image as base64
$imagePath = __DIR__ . '/../foto/profits.png';
$imageData = file_exists($imagePath) ? base64_encode(file_get_contents($imagePath)) : '';
$imageBase64 = $imageData ? 'data:image/png;base64,' . $imageData : '';

// Data: function to variables
$vermogen = getBedrag();
$datum = getDatum();
$nogTeGaan = nogTeGaanVoorDoelBehaling();
$spaardoel = getSpaardoel();

$dompdf = new Dompdf();

$html = <<<HTML
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }
    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding: 0 10px;
    }
    .logo {
        width: 50px;
    }
    .title {
        font-size: 24px;
        margin: 0;
        text-align: left;
    }
    table {
        font-family: Arial, sans-serif;
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    td, th {
        border: 1px solid black;
        text-align: left;
        padding: 8px;
    }
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
</style>
<div class="header">
    <h1 class="title">Welcome to the PDF Report</h1>
    <img src="{$imageBase64}" alt="Logo" class="logo">
</div>
<h2>Vermogen: €{$vermogen},-</h2>
<h2>Spaardoel: €{$spaardoel},-</h2>
<h2>Nog te gaan: €{$nogTeGaan},-</h2>
<table>
    <h1>inkomen</h1>
    <tr>
        <th>Datum</th>
        <th>Bedrag</th>
        <th>Type</th>
    </tr>
</table>
HTML;

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('BACK-UP_INFO-VERMOGEN.pdf');
?>
