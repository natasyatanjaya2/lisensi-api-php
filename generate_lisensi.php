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
    // Ambil dari environment (Railway akan inject otomatis)
    $host     = getenv("MYSQLHOST");
    $dbname   = getenv("MYSQLDATABASE");
    $user     = getenv("MYSQLUSER");
    $password = getenv("MYSQLPASSWORD");
    $port     = getenv("MYSQLPORT") ?: 3306;

    // Koneksi ke database
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Loop sampai dapat kode unik
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
