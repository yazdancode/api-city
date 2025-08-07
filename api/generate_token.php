<?php
header('Content-Type: application/json');

include __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'درخواست نامعتبر است']);
    exit;
}

$email = $_POST['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'ایمیل نامعتبر است']);
    exit;
}

require_once __DIR__ . '/../loader.php';

$user = getUserByEmail($email); // باید در loader.php تعریف شود

if (!$user) {
    echo json_encode(['error' => 'کاربری با این ایمیل یافت نشد']);
    exit;
}

$jwt = createApiToken($user); // باید در loader.php تعریف شود

echo json_encode([
    'name' => $user->name,
    'token' => $jwt
]);
