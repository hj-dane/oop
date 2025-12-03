<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../database/connection.php';   // PDO $conn
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

/* Read JSON body */
$data = json_decode(file_get_contents('php://input'), true);
if ($data === null) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

/* One field that can contain username OR email */
$identifier = trim($data['username'] ?? $data['email'] ?? '');
$password   = trim($data['password'] ?? '');

if ($identifier === '' || $password === '') {
    echo json_encode(["status" => "error", "message" => "Username/Email and Password are required."]);
    exit;
}

/* Fetch user from USERS + ROLES (match either email OR username) */
$sql = "
    SELECT 
        u.user_id,
        u.username,
        u.first_name AS First_name,
        u.last_name  AS Last_name,
        u.email      AS Email,
        u.password_hash,
        u.status,
        u.department_id,
        r.role_name
    FROM users u
    JOIN roles r ON r.role_id = u.role_id
    WHERE u.Email = :email OR u.username = :username
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':email'    => $identifier,
    ':username' => $identifier
]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* Validate credentials */
if (!$user) {
    echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    exit;
}

if ($user['status'] !== 'active') {
    echo json_encode(["status" => "error", "message" => "Your account is inactive. Please contact the administrator."]);
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    exit;
}

/* Set session data */
$_SESSION['accountID']     = $user['user_id'];
$_SESSION['firstname']     = $user['First_name'];
$_SESSION['lastname']      = $user['Last_name'];
$_SESSION['middlename']    = ''; // kept for compatibility
$_SESSION['Email']         = $user['Email'];
$_SESSION['userrole']      = $user['role_name'];      // Admin / Manager / Employee
$_SESSION['department_id'] = $user['department_id'];
$_SESSION['username']      = $user['username'];

/* Decide redirect based on role_name */
$role         = $user['role_name'];
$redirect_url = "navigation/employee/dashboard.php";  // default

if ($role === 'Admin') {
    $redirect_url = "navigation/admin/dashboard.php";
} elseif ($role === 'Manager') {
    $redirect_url = "navigation/manager/dashboard.php";
}

/* Return JSON response */
echo json_encode([
    "status"   => "success",
    "message"  => "Login successful.",
    "redirect" => $redirect_url,
    "user"     => [
        "userid"       => $user['user_id'],
        "lastname"     => $user['Last_name'],
        "firstname"    => $user['First_name'],
        "middlename"   => $_SESSION['middlename'],
        "email"        => $user['Email'],
        "username"     => $user['username'],
        "role"         => $role,
        "departmentId" => $user['department_id']
    ]
]);
exit;
