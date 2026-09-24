<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifikasi.php';
requireLogin();
requireStaff();

$pageTitle = 'Notifikasi - Perpustakaan SD N 1 Plebengan';
$currentPage = 'notifikasi';
$id_user = $_SESSION['id_user'];

// Membuka halaman ini otomatis menandai semua notifikasi sudah dibaca
notifTandaiSemuaDibaca($conn, $id_user);

$notifList = notifDaftar($conn, $id_user);
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>🔔 Notifikasi</h1>
    <p>Riwayat notifikasi verifikasi peminjaman & aktivitas lainnya</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>📬 Semua Notifikasi (<?php echo mysqli_num_rows($notifList); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($notifList) === 0): ?>
            <p class="text-center" style="padding: 30px 0; color: #999;">Belum ada notifikasi.</p>
        <?php else: ?>
            <div class="notif-page-list">
                <?php while ($n = mysqli_fetch_assoc($notifList)): ?>
                <div class="notif-page-item notif-item-<?php echo htmlspecialchars($n['tipe']); ?>">
                    <div class="notif-page-item-body">
                        <div class="notif-item-title"><?php echo htmlspecialchars($n['judul']); ?></div>
                        <div class="notif-item-msg"><?php echo htmlspecialchars($n['pesan']); ?></div>
                        <div class="notif-item-time"><?php echo htmlspecialchars($n['created_at']); ?></div>
                    </div>
                    <?php if (!empty($n['link'])): ?>
                    <a href="../<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-sm btn-secondary">Lihat</a>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
