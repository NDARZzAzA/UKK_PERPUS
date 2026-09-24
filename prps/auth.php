<?php
// =============================================
// FUNGSI AUTENTIKASI
// Menangani session, login, dan akses halaman
// =============================================

session_start();

// Fungsi: Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['id_user']) && !empty($_SESSION['id_user']);
}

// Fungsi: Cek apakah user adalah admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fungsi: Cek apakah user adalah petugas
function isPetugas() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'petugas';
}

// Fungsi: Cek apakah user adalah staf perpustakaan (admin ATAU petugas)
function isStaff() {
    return isAdmin() || isPetugas();
}

// Fungsi: Redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Fungsi: Redirect jika bukan admin (khusus halaman admin saja, misal Kelola Petugas)
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: " . (isStaff() ? "dashboard.php" : "../user/dashboard.php"));
        exit();
    }
}

// Fungsi: Redirect jika bukan staf (admin/petugas) - dipakai halaman operasional bersama
function requireStaff() {
    if (!isStaff()) {
        header("Location: ../user/dashboard.php");
        exit();
    }
}

// Fungsi: Redirect jika bukan anggota (user biasa)
function requireUser() {
    if (isStaff()) {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}

// =============================================
// PROTEKSI CSRF (Cross-Site Request Forgery)
// =============================================

// Fungsi: Ambil token CSRF (generate jika belum ada)
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi: Cetak input hidden berisi token CSRF, dipakai di dalam <form>
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

// Fungsi: Validasi token CSRF dari data POST. Panggil di awal setiap proses POST.
function verifyCsrfToken() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Permintaan tidak valid (CSRF token salah atau kedaluwarsa). Silakan muat ulang halaman dan coba lagi.');
    }
}

// =============================================
// BADGE STATUS TRANSAKSI (dipakai bersama di banyak halaman)
// =============================================
function statusBadge($status) {
    switch ($status) {
        case 'menunggu':
            return '<span class="badge badge-purple">⏳ Menunggu Verifikasi</span>';
        case 'dipinjam':
            return '<span class="badge badge-orange">📖 Dipinjam</span>';
        case 'dikembalikan':
            return '<span class="badge badge-green">✅ Dikembalikan</span>';
        case 'terlambat':
            return '<span class="badge badge-red">⏰ Terlambat</span>';
        case 'ditolak':
            return '<span class="badge badge-gray">✖️ Ditolak</span>';
        default:
            return '<span class="badge badge-gray">' . htmlspecialchars($status) . '</span>';
    }
}

// =============================================
// BADGE KONDISI BUKU (sebelum/sesudah dipinjam - dipakai bersama di banyak halaman)
// =============================================
function kondisiBadge($kondisi) {
    if ($kondisi === null || $kondisi === '') {
        return '<span class="badge badge-gray">-</span>';
    }
    switch ($kondisi) {
        case 'Baik':
            return '<span class="badge badge-green">✅ Baik</span>';
        case 'Rusak Ringan':
            return '<span class="badge badge-orange">⚠️ Rusak Ringan</span>';
        case 'Rusak Berat':
            return '<span class="badge badge-red">❗ Rusak Berat</span>';
        case 'Hilang':
            return '<span class="badge badge-gray">❓ Hilang</span>';
        default:
            return '<span class="badge badge-gray">' . htmlspecialchars($kondisi) . '</span>';
    }
}

// Daftar pilihan kondisi buku yang valid, dipakai untuk validasi input di server
function kondisiBukuValid() {
    return ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'];
}

// =============================================
// BADGE STATUS USULAN PENGADAAN BUKU (dipakai di user/usulan.php & admin/usulan.php)
// =============================================
function usulanStatusBadge($status) {
    switch ($status) {
        case 'menunggu':
            return '<span class="badge badge-purple">⏳ Menunggu Tinjauan</span>';
        case 'diterima':
            return '<span class="badge badge-green">✅ Diterima</span>';
        case 'ditolak':
            return '<span class="badge badge-red">✖️ Ditolak</span>';
        default:
            return '<span class="badge badge-gray">' . htmlspecialchars($status) . '</span>';
    }
}

// =============================================
// SAMPUL BUKU (gambar cover) - dipakai di semua halaman yang menampilkan kartu buku
// (index.php, katalog.php, user/borrow.php, user/dashboard.php, admin/buku.php, dsb)
// =============================================

// Folder fisik tempat file gambar sampul disimpan di server
define('SAMPUL_UPLOAD_DIR', __DIR__ . '/../assets/img/sampul/');

// Ekstensi & ukuran file yang diizinkan saat admin/petugas upload sampul baru
function sampulEkstensiValid() {
    return ['jpg', 'jpeg', 'png', 'webp'];
}
define('SAMPUL_MAX_SIZE', 2 * 1024 * 1024); // 2MB

// Fungsi: Bangun URL gambar sampul untuk ditampilkan di halaman.
// $prefix menyesuaikan lokasi file yang memanggilnya:
//   - file di folder root (index.php, katalog.php)   -> prefix ''
//   - file di folder user/ atau admin/                -> prefix '../'
// Mengembalikan null kalau buku belum punya sampul (tampilan pakai fallback warna bawaan).
function sampulUrl($sampul, $prefix = '') {
    if (empty($sampul)) {
        return null;
    }
    return $prefix . 'assets/img/sampul/' . rawurlencode($sampul);
}
?>
