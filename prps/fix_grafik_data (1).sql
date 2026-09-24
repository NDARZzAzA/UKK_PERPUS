-- =====================================================================
-- FIX: Grafik "Tren Peminjaman (6 Bulan Terakhir)" kosong
-- Jalankan file ini di phpMyAdmin (tab SQL) pada database yang SUDAH
-- berjalan (live). Ini AMAN dijalankan berkali-kali dan TIDAK menghapus
-- data yang sudah ada — hanya menambah beberapa transaksi contoh dengan
-- tanggal relatif ke HARI INI, supaya grafik langsung terisi.
--
-- Kalau kamu sudah punya transaksi asli (dari input murid via aplikasi)
-- yang tanggalnya dalam 6 bulan terakhir, grafik seharusnya sudah
-- otomatis terisi tanpa perlu file ini. File ini hanya untuk mengisi
-- demo/contoh kalau tabel transaksi masih kosong atau isinya data lama.
-- =====================================================================

-- Ambil beberapa id_user (role user/siswa) dan id_buku yang sudah ada,
-- supaya tidak perlu tahu ID persis di databasemu.
SET @u1 = (SELECT id_user FROM users WHERE role = 'user' ORDER BY id_user LIMIT 1 OFFSET 0);
SET @u2 = (SELECT id_user FROM users WHERE role = 'user' ORDER BY id_user LIMIT 1 OFFSET 1);
SET @u3 = (SELECT id_user FROM users WHERE role = 'user' ORDER BY id_user LIMIT 1 OFFSET 2);
SET @b1 = (SELECT id_buku FROM buku ORDER BY id_buku LIMIT 1 OFFSET 0);
SET @b2 = (SELECT id_buku FROM buku ORDER BY id_buku LIMIT 1 OFFSET 1);
SET @b3 = (SELECT id_buku FROM buku ORDER BY id_buku LIMIT 1 OFFSET 2);

INSERT INTO transaksi
    (kode_transaksi, id_user, id_buku, tanggal_pinjam, tanggal_kembali, tanggal_dikembalikan, status, denda)
VALUES
    ('TRX-DEMO-001', @u1, @b1,
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), INTERVAL 7 DAY),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), INTERVAL 6 DAY),
        'dikembalikan', 0),
    ('TRX-DEMO-002', @u2, @b2,
        DATE_SUB(CURDATE(), INTERVAL 4 MONTH),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 4 MONTH), INTERVAL 7 DAY),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 4 MONTH), INTERVAL 10 DAY),
        'dikembalikan', 3000),
    ('TRX-DEMO-003', @u3, @b3,
        DATE_SUB(CURDATE(), INTERVAL 3 MONTH),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 3 MONTH), INTERVAL 7 DAY),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 3 MONTH), INTERVAL 9 DAY),
        'dikembalikan', 2000),
    ('TRX-DEMO-004', @u1, @b2,
        DATE_SUB(CURDATE(), INTERVAL 2 MONTH),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL 7 DAY),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL 7 DAY),
        'dikembalikan', 0),
    ('TRX-DEMO-005', @u2, @b3,
        DATE_SUB(CURDATE(), INTERVAL 1 MONTH),
        DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), INTERVAL 7 DAY),
        NULL,
        'dipinjam', 0),
    ('TRX-DEMO-006', @u3, @b1,
        DATE_SUB(CURDATE(), INTERVAL 10 DAY),
        DATE_SUB(CURDATE(), INTERVAL 3 DAY),
        NULL,
        'terlambat', 5000);

-- Catatan: insert langsung lewat SQL ini TIDAK ikut mengurangi stok buku
-- (biasanya itu dilakukan oleh admin/transaksi.php saat transaksi dibuat
-- lewat form). Kalau mau stok tetap akurat, kurangi manual 2 (untuk
-- TRX-DEMO-005 dan TRX-DEMO-006 yang statusnya masih 'dipinjam'):
-- UPDATE buku SET stok = stok - 1 WHERE id_buku = @b3;
-- UPDATE buku SET stok = stok - 1 WHERE id_buku = @b1;
