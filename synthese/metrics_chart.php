<?php
$node = isset($_GET['node']) ? $_GET['node'] : 'esp32-1';
$db_path = __DIR__ . '/backend/lab.db';

$timestamps = []; $temps = []; $hums = [];

if (file_exists($db_path)) {
    $db = new SQLite3($db_path);
    $stmt = $db->prepare("SELECT ts, temperature, humidity FROM measurements WHERE node_id = ? ORDER BY id DESC LIMIT 40");
    $stmt->bindValue(1, $node, SQLITE3_TEXT);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $timestamps[] = $row['ts'];
        $temps[] = $row['temperature'];
        $hums[] = $row['humidity'];
    }
    $db->close();
}
$timestamps = array_reverse($timestamps);
$temps = array_reverse($temps);
$hums = array_reverse($hums);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Telemetry Analysis - <?php echo htmlspecialchars($node); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #111115; color: #fff; text-align: center; margin: 35px; }
        .wrapper { width: 85%; margin: 0 auto; background: #19191f; padding: 25px; border-radius: 12px; border: 1px solid #282830; }
        .control-bar { background: #23232b; padding: 15px; margin-bottom: 25px; border-radius: 8px; display: flex; justify-content: center; gap: 15px; align-items: center; }
        input[type="number"] { width: 65px; padding: 8px; border-radius: 4px; border: 1px solid #444; background: #2d2d35; color: white; text-align: center; }
        button { padding: 9px 18px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .toggle-btn { background: #4b5563; }
        .back-link { display: inline-block; color: #00f2fe; text-decoration: none; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">⬅️ Back to Central Hub Menu</a>
    <div class="wrapper">
        <h2>Live Metric Graphs: <?php echo strtoupper(htmlspecialchars($node)); ?></h2>
        
        <div class="control-bar">
            <button class="toggle-btn" onclick="toggleMetric('temp')">Temperature Line</button>
            <button class="toggle-btn" onclick="toggleMetric('hum')">Humidity Line</button>
            <span>|</span>
            <label>Min Y Limit:</label> <input type="number" id="minY" value="0">
            <label>Max Y Limit:</label> <input type="number" id="maxY" value="100">
            <button onclick="applyYLimits()">Apply Limits</button>
            <button style="background-color: #d97706;" onclick="resetYScale()">🔄 Reset Scale</button>
        </div>

        <div style="position: relative; height:45vh; width:100%">
            <canvas id="canvas_engine"></canvas>
        </div>
    </div>

    <script>
        const timestamps_array = <?php echo json_encode($timestamps); ?>;
        const temperature_data = <?php echo json_encode($temps); ?>;
        const humidity_data = <?php echo json_encode($hums); ?>;

        const ctx = document.getElementById('canvas_engine').getContext('2d');
        const analyticsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timestamps_array,
                datasets: [{
                    label: 'Temperature (°C)',
                    data: temperature_data,
                    borderColor: '#ff5722',
                    backgroundColor: 'rgba(255, 87, 34, 0.08)',
                    borderWidth: 3,
                    tension: 0.2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { grid: { color: '#2a2a35' }, ticks: { color: '#a1a1aa' } },
                    x: { grid: { color: '#2a2a35' }, ticks: { color: '#a1a1aa' } }
                }
            }
        });

        function toggleMetric(mode) {
            if(mode === 'temp') {
                analyticsChart.data.datasets[0].label = 'Temperature (°C)';
                analyticsChart.data.datasets[0].data = temperature_data;
                analyticsChart.data.datasets[0].borderColor = '#ff5722';
                analyticsChart.data.datasets[0].backgroundColor = 'rgba(255, 87, 34, 0.08)';
            } else {
                analyticsChart.data.datasets[0].label = 'Humidity (%)';
                analyticsChart.data.datasets[0].data = humidity_data;
                analyticsChart.data.datasets[0].borderColor = '#00bcd4';
                analyticsChart.data.datasets[0].backgroundColor = 'rgba(0, 188, 212, 0.08)';
            }
            analyticsChart.update();
        }

        function applyYLimits() {
            analyticsChart.options.scales.y.min = parseFloat(document.getElementById('minY').value);
            analyticsChart.options.scales.y.max = parseFloat(document.getElementById('maxY').value);
            analyticsChart.update();
        }

        function resetYScale() {
            delete analyticsChart.options.scales.y.min;
            delete analyticsChart.options.scales.y.max;
            document.getElementById('minY').value = 0;
            document.getElementById('maxY').value = 100;
            analyticsChart.update();
        }
    </script>
</body>
</html>
