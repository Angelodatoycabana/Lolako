<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] != "admin") {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Forbidden"]);
    exit();
}

include 'db.php';
header('Content-Type: application/json');

// Ensure events table exists
$conn->query("CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    start DATETIME NOT NULL,
    end DATETIME NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function readJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) { return []; }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $events = [];
    $res = $conn->query("SELECT id, title, DATE_FORMAT(start, '%Y-%m-%dT%H:%i:%s') AS start, 
                                 CASE WHEN `end` IS NULL THEN NULL ELSE DATE_FORMAT(`end`, '%Y-%m-%dT%H:%i:%s') END AS `end`,
                                 description
                          FROM events ORDER BY start ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) { $events[] = $row; }
    }
    echo json_encode($events);
    exit();
}

if ($method === 'POST') {
    $data = readJsonBody();
    $action = $data['action'] ?? '';

    if ($action === 'create') {
        $title = $data['title'] ?? '';
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? null;
        $description = $data['description'] ?? null;
        if ($title === '' || $start === '') { http_response_code(400); echo json_encode(["error"=>"Missing fields"]); exit(); }
        $stmt = $conn->prepare("INSERT INTO events (title, start, end, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $start, $end, $description);
        $stmt->execute();
        echo json_encode(["id"=>$stmt->insert_id]);
        exit();
    }

    if ($action === 'update') {
        $id = intval($data['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(["error"=>"Invalid id"]); exit(); }
        $title = $data['title'] ?? null;
        $start = $data['start'] ?? null;
        $end = $data['end'] ?? null;
        $description = $data['description'] ?? null;
        $stmt = $conn->prepare("UPDATE events SET title=COALESCE(?, title), start=COALESCE(?, start), end=COALESCE(?, end), description=COALESCE(?, description) WHERE id=?");
        $stmt->bind_param("ssssi", $title, $start, $end, $description, $id);
        $stmt->execute();
        echo json_encode(["ok"=>true]);
        exit();
    }

    if ($action === 'delete') {
        $id = intval($data['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(["error"=>"Invalid id"]); exit(); }
        $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["ok"=>true]);
        exit();
    }

    http_response_code(400);
    echo json_encode(["error"=>"Unknown action"]);
    exit();
}

http_response_code(405);
echo json_encode(["error"=>"Method not allowed"]);
?>


