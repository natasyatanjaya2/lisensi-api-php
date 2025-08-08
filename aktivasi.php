<?php
// aktivasi.php
header('Content-Type: application/json');

// Fungsi bantu untuk mengirim JSON response & keluar
function json_response($status, $pesan) {
    echo json_encode([
        "status" => $status,
        "pesan" => $pesan
    ]);
    exit;
}

// Ambil konfigurasi database dari environment Railway
$host     = getenv("MYSQLHOST");
$user     = getenv("MYSQLUSER");
$password = getenv("MYSQLPASSWORD");
$dbname   = getenv("MYSQLDATABASE");
$port     = getenv("MYSQLPORT") ?: 3306;

// Koneksi ke database
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Cek error koneksi
if ($conn->connect_error) {
    json_response("gagal", "Koneksi database gagal: " . $conn->connect_error);
}

// Ambil data JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validasi parsing JSON
if (!$data || !isset($data["kode_lisensi"])) {
    json_response("gagal", "Input JSON tidak valid atau kode lisensi kosong");
}

$kode = $data["kode_lisensi"];

// Cek apakah lisensi valid & belum aktif
$stmt = $conn->prepare("SELECT * FROM lisensi WHERE kode_lisensi=? AND status='belum_aktif'");
$stmt->bind_param("s", $kode);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Update status menjadi aktif
    $stmt2 = $conn->prepare("UPDATE lisensi SET status='aktif', tanggal_aktivasi=NOW() WHERE kode_lisensi=?");
    $stmt2->bind_param("s", $kode);
    $stmt2->execute();

    json_response("sukses", "Lisensi berhasil diaktifkan");
} else {
    json_response("gagal", "Kode lisensi tidak valid atau sudah aktif");
}

$conn->close();
?>
