-- =============================================
-- DATABASE: perpustakaan_sd_plebengan
-- Aplikasi Peminjaman Buku Digital
-- Perpustakaan SD N 1 Plebengan
-- =============================================

-- =============================================
-- PENTING - HOSTING GRATIS (InfinityFree, dsb):
-- Baris CREATE DATABASE / USE di bawah ini DINONAKTIFKAN karena hosting
-- gratis biasanya TIDAK MENGIZINKAN membuat database lewat SQL - database
-- harus dibuat lebih dulu lewat panel hosting (nama otomatis berformat
-- if0_xxxxxxx_namadb). Import file ini SETELAH memilih database yang
-- sudah kamu buat di phpMyAdmin (jangan lewat tab "SQL" global, tapi
-- masuk dulu ke database tujuan, baru Import file ini).
--
-- Kalau kamu pakai hosting sendiri/lokal (XAMPP/Laragon) dan MEMANG mau
-- membuat database baru otomatis, hapus tanda komentar (--) di 2 baris
-- di bawah ini.
-- =============================================
-- CREATE DATABASE IF NOT EXISTS perpustakaan_sd_plebengan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE perpustakaan_sd_plebengan;

-- Tabel Users (Admin & Siswa)
CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika tabel users sudah ada sebelumnya (upgrade dari versi lama tanpa role 'petugas'),
-- jalankan baris berikut secara manual sekali saja untuk menambahkan role petugas:
-- ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'petugas', 'user') NOT NULL DEFAULT 'user';

