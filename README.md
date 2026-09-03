# 📚 E-Perpustakaan SD Negeri Plebengan

Aplikasi web perpustakaan digital untuk **SD Negeri Plebengan**, Bambanglipuro, Bantul, Yogyakarta. Dibangun untuk mengelola katalog buku, peminjaman, pengembalian, dan keanggotaan perpustakaan sekolah secara online — bisa diakses siswa, petugas, dan admin kapan saja.

🔗 **Demo Live:** [sd-plebengan.free.nf](https://sd-plebengan.free.nf/index.php)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)

---

## ✨ Fitur

### Halaman Publik
- Landing page dengan profil singkat perpustakaan sekolah
- Katalog buku yang bisa dilihat tanpa login
- Halaman ulasan/testimoni

### Untuk Siswa (User)
- Registrasi & login akun
- Melihat katalog dan detail buku (termasuk sampul & ketersediaan)
- Ajukan peminjaman buku secara online
- Ajukan pengembalian buku
- Riwayat transaksi peminjaman pribadi
- Usulan judul buku baru
- Notifikasi status peminjaman/pengembalian

### Untuk Petugas & Admin
- Dashboard statistik perpustakaan
- Kelola data buku (tambah, edit, hapus, sampul, kondisi, jumlah stok)
- Kelola data anggota
- Verifikasi & kelola transaksi peminjaman/pengembalian
- Kelola pengelolaan petugas (khusus admin)
- Kelola ulasan dan usulan buku dari siswa
- Laporan aktivitas perpustakaan
- Sistem notifikasi internal

---

## 🛠️ Teknologi

- **Backend:** PHP (native, tanpa framework)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3 (custom, tanpa framework CSS eksternal)
- **Font:** Google Fonts (Fredoka, Plus Jakarta Sans)
- **Hosting demo:** InfinityFree

---

## 📁 Struktur Folder

```
├── admin/              # Halaman khusus admin & petugas (dashboard, kelola buku, dll)
├── user/               # Halaman khusus siswa (dashboard, peminjaman, dll)
├── includes/           # Komponen bersama (header, footer, auth, notifikasi)
├── config/             # Konfigurasi koneksi database
├── assets/
│   ├── css/            # Stylesheet
│   ├── img/            # Gambar (logo, foto sekolah, sampul buku)
│   └── video/          # Video hero landing page
├── dokumentasi/        # Dokumentasi tambahan proyek
├── database.sql        # Skema database utama
├── index.php           # Landing page publik
├── katalog.php         # Katalog buku publik
├── login.php / register.php
└── logout.php
```

---

## 🚀 Instalasi Lokal

1. **Clone repo ini**
```bash
   git clone https://github.com/USERNAME/NAMA-REPO.git
   cd NAMA-REPO
```

2. **Siapkan database**
   - Buat database baru di MySQL/MariaDB (misal via phpMyAdmin di XAMPP/Laragon)
   - Import `database.sql` ke database tersebut
   - Jika ada file migrasi tambahan (`migrasi_*.sql`, `fix_grafik_data.sql`), jalankan juga sesuai urutan/kebutuhan

3. **Atur koneksi database**

   Buat environment variable berikut (lebih aman) atau sesuaikan langsung di `config/database.php`:
```
   DB_HOST=localhost
   DB_USER=nama_user_db
   DB_PASS=password_db
   DB_NAME=nama_database
```

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

Diagram berikut menggambarkan struktur peran dan modul pada aplikasi Perpustakaan SD N 1 Plebengan, terbagi menjadi dua peran utama (Admin/Pustakawan dan User/Siswa) beserta kelompok modul dan halaman di bawahnya.

![Diagram Struktur Aplikasi Perpustakaan SD N 1 Plebengan](./struktur-aplikasi.png)

### 2. Penjelasan Struktur

#### 2.1 Peran Admin (Pustakawan)

Admin mengelola seluruh data master dan transaksi perpustakaan, terbagi menjadi dua kelompok modul:

- **Manajemen Data:** `buku.php`, `anggota.php`, `petugas.php`
- **Transaksi & Verifikasi:** `transaksi.php`, `verifikasi.php`

#### 2.2 Peran User (Siswa)

User (siswa) memiliki akses terbatas untuk melakukan peminjaman, pengembalian, dan mengelola akunnya sendiri:

- **Peminjaman:** `borrow.php`, `return.php`
- **Akun & Notifikasi:** `dashboard.php`, `usulan.php`, `notifikasi.php`

### 3. Ringkasan Modul

| Peran | Kelompok Modul | Halaman/File |
|---|---|---|
| Admin | Manajemen Data | `buku.php`, `anggota.php`, `petugas.php` |
| Admin | Transaksi & Verifikasi | `transaksi.php`, `verifikasi.php` |
| User | Peminjaman | `borrow.php`, `return.php` |
| User | Akun & Notifikasi | `dashboard.php`, `usulan.php`, `notifikasi.php` |

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
