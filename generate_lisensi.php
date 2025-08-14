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
    // Ambil dari environment (Railway inject otomatis)
    $host     = getenv("MYSQLHOST");
    $dbname   = getenv("MYSQLDATABASE");
    $user     = getenv("MYSQLUSER");
    $password = getenv("MYSQLPASSWORD");
    $port     = getenv("MYSQLPORT") ?: 3306;

    // Koneksi ke database
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // --- Ambil input durasi (DALAM BULAN) ---
    // Support JSON body, x-www-form-urlencoded, atau querystring
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);
    $durasiInput = null;

    if (is_array($json) && array_key_exists('durasi', $json)) {
        $durasiInput = $json['durasi'];
    } elseif (isset($_POST['durasi'])) {
        $durasiInput = $_POST['durasi'];
    } elseif (isset($_GET['durasi'])) { // opsional
        $durasiInput = $_GET['durasi'];
    }

    // Default ke 1 bulan jika tidak dikirim / tidak valid
    $durasiBulan = (int) $durasiInput;
    if ($durasiBulan <= 0) $durasiBulan = 1;

    // Validasi batasan wajar (1 .. 12 bulan = 1 tahun, ubah sesuai kebutuhan)
    if ($durasiBulan < 1 || $durasiBulan > 120) {
        throw new InvalidArgumentException("Durasi harus antara 1 hingga 12 bulan.");
    }

    // Loop sampai dapat kode unik
    do {
        $kode = generateKodeLisensi();
        $cek = $pdo->prepare("SELECT COUNT(*) FROM lisensi WHERE kode_lisensi = ?");
        $cek->execute([$kode]);
        $jumlah = (int) $cek->fetchColumn();
    } while ($jumlah > 0);

    // Simpan ke database
    // Catatan: kolom 'durasi' sekarang bermakna BULAN.
    $stmt = $pdo->prepare("
        INSERT INTO lisensi (kode_lisensi, status, durasi, tanggal_aktivasi)
        VALUES (?, ?, ?, NULL)
    ");
    $stmt->execute([$kode, "belum_aktif", $durasiBulan]);

    echo json_encode([
        "status"       => "sukses",
        "kode_lisensi" => $kode,
        "durasi"       => $durasiBulan  // dalam BULAN
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "gagal",
        "pesan"  => $e->getMessage()
    ]);
}
?>