-- Tabel Buku
CREATE TABLE IF NOT EXISTS buku (
    id_buku INT AUTO_INCREMENT PRIMARY KEY,
    kode_buku VARCHAR(20) NOT NULL UNIQUE,
    judul VARCHAR(200) NOT NULL,
    penulis VARCHAR(100) NOT NULL,
    penerbit VARCHAR(100) NOT NULL,
    tahun_terbit YEAR NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    stok INT NOT NULL DEFAULT 1,
    sampul VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Kolom 'sampul' menyimpan NAMA FILE gambar sampul buku (disimpan fisik di
-- folder assets/img/sampul/), bukan gambarnya langsung. NULL/kosong berarti
-- buku belum punya sampul dan akan ditampilkan dengan sampul default berwarna.

-- Tabel Anggota
CREATE TABLE IF NOT EXISTS anggota (
    id_anggota INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(20) NOT NULL UNIQUE,
    nama_anggota VARCHAR(100) NOT NULL,
    kelas VARCHAR(20) NOT NULL,
    alamat VARCHAR(255),
    no_telepon VARCHAR(15),
    id_user INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Transaksi (Peminjaman)
-- status 'menunggu'    = anggota baru mengajukan pinjam, belum diverifikasi petugas
-- status 'dipinjam'    = sudah diverifikasi/disetujui petugas, buku di tangan anggota
-- status 'ditolak'     = permintaan pinjam ditolak petugas (lihat catatan_petugas untuk alasan)
-- kondisi_sebelum = kondisi fisik buku saat diserahkan ke anggota (dicatat petugas saat menyetujui/menambah transaksi)
-- kondisi_sesudah = kondisi fisik buku saat dikembalikan (dicatat saat proses pengembalian, NULL jika belum dikembalikan)
CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(30) NOT NULL UNIQUE,
    id_user INT NOT NULL,
    id_buku INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE,
    tanggal_dikembalikan DATE,
    status ENUM('menunggu', 'dipinjam', 'dikembalikan', 'terlambat', 'ditolak') NOT NULL DEFAULT 'menunggu',
    denda DECIMAL(10,2) DEFAULT 0.00,
    catatan_petugas VARCHAR(255) DEFAULT NULL,
    id_petugas_verifikasi INT DEFAULT NULL,
    diverifikasi_at TIMESTAMP NULL DEFAULT NULL,
    kondisi_sebelum ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') NOT NULL DEFAULT 'Baik',
    kondisi_sesudah ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE,
    FOREIGN KEY (id_petugas_verifikasi) REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Notifikasi (bell icon untuk anggota & petugas/admin)
CREATE TABLE IF NOT EXISTS notifikasi (
    id_notifikasi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('info', 'sukses', 'ditolak') NOT NULL DEFAULT 'info',
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Ulasan (komentar + rating bintang di halaman beranda)
-- Catatan: tabel ini juga dibuat otomatis oleh config/database.php kalau belum ada,
-- jadi untuk database yang sudah berjalan sebelumnya tidak wajib jalankan ini secara manual.
CREATE TABLE IF NOT EXISTS ulasan (
    id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
    nama_pengulas VARCHAR(100) NOT NULL,
    peran VARCHAR(50) NOT NULL DEFAULT 'Pengunjung',
    rating TINYINT UNSIGNED NOT NULL,
    komentar TEXT NOT NULL,
    id_user INT NULL,
    status ENUM('tampil', 'disembunyikan') NOT NULL DEFAULT 'tampil',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Usulan Pengadaan Buku (siswa mengusulkan judul buku baru untuk dibeli perpustakaan)
-- status 'menunggu'  = usulan baru dikirim siswa, belum ditinjau admin/petugas
-- status 'diterima'  = usulan disetujui untuk pengadaan
-- status 'ditolak'   = usulan tidak disetujui (lihat catatan_admin untuk alasan)
CREATE TABLE IF NOT EXISTS usulan_buku (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DATA AWAL (SAMPLE DATA)
-- =============================================

-- Admin default
INSERT INTO users (nama_lengkap, username, password, role) VALUES
('Kepala Pustakawan SD N 1 Plebengan', 'admin', MD5('admin123'), 'admin');

-- Petugas (staf) perpustakaan default
INSERT INTO users (nama_lengkap, username, password, role) VALUES
('Wulan Setyaningsih', 'petugas', MD5('petugas123'), 'petugas'),
('Agus Prabowo', 'agus', MD5('petugas123'), 'petugas');

-- User/Siswa default
INSERT INTO users (nama_lengkap, username, password, role) VALUES
('Ahmad Rizky Pratama', 'ahmad', MD5('user123'), 'user'),
('Siti Nurhaliza', 'siti', MD5('user123'), 'user'),
('Budi Santoso', 'budi', MD5('user123'), 'user'),
('Dewi Lestari', 'dewi', MD5('user123'), 'user'),
('Reza Firmansyah', 'reza', MD5('user123'), 'user');

-- Data Anggota
INSERT INTO anggota (nis, nama_anggota, kelas, alamat, no_telepon, id_user) VALUES
('2024001', 'Ahmad Rizky Pratama', 'Kelas 6', 'Desa Plebengan RT 01/02', '081234567890', 2),
('2024002', 'Siti Nurhaliza', 'Kelas 5', 'Desa Plebengan RT 03/01', '081234567891', 3),
('2024003', 'Budi Santoso', 'Kelas 4', 'Desa Plebengan RT 02/03', '081234567892', 4),
('2024004', 'Dewi Lestari', 'Kelas 6', 'Desa Plebangan RT 01/01', '081234567893', 5),
('2024005', 'Reza Firmansyah', 'Kelas 3', 'Desa Plebengan RT 04/02', '081234567894', 6);

-- Data Buku (sesuai konteks SD)
-- Kolom 'sampul' diisi nama file gambar yang sudah disiapkan di folder
-- assets/img/sampul/ (dibuat otomatis sesuai judul & kategori masing-masing buku).
INSERT INTO buku (kode_buku, judul, penulis, penerbit, tahun_terbit, kategori, stok, sampul) VALUES
('BK001', 'Cerita Rakyat Nusantara', 'Mohammad Hatta', 'Gramedia Pustaka Utama', 2022, 'Cerita Anak', 5, 'bk001.jpg'),
('BK002', 'Dongeng Kancil dan Buaya', 'Guruh Soekarno', 'Erlangga', 2023, 'Dongeng', 4, 'bk002.jpg'),
('BK003', 'Atlas Dunia untuk Anak', 'Siti Sundari', 'Balai Pustaka', 2022, 'Pengetahuan Umum', 3, 'bk003.jpg'),
('BK004', 'Belajar Matematika Kelas 1-3', 'Depdiknas', 'Kemendikbud', 2023, 'Pelajaran', 10, 'bk004.jpg'),
('BK005', 'IPA untuk SD Kelas 4-6', 'Sri Wahyuni', 'Erlangga', 2023, 'Pelajaran', 8, 'bk005.jpg'),
('BK006', 'Ensiklopedia Anak Cerdas', 'Tim Pustaka Indonesia', 'Penerbit Cerdas', 2022, 'Pengetahuan Umum', 3, 'bk006.jpg'),
('BK007', 'Kumpulan Puisi Anak Indonesia', 'Taufik Ismail', 'Gramedia', 2021, 'Puisi', 4, 'bk007.jpg'),
('BK008', 'Sejarah Indonesia untuk Anak', 'Marah Roesli', 'Balai Pustaka', 2022, 'Sejarah', 5, 'bk008.jpg'),
('BK009', 'Atlas Indonesia Lengkap', 'Bakri Lie', 'Pusaka', 2023, 'Pengetahuan Umum', 3, 'bk009.jpg'),
('BK010', 'Seribu Satwa Nusantara', 'Rusdi', 'Gramedia', 2021, 'Pengetahuan Umum', 4, 'bk010.jpg'),
('BK011', 'Belajar Bahasa Indonesia', 'Hasan Basri', 'Kemendikbud', 2023, 'Pelajaran', 10, 'bk011.jpg'),
('BK012', 'Pandan Berduri', 'Yusaf R. Anas', 'Balai Pustaka', 2020, 'Cerita Anak', 6, 'bk012.jpg'),
('BK013', 'Fabel Nusantara: Si Kancil Cerdik', 'Nania Idris', 'Mizan', 2021, 'Dongeng', 5, 'bk013.jpg'),
('BK014', 'Mengenal Tokoh Pahlawan Indonesia', 'Rosihan Anwar', 'Balai Pustaka', 2022, 'Sejarah', 4, 'bk014.jpg'),
('BK015', 'Petualangan Sains untuk Anak', 'Yohanes Surya', 'Kandel', 2023, 'Pengetahuan Umum', 6, 'bk015.jpg'),
('BK016', 'Kumpulan Cerita Rakyat Jawa', 'Ki Padmosoekotjo', 'Kanisius', 2021, 'Cerita Anak', 5, 'bk016.jpg'),
('BK017', 'Belajar Bahasa Inggris Dasar', 'Rina Susanti', 'Erlangga', 2023, 'Pelajaran', 8, 'bk017.jpg'),
('BK018', 'Ensiklopedia Hewan Langka', 'Tim Pustaka Sains', 'Bhuana Ilmu Populer', 2022, 'Pengetahuan Umum', 3, 'bk018.jpg'),
('BK019', 'Panduan Sholat & Doa Sehari-hari', 'Ustadz Farid', 'Republika', 2023, 'Agama', 7, 'bk019.jpg'),
('BK020', 'Cerita Nabi untuk Anak Muslim', 'Yasin Al-Hafizh', 'Al-Qalam', 2022, 'Agama', 6, 'bk020.jpg');

-- Data Transaksi Sample
-- CATATAN: tanggal dibuat RELATIF terhadap CURDATE() (bukan tanggal tetap)
-- supaya sample data ini selalu masuk ke jendela "6 bulan terakhir" yang
-- dipakai grafik Tren Peminjaman di admin/transaksi.php, tidak peduli
-- kapan file ini di-import.
INSERT INTO transaksi (kode_transaksi, id_user, id_buku, tanggal_pinjam, tanggal_kembali, tanggal_dikembalikan, status, denda) VALUES
('TRX-DEMO-001', 2, 1,
    DATE_SUB(CURDATE(), INTERVAL 4 MONTH),
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 4 MONTH), INTERVAL 7 DAY),
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 4 MONTH), INTERVAL 6 DAY),
    'dikembalikan', 0.00),
('TRX-DEMO-002', 3, 2,
    DATE_SUB(CURDATE(), INTERVAL 2 MONTH),
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL 7 DAY),
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL 10 DAY),
    'dikembalikan', 3000.00),
