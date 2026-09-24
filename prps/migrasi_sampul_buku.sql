-- Migrasi: menambahkan dukungan "Sampul Buku" (gambar cover) di data buku
-- Jalankan file ini SATU KALI SAJA jika database Anda sudah ada sebelumnya
-- (kalau baru membuat database dari database.sql yang terbaru, kolom ini sudah otomatis ada).
--
-- CATATAN: kolom ini hanya menyimpan NAMA FILE gambar (mis. 'bk001.jpg'),
-- bukan gambarnya langsung. File gambarnya sendiri disimpan di folder
-- assets/img/sampul/ pada aplikasi. Aplikasi juga sudah otomatis menambahkan
-- kolom ini sendiri saat pertama kali dijalankan (self-healing), jadi
-- menjalankan file ini manual bersifat opsional.

ALTER TABLE buku
    ADD COLUMN sampul VARCHAR(255) DEFAULT NULL AFTER stok;
