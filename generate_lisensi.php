<?php
header("Content-Type: application/json");

// Fungsi generate kode lisensi
function generateKodeLisensi($panjang = 16) {
    $karakter = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $kode = '';
    for ($i = 0; $i < $panjang; $i++) {
        $kode .= $karakter[random_int(0, strlen($karakter) - 1)];
        if (($i + 1) % 4 == 0 && $i != $panjang - 1) {
            $kode .= '-';
        }
    }
    return $kode;
}

try {
    // Koneksi ke database
    $pdo = new PDO("mysql:host=localhost;dbname=software_ai", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Loop sampai dapat kode yang unik
    do {
        $kode = generateKodeLisensi();
        $cek = $pdo->prepare("SELECT COUNT(*) FROM lisensi WHERE kode_lisensi = ?");
        $cek->execute([$kode]);
        $jumlah = $cek->fetchColumn();
    } while ($jumlah > 0);

    // Simpan ke database
    $stmt = $pdo->prepare("INSERT INTO lisensi (kode_lisensi, status, tanggal_aktivasi) VALUES (?, ?, NULL)");
    $stmt->execute([$kode, "belum_aktif"]);

    echo json_encode([
        "status" => "sukses",
        "kode_lisensi" => $kode
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "gagal",
        "pesan" => $e->getMessage()
    ]);
}
?>