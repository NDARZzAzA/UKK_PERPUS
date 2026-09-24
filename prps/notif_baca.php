<?php
// =============================================
// TANDAI NOTIFIKASI DIBACA + REDIRECT
// Diletakkan di root supaya link tujuan (id_petugas.php, user/....php)
// bisa langsung dipakai tanpa perlu tahu apakah pemanggilnya admin atau user.
// =============================================
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/notifikasi.php';
requireLogin();

$id_user = $_SESSION['id_user'];
$redirectTo = 'index.php';

if (isset($_GET['all'])) {
    // Tandai semua notifikasi milik user ini sudah dibaca
    notifTandaiSemuaDibaca($conn, $id_user);
    $back = $_GET['back'] ?? '';
    // $back berasal dari REQUEST_URI halaman pemanggil (path absolut dari domain, mis. /admin/transaksi.php).
    // Hanya izinkan redirect yang diawali "/" dan bukan "//" (cegah open redirect ke domain luar).
    if ($back !== '' && $back[0] === '/' && (strlen($back) < 2 || $back[1] !== '/')) {
        $redirectTo = $back;
    } else {
        $redirectTo = isStaff() ? 'admin/dashboard.php' : 'user/dashboard.php';
    }
} elseif (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // Ambil link tujuan sebelum ditandai dibaca, pastikan notifikasi ini memang milik user ini
    $notif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT link FROM notifikasi WHERE id_notifikasi = $id AND id_user = " . (int) $id_user));
    notifTandaiDibaca($conn, $id, $id_user);
    if ($notif && !empty($notif['link'])) {
        $redirectTo = $notif['link'];
    } else {
        $redirectTo = isStaff() ? 'admin/dashboard.php' : 'user/dashboard.php';
    }
} else {
    $redirectTo = isStaff() ? 'admin/dashboard.php' : 'user/dashboard.php';
}

header('Location: ' . $redirectTo);
exit();
