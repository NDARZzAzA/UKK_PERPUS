<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireUser();

$pageTitle = 'Dashboard Siswa - Perpustakaan SD N 1 Plebengan';
$currentPage = 'dashboard';
$id_user = $_SESSION['id_user'];

// Statistik user
$totalPinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE id_user = $id_user"))['total'];
$menungguVerif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE id_user = $id_user AND status = 'menunggu'"))['total'];
$sedangDipinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE id_user = $id_user AND status = 'dipinjam'"))['total'];
$selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE id_user = $id_user AND status IN ('dikembalikan', 'terlambat')"))['total'];
$totalDenda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(denda), 0) as total FROM transaksi WHERE id_user = $id_user AND status IN ('dikembalikan', 'terlambat')"))['total'];

// Buku tersedia
$bukuTersedia = mysqli_query($conn, "SELECT * FROM buku WHERE stok > 0 ORDER BY created_at DESC LIMIT 6");

// Transaksi terakhir
$transaksiTerakhir = mysqli_query($conn, "SELECT t.*, b.judul, b.kode_buku FROM transaksi t JOIN buku b ON t.id_buku = b.id_buku WHERE t.id_user = $id_user ORDER BY t.created_at DESC LIMIT 5");

// Usulan pengadaan buku milik siswa
$totalUsulan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usulan_buku WHERE id_user = $id_user"))['total'];
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>Dashboard Siswa</h1>
    <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</p>
</div>

<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-number"><?php echo $totalPinjam; ?></div>
        <div class="stat-label">Total Peminjaman</div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-number"><?php echo $sedangDipinjam; ?></div>
        <div class="stat-label">Sedang Dipinjam</div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-number"><?php echo $menungguVerif; ?></div>
        <div class="stat-label">Menunggu Verifikasi</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-number"><?php echo $selesai; ?></div>
        <div class="stat-label">Selesai Dikembalikan</div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-number">Rp <?php echo number_format($totalDenda, 0, ',', '.'); ?></div>
        <div class="stat-label">Total Denda</div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-number"><?php echo $totalUsulan; ?></div>
        <div class="stat-label">Usulan Buku Saya</div>
    </div>
</div>

<div class="alert alert-success" style="display:flex; justify-content:space-between; align-items:center;">
    <span>📝 Punya judul buku favorit yang belum ada di perpustakaan? Ajukan usulan pengadaannya!</span>
    <a href="usulan.php" class="btn btn-sm btn-primary">Ajukan Usulan</a>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>📚 Buku Tersedia</h3>
            <a href="borrow.php" class="btn btn-sm btn-primary">Lihat Semua</a>
        </div>
        <div class="card-body">
            <?php while ($b = mysqli_fetch_assoc($bukuTersedia)): ?>
            <div class="book-card-mini">
                <div class="book-info-wrap">
                    <?php $src = sampulUrl($b['sampul'], '../'); ?>
                    <?php if ($src): ?>
                        <img src="<?php echo htmlspecialchars($src); ?>" alt="Sampul <?php echo htmlspecialchars($b['judul']); ?>" class="book-thumb-mini">
                    <?php endif; ?>
                    <div class="book-info">
                        <strong><?php echo htmlspecialchars($b['judul']); ?></strong>
                        <small><?php echo htmlspecialchars($b['penulis']); ?> (<?php echo (int) $b['tahun_terbit']; ?>)</small>
                    </div>
                </div>
                <span class="badge badge-green">Stok: <?php echo $b['stok']; ?></span>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📋 Riwayat Terakhir</h3>
            <a href="my_transactions.php" class="btn btn-sm btn-primary">Lihat Semua</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr><th>Buku</th><th>Tgl Pinjam</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php while ($t = mysqli_fetch_assoc($transaksiTerakhir)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['judul']); ?></td>
                        <td><?php echo $t['tanggal_pinjam']; ?></td>
                        <td>
                            <?php echo statusBadge($t['status']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($transaksiTerakhir) === 0): ?>
                    <tr><td colspan="3" class="text-center">Belum ada riwayat.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>