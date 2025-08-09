const express = require('express');
const mysql = require('mysql2');
const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());

// Koneksi ke database Railway
const db = mysql.createConnection({
  host: process.env.MYSQLHOST,
  user: process.env.MYSQLUSER,
  password: process.env.MYSQLPASSWORD,
  database: process.env.MYSQLDATABASE,
  port: process.env.MYSQLPORT || 3306
});

// ENDPOINT: Aktivasi lisensi
app.post('/aktivasi', (req, res) => {
  const kode = req.body.kode_lisensi;
  if (!kode) return res.status(400).json({ status: "gagal", pesan: "Kode lisensi kosong" });

  db.query("SELECT * FROM lisensi WHERE kode_lisensi=? AND status='belum_aktif'", [kode], (err, rows) => {
    if (err) return res.status(500).json({ status: "gagal", pesan: err.message });

    if (rows.length > 0) {
      db.query("UPDATE lisensi SET status='aktif', tanggal_aktivasi=NOW() WHERE kode_lisensi=?", [kode], (err2) => {
        if (err2) return res.status(500).json({ status: "gagal", pesan: err2.message });
        res.json({ status: "sukses", pesan: "Lisensi berhasil diaktifkan" });
      });
    } else {
      res.json({ status: "gagal", pesan: "Kode lisensi tidak valid atau sudah aktif" });
    }
  });
});

// ENDPOINT: Check status lisensi
app.post('/check', (req, res) => {
  const kode = req.body.kode_lisensi;
  if (!kode) return res.status(400).json({ status: "gagal", pesan: "Kode lisensi kosong" });

  db.query("SELECT * FROM lisensi WHERE kode_lisensi=? AND status='aktif'", [kode], (err, rows) => {
    if (err) return res.status(500).json({ status: "gagal", pesan: err.message });

    if (rows.length > 0) {
      const data = rows[0];
      res.json({
        status: "sukses",
        data: {
          id: data.id,
          kode_lisensi: data.kode_lisensi,
          tanggal_aktivasi: data.tanggal_aktivasi,
          status: data.status
        }
      });
    } else {
      res.json({ status: "gagal", pesan: "Kode lisensi tidak valid atau belum aktif" });
    }
  });
});

// Jalankan server
app.listen(PORT, () => console.log(`✅ Server berjalan di http://localhost:${PORT}`));
