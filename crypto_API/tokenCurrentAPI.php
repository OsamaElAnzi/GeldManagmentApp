<?php

$api_url = "https://api.coingecko.com/api/v3/coins/dogecoin/market_chart?vs_currency=eur&days=1";
$response = file_get_contents($api_url);
if (!$response) {
    die(json_encode(["error" => "Unable to fetch data from API."]));
}
$data = json_decode($response, true);
$prices = $data['prices'];
$time = [];
$price = [];
foreach ($prices as $entry) {
    $time[] = date('H:i:s', $entry[0] / 1000);
    $price[] = $entry[1];
}
$time_json = json_encode($time);
$price_json = json_encode($price);
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
        <h2 class="text-center mb-4">Tokens Prices in EUR (Last 24 Hours)</h2>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-title">
                        <h3>Dogecoin</h3>
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
                    borderWidth: 1,
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
