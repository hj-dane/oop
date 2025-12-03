<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

$userid = isset($_GET['userid']) ? (int)$_GET['userid'] : 0;

if ($userid <= 0) {
    echo json_encode(["error" => "Invalid user ID"]);
    exit;
}

try {
    $sql = "SELECT * FROM users WHERE user_id = :userid";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(["error" => "User not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
