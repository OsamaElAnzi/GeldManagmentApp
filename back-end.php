<?php

setupDatabase();

$spaardoel = getSpaarDoel();
$bedrag = getBedrag();
$datum = getDatum();
$condition = true;

if (isset($_GET['mode'])) {
    $_SESSION['mode'] = htmlspecialchars($_GET['mode']);
}

$mode = isset($_SESSION['mode']) ? $_SESSION['mode'] : 'day';

$darkModeEnabled = ($mode === 'dark');
$dayModeEnabled = ($mode === 'day');

if (isset($_GET['bedrag']) && isset($_GET['type'])) {
    $bedragNew = $_GET['bedrag'];
    $type = $_GET['type'];
    $bedragOutput = doelNaAanpassing($bedragNew, $type);
}
if (isset($_GET['bedragInvoeren']) && is_numeric($_GET['bedragInvoeren'])) {
    if (isset($_GET['INKOMEN'])) {
        $bedragInvoeren = $_GET['bedragInvoeren'];
        $soort_biljetten = $_GET['soort_biljetten'];
        if ((int)$bedragInvoeren >= (int) $soort_biljetten) {
            $bedrag = doelAanpassen($bedragInvoeren, 'INKOMEN');
            voegToeAanInkomenLijst($datum, $bedragInvoeren, $soort_biljetten);
        }
        header("Location:http://localhost/GeldManagmentApp/");
    } elseif (isset($_GET['UITGAVEN'])) {
        $bedragInvoeren = $_GET['bedragInvoeren'];
        $soort_biljetten = $_GET['soort_biljetten'];
        if ((int)$bedragInvoeren >= (int) $soort_biljetten) {
            $bedrag = doelAanpassen($bedragInvoeren, 'INKOMEN');
            voegToeAanUitgavenLijst($datum, $bedragInvoeren, $soort_biljetten);
        }
        header("Location:http://localhost/GeldManagmentApp/");
    }
} elseif (isset($_GET['SPAARDOEL']) && is_numeric($_GET['SPAARDOEL'])) {
    $spaardoel = (float) $_GET['SPAARDOEL'];
    updateSpaarDoel($spaardoel);
} elseif (isset($_GET['RESET-KNOP'])) {
    $bedrag = resetDoel();
    $condition = false;
    header("Location:http://localhost/GeldManagmentApp/");
}

$progress = ($spaardoel > 0) ? min(($bedrag / $spaardoel) * 100, 100) : 0;

$inkomen_page = isset($_GET['inkomen_page']) ? (int) $_GET['inkomen_page'] : 1;
$inkomen_limit = 11;
$inkomen_offset = ($inkomen_page - 1) * $inkomen_limit;
$inkomen_condition = '1';
$inkomen_total = countInkomenRows($inkomen_condition);
$inkomen_total_pages = ceil($inkomen_total / $inkomen_limit);
$inkomen_lijst = getInkomenLijst($inkomen_condition, $inkomen_limit, $inkomen_offset);

$uitgaven_page = isset($_GET['uitgaven_page']) ? (int) $_GET['uitgaven_page'] : 1;
$uitgaven_limit = 11;
$uitgaven_offset = ($uitgaven_page - 1) * $uitgaven_limit;
$uitgaven_condition = '1';
$uitgaven_total = countUitgavenRows($uitgaven_condition);
$uitgaven_total_pages = ceil($uitgaven_total / $uitgaven_limit);
$uitgaven_lijst = getUitgavenLijst($uitgaven_condition, $uitgaven_limit, $uitgaven_offset);
?>