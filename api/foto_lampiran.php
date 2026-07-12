<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = '../uploads/';

// ============================================
// Compression Function
// ============================================
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);
    if (!$info) return false;
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($source); break;
        case 'image/png': $image = imagecreatefrompng($source); break;
        case 'image/gif': $image = imagecreatefromgif($source); break;
        case 'image/webp': $image = imagecreatefromwebp($source); break;
        default: return false;
    }
    if (!$image) return false;

    $width = imagesx($image);
    $height = imagesy($image);
    $maxWidth = 800;

    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = floor($height * ($maxWidth / $width));
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        if ($mime == 'image/png' || $mime == 'image/webp' || $mime == 'image/gif') {
            imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
        }
        imagecopyresampled($tmp, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $tmp;
    }

    $result = false;
    switch ($mime) {
        case 'image/jpeg': $result = imagejpeg($image, $destination, $quality); break;
        case 'image/png': $result = imagepng($image, $destination, 8); break;
        case 'image/gif': $result = imagegif($image, $destination); break;
        case 'image/webp': $result = imagewebp($image, $destination, $quality); break;
    }
    imagedestroy($image);
    return $result;
}

function generateFilename($file, $fitur, $tanggal = '') {
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $date = $tanggal ?: date('Y-m-d');
    $date = preg_replace('/[^a-zA-Z0-9-]/', '-', $date);
    $feature = preg_replace('/[^a-zA-Z0-9-]/', '-', $fitur);
    return $date . '-' . $feature . '_' . time() . '_' . uniqid() . '.' . $ext;
}

// ============================================
// GET: List photos for a record OR bulk fetch all
// ============================================
if ($method === 'GET') {
    $fitur = $_GET['fitur'] ?? '';
    $record_id = $_GET['record_id'] ?? '';
    $bulk = isset($_GET['bulk']);

    // Bulk: fetch all fotos for all features at once
    if ($bulk) {
        $stmt = $pdo->query("SELECT * FROM foto_lampiran ORDER BY id ASC");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($all);
        exit;
    }

    if (!$fitur || !$record_id) {
        http_response_code(400);
        echo json_encode(['error' => 'fitur and record_id required']);
        exit;
    }

    $allowed_fitur = ['pengobatan', 'vaksinasi', 'monitoring', 'surveilans', 'kegiatan_lain', 'kunjungan_tamu', 'layanan_usg'];
    if (!in_array($fitur, $allowed_fitur)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid fitur']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM foto_lampiran WHERE fitur = ? AND record_id = ? ORDER BY id ASC");
    $stmt->execute([$fitur, $record_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($photos);
    exit;
}

// ============================================
// POST: Upload multiple photos
// ============================================
if ($method === 'POST') {
    $fitur = $_POST['fitur'] ?? '';
    $record_id = $_POST['record_id'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';

    if (!$fitur || !$record_id) {
        http_response_code(400);
        echo json_encode(['error' => 'fitur and record_id required']);
        exit;
    }

    $allowed_fitur = ['pengobatan', 'vaksinasi', 'monitoring', 'surveilans', 'kegiatan_lain', 'kunjungan_tamu', 'layanan_usg'];
    if (!in_array($fitur, $allowed_fitur)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid fitur']);
        exit;
    }

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $uploaded = [];
    $errors = [];

    // Support both single file (file) and multiple files (files[])
    $files = [];
    if (isset($_FILES['files'])) {
        // Multiple files
        $fileCount = is_array($_FILES['files']['name']) ? count($_FILES['files']['name']) : 1;
        for ($i = 0; $i < $fileCount; $i++) {
            if (is_array($_FILES['files']['name'])) {
                $files[] = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'size' => $_FILES['files']['size'][$i],
                    'error' => $_FILES['files']['error'][$i]
                ];
            } else {
                $files[] = $_FILES['files'];
            }
        }
    } elseif (isset($_FILES['file'])) {
        $files[] = $_FILES['file'];
    }

    if (empty($files)) {
        http_response_code(400);
        echo json_encode(['error' => 'No files uploaded']);
        exit;
    }

    foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error on {$file['name']}";
            continue;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid type: {$file['name']}";
            continue;
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = "Too large: {$file['name']} (max 10MB)";
            continue;
        }

        $filename = generateFilename($file, $fitur, $tanggal);
        $targetPath = $uploadDir . $filename;

        // Compress and save
        $saved = false;
        if (extension_loaded('gd')) {
            $saved = compressImage($file['tmp_name'], $targetPath, 70);
        }
        if (!$saved) {
            $saved = move_uploaded_file($file['tmp_name'], $targetPath);
        }

        if ($saved) {
            // Insert into database
            $stmt = $pdo->prepare("INSERT INTO foto_lampiran (fitur, record_id, filename) VALUES (?, ?, ?)");
            $stmt->execute([$fitur, $record_id, $filename]);
            $uploaded[] = [
                'id' => $pdo->lastInsertId(),
                'fitur' => $fitur,
                'record_id' => $record_id,
                'filename' => $filename
            ];

            // Also update the main table's foto column with the first photo
            if (count($uploaded) === 1) {
                $allowed_tables = ['pengobatan', 'vaksinasi', 'monitoring', 'surveilans', 'kegiatan_lain', 'kunjungan_tamu', 'layanan_usg'];
                if (in_array($fitur, $allowed_tables)) {
                    $stmt2 = $pdo->prepare("UPDATE $fitur SET foto = ? WHERE id = ? AND (foto IS NULL OR foto = '')");
                    $stmt2->execute([$filename, $record_id]);
                }
            }
        } else {
            $errors[] = "Failed to save: {$file['name']}";
        }
    }

    echo json_encode([
        'uploaded' => $uploaded,
        'errors' => $errors,
        'total_uploaded' => count($uploaded)
    ]);
    exit;
}

// ============================================
// DELETE: Remove a single photo
// ============================================
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id required']);
        exit;
    }

    // Get photo info first
    $stmt = $pdo->prepare("SELECT * FROM foto_lampiran WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$photo) {
        http_response_code(404);
        echo json_encode(['error' => 'Photo not found']);
        exit;
    }

    // Delete file from disk
    $filePath = $uploadDir . $photo['filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM foto_lampiran WHERE id = ?");
    $stmt->execute([$id]);

    // If this was the main foto, update with next available
    $allowed_tables = ['pengobatan', 'vaksinasi', 'monitoring', 'surveilans', 'kegiatan_lain', 'kunjungan_tamu', 'layanan_usg'];
    if (in_array($photo['fitur'], $allowed_tables)) {
        $stmt2 = $pdo->prepare("SELECT filename FROM foto_lampiran WHERE fitur = ? AND record_id = ? ORDER BY id ASC LIMIT 1");
        $stmt2->execute([$photo['fitur'], $photo['record_id']]);
        $nextPhoto = $stmt2->fetchColumn();
        $stmt3 = $pdo->prepare("UPDATE {$photo['fitur']} SET foto = ? WHERE id = ?");
        $stmt3->execute([$nextPhoto ?: null, $photo['record_id']]);
    }

    echo json_encode(['success' => true, 'deleted' => $photo['filename']]);
    exit;
}
