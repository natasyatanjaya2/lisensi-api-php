<?php
// check_aktivasi.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');                    // opsional, kalau dipanggil lintas-origin
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');  // opsional
header('Access-Control-Allow-Headers: Content-Type');        // opsional
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

function json_out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Ambil konfigurasi dari Railway ---
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: 3306;

if (!$host || !$user || !$db) {
    json_out(['status' => 'gagal', 'pesan' => 'ENV database belum lengkap'], 500);
}

// --- Koneksi DB ---
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

// --- Ambil input ---
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    // fallback ke form/urlencoded
    $input = $_POST;
}
$kode = isset($input['kode_lisensi']) ? trim($input['kode_lisensi']) : '';
// fallback GET ?kode_lisensi=XXXX (opsional)
if ($kode === '' && isset($_GET['kode_lisensi'])) {
    $kode = trim($_GET['kode_lisensi']);
}

if ($kode === '') {
    json_out(['status' => 'gagal', 'pesan' => 'Kode lisensi tidak boleh kosong'], 400);
}

// --- Cek lisensi aktif ---
try {
    $sql = "SELECT id, kode_lisensi, tanggal_aktivasi, status
            FROM lisensi
            WHERE kode_lisensi = ? AND status = 'aktif'
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kode]);
    $row = $stmt->fetch();

    if (!$row) {
        json_out(['status' => 'gagal', 'pesan' => 'Kode lisensi tidak valid atau belum aktif']);
    }

    json_out([
        'status' => 'sukses',
        'data'   => [
            'id'               => (int)$row['id'],
            'kode_lisensi'     => $row['kode_lisensi'],
            'tanggal_aktivasi' => $row['tanggal_aktivasi'],
            'status'           => $row['status']
        ]
    ]);
} catch (Throwable $e) {
    json_out(['status' => 'gagal', 'pesan' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
}
