<?php
/**
 * Generate IKI-SKP Report (.docx) via Python script.
 * GET params: bulan (1-12), tahun (YYYY)
 * Returns the .docx file as download.
 */
header('Content-Type: application/json');

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;

if ($bulan < 1 || $bulan > 12) {
    http_response_code(400);
    echo json_encode(['error' => 'Bulan harus 1-12']);
    exit;
}

if ($tahun < 2020 || $tahun > 2100) {
    http_response_code(400);
    echo json_encode(['error' => 'Tahun tidak valid']);
    exit;
}

// Run the Python script (path bisa dioverride via env IKI_SKP_SCRIPT,
// default path lama VPS; di container set ke script yang dibundel)
$script = getenv('IKI_SKP_SCRIPT') ?: '/opt/admin-puskeswan/generate_iki_skp.py';
if (!is_file($script) || !is_readable($script)) {
    http_response_code(501);
    echo json_encode(['error' => 'Generator IKI-SKP belum tersedia di server ini']);
    exit;
}
$cmd = escapeshellcmd(PHP_BINARY ? 'python3' : 'python3') . ' ' . escapeshellarg($script) . ' ' . escapeshellarg((string) $bulan) . ' ' . escapeshellarg((string) $tahun) . ' 2>&1';
$output = shell_exec($cmd);

// Parse output for the file path
if ($output && preg_match('/Generated:\s*(.+)/', $output, $matches)) {
    $filepath = trim($matches[1]);
    
    if (file_exists($filepath)) {
        $filename = basename($filepath);
        
        // Determine month name
        $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $downloadName = "IKI-SKP_{$months[$bulan]}_{$tahun}.docx";
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($filepath);
        exit;
    }
}

http_response_code(500);
echo json_encode(['error' => 'Gagal generate laporan', 'output' => $output]);
