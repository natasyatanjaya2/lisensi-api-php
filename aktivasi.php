<?php
// aktivasi.php
header('Content-Type: application/json');
// (opsional) kalau perlu dipanggil dari app lain:
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

function json_out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Ambil konfigurasi dari Railway
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: 3306;

if (!$host || !$user || !$db) {
    json_out(['status' => 'gagal', 'pesan' => 'ENV database belum lengkap'], 500);
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (Throwable $e) {
    json_out(['status' => 'gagal', 'pesan' => 'Koneksi database gagal: '.$e->getMessage()], 500);
}

// Ambil input JSON (fallback ke form-urlencoded jika perlu)
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST; // fallback
}
$kode = isset($input['kode_lisensi']) ? trim($input['kode_lisensi']) : '';

if ($kode === '') {
    json_out(['status' => 'gagal', 'pesan' => 'Kode lisensi tidak boleh kosong'], 400);
}

try {
    // Cek lisensi belum_aktif
    $stmt = $pdo->prepare("SELECT id FROM lisensi WHERE kode_lisensi = ? AND status = 'belum_aktif' LIMIT 1");
    $stmt->execute([$kode]);
    $row = $stmt->fetch();

    if (!$row) {
        json_out(['status' => 'gagal', 'pesan' => 'Kode lisensi tidak valid atau sudah aktif']);
    }

    // Aktivasi
    $upd = $pdo->prepare("UPDATE lisensi SET status='aktif', tanggal_aktivasi=NOW() WHERE id = ?");
    $upd->execute([$row['id']]);

    json_out(['status' => 'sukses', 'pesan' => 'Lisensi berhasil diaktifkan']);
} catch (Throwable $e) {
    json_out(['status' => 'gagal', 'pesan' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
}
