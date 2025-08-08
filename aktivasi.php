<?php
// aktivasi.php
header('Content-Type: application/json');

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
    echo json_encode([
        "status" => "gagal",
        "pesan" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit;
}

// Ambil data JSON input
$data = json_decode(file_get_contents("php://input"), true);
$kode = $data["kode_lisensi"] ?? null;

// Validasi input
if (!$kode) {
    echo json_encode([
        "status" => "gagal",
        "pesan" => "Kode lisensi tidak boleh kosong"
    ]);
    exit;
}

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

    echo json_encode(["status" => "sukses", "pesan" => "Lisensi berhasil diaktifkan"]);
} else {
    echo json_encode(["status" => "gagal", "pesan" => "Kode lisensi tidak valid atau sudah aktif"]);
}

$conn->close();
?>
