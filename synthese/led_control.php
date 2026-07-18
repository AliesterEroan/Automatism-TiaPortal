<?php
$node = isset($_GET['node']) ? $_GET['node'] : 'esp32-1';
$db_path = __DIR__ . '/backend/lab.db';

$led_profiles = ['led1' => 'OFF', 'led2' => 'OFF', 'led3' => 'OFF'];

if (file_exists($db_path)) {
    try {
        $db = new SQLite3($db_path);
        foreach (array_keys($led_profiles) as $id) {
            // Checked against your exact schema (node_id, led_id, color)
            $stmt = $db->prepare("SELECT color FROM led_commands WHERE node_id=:node AND led_id=:led ORDER BY id DESC LIMIT 1");
            $stmt->bindValue(':node', $node, SQLITE3_TEXT);
            $stmt->bindValue(':led', $id, SQLITE3_TEXT);
            $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if ($res && !empty($res['color'])) {
                $led_profiles[$id] = strtoupper($res['color']);
            }
        }
        $db->close();
    } catch (Exception $e) {
        // Fallback gracefully instead of throwing a 500 error page
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Direct LED Control Interface</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #141416; color: #fff; padding-top: 50px; margin: 0; }
        .card { background: #1f1f23; padding: 30px; display: inline-block; border-radius: 12px; width: 380px; border: 1px solid #29292e; box-shadow: 0 4px 15px rgba(0,0,0,0.4); }
        .led-row { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; padding: 12px; background: #2a2a30; border-radius: 6px; }
        .indicator { width: 18px; height: 18px; border-radius: 50%; display: inline-block; background: #444; transition: all 0.3s ease; }
        .btn-group button { padding: 6px 12px; border: none; border-radius: 4px; color: white; cursor: pointer; font-weight: bold; margin-left: 2px; }
        .back-link { display: inline-block; color: #00f2fe; text-decoration: none; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">⬅️ Back to Central Hub Menu</a>
    <br>
    <div class="card">
        <h2>Control Board: <?php echo strtoupper(htmlspecialchars($node)); ?></h2>
        <hr style="border-color: #29292e;">

        <?php 
        $colors = ['led1' => ['name' => 'Red', 'hex' => '#dc2626'], 'led2' => ['name' => 'Yellow', 'hex' => '#eab308'], 'led3' => ['name' => 'Green', 'hex' => '#16a34a']];
        foreach($colors as $id => $meta): 
            $currentState = $led_profiles[$id];
            // Determine glowing effect if active
            $glowStyle = "background: #444;";
            if ($currentState === 'R' || $currentState === 'ON') $glowStyle = "background: #dc2626; box-shadow: 0 0 10px #dc2626;";
            if ($currentState === 'J') $glowStyle = "background: #eab308; box-shadow: 0 0 10px #eab308;";
            if ($currentState === 'V') $glowStyle = "background: #16a34a; box-shadow: 0 0 10px #16a34a;";
        ?>
        <div class="led-row">
            <span class="indicator" style="<?php echo $glowStyle; ?>"></span>
            <strong><?php echo $meta['name']; ?> [<?php echo $currentState; ?>]</strong>
            <div class="btn-group">
                <button style="background:#dc2626" onclick="fireCmd('<?php echo $id; ?>','R')">R</button>
                <button style="background:#eab308; color:#000" onclick="fireCmd('<?php echo $id; ?>','J')">J</button>
                <button style="background:#16a34a" onclick="fireCmd('<?php echo $id; ?>','V')">V</button>
                <button style="background:#4b5563" onclick="fireCmd('<?php echo $id; ?>','OFF')">OFF</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        const targetNode = <?php echo json_encode($node); ?>;
        
        async function fireCmd(ledId, color) {
            try {
                const response = await fetch('api/led_command.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ node_id: targetNode, led_id: ledId, color: color })
                });
                const result = await response.json();
                if(result.ok) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch(e) { 
                alert('Communication Link Error'); 
            }
        }
    </script>
</body>
</html>
