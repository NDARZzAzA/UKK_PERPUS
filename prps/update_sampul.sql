-- Jalankan query ini di phpMyAdmin (tab SQL) SETELAH ke-10 file gambar
-- (bk001.jpg s/d bk010.jpg) sudah kamu upload ke folder assets/img/sampul/
-- di hosting lewat File Manager.
--
-- Query ini hanya mengisi nama file gambar ke kolom 'sampul' masing-masing
-- buku (bukan mengunggah gambarnya - itu tetap lewat File Manager).

UPDATE buku SET sampul = 'bk001.jpg' WHERE kode_buku = 'BK001';
UPDATE buku SET sampul = 'bk002.jpg' WHERE kode_buku = 'BK002';
UPDATE buku SET sampul = 'bk003.jpg' WHERE kode_buku = 'BK003';
UPDATE buku SET sampul = 'bk004.jpg' WHERE kode_buku = 'BK004';
UPDATE buku SET sampul = 'bk005.jpg' WHERE kode_buku = 'BK005';
UPDATE buku SET sampul = 'bk006.jpg' WHERE kode_buku = 'BK006';
UPDATE buku SET sampul = 'bk007.jpg' WHERE kode_buku = 'BK007';
UPDATE buku SET sampul = 'bk008.jpg' WHERE kode_buku = 'BK008';
UPDATE buku SET sampul = 'bk009.jpg' WHERE kode_buku = 'BK009';
UPDATE buku SET sampul = 'bk010.jpg' WHERE kode_buku = 'BK010';
