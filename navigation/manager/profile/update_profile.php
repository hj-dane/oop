<?php
// navigation/admin/account/update_profile.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$userid = (int)$_SESSION['accountID'];

require '../../../database/connection.php'; // PDO $conn

try {
    // Fetch existing image path (for optional delete)
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $userid]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldImage = $existing['profile_image'] ?? null;

    $email      = trim($_POST['email']       ?? '');
    $mobile     = trim($_POST['mobile']      ?? '');
    $address    = trim($_POST['address']     ?? '');
    $first_name = trim($_POST['first_name']  ?? '');
    $last_name  = trim($_POST['last_name']   ?? '');

    if ($email === '' || $first_name === '' || $last_name === '') {
        echo json_encode(['success' => false, 'error' => 'First name, last name, and email are required.']);
        exit();
    }

    $newImagePath = null;

    // Handle profile image upload, if any
    if (!empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Error uploading image.']);
            exit();
        }

        if ($file['size'] > 2 * 1024 * 1024) { // 2MB
            echo json_encode(['success' => false, 'error' => 'Image must be 2MB or less.']);
            exit();
        }

        $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            echo json_encode(['success' => false, 'error' => 'Only JPG and PNG images are allowed.']);
            exit();
        }

        $ext  = $allowed[$mime];
        $dir  = '../../../uploads/profile/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $filename = 'user_' . $userid . '_' . uniqid() . $ext;
        $target   = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['success' => false, 'error' => 'Failed to save image file.']);
            exit();
        }

        // Store relative path from project root
        $newImagePath = 'uploads/profile/' . $filename;

        // Delete old file, if any
        if ($oldImage && file_exists('../../../' . $oldImage)) {
            @unlink('../../../' . $oldImage);
        }
    }

    $sql = "UPDATE users
            SET email = :email,
                mobile = :mobile,
                address = :address,
                first_name = :first_name,
                last_name  = :last_name";

    $params = [
        ':email'      => $email,
        ':mobile'     => $mobile,
        ':address'    => $address,
        ':first_name' => $first_name,
        ':last_name'  => $last_name,
        ':id'         => $userid
    ];

    if ($newImagePath) {
        $sql .= ", profile_image = :img";
        $params[':img'] = $newImagePath;
    }

    $sql .= " WHERE user_id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Refresh session name (for navbar)
    $_SESSION['firstname'] = $first_name;
    $_SESSION['lastname']  = $last_name;

    $reloadName = trim($first_name . ' ' . ($_SESSION['middlename'] ?? '') . ' ' . $last_name);

    echo json_encode([
        'success'    => 'Profile updated successfully.',
        'reloadName' => $reloadName
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
