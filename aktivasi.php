<?php
// aktivasi.php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "software_ai");

$data = json_decode(file_get_contents("php://input"), true);
$kode = $data["kode_lisensi"];

$stmt = $conn->prepare("SELECT * FROM lisensi WHERE kode_lisensi=? AND status='belum_aktif'");
$stmt->bind_param("s", $kode);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Update aktivasi
    $stmt2 = $conn->prepare("UPDATE lisensi SET status='aktif', tanggal_aktivasi=NOW() WHERE kode_lisensi=?");
    $stmt2->bind_param("s", $kode);
    $stmt2->execute();

    echo json_encode(["status" => "sukses", "pesan" => "Lisensi berhasil diaktifkan"]);
} else {
    echo json_encode(["status" => "gagal", "pesan" => "Kode lisensi tidak valid atau sudah aktif"]);
}
?>