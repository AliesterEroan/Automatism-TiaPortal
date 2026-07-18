<?php
header('Content-Type: application/json; charset=utf-8');

// Step up one folder level to find the backend directory
$dbPath = __DIR__ . '/../backend/lab.db';

if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database context path missing']);
    exit;
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optimized select statement to fetch the absolute latest telemetry row for each node
    $stmt = $pdo->query("
        SELECT m1.node_id, 
               m1.temperature, 
               m1.humidity, 
               m1.ts AS last_ts
        FROM measurements m1
        WHERE m1.id IN (SELECT MAX(id) FROM measurements GROUP BY node_id)
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
