<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireStaff();

$pageTitle = 'Dashboard ' . (isAdmin() ? 'Admin' : 'Petugas') . ' - Perpustakaan SD N 1 Plebengan';
$currentPage = 'dashboard';

// Statistik dashboard
$totalBuku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM buku"))['total'];
$totalAnggota = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM anggota"))['total'];
$totalTransaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi"))['total'];
$menungguVerif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'menunggu'"))['total'];
$sedangDipinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'dipinjam'"))['total'];
$telatKembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'terlambat'"))['total'];
$totalDenda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(denda), 0) as total FROM transaksi WHERE status IN ('dikembalikan', 'terlambat')"))['total'];

// Buku terbaru
$bukuTerbaru = mysqli_query($conn, "SELECT * FROM buku ORDER BY created_at DESC LIMIT 5");

// Transaksi terbaru
$transaksiTerbaru = mysqli_query($conn, "SELECT t.*, b.judul, u.nama_lengkap 
    FROM transaksi t 
    JOIN buku b ON t.id_buku = b.id_buku 
    JOIN users u ON t.id_user = u.id_user 
    ORDER BY t.created_at DESC LIMIT 5");
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>Dashboard <?php echo isAdmin() ? 'Admin' : 'Petugas'; ?></h1>
    <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</p>
</div>

<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-number"><?php echo $totalBuku; ?></div>
        <div class="stat-label">Total Buku</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-number"><?php echo $totalAnggota; ?></div>
        <div class="stat-label">Total Anggota</div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-number"><?php echo $menungguVerif; ?></div>
        <div class="stat-label">Menunggu Verifikasi</div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-number"><?php echo $totalTransaksi; ?></div>
        <div class="stat-label">Total Transaksi</div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-number"><?php echo $sedangDipinjam; ?></div>
        <div class="stat-label">Sedang Dipinjam</div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-number"><?php echo $telatKembali; ?></div>
        <div class="stat-label">Terlambat Kembali</div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-number">Rp <?php echo number_format($totalDenda, 0, ',', '.'); ?></div>
        <div class="stat-label">Total Denda</div>
    </div>
</div>

<?php if ($menungguVerif > 0): ?>
<div class="alert alert-danger" style="display:flex; justify-content:space-between; align-items:center;">
    <span>🛎️ Ada <strong><?php echo $menungguVerif; ?></strong> permintaan peminjaman yang menunggu verifikasi Anda.</span>
    <a href="verifikasi.php" class="btn btn-sm btn-primary">Verifikasi Sekarang</a>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>📚 Buku Terbaru</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr><th>Kode</th><th>Judul</th><th>Stok</th></tr>
                </thead>
                <tbody>
                    <?php while ($b = mysqli_fetch_assoc($bukuTerbaru)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['kode_buku']); ?></td>
                        <td><?php echo htmlspecialchars($b['judul']); ?></td>
                        <td><span class="badge badge-blue"><?php echo (int) $b['stok']; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📋 Transaksi Terbaru</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr><th>Kode</th><th>Peminjam</th><th>Buku</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php while ($t = mysqli_fetch_assoc($transaksiTerbaru)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['kode_transaksi']); ?></td>
                        <td><?php echo htmlspecialchars($t['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($t['judul']); ?></td>
                        <td>
                            <?php echo statusBadge($t['status']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>