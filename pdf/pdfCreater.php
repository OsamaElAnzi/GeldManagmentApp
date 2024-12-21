<?php
require_once __DIR__ . '/../vendor/autoload.php';
include './functions.php';
use Dompdf\Dompdf;

// Instantiate and use the dompdf class
$dompdf = new Dompdf();

$html = '
<style>
    table {
        font-family: arial;
        width: 100%;
        border-collapse: collapse;
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
<h1>Welcome to the PDF Report</h1>
<table>
    <tr>
        <th>Name</th>
        <th>Age</th>
        <th>Country</th>
    </tr>
    <tr>
        <td>Jane Doe</td>
        <td>29</td>
        <td>USA</td>
    </tr>
    <tr>
        <td>John Smith</td>
        <td>35</td>
        <td>UK</td>
    </tr>
</table>
';

// Load HTML content
$dompdf->loadHtml($html);

// Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF
$dompdf->stream('BACK-UP_INFO-VERMOGEN.pdf');
?>
