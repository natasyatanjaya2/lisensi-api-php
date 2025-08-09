const express = require('express');
const mysql = require('mysql2');
const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());

// --- Koneksi ke database (pakai pool) ---
const db = mysql.createPool({
  host: process.env.MYSQLHOST,
  user: process.env.MYSQLUSER,
  password: process.env.MYSQLPASSWORD,
  database: process.env.MYSQLDATABASE,
  port: process.env.MYSQLPORT || 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Tes koneksi awal
db.getConnection((err, conn) => {
  if (err) {
    console.error('❌ Gagal konek MySQL:', err.message);
  } else {
    console.log('✅ Terkoneksi ke MySQL (Railway)');
    conn.release();
  }
});

// --- Health check ---
app.get('/', (req, res) => {
  res.json({ ok: true, service: 'lisensi-api', time: new Date().toISOString() });
});

// --- Aktivasi lisensi ---
app.post('/aktivasi', (req, res) => {
  const kode = req.body?.kode_lisensi;
  if (!kode) return res.status(400).json({ status: 'gagal', pesan: 'Kode lisensi kosong' });

  db.query("SELECT * FROM lisensi WHERE kode_lisensi=? AND status='belum_aktif'", [kode], (err, rows) => {
    if (err) return res.status(500).json({ status: 'gagal', pesan: err.message });

    if (rows.length > 0) {
      db.query(
        "UPDATE lisensi SET status='aktif', tanggal_aktivasi=NOW() WHERE kode_lisensi=?",
        [kode],
        (err2) => {
          if (err2) return res.status(500).json({ status: 'gagal', pesan: err2.message });
          res.json({ status: 'sukses', pesan: 'Lisensi berhasil diaktifkan' });
        }
      );
    } else {
      res.json({ status: 'gagal', pesan: 'Kode lisensi tidak valid atau sudah aktif' });
    }
  });
});

// --- Check status lisensi ---
app.post('/check', (req, res) => {
  const kode = req.body?.kode_lisensi;
  if (!kode) return res.status(400).json({ status: 'gagal', pesan: 'Kode lisensi kosong' });

  db.query("SELECT * FROM lisensi WHERE kode_lisensi=? AND status='aktif'", [kode], (err, rows) => {
    if (err) return res.status(500).json({ status: 'gagal', pesan: err.message });

    if (rows.length > 0) {
      const d = rows[0];
      res.json({
        status: 'sukses',
        data: {
          id: d.id,
          kode_lisensi: d.kode_lisensi,
          tanggal_aktivasi: d.tanggal_aktivasi,
          status: d.status
        }
      });
    } else {
      res.json({ status: 'gagal', pesan: 'Kode lisensi tidak valid atau belum aktif' });
    }
  });
});

// 404 fallback
app.use((req, res) => res.status(404).json({ status: 'gagal', pesan: 'Endpoint tidak ditemukan' }));

app.listen(PORT, () => console.log(`✅ Server berjalan di port ${PORT}`));
