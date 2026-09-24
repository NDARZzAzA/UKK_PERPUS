<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireUser();

$pageTitle = 'Pengembalian Buku - Perpustakaan SD N 1 Plebengan';
$currentPage = 'return';
$id_user = $_SESSION['id_user'];
$message = '';
$messageType = '';

// Proses Pengembalian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'kembalikan') {
    verifyCsrfToken();
    $id_transaksi = (int)$_POST['id_transaksi'];
    $tgl_dikembalikan = $_POST['tanggal_dikembalikan'];
    $kondisiSesudah = $_POST['kondisi_sesudah'] ?? 'Baik';
    if (!in_array($kondisiSesudah, kondisiBukuValid(), true)) $kondisiSesudah = 'Baik';
    $kondisiSesudahEsc = mysqli_real_escape_string($conn, $kondisiSesudah);
    
    $trx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = $id_transaksi AND id_user = $id_user AND status = 'dipinjam'"));
    
    if ($trx) {
        $denda = 0;
        $status = 'dikembalikan';
        if (strtotime($tgl_dikembalikan) > strtotime($trx['tanggal_kembali'])) {
            $selisih = (strtotime($tgl_dikembalikan) - strtotime($trx['tanggal_kembali'])) / (60 * 60 * 24);
            $denda = ceil($selisih) * 1000;
            $status = 'terlambat';
        }
        
        mysqli_query($conn, "UPDATE transaksi SET tanggal_dikembalikan = '$tgl_dikembalikan', status = '$status', denda = $denda, kondisi_sesudah = '$kondisiSesudahEsc' WHERE id_transaksi = $id_transaksi");
        mysqli_query($conn, "UPDATE buku SET stok = stok + {$trx['jumlah']} WHERE id_buku = {$trx['id_buku']}");
        
        $message = 'Buku berhasil dikembalikan!';
        if ($denda > 0) {
            $message .= ' Terlambat ' . ceil($selisih) . ' hari. Denda: Rp ' . number_format($denda, 0, ',', '.');
        }
        $messageType = 'success';
    } else {
        $message = 'Data transaksi tidak valid!';
        $messageType = 'danger';
    }
}

// Buku yang sedang dipinjam user
$dipinjam = mysqli_query($conn, "SELECT t.*, b.judul, b.kode_buku, b.penulis 
    FROM transaksi t 
    JOIN buku b ON t.id_buku = b.id_buku 
    WHERE t.id_user = $id_user AND t.status = 'dipinjam'");
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>📦 Pengembalian Buku</h1>
    <p>Kembalikan buku yang sedang Anda pinjam</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<?php if (mysqli_num_rows($dipinjam) > 0): ?>
<div class="books-grid">
    <?php while ($t = mysqli_fetch_assoc($dipinjam)): ?>
    <div class="book-card return-card">
        <div class="book-cover return-cover">
            <span class="book-category"><?php echo htmlspecialchars($t['kode_buku']); ?></span>
            <span class="badge badge-orange">Dipinjam</span>
        </div>
        <div class="book-detail">
            <h3 class="book-title"><?php echo htmlspecialchars($t['judul']); ?></h3>
            <p class="book-author"><?php echo htmlspecialchars($t['penulis']); ?></p>
            <p class="book-info-text"><strong>Jumlah Dipinjam:</strong> <?php echo (int) $t['jumlah']; ?> buku</p>
            <p class="book-info-text"><strong>Tgl Pinjam:</strong> <?php echo $t['tanggal_pinjam']; ?></p>
            <p class="book-info-text"><strong>Batas Kembali:</strong> <?php echo $t['tanggal_kembali']; ?></p>
            <p class="book-info-text"><strong>Kondisi Saat Dipinjam:</strong> <?php echo kondisiBadge($t['kondisi_sebelum']); ?></p>
            <?php 
            $hariTerlambat = 0;
            if (strtotime(date('Y-m-d')) > strtotime($t['tanggal_kembali'])) {
                $hariTerlambat = ceil((strtotime(date('Y-m-d')) - strtotime($t['tanggal_kembali'])) / (60*60*24));
            }
            if ($hariTerlambat > 0): ?>
                <p class="book-info-text text-danger"><strong>Terlambat:</strong> <?php echo $hariTerlambat; ?> hari (Denda: Rp <?php echo number_format($hariTerlambat * 1000, 0, ',', '.'); ?>)</p>
            <?php endif; ?>
        </div>
        <form method="POST" action="" onsubmit="return confirm('Kembalikan buku ini?')">
            <?php echo csrfField(); ?>
            <input type="hidden" name="aksi" value="kembalikan">
            <input type="hidden" name="id_transaksi" value="<?php echo (int) $t['id_transaksi']; ?>">
            <input type="hidden" name="tanggal_dikembalikan" value="<?php echo date('Y-m-d'); ?>">
            <div class="form-group">
                <label>Kondisi Buku Saat Dikembalikan</label>
                <select name="kondisi_sesudah" required>
                    <option value="Baik" selected>Baik</option>
                    <option value="Rusak Ringan">Rusak Ringan</option>
                    <option value="Rusak Berat">Rusak Berat</option>
                    <option value="Hilang">Hilang</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success btn-full">Kembalikan Buku</button>
        </form>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center" style="padding: 40px;">
        <p style="font-size: 48px;">📭</p>
        <h3>Tidak Ada Buku Dipinjam</h3>
        <p>Anda tidak memiliki buku yang sedang dipinjam.</p>
        <a href="borrow.php" class="btn btn-primary" style="margin-top: 15px;">Pinjam Buku</a>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>