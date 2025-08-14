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

    // --- Ambil input durasi (hari) ---
    // Support JSON body atau x-www-form-urlencoded
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    $durasiInput = null;

    if (is_array($json) && array_key_exists('durasi', $json)) {
        $durasiInput = $json['durasi'];
    } elseif (isset($_POST['durasi'])) {
        $durasiInput = $_POST['durasi'];
    } elseif (isset($_GET['durasi'])) { // opsional: dukung querystring
        $durasiInput = $_GET['durasi'];
    }

    // Default 30 hari jika tidak dikirim
    $durasi = (int) $durasiInput;
    if ($durasi <= 0) $durasi = 30;

    // Validasi batasan wajar (1 .. 3650 hari)
    if ($durasi < 1 || $durasi > 3650) {
        throw new InvalidArgumentException("Durasi harus antara 1 hingga 3650 hari.");
    }

    // Loop sampai dapat kode unik
    do {
        $kode = generateKodeLisensi();
        $cek = $pdo->prepare("SELECT COUNT(*) FROM lisensi WHERE kode_lisensi = ?");
        $cek->execute([$kode]);
        $jumlah = (int) $cek->fetchColumn();
    } while ($jumlah > 0);

    // Simpan ke database (status default: belum_aktif, tanggal_aktivasi: NULL)
    $stmt = $pdo->prepare("
        INSERT INTO lisensi (kode_lisensi, status, durasi, tanggal_aktivasi)
        VALUES (?, ?, ?, NULL)
    ");
    $stmt->execute([$kode, "belum_aktif", $durasi]);

    echo json_encode([
        "status" => "sukses",
        "kode_lisensi" => $kode,
        "durasi" => $durasi,
        // "expired_at" baru bisa dihitung setelah aktivasi (tanggal_aktivasi + durasi)
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "gagal",
        "pesan" => $e->getMessage()
    ]);
}
