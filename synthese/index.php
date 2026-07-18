<?php
$db_path = __DIR__ . '/backend/lab.db';
$nodes = [];

if (file_exists($db_path)) {
    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $stmt = $pdo->query("SELECT DISTINCT node_id FROM measurements UNION SELECT DISTINCT node_id FROM led_commands");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['node_id'])) $nodes[] = $row['node_id'];
        }
    } catch (PDOException $e) {}
}
if (empty($nodes)) { $nodes = ['esp32-1', 'esp32-2', 'esp32-3']; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Server Gateway Hub</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; text-align: center; background: #121214; color: #e1e1e6; margin-top: 100px; }
        .hub-card { background: #1d1d22; padding: 40px; display: inline-block; border-radius: 12px; border: 1px solid #29292e; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        select, button { padding: 12px 24px; font-size: 16px; margin: 15px; border-radius: 6px; border: none; font-weight: 600; }
        select { background: #29292e; color: #fff; cursor: pointer; border: 1px solid #3e3e4a; }
        button { background: #007bff; color: white; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #0056b3; }
        .global-btn { background: #6c757d; } .global-btn:hover { background: #5a6268; }
        .chart-btn { background: #28a745; } .chart-btn:hover { background: #1e7e34; }
        h1 { color: #00f2fe; margin-bottom: 5px; }
        p { color: #a1a1aa; margin-bottom: 25px; }
    </style>
</head>
<body>
    <div class="hub-card">
        <h1>IoT Multi-Node Dashboard</h1>
        <p>Select an operational connection or view the master system telemetry:</p>
        
        <form method="GET">
            <select name="node" id="nodeSelect" required>
                <?php foreach($nodes as $node): ?>
                    <option value="<?php echo htmlspecialchars($node); ?>"><?php echo strtoupper(htmlspecialchars($node)); ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <button type="button" onclick="routeTo('led_control.php')">🕹️ Node Switchboard</button>
            <button type="button" class="chart-btn" onclick="routeTo('metrics_chart.php')">📊 Target Chart Toggles</button>
            <hr style="border-color: #29292e; margin: 20px 0;">
            <button type="button" class="global-btn" onclick="window.location.href='dashboard.php'">🖥️ View Master Network Status Table</button>
        </form>
    </div>

    <script>
        function routeTo(targetPage) {
            const nodeValue = document.getElementById('nodeSelect').value;
            if(nodeValue) {
                window.location.href = targetPage + "?node=" + encodeURIComponent(nodeValue);
            }
        }
    </script>
</body>
</html>
