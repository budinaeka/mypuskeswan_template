<?php
/**
 * Koneksi database myPuskeswan.
 *
 * Prioritas konfigurasi:
 *   1. Environment variable (container / docker compose)
 *   2. File /etc/mypuskeswan-db.php  (instalasi VPS, opsional)
 *   3. Nilai default (mode lokal / pengembangan)
 *
 * Variabel yang dibaca: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
 */

$env = static function (string $key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return ($value === null || $value === '') ? $default : $value;
};

// Override opsional dari file eksternal (tidak pernah di-commit)
if (is_file('/etc/mypuskeswan-db.php')) {
    require '/etc/mypuskeswan-db.php';           // mendefinisikan / menimpa $db_* lama
}

$host     = $env('DB_HOST', $host ?? 'localhost');
$port     = $env('DB_PORT', $port ?? '3306');
$db_name  = $env('DB_NAME', $db_name ?? 'mypuskeswan_db');
$username = $env('DB_USER', $username ?? 'mypuskeswan');
$password = $env('DB_PASSWORD', $password ?? 'change-me');

$dsn = "mysql:host={$host};port={$port};dbname={$db_name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 10,
    ]);
} catch (PDOException $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "[db] koneksi gagal: {$e->getMessage()}\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => 'Database tidak tersedia',
        'detail'  => (getenv('APP_ENV') === 'production') ? null : $e->getMessage(),
    ]);
    exit;
}

// Path upload absolut — konsisten untuk CLI maupun web
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', rtrim($env('UPLOAD_DIR', dirname(__DIR__) . '/uploads'), '/') . '/');
}
