<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifikasi.php';
requireLogin();
requireStaff(); // Admin maupun petugas boleh meninjau usulan pengadaan buku

$pageTitle = 'Usulan Pengadaan Buku - Perpustakaan SD N 1 Plebengan';
$currentPage = 'usulan';
$message = '';
$messageType = '';

// Proses tinjauan (terima/tolak)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int) ($_POST['id_usulan'] ?? 0);
    $catatan = trim($_POST['catatan_admin'] ?? '');
    $catatanEsc = mysqli_real_escape_string($conn, $catatan);
    $id_petugas = $_SESSION['id_user'];

    $usulan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM usulan_buku WHERE id_usulan = $id"));

    if (!$usulan) {
        $message = 'Usulan tidak ditemukan.';
        $messageType = 'danger';
    } elseif ($aksi === 'terima') {
        mysqli_query($conn, "UPDATE usulan_buku SET status = 'diterima', catatan_admin = '$catatanEsc', 
            ditinjau_oleh = $id_petugas, ditinjau_at = NOW() WHERE id_usulan = $id");
        notifBuat($conn, $usulan['id_user'], 'Usulan Buku Diterima',
            "Usulan pengadaan buku \"{$usulan['judul_buku']}\" yang Anda ajukan telah diterima." . ($catatan ? " Catatan: $catatan" : ''),
            'sukses', 'user/usulan.php');
        $message = 'Usulan pengadaan buku diterima dan siswa telah diberi notifikasi.';
        $messageType = 'success';
    } elseif ($aksi === 'tolak') {
        mysqli_query($conn, "UPDATE usulan_buku SET status = 'ditolak', catatan_admin = '$catatanEsc', 
            ditinjau_oleh = $id_petugas, ditinjau_at = NOW() WHERE id_usulan = $id");
        notifBuat($conn, $usulan['id_user'], 'Usulan Buku Ditolak',
            "Usulan pengadaan buku \"{$usulan['judul_buku']}\" yang Anda ajukan belum dapat disetujui." . ($catatan ? " Catatan: $catatan" : ''),
            'ditolak', 'user/usulan.php');
        $message = 'Usulan pengadaan buku ditolak dan siswa telah diberi notifikasi.';
        $messageType = 'success';
    } elseif ($aksi === 'hapus') {
        mysqli_query($conn, "DELETE FROM usulan_buku WHERE id_usulan = $id");
        $message = 'Usulan berhasil dihapus.';
        $messageType = 'success';
    }
}

// Filter status (opsional)
$filterStatus = $_GET['status'] ?? '';
$whereClause = '';
if (in_array($filterStatus, ['menunggu', 'diterima', 'ditolak'], true)) {
    $whereClause = "WHERE ub.status = '" . mysqli_real_escape_string($conn, $filterStatus) . "'";
}

$usulanList = mysqli_query($conn, "SELECT ub.*, u.nama_lengkap 
    FROM usulan_buku ub 
    JOIN users u ON ub.id_user = u.id_user 
    $whereClause 
    ORDER BY (ub.status = 'menunggu') DESC, ub.created_at DESC");
$totalMenunggu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usulan_buku WHERE status = 'menunggu'"))['total'];
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>📝 Usulan Pengadaan Buku</h1>
    <p>Daftar usulan judul buku baru yang dikirim siswa. Tinjau dan berikan keputusan; siswa akan otomatis mendapat notifikasi.</p>
</div>

<?php if ($totalMenunggu > 0): ?>
<div class="alert alert-danger" style="display:flex; justify-content:space-between; align-items:center;">
    <span>📝 Ada <strong><?php echo $totalMenunggu; ?></strong> usulan pengadaan buku yang menunggu tinjauan Anda.</span>
</div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>📋 Daftar Usulan (<?php echo mysqli_num_rows($usulanList); ?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="menunggu" <?php echo $filterStatus === 'menunggu' ? 'selected' : ''; ?>>Menunggu Tinjauan</option>
                <option value="diterima" <?php echo $filterStatus === 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                <option value="ditolak" <?php echo $filterStatus === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
            </select>
            <?php if ($filterStatus): ?><a href="usulan.php" class="btn btn-secondary">Reset</a><?php endif; ?>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>Diusulkan Oleh</th><th>Judul Buku</th><th>Kategori</th><th>Alasan</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($usulanList)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['judul_buku']); ?></strong>
                            <?php if ($row['penulis']): ?><br><small><?php echo htmlspecialchars($row['penulis']); ?></small><?php endif; ?>
                        </td>
                        <td><?php echo $row['kategori'] ? htmlspecialchars($row['kategori']) : '-'; ?></td>
                        <td style="max-width:240px;"><?php echo nl2br(htmlspecialchars($row['alasan'])); ?></td>
                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at']))); ?></td>
                        <td>
                            <?php echo usulanStatusBadge($row['status']); ?>
                            <?php if ($row['status'] !== 'menunggu' && $row['catatan_admin']): ?>
                                <br><small><em><?php echo htmlspecialchars($row['catatan_admin']); ?></em></small>
                            <?php endif; ?>
                        </td>
                        <td class="action-btns">
                            <?php if ($row['status'] === 'menunggu'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return promptTerima(this)">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="aksi" value="terima">
                                    <input type="hidden" name="id_usulan" value="<?php echo (int) $row['id_usulan']; ?>">
                                    <input type="hidden" name="catatan_admin" class="catatan-input" value="">
                                    <button type="submit" class="btn btn-sm btn-success">✅ Terima</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return promptTolak(this)">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="aksi" value="tolak">
                                    <input type="hidden" name="id_usulan" value="<?php echo (int) $row['id_usulan']; ?>">
                                    <input type="hidden" name="catatan_admin" class="catatan-input" value="">
                                    <button type="submit" class="btn btn-sm btn-warning">✖️ Tolak</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_usulan" value="<?php echo (int) $row['id_usulan']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($usulanList) === 0): ?>
                    <tr><td colspan="8" class="text-center">Belum ada usulan pengadaan buku.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Minta catatan singkat sebelum menerima/menolak usulan (opsional, boleh dikosongkan)
function promptTerima(form) {
    const catatan = prompt('Catatan untuk siswa (opsional, boleh dikosongkan):', '');
    if (catatan === null) return false; // batal
    form.querySelector('.catatan-input').value = catatan;
    return confirm('Terima usulan pengadaan buku ini?');
}
function promptTolak(form) {
    const catatan = prompt('Alasan penolakan untuk siswa (opsional, boleh dikosongkan):', '');
    if (catatan === null) return false; // batal
    form.querySelector('.catatan-input').value = catatan;
    return confirm('Tolak usulan pengadaan buku ini?');
}
</script>

<?php include '../includes/footer.php'; ?>
