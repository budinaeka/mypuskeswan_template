<?php
/**
 * init-db.php — inisialisasi skema + seed user admin (idempotent).
 *
 * Dipakai oleh docker/entrypoint.sh setiap container start.
 * Aman dijalankan berulang: hanya CREATE TABLE IF NOT EXISTS,
 * ALTER kolom bila belum ada, dan INSERT user bila belum ada.
 *
 *   php scripts/init-db.php          → migrasi penuh
 *   php scripts/init-db.php --check  → exit 0 bila DB bisa diakses
 */

$checkOnly = in_array('--check', $argv ?? [], true);

require dirname(__DIR__) . '/api/config.php';   // $pdo, $env closure tidak diekspor — baca ulang env di sini

if ($checkOnly) {
    $pdo->query('SELECT 1');
    exit(0);
}

$getenv = static function (string $key, $default = null) {
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return ($v === null || $v === '') ? $default : $v;
};

$log = static function (string $msg) {
    fwrite(STDOUT, "[init-db] {$msg}\n");
};

// --- 1. Skema dasar dari schema.sql (bila file ada) -----------------------
$schemaFile = dirname(__DIR__) . '/schema.sql';
if (is_file($schemaFile)) {
    $log("menerapkan {$schemaFile}");
    $sql = file_get_contents($schemaFile);
    // Jalankan statement per statement (PDO::exec multi-statement tidak portable)
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            // 1060/1061/1050 = kolom/kunci/tabel sudah ada → abaikan
            if (!in_array($e->errorInfo[1] ?? 0, [1050, 1060, 1061, 1068], true)) {
                $log("PERINGATAN: {$e->getMessage()}");
            }
        }
    }
}

// --- 2. Kolom 'foto' yang mungkin belum ada di skema lama -----------------
$tablesWithFoto = [
    'pengobatan', 'vaksinasi', 'monitoring', 'surveilans',
    'surat', 'kegiatan_lain', 'kunjungan_tamu',
];
foreach ($tablesWithFoto as $table) {
    try {
        $exists = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'foto'")->rowCount() > 0;
        if (!$exists) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN foto VARCHAR(255) DEFAULT NULL");
            $log("kolom foto ditambahkan ke {$table}");
        }
    } catch (PDOException $e) {
        $log("PERINGATAN ({$table}): {$e->getMessage()}");
    }
}

// --- 3. Tabel foto_lampiran (multi-foto per record) ------------------------
$pdo->exec("CREATE TABLE IF NOT EXISTS foto_lampiran (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fitur VARCHAR(100) NOT NULL,
    record_id BIGINT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    INDEX idx_foto_fitur_record (fitur, record_id)
)");
$log("tabel foto_lampiran siap");

// --- 4. Seed user admin -----------------------------------------------------
$adminUser = $getenv('ADMIN_USERNAME', 'admin');
$adminPass = $getenv('ADMIN_PASSWORD', 'admin123');

$stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
$stmt->execute([$adminUser]);
$row = $stmt->fetch();

if (!$row) {
    $hash = password_hash($adminPass, PASSWORD_BCRYPT);
    $pdo->prepare(
        "INSERT INTO users (username, password, role, name) VALUES (?, ?, 'admin', 'Administrator')"
    )->execute([$adminUser, $hash]);
    $log("user admin '{$adminUser}' dibuat");
} elseif ($row['password'] === '123' || $row['password'] === 'admin123') {
    // Upgrade password plain-text lama ke bcrypt sekali saja
    $hash = password_hash($row['password'] === '123' ? '123' : $adminPass, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $row['id']]);
    $log("password user '{$adminUser}' diupgrade ke bcrypt");
} else {
    $log("user '{$adminUser}' sudah ada, dilewati");
}

$log('selesai');
