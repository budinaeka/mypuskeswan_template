<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password required']);
    exit;
}

$username = $input['username'];
$password = $input['password'];

try {
    $stmt = $pdo->prepare("SELECT id, username, name, role, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Username atau password salah']);
        exit;
    }

    $hash = $user['password'];
    $ok = false;

    // 1. Coba verifikasi bcrypt (format $2y$...)
    if (is_string($hash) && str_starts_with($hash, '$2y$')) {
        $ok = password_verify($password, $hash);
        // Rehash otomatis bila cost sudah usang
        if ($ok && password_needs_rehash($hash, PASSWORD_BCRYPT)) {
            $new = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$new, $user['id']]);
        }
    } elseif (hash_equals((string) $hash, (string) $password)) {
        // 2. Fallback: password plain-text lama → cocok, langsung upgrade ke bcrypt
        $ok = true;
        $new = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$new, $user['id']]);
    }

    if (!$ok) {
        http_response_code(401);
        echo json_encode(['error' => 'Username atau password salah']);
        exit;
    }

    unset($user['password']);
    echo json_encode($user);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
}
