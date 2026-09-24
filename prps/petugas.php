<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin(); // Halaman ini KHUSUS Admin - petugas tidak boleh mengelola sesama petugas

$pageTitle = 'Kelola Petugas - Perpustakaan SD N 1 Plebengan';
$currentPage = 'petugas';
$message = '';
$messageType = '';

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
        $username = mysqli_real_escape_string($conn, trim($_POST['username']));
        $passwordInput = $_POST['password'] ?? '';

        $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
        if (empty($nama) || empty($username) || empty($passwordInput)) {
            $message = 'Semua field wajib diisi!';
            $messageType = 'danger';
        } elseif (strlen($passwordInput) < 6) {
            $message = 'Password minimal 6 karakter!';
            $messageType = 'danger';
        } elseif (mysqli_num_rows($cek) > 0) {
            $message = 'Username sudah digunakan!';
            $messageType = 'danger';
        } else {
            $password = password_hash($passwordInput, PASSWORD_DEFAULT);
            mysqli_query($conn, "INSERT INTO users (nama_lengkap, username, password, role) 
                VALUES ('$nama', '$username', '$password', 'petugas')");
            $message = 'Akun petugas berhasil ditambahkan!';
            $messageType = 'success';
        }
    } elseif ($aksi === 'edit') {
        $id = (int)$_POST['id_user'];
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
        $username = mysqli_real_escape_string($conn, trim($_POST['username']));
        $passwordInput = $_POST['password'] ?? '';

        $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username' AND id_user != $id");
        if (empty($nama) || empty($username)) {
            $message = 'Nama dan username wajib diisi!';
            $messageType = 'danger';
        } elseif (mysqli_num_rows($cek) > 0) {
            $message = 'Username sudah digunakan akun lain!';
            $messageType = 'danger';
        } else {
            if (!empty($passwordInput)) {
                if (strlen($passwordInput) < 6) {
                    $message = 'Password minimal 6 karakter!';
                    $messageType = 'danger';
                } else {
                    $password = password_hash($passwordInput, PASSWORD_DEFAULT);
                    mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama', username='$username', password='$password' 
                        WHERE id_user=$id AND role='petugas'");
                    $message = 'Data petugas & password berhasil diperbarui!';
                    $messageType = 'success';
                }
            } else {
                mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama', username='$username' 
                    WHERE id_user=$id AND role='petugas'");
                $message = 'Data petugas berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id_user'];
        mysqli_query($conn, "DELETE FROM users WHERE id_user = $id AND role = 'petugas'");
        $message = 'Akun petugas berhasil dihapus!';
        $messageType = 'success';
    }
}

// Ambil data petugas
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$whereClause = $search ? "AND (nama_lengkap LIKE '%$search%' OR username LIKE '%$search%')" : '';
$petugasList = mysqli_query($conn, "SELECT * FROM users WHERE role = 'petugas' $whereClause ORDER BY id_user DESC");

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id_user = $id AND role = 'petugas'"));
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>🧑‍💼 Kelola Petugas</h1>
    <button class="btn btn-primary" onclick="toggleForm('form-petugas')">+ Tambah Petugas</button>
</div>

<div class="info-box">
    <p>Halaman ini khusus <strong>Admin</strong>. Petugas yang ditambahkan di sini dapat login melalui tab
    <strong>"Petugas"</strong> di halaman login, dan memiliki akses ke Data Buku, Kelola Anggota, serta Transaksi
    &mdash; namun tidak dapat mengelola akun petugas lain.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Form Tambah/Edit -->
<div id="form-petugas" class="card form-card" style="display: <?php echo $editData ? 'block' : 'none'; ?>;">
    <div class="card-header">
        <h3><?php echo $editData ? '✏️ Edit Petugas' : '➕ Tambah Petugas Baru'; ?></h3>
        <button class="btn-close" onclick="toggleForm('form-petugas')">&times;</button>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="aksi" value="<?php echo $editData ? 'edit' : 'tambah'; ?>">
            <?php if ($editData): ?>
                <input type="hidden" name="id_user" value="<?php echo (int) $editData['id_user']; ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?php echo $editData ? htmlspecialchars($editData['nama_lengkap']) : ''; ?>" required placeholder="Nama lengkap petugas">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo $editData ? htmlspecialchars($editData['username']) : ''; ?>" required placeholder="Username untuk login">
                </div>
            </div>
            <div class="form-group">
                <label>Password <?php echo $editData ? '(kosongkan jika tidak ingin mengubah)' : ''; ?></label>
                <input type="password" name="password" placeholder="<?php echo $editData ? 'Min. 6 karakter (opsional)' : 'Min. 6 karakter'; ?>" <?php echo $editData ? '' : 'required'; ?>>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo $editData ? 'Simpan Perubahan' : 'Tambah Petugas'; ?></button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm('form-petugas')">Batal</button>
                <?php if ($editData): ?><a href="petugas.php" class="btn btn-secondary">Batal Edit</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Petugas -->
<div class="card">
    <div class="card-header">
        <h3>📋 Daftar Petugas (<?php echo mysqli_num_rows($petugasList); ?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Cari nama atau username..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">🔍 Cari</button>
            <?php if ($search): ?><a href="petugas.php" class="btn btn-secondary">Reset</a><?php endif; ?>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>Nama Lengkap</th><th>Username</th><th>Terdaftar</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($petugasList)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($row['username']); ?></span></td>
                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at']))); ?></td>
                        <td class="action-btns">
                            <a href="?edit=<?php echo (int) $row['id_user']; ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_user" value="<?php echo (int) $row['id_user']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($petugasList) === 0): ?>
                    <tr><td colspan="5" class="text-center">Belum ada akun petugas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>function toggleForm(id) { const el = document.getElementById(id); el.style.display = el.style.display === 'none' ? 'block' : 'none'; }</script>
<?php include '../includes/footer.php'; ?>
