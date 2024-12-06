<?php

$apiUrl = 'https://api.bitvavo.com/v2';

// Huidige tijdstempel in milliseconden
$timestamp = round(microtime(true) * 1000);

// Endpoint om prijsgegevens op te vragen voor Dogecoin
$endpoint = '/ticker/price';
$symbol = 'DOGE-EUR'; // Specificeer Dogecoin in euro
$endpointWithQuery = $endpoint . '?market=' . $symbol;

// Handtekening maken
$message = $timestamp . 'GET' . $endpointWithQuery;

// cURL initialiseren
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl . $endpointWithQuery,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Bitvavo-Access-Timestamp: ' . $timestamp,
        'Content-Type: application/json'
    ],
]);

// Uitvoeren en antwoord ophalen
$response = curl_exec($curl);
curl_close($curl);

if (!$response) {
    die('API-verzoek mislukt. Controleer je API-sleutel of netwerkverbinding.');
}

$data = json_decode($response, true);

// Controleer of gegevens correct zijn opgehaald
if (!isset($data['price'])) {
    die('Geen prijsgegevens gevonden voor Dogecoin.');
}

// Tijdreeks en prijs genereren (mock-data in dit voorbeeld)
$timeSeries = [];
$priceSeries = [];
$currentPrice = (float)$data['price'];

for ($i = 23; $i >= 0; $i--) {
    $timeSeries[] = date('H:i', strtotime("-$i hours"));
    $priceSeries[] = $currentPrice * (1 + (rand(-5, 5) / 100)); // Simuleer schommelingen
}

// Converteer arrays naar JSON
$time_json = json_encode($timeSeries);
$price_json = json_encode($priceSeries);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dogecoin Price Chart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <a href="./cryptoWallet.php" class="btn btn-primary">Terug</a>
        <h2 class="text-center mb-4">Dogecoin Prices in EUR (Last 24 Hours)</h2>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-title d-flex justify-content-center pt-3">
                        <h3>Dogecoin <strong>€<?php echo number_format($currentPrice, 2); ?></strong></h3>
                    </div>
                    <div class="card-body">
                        <canvas id="DogeCoin"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const timeData = <?php echo $time_json; ?>;
        const priceData = <?php echo $price_json; ?>;

        const ctx = document.getElementById('DogeCoin').getContext('2d');
        const DogeCoin = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeData,
                datasets: [{
                    label: 'Dogecoin Price (EUR)',
                    data: priceData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.4, // Maak de lijn vloeiender
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Price (EUR)'
                        },
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
