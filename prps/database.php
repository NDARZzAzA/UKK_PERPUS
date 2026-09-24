<?php
// =============================================
// KONFIGURASI DATABASE
// Perpustakaan SD N 1 Plebengan
//
// PENTING - KEAMANAN:
// Sebaiknya JANGAN menyimpan kredensial database langsung di file ini,
// apalagi jika file ini akan diunggah ke repo publik (GitHub, dsb).
// Nilai di bawah bisa dioverride lewat environment variable server
// (DB_HOST, DB_USER, DB_PASS, DB_NAME) tanpa mengubah kode ini.
// Jika environment variable tidak tersedia, dipakai nilai default.
// =============================================

define('DB_HOST', getenv('DB_HOST') ?: 'sql208.infinityfree.com');
define('DB_USER', getenv('DB_USER') ?: 'if0_42535492');
define('DB_PASS', getenv('DB_PASS') ?: 'SHWCdFfpXKed8VW');
define('DB_NAME', getenv('DB_NAME') ?: 'if0_42535492_perpustakaansd');

// =============================================
// MODE DEBUG
// Selama masih tahap setup/testing, biarkan true supaya pesan error
// (query gagal, tabel tidak ada, dsb) ditampilkan jelas di layar,
// bukan cuma "HTTP ERROR 500" polos.
// SETELAH aplikasi berjalan normal, ubah ke false agar detail teknis
// tidak terlihat pengunjung biasa.
// =============================================
define('APP_DEBUG', true);

// Tampilkan pesan error PHP yang jelas selama mode debug aktif
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Halaman error yang rapi untuk exception/fatal error yang tidak tertangani,
// termasuk query database yang gagal (tabel belum ada, kolom salah, dll)
set_exception_handler(function ($e) {
    http_response_code(500);
    error_log('Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Terjadi Kesalahan</title></head>';
    echo '<body style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:0 20px;color:#333;line-height:1.6;">';
    echo '<h2>⚠️ Terjadi kesalahan pada sistem</h2>';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<p>Detail teknis (hanya tampil karena APP_DEBUG aktif):</p>';
        echo '<pre style="background:#f5f5f5;padding:16px;border-radius:8px;white-space:pre-wrap;word-break:break-word;">'
            . htmlspecialchars($e->getMessage()) . "\n\nFile: " . htmlspecialchars($e->getFile()) . ':' . (int) $e->getLine()
            . '</pre>';
        echo '<p>Penyebab paling umum: nama/struktur tabel di database belum sesuai dengan database.sql terbaru, atau ada file lama yang belum ter-upload ulang.</p>';
    } else {
        echo '<p>Silakan hubungi administrator perpustakaan.</p>';
    }
    echo '</body></html>';
    exit();
});

// Koneksi ke database MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Koneksi database gagal: " . $e->getMessage());
    http_response_code(500);
    if (APP_DEBUG) {
        die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
    }
    die("Koneksi database gagal. Silakan hubungi administrator.");
}

// =============================================
// AUTO-PERBAIKAN KOLOM 'role' (self-healing)
//
// Versi lama database.sql belum punya role 'petugas' di kolom ENUM.
// Kalau database yang sudah lebih dulu dibuat (mis. di hosting) masih
// pakai struktur lama, MySQL akan diam-diam menyimpan role 'petugas'
// sebagai string kosong ('') alih-alih menolak dengan error - akibatnya
// akun petugas tidak bisa login dan tidak muncul di "Kelola Petugas".
//
// Blok ini otomatis mengecek dan memperbaiki struktur kolom tsb setiap
// kali aplikasi jalan, supaya tidak perlu jalankan ALTER TABLE manual
// lewat phpMyAdmin. Query-nya ringan (cek metadata kolom), jadi aman
// dijalankan di setiap request.
// =============================================
$roleColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if ($roleColumnCheck && ($colInfo = mysqli_fetch_assoc($roleColumnCheck))) {
    if (strpos($colInfo['Type'], "'petugas'") === false) {
        // Kolom role belum mengenal 'petugas' - perbarui strukturnya
        mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'petugas', 'user') NOT NULL DEFAULT 'user'");
    }
}
// Perbaiki baris yang sudah terlanjur tersimpan kosong akibat bug ENUM lama.
// Baris seperti ini hanya bisa terjadi dari proses tambah-petugas (admin/petugas.php),
// jadi aman diasumsikan sebagai role 'petugas'.
mysqli_query($conn, "UPDATE users SET role = 'petugas' WHERE role = ''");

