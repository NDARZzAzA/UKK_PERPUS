<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
requireStaff();

$pageTitle = 'Data Buku - Perpustakaan SD N 1 Plebengan';
$currentPage = 'buku';
$message = '';
$messageType = '';

// Fungsi: proses file sampul yang diunggah lewat form (jika ada).
// Mengembalikan: ['ok' => bool, 'filename' => string|null, 'error' => string|null]
// - 'ok' => true, 'filename' => null   berarti TIDAK ada file yang diunggah (bukan error)
// - 'ok' => false                      berarti file diunggah tapi tidak valid
function prosesUploadSampul($kodeBuku) {
    if (!isset($_FILES['sampul_file']) || $_FILES['sampul_file']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => null, 'error' => null];
    }
    if ($_FILES['sampul_file']['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'Gagal mengunggah file sampul.'];
    }
    if ($_FILES['sampul_file']['size'] > SAMPUL_MAX_SIZE) {
        return ['ok' => false, 'filename' => null, 'error' => 'Ukuran file sampul maksimal 2MB.'];
    }
    $ext = strtolower(pathinfo($_FILES['sampul_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, sampulEkstensiValid(), true)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Format file sampul harus JPG, PNG, atau WEBP.'];
    }
    // Pastikan ini benar-benar file gambar, bukan sekadar nama file yang di-rename
    $imageInfo = @getimagesize($_FILES['sampul_file']['tmp_name']);
    if ($imageInfo === false) {
        return ['ok' => false, 'filename' => null, 'error' => 'File yang diunggah bukan gambar yang valid.'];
    }
    if (!is_dir(SAMPUL_UPLOAD_DIR)) {
        mkdir(SAMPUL_UPLOAD_DIR, 0755, true);
    }
    $namaAman = preg_replace('/[^a-z0-9]+/i', '-', strtolower($kodeBuku));
    $filename = $namaAman . '-' . time() . '.' . $ext;
    if (!move_uploaded_file($_FILES['sampul_file']['tmp_name'], SAMPUL_UPLOAD_DIR . $filename)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Gagal menyimpan file sampul ke server.'];
    }
    return ['ok' => true, 'filename' => $filename, 'error' => null];
}

// Fungsi: hapus file sampul lama dari server (dipanggil saat sampul diganti/buku dihapus)
function hapusFileSampul($filename) {
    if (!empty($filename)) {
        $path = SAMPUL_UPLOAD_DIR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $kode_buku = mysqli_real_escape_string($conn, trim($_POST['kode_buku']));
        $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
        $penulis = mysqli_real_escape_string($conn, trim($_POST['penulis']));
        $penerbit = mysqli_real_escape_string($conn, trim($_POST['penerbit']));
        $tahun_terbit = (int)$_POST['tahun_terbit'];
        $kategori = mysqli_real_escape_string($conn, trim($_POST['kategori']));
        $stok = (int)$_POST['stok'];

        $cek = mysqli_query($conn, "SELECT kode_buku FROM buku WHERE kode_buku = '$kode_buku'");
        if (mysqli_num_rows($cek) > 0) {
            $message = 'Kode buku sudah ada!';
            $messageType = 'danger';
        } else {
            $upload = prosesUploadSampul($_POST['kode_buku']);
            if (!$upload['ok']) {
                $message = $upload['error'];
                $messageType = 'danger';
            } else {
                $sampulSql = $upload['filename'] ? "'" . mysqli_real_escape_string($conn, $upload['filename']) . "'" : 'NULL';
                mysqli_query($conn, "INSERT INTO buku (kode_buku, judul, penulis, penerbit, tahun_terbit, kategori, stok, sampul) 
                    VALUES ('$kode_buku', '$judul', '$penulis', '$penerbit', $tahun_terbit, '$kategori', $stok, $sampulSql)");
                $message = 'Buku berhasil ditambahkan!';
                $messageType = 'success';
            }
        }
    } elseif ($aksi === 'edit') {
        $id = (int)$_POST['id_buku'];
        $kode_buku = mysqli_real_escape_string($conn, trim($_POST['kode_buku']));
        $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
        $penulis = mysqli_real_escape_string($conn, trim($_POST['penulis']));
        $penerbit = mysqli_real_escape_string($conn, trim($_POST['penerbit']));
        $tahun_terbit = (int)$_POST['tahun_terbit'];
        $kategori = mysqli_real_escape_string($conn, trim($_POST['kategori']));
        $stok = (int)$_POST['stok'];
        $hapusSampul = isset($_POST['hapus_sampul']);

        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sampul FROM buku WHERE id_buku = $id"));
        $sampulLama = $existing ? $existing['sampul'] : null;

        $upload = prosesUploadSampul($_POST['kode_buku']);
        if (!$upload['ok']) {
            $message = $upload['error'];
            $messageType = 'danger';
        } else {
            $sampulSetClause = '';
            if ($upload['filename']) {
                // Ada sampul baru diunggah: ganti dan hapus file lama
                hapusFileSampul($sampulLama);
                $sampulSetClause = ", sampul='" . mysqli_real_escape_string($conn, $upload['filename']) . "'";
            } elseif ($hapusSampul) {
                // Admin memilih menghapus sampul tanpa mengganti dengan yang baru
                hapusFileSampul($sampulLama);
                $sampulSetClause = ", sampul=NULL";
            }

            mysqli_query($conn, "UPDATE buku SET kode_buku='$kode_buku', judul='$judul', penulis='$penulis', 
                penerbit='$penerbit', tahun_terbit=$tahun_terbit, kategori='$kategori', stok=$stok$sampulSetClause WHERE id_buku=$id");
            $message = 'Buku berhasil diperbarui!';
            $messageType = 'success';
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id_buku'];
        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sampul FROM buku WHERE id_buku = $id"));
        mysqli_query($conn, "DELETE FROM buku WHERE id_buku = $id");
        if ($existing) {
            hapusFileSampul($existing['sampul']);
        }
        $message = 'Buku berhasil dihapus!';
        $messageType = 'success';
    }
}

// Ambil data buku
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$whereClause = $search ? "WHERE judul LIKE '%$search%' OR kode_buku LIKE '%$search%' OR penulis LIKE '%$search%' OR kategori LIKE '%$search%'" : '';
$buku = mysqli_query($conn, "SELECT * FROM buku $whereClause ORDER BY id_buku DESC");

// Ambil data untuk edit
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM buku WHERE id_buku = $id"));
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>📖 Data Buku</h1>
    <button class="btn btn-primary" onclick="toggleForm('form-buku')">+ Tambah Buku</button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Form Tambah/Edit Buku -->
<div id="form-buku" class="card form-card" style="display: <?php echo $editData ? 'block' : 'none'; ?>;">
    <div class="card-header">
        <h3><?php echo $editData ? '✏️ Edit Buku' : '➕ Tambah Buku Baru'; ?></h3>
        <button class="btn-close" onclick="toggleForm('form-buku')">&times;</button>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="aksi" value="<?php echo $editData ? 'edit' : 'tambah'; ?>">
            <?php if ($editData): ?>
                <input type="hidden" name="id_buku" value="<?php echo (int) $editData['id_buku']; ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Kode Buku</label>
                    <input type="text" name="kode_buku" value="<?php echo $editData ? htmlspecialchars($editData['kode_buku']) : ''; ?>" required placeholder="Contoh: BK013">
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" required>
                        <option value="">-- Pilih --</option>
                        <?php 
                        $kategoriList = ['Cerita Anak', 'Dongeng', 'Pelajaran', 'Pengetahuan Umum', 'Puisi', 'Sejarah', 'Ensiklopedia', 'Komik', 'Lainnya'];
                        foreach ($kategoriList as $kat):
                        ?>
                            <option value="<?php echo $kat; ?>" <?php echo ($editData && $editData['kategori'] === $kat) ? 'selected' : ''; ?>><?php echo $kat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Judul Buku</label>
                <input type="text" name="judul" value="<?php echo $editData ? htmlspecialchars($editData['judul']) : ''; ?>" required placeholder="Judul buku">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Penulis</label>
                    <input type="text" name="penulis" value="<?php echo $editData ? htmlspecialchars($editData['penulis']) : ''; ?>" required placeholder="Nama penulis">
                </div>
                <div class="form-group">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" value="<?php echo $editData ? htmlspecialchars($editData['penerbit']) : ''; ?>" required placeholder="Nama penerbit">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" value="<?php echo $editData ? $editData['tahun_terbit'] : date('Y'); ?>" required min="1900" max="2030">
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" value="<?php echo $editData ? $editData['stok'] : '1'; ?>" required min="0">
                </div>
            </div>
            <div class="form-group">
                <label>Sampul Buku (gambar cover)</label>
                <?php $editSrc = $editData ? sampulUrl($editData['sampul'], '../') : null; ?>
                <?php if ($editSrc): ?>
                    <div class="sampul-preview-current">
                        <img src="<?php echo htmlspecialchars($editSrc); ?>" alt="Sampul saat ini">
                        <label class="sampul-remove-check">
                            <input type="checkbox" name="hapus_sampul" value="1"> Hapus sampul ini
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="sampul_file" accept=".jpg,.jpeg,.png,.webp">
                <small class="form-hint">Opsional. Format JPG/PNG/WEBP, maksimal 2MB. Kalau tidak diisi, sampul lama tetap dipakai (atau memakai sampul default jika belum ada).</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo $editData ? 'Simpan Perubahan' : 'Tambah Buku'; ?></button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm('form-buku')">Batal</button>
                <?php if ($editData): ?>
                    <a href="buku.php" class="btn btn-secondary">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Pencarian -->
<div class="card">
    <div class="card-header">
        <h3>📋 Daftar Buku (<?php echo mysqli_num_rows($buku); ?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Cari judul, kode, penulis, atau kategori..." value="<?php echo htmlspecialchars($search); ?>" id="search-input">
            <button type="submit" class="btn btn-primary">🔍 Cari</button>
            <?php if ($search): ?>
                <a href="buku.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>
        <div class="table-responsive">
            <table class="table" id="table-buku">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Sampul</th>
                        <th>Kode</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Penerbit</th>
                        <th>Tahun</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($buku)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <?php $rowSrc = sampulUrl($row['sampul'], '../'); ?>
                            <?php if ($rowSrc): ?>
                                <img src="<?php echo htmlspecialchars($rowSrc); ?>" alt="Sampul <?php echo htmlspecialchars($row['judul']); ?>" class="book-thumb-table">
                            <?php else: ?>
                                <span class="book-thumb-table book-thumb-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['kode_buku']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                        <td><?php echo htmlspecialchars($row['penulis']); ?></td>
                        <td><?php echo htmlspecialchars($row['penerbit']); ?></td>
                        <td><?php echo (int) $row['tahun_terbit']; ?></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($row['kategori']); ?></span></td>
                        <td><span class="badge <?php echo $row['stok'] > 0 ? 'badge-green' : 'badge-red'; ?>"><?php echo (int) $row['stok']; ?></span></td>
                        <td class="action-btns">
                            <a href="?edit=<?php echo (int) $row['id_buku']; ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_buku" value="<?php echo (int) $row['id_buku']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($buku) === 0): ?>
                    <tr><td colspan="10" class="text-center">Tidak ada data buku ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>function toggleForm(id) { const el = document.getElementById(id); el.style.display = el.style.display === 'none' ? 'block' : 'none'; }</script>
<?php include '../includes/footer.php'; ?>