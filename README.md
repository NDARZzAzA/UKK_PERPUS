
   > ⚠️ **Jangan pernah commit kredensial database asli ke repo publik.** Gunakan environment variable, atau file konfigurasi terpisah yang dimasukkan ke `.gitignore`.

4. **Jalankan dengan PHP built-in server** (atau taruh di folder `htdocs` XAMPP/Laragon)
```bash
   php -S localhost:8000
```
   Lalu buka `http://localhost:8000` di browser.

---

## 🌐 Deploy ke Hosting (InfinityFree / hosting PHP+MySQL lainnya)

1. Buat database lewat panel hosting (biasanya format nama `if0_xxxxxxx_namadb`)
2. Import `database.sql` lewat phpMyAdmin — **masuk dulu ke database tujuan**, baru import (bukan lewat tab SQL global)
3. Upload seluruh file proyek ke `htdocs`/`public_html`
4. Set environment variable `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` sesuai kredensial hosting (kalau panel hosting mendukung), atau sesuaikan `config/database.php`
5. Set `APP_DEBUG` di `config/database.php` menjadi `false` setelah aplikasi berjalan normal, supaya detail error teknis tidak terlihat pengunjung

---

## 📐 Struktur Aplikasi

### 1. Diagram Struktur

Diagram berikut menggambarkan struktur folder dan file pada aplikasi E-Perpustakaan SD N 1 Plebengan, terdiri dari enam folder utama (`admin/`, `user/`, `includes/`, `config/`, `assets/`, `dokumentasi/`) beserta file-file inti di direktori root.

![Diagram Struktur Aplikasi Perpustakaan SD N 1 Plebengan](./assets/img/struktur-aplikasi.png)

### 2. Penjelasan Struktur

#### 2.1 Folder `admin/` dan `user/`

Dua folder ini menyimpan halaman yang dipisah berdasarkan peran pengguna:

- **`admin/`:** Halaman khusus admin & petugas (dashboard, kelola buku, dll)
- **`user/`:** Halaman khusus siswa (dashboard, peminjaman, dll)

#### 2.2 Folder `includes/` dan `config/`

Dua folder ini berisi komponen dan konfigurasi pendukung aplikasi:

- **`includes/`:** Komponen bersama (header, footer, auth, notifikasi)
- **`config/`:** Konfigurasi koneksi database

#### 2.3 Folder `assets/` dan `dokumentasi/`

Folder ini menyimpan aset statis dan dokumentasi tambahan proyek:

- **`assets/css/`:** Stylesheet
- **`assets/img/`:** Gambar (logo, foto sekolah, sampul buku)
- **`assets/video/`:** Video hero landing page
- **`dokumentasi/`:** Dokumentasi tambahan proyek

#### 2.4 File Utama

File-file berikut berada langsung di direktori root aplikasi:

- **`database.sql`:** Skema database utama
- **`index.php`:** Landing page publik
- **`katalog.php`:** Katalog buku publik
- **`login.php` / `register.php`:** Login & registrasi akun
- **`logout.php`:** Keluar dari sesi aplikasi

### 3. Ringkasan Struktur Folder

| Folder/File | Deskripsi | Isi/Contoh |
|---|---|---|
| `admin/` | Halaman admin & petugas | dashboard, kelola buku, kelola anggota, kelola petugas, dll |
| `user/` | Halaman siswa | dashboard, peminjaman, pengembalian, usulan buku, dll |
| `includes/` | Komponen bersama | header, footer, auth, notifikasi |
| `config/` | Konfigurasi database | `database.php` |
| `assets/` | Aset statis | `css/`, `img/`, `video/` |
| `dokumentasi/` | Dokumentasi proyek | Dokumentasi tambahan proyek |
| File Utama | Halaman inti aplikasi | `index.php`, `katalog.php`, `login.php`, `register.php`, `logout.php`, `database.sql` |

---

## 🔒 Catatan Keamanan

- Pastikan `config/database.php` **tidak berisi kredensial produksi yang di-hardcode** sebelum repo dipublikasikan — gunakan environment variable.
- Set `APP_DEBUG = false` di lingkungan produksi.
- Ganti password admin/petugas default (jika ada data contoh di `database.sql`) sebelum digunakan secara nyata.

---

## 📄 Lisensi

Proyek ini dibuat untuk kebutuhan internal **SD Negeri Plebengan**. Silakan sesuaikan lisensi (misal MIT) jika ingin dibuka untuk digunakan sekolah lain.

---

## 🙏 Kredit

Dikembangkan untuk mendukung digitalisasi perpustakaan **SD Negeri Plebengan**, Bambanglipuro, Bantul, D.I. Yogyakarta.
