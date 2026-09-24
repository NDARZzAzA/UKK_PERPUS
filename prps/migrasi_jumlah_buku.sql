-- Migrasi: menambahkan dukungan "jumlah buku dipinjam" per transaksi
-- Jalankan file ini SATU KALI SAJA jika database Anda sudah ada sebelumnya
-- (kalau baru membuat database dari database.sql yang terbaru, kolom ini sudah otomatis ada).

ALTER TABLE transaksi
    ADD COLUMN jumlah INT NOT NULL DEFAULT 1 AFTER id_buku;