// =============================================
// AUTO-PERBAIKAN: Tabel Ulasan/Rating (self-healing)
//
// Fitur "Komentar/Ulasan & Bintang" di halaman beranda butuh tabel ulasan.
// Blok ini otomatis membuat tabelnya kalau belum ada, supaya tidak perlu
// import ulang database.sql secara manual lewat phpMyAdmin di hosting.
// =============================================
$ulasanTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'ulasan'");
if ($ulasanTableCheck && mysqli_num_rows($ulasanTableCheck) === 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ulasan (
        id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
        nama_pengulas VARCHAR(100) NOT NULL,
        peran VARCHAR(50) NOT NULL DEFAULT 'Pengunjung',
        rating TINYINT UNSIGNED NOT NULL,
        komentar TEXT NOT NULL,
        id_user INT NULL,
        status ENUM('tampil', 'disembunyikan') NOT NULL DEFAULT 'tampil',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// =============================================
// AUTO-PERBAIKAN: Verifikasi Peminjaman oleh Petugas + Notifikasi (self-healing)
//
// Fitur ini menambah alur baru: saat anggota mengajukan pinjam buku, status
// transaksi awalnya 'menunggu' (belum otomatis 'dipinjam') sampai divalidasi
// oleh petugas/admin lewat admin/verifikasi.php. Setiap perubahan status
// mengirim notifikasi ke pihak terkait lewat tabel 'notifikasi'.
// Blok ini otomatis membuat tabel/kolom yang dibutuhkan kalau belum ada,
// supaya tidak perlu import ulang database.sql secara manual di hosting.
// =============================================

// 1) Tabel notifikasi
$notifTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'notifikasi'");
if ($notifTableCheck && mysqli_num_rows($notifTableCheck) === 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifikasi (
        id_notifikasi INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        judul VARCHAR(150) NOT NULL,
        pesan TEXT NOT NULL,
        tipe ENUM('info', 'sukses', 'ditolak') NOT NULL DEFAULT 'info',
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// 2) Kolom status transaksi perlu mengenal 'menunggu' (menunggu verifikasi) dan 'ditolak'
$statusColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM transaksi LIKE 'status'");
if ($statusColumnCheck && ($colInfo = mysqli_fetch_assoc($statusColumnCheck))) {
    if (strpos($colInfo['Type'], "'menunggu'") === false) {
        mysqli_query($conn, "ALTER TABLE transaksi MODIFY COLUMN status 
            ENUM('menunggu', 'dipinjam', 'dikembalikan', 'terlambat', 'ditolak') NOT NULL DEFAULT 'menunggu'");
    }
}

// 3) Kolom tambahan untuk mencatat siapa yang memverifikasi & alasan penolakan
$catatanColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM transaksi LIKE 'catatan_petugas'");
if ($catatanColumnCheck && mysqli_num_rows($catatanColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN catatan_petugas VARCHAR(255) DEFAULT NULL AFTER denda");
}
$verifPetugasColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM transaksi LIKE 'id_petugas_verifikasi'");
if ($verifPetugasColumnCheck && mysqli_num_rows($verifPetugasColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN id_petugas_verifikasi INT DEFAULT NULL AFTER catatan_petugas");
    mysqli_query($conn, "ALTER TABLE transaksi ADD CONSTRAINT fk_transaksi_petugas_verifikasi 
        FOREIGN KEY (id_petugas_verifikasi) REFERENCES users(id_user) ON DELETE SET NULL");
}
$verifWaktuColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM transaksi LIKE 'diverifikasi_at'");
if ($verifWaktuColumnCheck && mysqli_num_rows($verifWaktuColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN diverifikasi_at TIMESTAMP NULL DEFAULT NULL AFTER id_petugas_verifikasi");
}

// =============================================
// AUTO-PERBAIKAN: Kolom 'jumlah' buku per transaksi (self-healing)
//
// Fitur ini memungkinkan anggota meminjam lebih dari 1 eksemplar buku
// sekaligus dalam satu transaksi. Database yang dibuat sebelum fitur ini
// ada (atau lewat database.sql versi lama) belum punya kolom ini, sehingga
// INSERT di user/borrow.php gagal dengan error "Unknown column 'jumlah'".
// =============================================
$jumlahColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM transaksi LIKE 'jumlah'");
if ($jumlahColumnCheck && mysqli_num_rows($jumlahColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN jumlah INT NOT NULL DEFAULT 1 AFTER id_buku");
}

// =============================================
// AUTO-PERBAIKAN: Kolom Kondisi Buku Sebelum & Sesudah Dipinjam (self-healing)
//
// Fitur ini mencatat kualitas/kondisi fisik buku saat diserahkan ke anggota
// (kondisi_sebelum) dan saat dikembalikan (kondisi_sesudah), supaya petugas
// bisa melacak kerusakan/kehilangan buku akibat peminjaman.
// =============================================
$kondisiSebelumColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM transaksi LIKE 'kondisi_sebelum'");
if ($kondisiSebelumColumnCheck && mysqli_num_rows($kondisiSebelumColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN kondisi_sebelum 
        ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') NOT NULL DEFAULT 'Baik' AFTER diverifikasi_at");
}
$kondisiSesudahColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM transaksi LIKE 'kondisi_sesudah'");
if ($kondisiSesudahColumnCheck && mysqli_num_rows($kondisiSesudahColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN kondisi_sesudah 
        ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') DEFAULT NULL AFTER kondisi_sebelum");
}

// =============================================
// AUTO-PERBAIKAN: Tabel Usulan Pengadaan Buku (self-healing)
//
// Fitur ini memungkinkan siswa mengusulkan judul buku baru untuk dibeli
// perpustakaan. Usulan ditinjau oleh admin/petugas (diterima/ditolak),
// dan siswa mendapat notifikasi hasil tinjauannya lewat tabel 'notifikasi'.
// Blok ini otomatis membuat tabelnya kalau belum ada, supaya tidak perlu
// import ulang database.sql secara manual lewat phpMyAdmin di hosting.
// =============================================
$usulanTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'usulan_buku'");
if ($usulanTableCheck && mysqli_num_rows($usulanTableCheck) === 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS usulan_buku (
        id_usulan INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        judul_buku VARCHAR(200) NOT NULL,
        penulis VARCHAR(100) DEFAULT NULL,
        kategori VARCHAR(50) DEFAULT NULL,
        alasan TEXT NOT NULL,
        status ENUM('menunggu', 'diterima', 'ditolak') NOT NULL DEFAULT 'menunggu',
        catatan_admin VARCHAR(255) DEFAULT NULL,
        ditinjau_oleh INT DEFAULT NULL,
        ditinjau_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
        FOREIGN KEY (ditinjau_oleh) REFERENCES users(id_user) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// =============================================
// AUTO-PERBAIKAN: Kolom Sampul Buku (self-healing)
//
// Fitur ini menampilkan gambar sampul/cover di setiap kartu buku (katalog,
// beranda, halaman pinjam, dsb) sesuai judulnya. Kolom 'sampul' hanya
// menyimpan NAMA FILE gambar (fisiknya ada di folder assets/img/sampul/);
// kalau kosong/NULL, tampilan otomatis memakai sampul default berwarna.
// =============================================
$sampulColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM buku LIKE 'sampul'");
if ($sampulColumnCheck && mysqli_num_rows($sampulColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE buku ADD COLUMN sampul VARCHAR(255) DEFAULT NULL AFTER stok");
}
?>