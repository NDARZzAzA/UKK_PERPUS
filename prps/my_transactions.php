<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireUser();

$pageTitle = 'Riwayat Peminjaman - Perpustakaan SD N 1 Plebengan';
$currentPage = 'my_transactions';
$id_user = $_SESSION['id_user'];

// Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$statusValid = ['menunggu', 'dipinjam', 'dikembalikan', 'terlambat', 'ditolak'];
$filterStatus = (isset($_GET['status']) && in_array($_GET['status'], $statusValid, true)) ? $_GET['status'] : '';

$whereClause = "WHERE t.id_user = $id_user";
if ($search) $whereClause .= " AND (b.judul LIKE '%$search%' OR t.kode_transaksi LIKE '%$search%')";
if ($filterStatus) $whereClause .= " AND t.status = '$filterStatus'";

$transaksi = mysqli_query($conn, "SELECT t.*, b.judul, b.kode_buku, b.penulis 
    FROM transaksi t JOIN buku b ON t.id_buku = b.id_buku 
    $whereClause ORDER BY t.created_at DESC");

// Statistik
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM transaksi WHERE id_user = $id_user"));
$aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM transaksi WHERE id_user = $id_user AND status = 'dipinjam'"))['c'];
$selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM transaksi WHERE id_user = $id_user AND status IN ('dikembalikan','terlambat')"))['c'];
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>📋 Riwayat Peminjaman Saya</h1>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card stat-blue"><div class="stat-number"><?php echo $total; ?></div><div class="stat-label">Total</div></div>
    <div class="stat-card stat-orange"><div class="stat-number"><?php echo $aktif; ?></div><div class="stat-label">Aktif</div></div>
    <div class="stat-card stat-green"><div class="stat-number"><?php echo $selesai; ?></div><div class="stat-label">Selesai</div></div>
</div>

<div class="card">
    <div class="card-header"><h3>📋 Daftar Transaksi</h3></div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Cari judul buku atau kode transaksi..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="menunggu" <?php echo $filterStatus === 'menunggu' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                <option value="dipinjam" <?php echo $filterStatus === 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                <option value="dikembalikan" <?php echo $filterStatus === 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                <option value="terlambat" <?php echo $filterStatus === 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
                <option value="ditolak" <?php echo $filterStatus === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="my_transactions.php" class="btn btn-secondary">Reset</a>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>Kode</th><th>Buku</th><th>Penulis</th><th>Jumlah</th><th>Tgl Pinjam</th><th>Batas</th><th>Dikembalikan</th><th>Status</th><th>Denda</th><th>Kondisi Sebelum</th><th>Kondisi Sesudah</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($transaksi)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['kode_transaksi']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                        <td><?php echo htmlspecialchars($row['penulis']); ?></td>
                        <td><?php echo (int) $row['jumlah']; ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_pinjam']); ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_kembali']); ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_dikembalikan'] ?: '-'); ?></td>
                        <td>
                            <?php echo statusBadge($row['status']); ?>
                            <?php if ($row['status'] === 'ditolak' && !empty($row['catatan_petugas'])): ?>
                                <br><small style="color:#999;">Alasan: <?php echo htmlspecialchars($row['catatan_petugas']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['denda'] > 0 ? 'Rp ' . number_format($row['denda'], 0, ',', '.') : '-'; ?></td>
                        <td><?php echo kondisiBadge($row['kondisi_sebelum']); ?></td>
                        <td><?php echo kondisiBadge($row['kondisi_sesudah']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($transaksi) === 0): ?>
                    <tr><td colspan="12" class="text-center">Tidak ada data transaksi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>