('TRX-DEMO-003', 2, 5,
    DATE_SUB(CURDATE(), INTERVAL 3 DAY),
    DATE_ADD(CURDATE(), INTERVAL 4 DAY),
    NULL, 'dipinjam', 0.00);

-- Data Usulan Pengadaan Buku Sample (dikirim siswa, ditinjau admin/petugas)
INSERT INTO usulan_buku (id_user, judul_buku, penulis, kategori, alasan, status, catatan_admin, ditinjau_oleh, ditinjau_at, created_at) VALUES
(2, 'Laskar Pelangi', 'Andrea Hirata', 'Cerita Anak', 'Banyak teman sekelas yang ingin baca novel ini tapi belum ada di perpustakaan.', 'menunggu', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'Ensiklopedia Sains Sederhana', 'Tim Pustaka Sains', 'Pengetahuan Umum', 'Untuk membantu tugas IPA, koleksi sains yang ada masih sedikit.', 'diterima', 'Disetujui, akan dianggarkan bulan depan.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(6, 'Komik Edukasi Sejarah Dunia', 'Tim Kreatif', 'Sejarah', 'Bentuk komik lebih menarik minat baca adik kelas.', 'ditolak', 'Untuk saat ini fokus pengadaan pada buku pelajaran wajib dulu.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Data Ulasan Sample (tampil di beranda sebagai contoh, boleh dihapus/disembunyikan lewat admin)
INSERT INTO ulasan (nama_pengulas, peran, rating, komentar, id_user, created_at) VALUES
('Naila Az-Zahra', 'Siswa', 5, 'Sukaaa banget! Sekarang aku bisa cek dulu bukunya ada apa nggak sebelum ke perpustakaan.', 2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
('Bu Sri Wahyuni', 'Petugas Perpustakaan', 5, 'Rekap peminjaman jadi jauh lebih rapi, tidak perlu tulis manual di buku besar lagi. Sangat membantu!', NULL, DATE_SUB(NOW(), INTERVAL 8 DAY)),
('Pak Adi Nugroho', 'Orang Tua Siswa', 4, 'Anak saya jadi lebih semangat baca karena bisa lihat sendiri koleksi yang tersedia dari rumah.', NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('Reza Firmansyah', 'Siswa', 4, 'Mudah dipakai, tampilannya juga bagus. Semoga ke depannya bisa nambah lebih banyak buku cerita.', 6, DATE_SUB(NOW(), INTERVAL 2 DAY));
