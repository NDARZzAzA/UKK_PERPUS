<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireStaff(); // Admin maupun petugas boleh moderasi ulasan pengunjung

$pageTitle = 'Kelola Ulasan - Perpustakaan SD N 1 Plebengan';
$currentPage = 'ulasan';
$message = '';
$messageType = '';

// Proses moderasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int) ($_POST['id_ulasan'] ?? 0);

    if ($aksi === 'sembunyikan') {
        mysqli_query($conn, "UPDATE ulasan SET status = 'disembunyikan' WHERE id_ulasan = $id");
        $message = 'Ulasan disembunyikan dari beranda.';
        $messageType = 'success';
    } elseif ($aksi === 'tampilkan') {
        mysqli_query($conn, "UPDATE ulasan SET status = 'tampil' WHERE id_ulasan = $id");
        $message = 'Ulasan ditampilkan kembali di beranda.';
        $messageType = 'success';
    } elseif ($aksi === 'hapus') {
        mysqli_query($conn, "DELETE FROM ulasan WHERE id_ulasan = $id");
        $message = 'Ulasan berhasil dihapus.';
        $messageType = 'success';
    }
}

// Filter status (opsional)
$filterStatus = $_GET['status'] ?? '';
$whereClause = '';
if ($filterStatus === 'tampil' || $filterStatus === 'disembunyikan') {
    $whereClause = "WHERE status = '" . mysqli_real_escape_string($conn, $filterStatus) . "'";
}

$ulasanList = mysqli_query($conn, "SELECT * FROM ulasan $whereClause ORDER BY created_at DESC");
$ringkasan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, COALESCE(AVG(rating),0) as rata FROM ulasan WHERE status = 'tampil'"));
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>💬 Kelola Ulasan Pengunjung</h1>
</div>

<div class="info-box">
    <p>Ulasan &amp; rating bintang di halaman beranda dikirim langsung oleh pengunjung situs. Gunakan halaman ini untuk
    menyembunyikan ulasan yang tidak pantas, atau menghapusnya secara permanen. Rating rata-rata saat ini:
    <strong>⭐ <?php echo number_format((float) $ringkasan['rata'], 1); ?></strong> dari <strong><?php echo (int) $ringkasan['total']; ?> ulasan tampil</strong>.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>📋 Daftar Ulasan (<?php echo mysqli_num_rows($ulasanList); ?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="tampil" <?php echo $filterStatus === 'tampil' ? 'selected' : ''; ?>>Tampil di Beranda</option>
                <option value="disembunyikan" <?php echo $filterStatus === 'disembunyikan' ? 'selected' : ''; ?>>Disembunyikan</option>
            </select>
            <?php if ($filterStatus): ?><a href="ulasan.php" class="btn btn-secondary">Reset</a><?php endif; ?>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>Nama</th><th>Peran</th><th>Rating</th><th>Ulasan</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($ulasanList)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_pengulas']); ?></td>
                        <td><?php echo htmlspecialchars($row['peran']); ?></td>
                        <td><?php echo str_repeat('⭐', (int) $row['rating']); ?></td>
                        <td style="max-width:280px;"><?php echo nl2br(htmlspecialchars($row['komentar'])); ?></td>
                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at']))); ?></td>
                        <td>
                            <?php if ($row['status'] === 'tampil'): ?>
                                <span class="badge badge-green">Tampil</span>
                            <?php else: ?>
                                <span class="badge badge-orange">Disembunyikan</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-btns">
                            <?php if ($row['status'] === 'tampil'): ?>
                                <form method="POST" style="display:inline;">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="aksi" value="sembunyikan">
                                    <input type="hidden" name="id_ulasan" value="<?php echo (int) $row['id_ulasan']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">🙈 Sembunyikan</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="aksi" value="tampilkan">
                                    <input type="hidden" name="id_ulasan" value="<?php echo (int) $row['id_ulasan']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">👁️ Tampilkan</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_ulasan" value="<?php echo (int) $row['id_ulasan']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($ulasanList) === 0): ?>
                    <tr><td colspan="8" class="text-center">Belum ada ulasan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
