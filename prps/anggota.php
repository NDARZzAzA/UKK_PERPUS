<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireStaff();

$pageTitle = 'Kelola Anggota - Perpustakaan SD N 1 Plebengan';
$currentPage = 'anggota';
$message = '';
$messageType = '';

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nis = mysqli_real_escape_string($conn, trim($_POST['nis']));
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama_anggota']));
        $kelas = mysqli_real_escape_string($conn, trim($_POST['kelas']));
        $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));
        $no_telp = mysqli_real_escape_string($conn, trim($_POST['no_telepon'] ?? ''));

        $cek = mysqli_query($conn, "SELECT nis FROM anggota WHERE nis = '$nis'");
        if (mysqli_num_rows($cek) > 0) {
            $message = 'NIS sudah terdaftar!';
            $messageType = 'danger';
        } else {
            mysqli_query($conn, "INSERT INTO anggota (nis, nama_anggota, kelas, alamat, no_telepon) 
                VALUES ('$nis', '$nama', '$kelas', '$alamat', '$no_telp')");
            $message = 'Anggota berhasil ditambahkan!';
            $messageType = 'success';
        }
    } elseif ($aksi === 'edit') {
        $id = (int)$_POST['id_anggota'];
        $nis = mysqli_real_escape_string($conn, trim($_POST['nis']));
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama_anggota']));
        $kelas = mysqli_real_escape_string($conn, trim($_POST['kelas']));
        $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));
        $no_telp = mysqli_real_escape_string($conn, trim($_POST['no_telepon'] ?? ''));

        mysqli_query($conn, "UPDATE anggota SET nis='$nis', nama_anggota='$nama', kelas='$kelas', 
            alamat='$alamat', no_telepon='$no_telp' WHERE id_anggota=$id");
        $message = 'Anggota berhasil diperbarui!';
        $messageType = 'success';
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id_anggota'];
        mysqli_query($conn, "DELETE FROM anggota WHERE id_anggota = $id");
        $message = 'Anggota berhasil dihapus!';
        $messageType = 'success';
    }
}

// Ambil data anggota
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$whereClause = $search ? "WHERE nama_anggota LIKE '%$search%' OR nis LIKE '%$search%' OR kelas LIKE '%$search%'" : '';
$anggota = mysqli_query($conn, "SELECT a.*, u.username, u.id_user 
    FROM anggota a LEFT JOIN users u ON a.id_user = u.id_user 
    $whereClause ORDER BY a.id_anggota DESC");

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM anggota WHERE id_anggota = $id"));
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>👥 Kelola Anggota</h1>
    <button class="btn btn-primary" onclick="toggleForm('form-anggota')">+ Tambah Anggota</button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Form Tambah/Edit -->
<div id="form-anggota" class="card form-card" style="display: <?php echo $editData ? 'block' : 'none'; ?>;">
    <div class="card-header">
        <h3><?php echo $editData ? '✏️ Edit Anggota' : '➕ Tambah Anggota Baru'; ?></h3>
        <button class="btn-close" onclick="toggleForm('form-anggota')">&times;</button>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="aksi" value="<?php echo $editData ? 'edit' : 'tambah'; ?>">
            <?php if ($editData): ?>
                <input type="hidden" name="id_anggota" value="<?php echo (int) $editData['id_anggota']; ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>NIS / NISN</label>
                    <input type="text" name="nis" value="<?php echo $editData ? htmlspecialchars($editData['nis']) : ''; ?>" required placeholder="Nomor Induk Siswa">
                </div>
                <div class="form-group">
                    <label>Nama Anggota</label>
                    <input type="text" name="nama_anggota" value="<?php echo $editData ? htmlspecialchars($editData['nama_anggota']) : ''; ?>" required placeholder="Nama lengkap siswa">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="kelas" required>
                        <option value="">-- Pilih --</option>
                        <?php
                        $kelasList = ['Kelas 1','Kelas 2','Kelas 3','Kelas 4','Kelas 5','Kelas 6'];
                        foreach ($kelasList as $kls):
                        ?>
                            <option value="<?php echo $kls; ?>" <?php echo ($editData && $editData['kelas'] === $kls) ? 'selected' : ''; ?>><?php echo $kls; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>No. Telepon Ortu</label>
                    <input type="text" name="no_telepon" value="<?php echo $editData ? htmlspecialchars($editData['no_telepon'] ?? '') : ''; ?>" placeholder="08xxxxxxxxxx">
                </div>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <input type="text" name="alamat" value="<?php echo $editData ? htmlspecialchars($editData['alamat'] ?? '') : ''; ?>" placeholder="Alamat rumah siswa">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo $editData ? 'Simpan Perubahan' : 'Tambah Anggota'; ?></button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm('form-anggota')">Batal</button>
                <?php if ($editData): ?><a href="anggota.php" class="btn btn-secondary">Batal Edit</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Anggota -->
<div class="card">
    <div class="card-header">
        <h3>📋 Daftar Anggota (<?php echo mysqli_num_rows($anggota); ?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Cari nama, NIS, atau kelas..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">🔍 Cari</button>
            <?php if ($search): ?><a href="anggota.php" class="btn btn-secondary">Reset</a><?php endif; ?>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Alamat</th><th>Telepon</th><th>Akun</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($anggota)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nis']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['nama_anggota']); ?></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($row['kelas']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['no_telepon'] ?: '-'); ?></td>
                        <td><?php echo $row['username'] ? '<span class="badge badge-green">Ada</span>' : '<span class="badge badge-red">Belum</span>'; ?></td>
                        <td class="action-btns">
                            <a href="?edit=<?php echo (int) $row['id_anggota']; ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_anggota" value="<?php echo (int) $row['id_anggota']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($anggota) === 0): ?>
                    <tr><td colspan="8" class="text-center">Tidak ada data anggota.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>function toggleForm(id) { const el = document.getElementById(id); el.style.display = el.style.display === 'none' ? 'block' : 'none'; }</script>
<?php include '../includes/footer.php'; ?>