<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifikasi.php';
requireLogin();
requireUser();

$pageTitle = 'Usulan Pengadaan Buku - Perpustakaan SD N 1 Plebengan';
$currentPage = 'usulan';
$id_user = $_SESSION['id_user'];
$message = '';
$messageType = '';

// Proses pengajuan usulan baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $judul_buku = trim($_POST['judul_buku'] ?? '');
    $penulis = trim($_POST['penulis'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $alasan = trim($_POST['alasan'] ?? '');

    if ($judul_buku === '' || $alasan === '') {
        $message = 'Judul buku dan alasan usulan wajib diisi!';
        $messageType = 'danger';
    } elseif (mb_strlen($judul_buku) > 200) {
        $message = 'Judul buku terlalu panjang (maksimal 200 karakter).';
        $messageType = 'danger';
    } else {
        $judul_buku_esc = mysqli_real_escape_string($conn, $judul_buku);
        $penulis_esc = $penulis !== '' ? "'" . mysqli_real_escape_string($conn, $penulis) . "'" : 'NULL';
        $kategori_esc = $kategori !== '' ? "'" . mysqli_real_escape_string($conn, $kategori) . "'" : 'NULL';
        $alasan_esc = mysqli_real_escape_string($conn, $alasan);

        mysqli_query($conn, "INSERT INTO usulan_buku (id_user, judul_buku, penulis, kategori, alasan, status) 
            VALUES ($id_user, '$judul_buku_esc', $penulis_esc, $kategori_esc, '$alasan_esc', 'menunggu')");

        // Notifikasi ke semua petugas & admin bahwa ada usulan baru yang perlu ditinjau
        $namaSiswa = $_SESSION['nama_lengkap'];
        notifBuatUntukStaf(
            $conn,
            'Usulan Pengadaan Buku Baru',
            "$namaSiswa mengusulkan pengadaan buku \"$judul_buku\". Menunggu tinjauan Anda.",
            'info',
            'admin/usulan.php'
        );

        $message = 'Usulan pengadaan buku berhasil dikirim! Terima kasih atas masukannya, silakan tunggu tinjauan dari petugas perpustakaan.';
        $messageType = 'success';
    }
}

// Riwayat usulan milik siswa yang sedang login
$usulanSaya = mysqli_query($conn, "SELECT * FROM usulan_buku WHERE id_user = $id_user ORDER BY created_at DESC");
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>📝 Usulan Pengadaan Buku</h1>
    <p>Punya judul buku favorit yang belum ada di perpustakaan? Ajukan usulan pengadaan di sini, akan ditinjau oleh petugas perpustakaan.</p>
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

<div class="dashboard-grid">
    <!-- Form Usulan Baru -->
    <div class="card">
        <div class="card-header">
            <h3>➕ Ajukan Usulan Baru</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label>Judul Buku <span style="color:#e63946;">*</span></label>
                    <input type="text" name="judul_buku" placeholder="Contoh: Laskar Pelangi" maxlength="200" required>
                </div>
                <div class="form-group">
                    <label>Penulis (opsional)</label>
                    <input type="text" name="penulis" placeholder="Contoh: Andrea Hirata" maxlength="100">
                </div>
                <div class="form-group">
                    <label>Kategori (opsional)</label>
                    <input type="text" name="kategori" placeholder="Contoh: Cerita Anak, Pengetahuan Umum" maxlength="50">
                </div>
                <div class="form-group">
                    <label>Alasan Usulan <span style="color:#e63946;">*</span></label>
                    <textarea name="alasan" rows="4" placeholder="Jelaskan mengapa buku ini perlu diadakan perpustakaan..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-full">📩 Kirim Usulan</button>
            </form>
        </div>
    </div>

    <!-- Riwayat Usulan Saya -->
    <div class="card">
        <div class="card-header">
            <h3>📋 Riwayat Usulan Saya (<?php echo mysqli_num_rows($usulanSaya); ?>)</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Judul Buku</th><th>Tanggal</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($usulanSaya)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($u['judul_buku']); ?></strong>
                                <?php if ($u['penulis']): ?><br><small><?php echo htmlspecialchars($u['penulis']); ?></small><?php endif; ?>
                                <?php if ($u['status'] !== 'menunggu' && $u['catatan_admin']): ?>
                                    <br><small><em>Catatan: <?php echo htmlspecialchars($u['catatan_admin']); ?></em></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(date('d M Y', strtotime($u['created_at']))); ?></td>
                            <td><?php echo usulanStatusBadge($u['status']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($usulanSaya) === 0): ?>
                        <tr><td colspan="3" class="text-center">Anda belum pernah mengajukan usulan buku.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
