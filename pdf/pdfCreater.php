<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

use Dompdf\Dompdf;

// Load the image as base64
$imagePath = __DIR__ . '/../foto/profits.png';
$imageData = file_exists($imagePath) ? base64_encode(file_get_contents($imagePath)) : '';
$imageBase64 = $imageData ? 'data:image/png;base64,' . $imageData : '';
$moneyPath = __DIR__ . '/../foto/money.png';
$moneyData = file_exists($moneyPath) ? base64_encode(file_get_contents($moneyPath)) : '';

$moneyBase64 = $moneyData? 'data:image/png;base64,'. $moneyData : '';

// Data: function to variables
$vermogen = getBedrag();
$datum = getDatum();
$nogTeGaan = nogTeGaanVoorDoelBehaling();
$spaardoel = getSpaardoel();
$datum = getDatum();
//500EUR
$EUR500_Aantal = biljet500();
$EUR500_Bedrag = bedragBiljet500();
//200EUR
$EUR200_Aantal = biljet200();
$EUR200_Bedrag = bedragBiljet200();
//100EUR
$EUR100_Aantal = biljet100();
$EUR100_Bedrag = bedragBiljet100();
//50EUR
$EUR50_Aantal = biljet50();
$EUR50_Bedrag = bedragBiljet50();
//20EUR
$EUR20_Aantal = biljet20();
$EUR20_Bedrag = bedragBiljet20();
//10EUR
$EUR10_Aantal = biljet10();
$EUR10_Bedrag = bedragBiljet10();
//5EUR
$EUR5_Aantal = biljet5();
$EUR5_Bedrag = bedragBiljet5();
// ALLE TRANSACIES
$transacties = heleTranactieLijst();
$dompdf = new Dompdf();
$transacties = heleTranactieLijst();

$html = <<<HTML
<style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
    .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding: 0 10px; }
    .logo { width: 50px; }
    .title { font-size: 24px; margin: 0; text-align: left; }
    table { font-family: Arial, sans-serif; width: 100%; border-collapse: collapse; margin-top: 20px; }
    td, th { border: 1px solid black; text-align: left; padding: 8px; }
    tr:nth-child(even) { background-color: #f2f2f2; }
</style>
<div class="header">
    <h1 class="title">Welcome to the PDF Report</h1>
    <img src="{$imageBase64}" alt="Logo" class="logo">
</div>
<h2>Datum: {$datum}</h2>
<h2>Vermogen: €{$vermogen},-</h2>
<h2>Spaardoel: €{$spaardoel},-</h2>
<h2>Nog te gaan: €{$nogTeGaan},-</h2>
<table>
    <h2>Overzicht van de biljetten</h2>
    <tr>
        <th><img src="{$moneyBase64}" width="20px" alt="Logo" class="logo"></th>
        <th>Aantal</th>
        <th>Bedrag</th>
    </tr>
    <tr><td>€500</td><td>{$EUR500_Aantal}</td><td>{$EUR500_Bedrag}</td></tr>
    <tr><td>€200</td><td>{$EUR200_Aantal}</td><td>{$EUR200_Bedrag}</td></tr>
    <tr><td>€100</td><td>{$EUR100_Aantal}</td><td>{$EUR100_Bedrag}</td></tr>
    <tr><td>€50</td><td>{$EUR50_Aantal}</td><td>{$EUR50_Bedrag}</td></tr>
    <tr><td>€20</td><td>{$EUR20_Aantal}</td><td>{$EUR20_Bedrag}</td></tr>
    <tr><td>€10</td><td>{$EUR10_Aantal}</td><td>{$EUR10_Bedrag}</td></tr>
    <tr><td>€5</td><td>{$EUR5_Aantal}</td><td>{$EUR5_Bedrag}</td></tr>
</table>
<h2>Transacties</h2>
<table>
    <tr>
        <th>Datum</th>
        <th>Bedrag</th>
        <th>Soort Biljetten</th>
        <th>Aantal Biljetten Inkomen</th>
        <th>Aantal Biljetten Uitgaven</th>
    </tr>
    {$transacties}
</table>
HTML;

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'BACK-UP_INFO-VERMOGEN_' . date('Y-m-d-m-s') . '.pdf';
$dompdf->stream($filename);
?>
