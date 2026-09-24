<?php
// Header HTML umum - disertakan di setiap halaman
require_once __DIR__ . '/notifikasi.php';
$currentPage = isset($currentPage) ? $currentPage : '';

// Data notifikasi milik user yang sedang login (dipakai di top-bar)
$_notifUserId = $_SESSION['id_user'] ?? 0;
$_notifBelumDibaca = notifHitungBelumDibaca($conn, $_notifUserId);
$_notifTerbaru = notifDaftar($conn, $_notifUserId, 6);
$_notifRootPrefix = isStaff() ? '../admin/' : '../user/';

// Jumlah permintaan pinjam yang masih menunggu verifikasi (hanya relevan untuk staf)
$_menungguVerifikasi = 0;
// Jumlah usulan pengadaan buku yang masih menunggu tinjauan (hanya relevan untuk staf)
$_usulanMenunggu = 0;
if (isStaff()) {
    $_verifRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM transaksi WHERE status = 'menunggu'"));
    $_menungguVerifikasi = $_verifRow ? (int) $_verifRow['c'] : 0;
    $_usulanRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM usulan_buku WHERE status = 'menunggu'"));
    $_usulanMenunggu = $_usulanRow ? (int) $_usulanRow['c'] : 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Perpustakaan SD N 1 Plebengan'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=20260827">
</head>
<body>
<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>📚 Perpustakaan</h2>
            <p class="sidebar-subtitle">SD N 1 Plebengan</p>
        </div>
        <nav class="sidebar-nav">
            <?php if (isStaff()): ?>
                <a href="../admin/dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="../admin/buku.php" class="nav-item <?php echo $currentPage === 'buku' ? 'active' : ''; ?>">
                    <span class="nav-icon">📖</span> Data Buku
                </a>
                <a href="../admin/anggota.php" class="nav-item <?php echo $currentPage === 'anggota' ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span> Kelola Anggota
                </a>
                <a href="../admin/verifikasi.php" class="nav-item <?php echo $currentPage === 'verifikasi' ? 'active' : ''; ?>">
                    <span class="nav-icon">🛎️</span> Verifikasi Peminjaman
                    <?php if ($_menungguVerifikasi > 0): ?><span class="nav-badge"><?php echo $_menungguVerifikasi; ?></span><?php endif; ?>
                </a>
                <a href="../admin/transaksi.php" class="nav-item <?php echo $currentPage === 'transaksi' ? 'active' : ''; ?>">
                    <span class="nav-icon">📋</span> Transaksi
                </a>
                <a href="../admin/ulasan.php" class="nav-item <?php echo $currentPage === 'ulasan' ? 'active' : ''; ?>">
                    <span class="nav-icon">💬</span> Ulasan Pengunjung
                </a>
                <a href="../admin/usulan.php" class="nav-item <?php echo $currentPage === 'usulan' ? 'active' : ''; ?>">
                    <span class="nav-icon">📝</span> Usulan Pengadaan Buku
                    <?php if ($_usulanMenunggu > 0): ?><span class="nav-badge"><?php echo $_usulanMenunggu; ?></span><?php endif; ?>
                </a>
                <?php if (isAdmin()): ?>
                <a href="../admin/petugas.php" class="nav-item <?php echo $currentPage === 'petugas' ? 'active' : ''; ?>">
                    <span class="nav-icon">🧑‍💼</span> Kelola Petugas
                </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="../user/dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="../user/borrow.php" class="nav-item <?php echo $currentPage === 'borrow' ? 'active' : ''; ?>">
                    <span class="nav-icon">📖</span> Peminjaman Buku
                </a>
                <a href="../user/return.php" class="nav-item <?php echo $currentPage === 'return' ? 'active' : ''; ?>">
                    <span class="nav-icon">📦</span> Pengembalian Buku
                </a>
                <a href="../user/my_transactions.php" class="nav-item <?php echo $currentPage === 'my_transactions' ? 'active' : ''; ?>">
                    <span class="nav-icon">📋</span> Riwayat Saya
                </a>
                <a href="../user/usulan.php" class="nav-item <?php echo $currentPage === 'usulan' ? 'active' : ''; ?>">
                    <span class="nav-icon">📝</span> Usulan Pengadaan Buku
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="../index.php" class="nav-item">
                <span class="nav-icon">🏠</span> Beranda
            </a>
            <a href="../logout.php" class="nav-item logout-btn">
                <span class="nav-icon">🚪</span> Logout
            </a>
        </div>
    </aside>
    <!-- Main Content -->
    <main class="main-content">
        <header class="top-bar">
            <div class="notif-wrap">
                <button type="button" class="notif-bell" onclick="toggleNotifDropdown(event)" aria-label="Notifikasi">
                    🔔
                    <?php if ($_notifBelumDibaca > 0): ?><span class="notif-count"><?php echo $_notifBelumDibaca > 9 ? '9+' : $_notifBelumDibaca; ?></span><?php endif; ?>
                </button>
                <div id="notif-dropdown" class="notif-dropdown" style="display:none;">
                    <div class="notif-dropdown-header">
                        <strong>Notifikasi</strong>
                        <?php if ($_notifBelumDibaca > 0): ?>
                        <a href="../notif_baca.php?all=1&back=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Tandai semua dibaca</a>
                        <?php endif; ?>
                    </div>
                    <div class="notif-dropdown-list">
                        <?php if (mysqli_num_rows($_notifTerbaru) === 0): ?>
                            <div class="notif-empty">Belum ada notifikasi.</div>
                        <?php else: ?>
                            <?php while ($_n = mysqli_fetch_assoc($_notifTerbaru)): ?>
                            <a class="notif-item <?php echo $_n['is_read'] ? '' : 'notif-item-unread'; ?> notif-item-<?php echo htmlspecialchars($_n['tipe']); ?>"
                               href="../notif_baca.php?id=<?php echo (int) $_n['id_notifikasi']; ?>">
                                <div class="notif-item-title"><?php echo htmlspecialchars($_n['judul']); ?></div>
                                <div class="notif-item-msg"><?php echo htmlspecialchars($_n['pesan']); ?></div>
                                <div class="notif-item-time"><?php echo notifWaktuRelatif($_n['created_at']); ?></div>
                            </a>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notif-dropdown-footer">
                        <a href="<?php echo $_notifRootPrefix; ?>notifikasi.php">Lihat semua notifikasi</a>
                    </div>
                </div>
            </div>
            <div class="user-info">
                <span class="user-role"><?php echo strtoupper($_SESSION['role']); ?></span>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
            </div>
        </header>
        <div class="content-wrapper">
