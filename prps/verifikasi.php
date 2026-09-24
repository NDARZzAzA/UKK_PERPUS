<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifikasi.php';
requireLogin();
requireStaff();

$pageTitle = 'Verifikasi Peminjaman - Perpustakaan SD N 1 Plebengan';
$currentPage = 'verifikasi';
$id_petugas = $_SESSION['id_user'];
$message = '';
$messageType = '';

// Proses Setujui / Tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int) ($_POST['id_transaksi'] ?? 0);

    // Ambil data transaksi yang statusnya masih 'menunggu' (mencegah verifikasi dobel/basi)
    $trx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT t.*, b.judul, b.id_buku, u.nama_lengkap 
        FROM transaksi t 
        JOIN buku b ON t.id_buku = b.id_buku 
        JOIN users u ON t.id_user = u.id_user 
        WHERE t.id_transaksi = $id AND t.status = 'menunggu'"));

    if (!$trx) {
        $message = 'Permintaan tidak ditemukan atau sudah diproses sebelumnya.';
        $messageType = 'danger';
    } elseif ($aksi === 'setujui') {
        $kondisiSebelum = $_POST['kondisi_sebelum'] ?? 'Baik';
        if (!in_array($kondisiSebelum, kondisiBukuValid(), true)) $kondisiSebelum = 'Baik';
        $kondisiSebelumEsc = mysqli_real_escape_string($conn, $kondisiSebelum);

        mysqli_query($conn, "UPDATE transaksi 
            SET status = 'dipinjam', id_petugas_verifikasi = $id_petugas, diverifikasi_at = NOW(), kondisi_sebelum = '$kondisiSebelumEsc' 
            WHERE id_transaksi = $id");
        // Stok sudah dicadangkan sejak anggota mengajukan, jadi tidak perlu dikurangi lagi di sini.

        notifBuat(
            $conn,
            $trx['id_user'],
            'Peminjaman Disetujui ✅',
            "Permintaan pinjam buku \"{$trx['judul']}\" (Kode: {$trx['kode_transaksi']}) telah disetujui. Batas kembali: {$trx['tanggal_kembali']}.",
            'sukses',
            'user/my_transactions.php'
        );

        $message = "Peminjaman \"{$trx['judul']}\" oleh {$trx['nama_lengkap']} telah disetujui.";
        $messageType = 'success';
    } elseif ($aksi === 'tolak') {
        $alasan = trim($_POST['alasan'] ?? '');
        if ($alasan === '') $alasan = 'Tidak ada alasan spesifik dari petugas.';
        $alasanEsc = mysqli_real_escape_string($conn, $alasan);

        mysqli_query($conn, "UPDATE transaksi 
            SET status = 'ditolak', catatan_petugas = '$alasanEsc', id_petugas_verifikasi = $id_petugas, diverifikasi_at = NOW() 
            WHERE id_transaksi = $id");
        // Kembalikan stok yang tadinya dicadangkan saat pengajuan
        mysqli_query($conn, "UPDATE buku SET stok = stok + {$trx['jumlah']} WHERE id_buku = {$trx['id_buku']}");

        notifBuat(
            $conn,
            $trx['id_user'],
            'Peminjaman Ditolak ✖️',
            "Permintaan pinjam buku \"{$trx['judul']}\" (Kode: {$trx['kode_transaksi']}) ditolak. Alasan: $alasan",
            'ditolak',
            'user/my_transactions.php'
        );

        $message = "Peminjaman \"{$trx['judul']}\" oleh {$trx['nama_lengkap']} telah ditolak.";
        $messageType = 'success';
    }
}

// Daftar permintaan yang masih menunggu verifikasi (paling lama diajukan tampil duluan)
$daftarMenunggu = mysqli_query($conn, "SELECT t.*, b.judul, b.kode_buku, b.penulis, u.nama_lengkap, u.username, a.nis, a.kelas 
    FROM transaksi t 
    JOIN buku b ON t.id_buku = b.id_buku 
    JOIN users u ON t.id_user = u.id_user 
    LEFT JOIN anggota a ON a.id_user = u.id_user 
    WHERE t.status = 'menunggu' 
    ORDER BY t.created_at ASC");
$jumlahMenunggu = mysqli_num_rows($daftarMenunggu);
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>🛎️ Verifikasi Peminjaman</h1>
    <p>Setujui atau tolak permintaan pinjam buku dari anggota</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php if ($messageType === 'success'): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        try {
            var audio = new Audio('../assets/audio/notifikasi.mp3');
            audio.volume = 0.6;
            var p = audio.play();
            if (p && typeof p.catch === 'function') { p.catch(function () {}); }
        } catch (e) {}
    });
    </script>
    <?php endif; ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>⏳ Menunggu Verifikasi (<?php echo $jumlahMenunggu; ?>)</h3>
    </div>
    <div class="card-body">
        <?php if ($jumlahMenunggu === 0): ?>
            <p class="text-center" style="padding: 30px 0; color: #999;">Tidak ada permintaan peminjaman yang menunggu verifikasi. 🎉</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Kode</th><th>Anggota</th><th>Buku</th><th>Jumlah</th><th>Tgl Pinjam</th><th>Batas Kembali</th><th>Durasi</th><th>Diajukan</th><th>Kondisi Dilaporkan</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($daftarMenunggu)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['kode_transaksi']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($row['nama_lengkap']); ?>
                                <?php if ($row['nis']): ?><br><small><?php echo htmlspecialchars($row['nis']); ?> - <?php echo htmlspecialchars($row['kelas']); ?></small><?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['judul']); ?>
                                <br><small><?php echo htmlspecialchars($row['kode_buku']); ?></small>
                            </td>
                            <td><?php echo (int) $row['jumlah']; ?> buku</td>
                            <td><?php echo htmlspecialchars($row['tanggal_pinjam']); ?></td>
                            <td><?php echo htmlspecialchars($row['tanggal_kembali']); ?></td>
                            <td><?php echo ceil((strtotime($row['tanggal_kembali']) - strtotime($row['tanggal_pinjam'])) / 86400); ?> hari</td>
                            <td><small><?php echo notifWaktuRelatif($row['created_at']); ?></small></td>
                            <td><?php echo kondisiBadge($row['kondisi_sebelum']); ?><br><small style="color:#999;">Laporan anggota</small></td>
                            <td class="action-btns">
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Setujui peminjaman buku \'<?php echo htmlspecialchars(addslashes($row['judul'])); ?>\' untuk <?php echo htmlspecialchars(addslashes($row['nama_lengkap'])); ?>?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="aksi" value="setujui">
                                    <input type="hidden" name="id_transaksi" value="<?php echo (int) $row['id_transaksi']; ?>">
                                    <select name="kondisi_sebelum" title="Kondisi buku saat diserahkan ke anggota (bisa dikoreksi dari laporan awal anggota)" style="margin-bottom:6px;">
                                        <option value="Baik" <?php echo $row['kondisi_sebelum'] === 'Baik' ? 'selected' : ''; ?>>Baik</option>
                                        <option value="Rusak Ringan" <?php echo $row['kondisi_sebelum'] === 'Rusak Ringan' ? 'selected' : ''; ?>>Rusak Ringan</option>
                                        <option value="Rusak Berat" <?php echo $row['kondisi_sebelum'] === 'Rusak Berat' ? 'selected' : ''; ?>>Rusak Berat</option>
                                    </select><br>
                                    <button type="submit" class="btn btn-sm btn-success">✅ Setujui</button>
                                </form>
                                <form method="POST" action="" style="display:inline;" onsubmit="return siapkanTolak(this)">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="aksi" value="tolak">
                                    <input type="hidden" name="id_transaksi" value="<?php echo (int) $row['id_transaksi']; ?>">
                                    <input type="hidden" name="alasan" class="input-alasan-tolak">
                                    <button type="submit" class="btn btn-sm btn-danger">❌ Tolak</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function siapkanTolak(form) {
    const alasan = prompt('Alasan penolakan (opsional, akan dikirim ke anggota sebagai notifikasi):', '');
    if (alasan === null) return false; // batal
    form.querySelector('.input-alasan-tolak').value = alasan;
    return confirm('Yakin tolak permintaan peminjaman ini?');
}
</script>

<?php include '../includes/footer.php'; ?>
