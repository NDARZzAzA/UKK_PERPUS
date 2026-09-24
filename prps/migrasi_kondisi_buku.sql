-- Migrasi: menambahkan pencatatan kualitas/kondisi fisik buku
-- sebelum dipinjam dan sesudah dikembalikan.
-- Jalankan file ini SATU KALI SAJA jika database Anda sudah ada sebelumnya
-- (kalau baru membuat database dari database.sql yang terbaru, kolom ini sudah otomatis ada).
--
-- CATATAN: aplikasi juga sudah punya mekanisme self-healing di
-- config/database.php yang otomatis menambahkan kolom ini sendiri kalau
-- belum ada, jadi menjalankan file ini secara manual sebenarnya opsional.

ALTER TABLE transaksi
    ADD COLUMN kondisi_sebelum ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') NOT NULL DEFAULT 'Baik' AFTER diverifikasi_at,
    ADD COLUMN kondisi_sesudah ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') DEFAULT NULL AFTER kondisi_sebelum;
