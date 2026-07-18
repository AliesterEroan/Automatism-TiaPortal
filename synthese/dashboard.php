<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>IoT Network Status Table</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial,sans-serif;background:#121214;color:#e1e1e6;margin:0;padding:20px}
    .card{max-width:1100px;margin:auto;background:#1d1d22;padding:25px;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,.4);border:1px solid #29292e}
    h1{font-size:1.4rem;margin-top:0;color:#00f2fe}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    th,td{border-bottom:1px solid #29292e;padding:12px;text-align:left}
    th{background:#23232b;color:#a1a1aa}
    .led-buttons button{padding:6px 10px;margin-right:4px;border:0;border-radius:6px;cursor:pointer;font-size:.8rem;font-weight:bold}
    .btn-r{background:#dc2626;color:#fff}
    .btn-j{background:#eab308;color:#000}
    .btn-v{background:#16a34a;color:#fff}
    .btn-off{background:#4b5563;color:#fff}
    #status{font-size:.9rem;color:#a1a1aa;margin-top:8px}
    .nav-btn { display: inline-block; background: #29292e; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 15px; }
  </style>
</head>
<body>
<div style="max-width:1100px; margin: auto;">
    <a href="index.php" class="nav-btn">⬅️ Back to Central Hub Menu</a>
</div>
<div class="card">
  <h1>IoT Lab Dashboard (3 ESP32 Networks via MQTT)</h1>
  <div id="status">Chargement...</div>

  <table>
    <thead>
      <tr>
        <th>Node Identifier</th>
        <th>Température (°C)</th>
        <th>Humidité (%)</th>
        <th>Dernière mise à jour</th>
        <th>LED 1 (Red Link)</th>
        <th>LED 2 (Yellow Link)</th>
        <th>LED 3 (Green Link)</th>
      </tr>
    </thead>
    <tbody id="tbody-nodes"></tbody>
  </table>
</div>

<script>
const NODES = ["esp32-1","esp32-2","esp32-3"];

async function fetchSensors(){
  try{
    const res = await fetch("api/sensors.php");
    const data = await res.json();

    const byNode = {};
    data.forEach(row => { byNode[row.node_id] = row; });

    const tbody = document.getElementById("tbody-nodes");
    tbody.innerHTML = "";

    NODES.forEach(nodeId => {
      const row = byNode[nodeId] || {};
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td><strong>${nodeId.toUpperCase()}</strong></td>
        <td style="color:#ff5722">${row.temperature ?? "--"} °C</td>
        <td style="color:#00bcd4">${row.humidity ?? "--"} %</td>
        <td><small>${row.last_ts ?? "--"}</small></td>
        <td class="led-buttons">${makeLedButtons(nodeId, "led1")}</td>
        <td class="led-buttons">${makeLedButtons(nodeId, "led2")}</td>
        <td class="led-buttons">${makeLedButtons(nodeId, "led3")}</td>
      `;
      tbody.appendChild(tr);
    });

    document.getElementById("status").textContent = "Dernière mise à jour : " + new Date().toLocaleTimeString();
  }catch(e){
    console.error(e);
    document.getElementById("status").textContent = "Erreur de chargement des données";
  }
}

function makeLedButtons(nodeId, ledId){
  return `
    <button class="btn-r"   onclick="sendLed('${nodeId}','${ledId}','R')">R</button>
    <button class="btn-j"   onclick="sendLed('${nodeId}','${ledId}','J')">J</button>
    <button class="btn-v"   onclick="sendLed('${nodeId}','${ledId}','V')">V</button>
    <button class="btn-off" onclick="sendLed('${nodeId}','${ledId}','OFF')">OFF</button>
  `;
}

async function sendLed(nodeId, ledId, color){
  try{
    const res = await fetch("api/led_command.php", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({ node_id: nodeId, led_id: ledId, color: color })
    });
    const data = await res.json();
    if(!data.ok) alert("Erreur API: " + (data.error || "inconnue"));
  }catch(e){
    console.error(e);
    alert("Erreur de connexion à l'API");
  }
}

fetchSensors();
setInterval(fetchSensors, 4000);
</script>
</body>
</html>
