<?php
// API URL instellen
$api_url = "https://api.coingecko.com/api/v3/coins/dogecoin/market_chart?vs_currency=eur&days=1";

// Ophalen van de API-data
$response = file_get_contents($api_url);
if (!$response) {
    die(json_encode(["error" => "Unable to fetch data from API."]));
}

// Decoderen van de JSON-response
$data = json_decode($response, true);

// Initialiseren van tijd en prijs arrays
$prices = $data['prices'];
$time = [];
$price = [];

// Verwerken van de data
foreach ($prices as $entry) {
    $time[] = date('H:i:s', $entry[0] / 1000); // Tijd in uren:minuten:seconden
    $price[] = $entry[1];                      // Prijswaarde
}

// Encodeer arrays als JSON voor gebruik in JavaScript
$time_json = json_encode($time);
$price_json = json_encode($price);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dogecoin Price Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Dogecoin Price in EUR (Last 24 Hours)</h2>
    <canvas id="myChart" width="100" height="100"></canvas>

    <script>
        // Data ontvangen van PHP
        const timeData = <?php echo $time_json; ?>; // Tijdstippen uit PHP
        const priceData = <?php echo $price_json; ?>; // Prijzen uit PHP

        // Tekenen van de grafiek
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'line', // Lijn-grafiek
            data: {
                labels: timeData, // Tijd op de x-as
                datasets: [{
                    label: 'Dogecoin Price (EUR)',
                    data: priceData, // Prijzen op de y-as
                    borderColor: 'rgba(75, 192, 192, 1)', // Lijnkleur
                    borderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
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
</body>
</html>
