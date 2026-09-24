<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifikasi.php';
requireLogin();
requireUser();

$pageTitle = 'Peminjaman Buku - Perpustakaan SD N 1 Plebengan';
$currentPage = 'borrow';
$id_user = $_SESSION['id_user'];
$message = '';
$messageType = '';

// Proses Peminjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $id_buku = (int)$_POST['id_buku'];
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    if ($jumlah < 1) $jumlah = 1;
    $durasi = (int)($_POST['durasi'] ?? 7);
    $durasiValid = [3, 7, 14, 30];
    if (!in_array($durasi, $durasiValid, true)) $durasi = 7;
    $kondisiSebelum = $_POST['kondisi_sebelum'] ?? 'Baik';
    if (!in_array($kondisiSebelum, kondisiBukuValid(), true)) $kondisiSebelum = 'Baik';
    $kondisiSebelumEsc = mysqli_real_escape_string($conn, $kondisiSebelum);
    $tgl_pinjam = date('Y-m-d');
    $tgl_kembali = date('Y-m-d', strtotime("+$durasi days"));

    // Cek stok
    $stokBuku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok, judul FROM buku WHERE id_buku = $id_buku"));
    if (!$stokBuku || $stokBuku['stok'] <= 0) {
        $message = 'Stok buku habis atau buku tidak ditemukan!';
        $messageType = 'danger';
    } elseif ($jumlah > (int) $stokBuku['stok']) {
        $message = 'Jumlah yang diminta (' . $jumlah . ') melebihi stok tersedia (' . (int) $stokBuku['stok'] . ')!';
        $messageType = 'danger';
    } else {
        // Cek apakah user masih punya permintaan/peminjaman aktif untuk buku ini
        // (sedang menunggu verifikasi ATAU sedang dipinjam dan belum dikembalikan)
        $cekPinjam = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_user = $id_user AND id_buku = $id_buku AND status IN ('menunggu', 'dipinjam')");
        if (mysqli_num_rows($cekPinjam) > 0) {
            $message = 'Anda masih memiliki permintaan/peminjaman aktif untuk buku ini!';
            $messageType = 'danger';
        } else {
            $count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM transaksi"));
            $kode = 'TRX-' . date('Ymd') . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            // Status awal 'menunggu' - stok langsung dicadangkan supaya tidak dobel-pesan,
            // baru resmi jadi 'dipinjam' setelah petugas memverifikasi lewat admin/verifikasi.php
            // kondisi_sebelum diisi anggota sebagai laporan awal, tetap bisa dikoreksi petugas saat verifikasi
            mysqli_query($conn, "INSERT INTO transaksi (kode_transaksi, id_user, id_buku, jumlah, tanggal_pinjam, tanggal_kembali, status, kondisi_sebelum) 
                VALUES ('$kode', $id_user, $id_buku, $jumlah, '$tgl_pinjam', '$tgl_kembali', 'menunggu', '$kondisiSebelumEsc')");
            mysqli_query($conn, "UPDATE buku SET stok = stok - $jumlah WHERE id_buku = $id_buku");

            // Notifikasi ke semua petugas & admin bahwa ada permintaan baru yang perlu diverifikasi
            $namaAnggota = $_SESSION['nama_lengkap'];
            notifBuatUntukStaf(
                $conn,
                'Permintaan Peminjaman Baru',
                "$namaAnggota mengajukan pinjam $jumlah buku \"{$stokBuku['judul']}\" selama $durasi hari (Kode: $kode). Menunggu verifikasi Anda.",
                'info',
                'admin/verifikasi.php'
            );

            $message = 'Permintaan peminjaman berhasil dikirim! Kode: ' . $kode . ' (' . $jumlah . ' buku, ' . $durasi . ' hari). Silakan tunggu verifikasi dari petugas perpustakaan.';
            $messageType = 'success';
        }
    }
}

// Ambil buku tersedia
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filterKategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';

$whereClause = "WHERE stok > 0";
if ($search) $whereClause .= " AND (judul LIKE '%$search%' OR penulis LIKE '%$search%' OR kode_buku LIKE '%$search%')";
if ($filterKategori) $whereClause .= " AND kategori = '$filterKategori'";

$buku = mysqli_query($conn, "SELECT * FROM buku $whereClause ORDER BY judul ASC");

// Ambil daftar kategori
$kategoriList = mysqli_query($conn, "SELECT DISTINCT kategori FROM buku ORDER BY kategori");
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>📖 Peminjaman Buku</h1>
    <p>Pilih buku yang ingin Anda pinjam. Setiap permintaan akan diverifikasi petugas perpustakaan terlebih dahulu sebelum resmi berstatus "Dipinjam".</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
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

<!-- Pencarian & Filter -->
<div class="card">
    <div class="card-body">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Cari judul, penulis, atau kode buku..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="kategori">
                <option value="">Semua Kategori</option>
                <?php while ($k = mysqli_fetch_assoc($kategoriList)): ?>
                    <option value="<?php echo htmlspecialchars($k['kategori']); ?>" <?php echo $filterKategori === $k['kategori'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($k['kategori']); ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Cari</button>
            <?php if ($search || $filterKategori): ?><a href="borrow.php" class="btn btn-secondary">Reset</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- Daftar Buku -->
<div class="books-grid">
    <?php while ($b = mysqli_fetch_assoc($buku)): ?>
    <div class="book-card">
        <div class="book-cover"<?php $src = sampulUrl($b['sampul'], '../'); if ($src): ?> style="background-image:url('<?php echo htmlspecialchars($src); ?>')"<?php endif; ?>>
            <span class="book-category"><?php echo htmlspecialchars($b['kategori']); ?></span>
            <span class="book-stock">Stok: <?php echo (int) $b['stok']; ?></span>
        </div>
        <div class="book-detail">
            <h3 class="book-title"><?php echo htmlspecialchars($b['judul']); ?></h3>
            <p class="book-author"><?php echo htmlspecialchars($b['penulis']); ?></p>
            <p class="book-publisher"><?php echo htmlspecialchars($b['penerbit']); ?> - <?php echo (int) $b['tahun_terbit']; ?></p>
            <p class="book-code"><?php echo htmlspecialchars($b['kode_buku']); ?></p>
        </div>
        <form method="POST" action="" onsubmit="return confirm('Ajukan peminjaman buku ini? Permintaan akan diverifikasi petugas terlebih dahulu.')">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id_buku" value="<?php echo (int) $b['id_buku']; ?>">
            <div class="form-group">
                <label>Jumlah Buku</label>
                <input type="number" name="jumlah" value="1" min="1" max="<?php echo (int) $b['stok']; ?>" required>
            </div>
            <div class="form-group">
                <label>Lama Peminjaman</label>
                <select name="durasi" required>
                    <option value="3">3 hari</option>
                    <option value="7" selected>7 hari</option>
                    <option value="14">14 hari</option>
                    <option value="30">30 hari</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kondisi Buku Saat Ini</label>
                <select name="kondisi_sebelum" required>
                    <option value="Baik" selected>Baik</option>
                    <option value="Rusak Ringan">Rusak Ringan</option>
                    <option value="Rusak Berat">Rusak Berat</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-full">📩 Ajukan Peminjaman</button>
        </form>
    </div>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($buku) === 0): ?>
    <div class="text-center" style="grid-column: 1/-1; padding: 40px;">
        <p>Tidak ada buku tersedia.</p>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>