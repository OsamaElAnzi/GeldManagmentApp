<?php
$api_url = "https://api.coingecko.com/api/v3/coins/dogecoin/market_chart?vs_currency=eur&days=1";
$response = file_get_contents($api_url);
$data = json_decode($response, true);

$prices = $data['prices'];
$time = [];
$price = [];

foreach ($prices as $entry) {
    $time[] = date('H:i:s', $entry[0] / 1000);  // Convert timestamp to readable time
    $price[] = $entry[1];
}

echo json_encode(['time' => $time, 'price' => $price]);
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
    <h2>Dogecoin Price in EUR Over the Last 24 Hours</h2>
    <canvas id="myChart" width="400" height="400"></canvas>

    <script>
        // Fetch the Dogecoin price data from your PHP script
        fetch('bitcoinCurrentAPI.php')  // Ensure this matches the actual file name
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('myChart').getContext('2d');
                const myChart = new Chart(ctx, {
                    type: 'line',  // Line chart for time series
                    data: {
                        labels: data.time,  // Time labels
                        datasets: [{
                            label: 'Dogecoin Price (EUR)',
                            data: data.price,  // Price data
                            borderColor: 'rgba(75, 192, 192, 1)', // Line color
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
            })
            .catch(error => console.log('Error fetching data:', error));
    </script>
</body>
</html>
