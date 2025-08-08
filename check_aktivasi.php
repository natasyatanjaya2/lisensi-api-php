<?php
header("Content-Type: application/json");

// Ambil JSON input
$input = json_decode(file_get_contents("php://input"), true);
$kode = $input["kode_lisensi"] ?? null;

// Validasi input
if (!$kode) {
    echo json_encode(["status" => "gagal", "pesan" => "Kode lisensi tidak ditemukan"]);
    exit;
}

try {
    // Ambil koneksi database dari environment Railway
    $host     = getenv("MYSQLHOST");
    $user     = getenv("MYSQLUSER");
    $password = getenv("MYSQLPASSWORD");
    $dbname   = getenv("MYSQLDATABASE");
    $port     = getenv("MYSQLPORT") ?: 3306;

    // Koneksi ke database
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query lisensi aktif
    $stmt = $pdo->prepare("SELECT * FROM lisensi WHERE kode_lisensi = :kode AND status = 'aktif' LIMIT 1");
    $stmt->execute(['kode' => $kode]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            "status" => "sukses",
            "data" => [
                "id" => $data['id'],
                "kode_lisensi" => $data['kode_lisensi'],
                "tanggal_aktivasi" => $data['tanggal_aktivasi'],
                "status" => $data['status']
            ]
        ]);
    } else {
        echo json_encode(["status" => "gagal", "pesan" => "Kode lisensi tidak valid atau belum aktif"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "gagal", "pesan" => "Koneksi gagal: " . $e->getMessage()]);
}
?>
