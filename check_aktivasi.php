<?php
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"));

if (!isset($input->kode_lisensi)) {
    echo json_encode(["status" => "gagal", "pesan" => "Kode lisensi tidak ditemukan"]);
    exit;
}

$kode = $input->kode_lisensi;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=software_ai", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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