<?php
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Payload unreadable']);
    exit;
}

$node_id = $data['node_id'] ?? null;
$led_id  = $data['led_id']  ?? null;
$color   = strtoupper($data['color'] ?? '');

if (!$node_id || !$led_id || !in_array($color, ['OFF','R','J','V'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid structural constraints']);
    exit;
}

// Step up one folder level to find the backend directory
$dbPath = __DIR__ . '/../backend/lab.db';

if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database layer unreachable']);
    exit;
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO led_commands (node_id, led_id, color, status) VALUES (:node_id, :led_id, :color, 'pending')");
    $stmt->execute([
        ':node_id' => $node_id,
        ':led_id'  => $led_id,
        ':color'   => $color
    ]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
