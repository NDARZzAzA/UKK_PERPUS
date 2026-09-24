<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifikasi.php';
requireLogin();
requireStaff();

$pageTitle = 'Manajemen Transaksi - Perpustakaan SD N 1 Plebengan';
$currentPage = 'transaksi';
$message = '';
$messageType = '';

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $id_user = (int)$_POST['id_user'];
        $id_buku = (int)$_POST['id_buku'];
        $jumlah = (int)($_POST['jumlah'] ?? 1);
        if ($jumlah < 1) $jumlah = 1;
        $tgl_pinjam = $_POST['tanggal_pinjam'];
        $tgl_kembali = $_POST['tanggal_kembali'];
        $kondisiSebelum = $_POST['kondisi_sebelum'] ?? 'Baik';
        if (!in_array($kondisiSebelum, kondisiBukuValid(), true)) $kondisiSebelum = 'Baik';
        $kondisiSebelumEsc = mysqli_real_escape_string($conn, $kondisiSebelum);

        // Cek stok
        $stokBuku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok, judul FROM buku WHERE id_buku = $id_buku"));
        if (!$stokBuku || $stokBuku['stok'] <= 0) {
            $message = 'Stok buku habis atau buku tidak ditemukan!';
            $messageType = 'danger';
        } elseif ($jumlah > (int) $stokBuku['stok']) {
            $message = 'Jumlah yang diminta (' . $jumlah . ') melebihi stok tersedia (' . (int) $stokBuku['stok'] . ')!';
            $messageType = 'danger';
        } else {
            $count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM transaksi"));
            $kode = 'TRX-' . date('Ymd') . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            mysqli_query($conn, "INSERT INTO transaksi (kode_transaksi, id_user, id_buku, jumlah, tanggal_pinjam, tanggal_kembali, status, kondisi_sebelum) 
                VALUES ('$kode', $id_user, $id_buku, $jumlah, '$tgl_pinjam', '$tgl_kembali', 'dipinjam', '$kondisiSebelumEsc')");
            mysqli_query($conn, "UPDATE buku SET stok = stok - $jumlah WHERE id_buku = $id_buku");
            $message = 'Transaksi peminjaman berhasil ditambahkan!';
            $messageType = 'success';
        }
    } elseif ($aksi === 'kembalikan') {
        $id = (int)$_POST['id_transaksi'];
        $tgl_dikembalikan = $_POST['tanggal_dikembalikan'];
        $kondisiSesudah = $_POST['kondisi_sesudah'] ?? 'Baik';
        if (!in_array($kondisiSesudah, kondisiBukuValid(), true)) $kondisiSesudah = 'Baik';
        $kondisiSesudahEsc = mysqli_real_escape_string($conn, $kondisiSesudah);

        $trx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = $id"));

        if (!$trx) {
            $message = 'Data transaksi tidak ditemukan!';
            $messageType = 'danger';
        } else {
            // Hitung denda (Rp 1000/hari terlambat)
            $denda = 0;
            $status = 'dikembalikan';
            if (strtotime($tgl_dikembalikan) > strtotime($trx['tanggal_kembali'])) {
                $selisih = (strtotime($tgl_dikembalikan) - strtotime($trx['tanggal_kembali'])) / (60 * 60 * 24);
                $denda = ceil($selisih) * 1000;
                $status = 'terlambat';
            }

            mysqli_query($conn, "UPDATE transaksi SET tanggal_dikembalikan = '$tgl_dikembalikan', status = '$status', denda = $denda, kondisi_sesudah = '$kondisiSesudahEsc' WHERE id_transaksi = $id");
            mysqli_query($conn, "UPDATE buku SET stok = stok + {$trx['jumlah']} WHERE id_buku = {$trx['id_buku']}");
            $message = 'Buku berhasil dikembalikan!' . ($denda > 0 ? ' Denda: Rp ' . number_format($denda, 0, ',', '.') : '');
            $messageType = 'success';

            $judulBuku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT judul FROM buku WHERE id_buku = {$trx['id_buku']}"))['judul'] ?? 'buku';
            $pesanKembali = "Pengembalian buku \"$judulBuku\" (Kode: {$trx['kode_transaksi']}) telah dicatat oleh petugas.";
            if ($denda > 0) $pesanKembali .= ' Denda keterlambatan: Rp ' . number_format($denda, 0, ',', '.') . '.';
            notifBuat($conn, $trx['id_user'], 'Buku Telah Dikembalikan 📦', $pesanKembali, $denda > 0 ? 'ditolak' : 'sukses', 'user/my_transactions.php');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id_transaksi'];
        $trx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = $id"));
        // Stok dicadangkan sejak status 'menunggu', jadi dikembalikan untuk kedua status ini
        if ($trx && in_array($trx['status'], ['dipinjam', 'menunggu'], true)) {
            mysqli_query($conn, "UPDATE buku SET stok = stok + {$trx['jumlah']} WHERE id_buku = {$trx['id_buku']}");
        }
        mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $id");
        $message = 'Transaksi berhasil dihapus!';
        $messageType = 'success';
    }
}

// Filter & Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$statusValid = ['menunggu', 'dipinjam', 'dikembalikan', 'terlambat', 'ditolak'];
$filterStatus = (isset($_GET['status']) && in_array($_GET['status'], $statusValid, true)) ? $_GET['status'] : '';

$whereClause = "WHERE 1=1";
if ($search) $whereClause .= " AND (t.kode_transaksi LIKE '%$search%' OR b.judul LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%')";
if ($filterStatus) $whereClause .= " AND t.status = '$filterStatus'";

$transaksi = mysqli_query($conn, "SELECT t.*, b.judul, b.kode_buku, u.nama_lengkap, u.username 
    FROM transaksi t 
    JOIN buku b ON t.id_buku = b.id_buku 
    JOIN users u ON t.id_user = u.id_user 
    $whereClause ORDER BY t.created_at DESC");

// Data untuk form
$users = mysqli_query($conn, "SELECT u.id_user, u.nama_lengkap, a.nis, a.kelas FROM users u LEFT JOIN anggota a ON u.id_user = a.id_user WHERE u.role = 'user'");
$bukus = mysqli_query($conn, "SELECT * FROM buku WHERE stok > 0 ORDER BY judul");

// Data untuk grafik status transaksi (semua data, tidak terpengaruh filter/search)
$statusCounts = ['menunggu' => 0, 'dipinjam' => 0, 'dikembalikan' => 0, 'terlambat' => 0, 'ditolak' => 0];
$statusResult = mysqli_query($conn, "SELECT status, COUNT(*) AS jumlah FROM transaksi GROUP BY status");
while ($row = mysqli_fetch_assoc($statusResult)) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int) $row['jumlah'];
    }
}

// Data untuk grafik jumlah peminjaman per bulan (6 bulan terakhir)
$monthlyLabels = [];
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $monthlyLabels[$ym] = date('M Y', strtotime("-$i months"));
    $monthlyData[$ym] = 0;
}
$monthlyResult = mysqli_query($conn, "SELECT DATE_FORMAT(tanggal_pinjam, '%Y-%m') AS bulan, COUNT(*) AS jumlah
    FROM transaksi
    WHERE tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY bulan ORDER BY bulan");
while ($row = mysqli_fetch_assoc($monthlyResult)) {
    if (isset($monthlyData[$row['bulan']])) {
        $monthlyData[$row['bulan']] = (int) $row['jumlah'];
    }
}

// Data untuk pengembalian
$returnData = null;
if (isset($_GET['return'])) {
    $id = (int)$_GET['return'];
    $returnData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT t.*, b.judul, b.kode_buku, u.nama_lengkap 
        FROM transaksi t JOIN buku b ON t.id_buku = b.id_buku JOIN users u ON t.id_user = u.id_user WHERE t.id_transaksi = $id AND t.status = 'dipinjam'"));
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1>📋 Manajemen Transaksi</h1>
    <div class="page-header-actions">
        <a href="laporan.php<?php echo $filterStatus ? '?status=' . urlencode($filterStatus) : ''; ?>" class="btn btn-secondary">🖨️ Cetak Laporan</a>
        <button class="btn btn-primary" onclick="toggleForm('form-transaksi')">+ Tambah Transaksi</button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Form Tambah Transaksi -->
<div id="form-transaksi" class="card form-card" style="display: none;">
    <div class="card-header">
        <h3>➕ Tambah Peminjaman</h3>
        <button class="btn-close" onclick="toggleForm('form-transaksi')">&times;</button>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label>Peminjam</label>
                <select name="id_user" required>
                    <option value="">-- Pilih Peminjam --</option>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <option value="<?php echo (int) $u['id_user']; ?>"><?php echo htmlspecialchars($u['nama_lengkap']) . ' (' . htmlspecialchars($u['nis'] ?: $u['username']) . ') - ' . htmlspecialchars($u['kelas'] ?? ''); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Buku</label>
                <select name="id_buku" required>
                    <option value="">-- Pilih Buku (stok tersedia) --</option>
                    <?php mysqli_data_seek($bukus, 0); while ($b = mysqli_fetch_assoc($bukus)): ?>
                        <option value="<?php echo (int) $b['id_buku']; ?>"><?php echo htmlspecialchars($b['kode_buku']) . ' - ' . htmlspecialchars($b['judul']) . ' (Stok: ' . (int) $b['stok'] . ')'; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah Buku</label>
                <input type="number" name="jumlah" value="1" min="1" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Kembali</label>
                    <input type="date" name="tanggal_kembali" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Kondisi Buku Saat Diserahkan</label>
                <select name="kondisi_sebelum" required>
                    <option value="Baik" selected>Baik</option>
                    <option value="Rusak Ringan">Rusak Ringan</option>
                    <option value="Rusak Berat">Rusak Berat</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm('form-transaksi')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Pengembalian -->
<?php if ($returnData): ?>
<div class="card form-card">
    <div class="card-header">
        <h3>📦 Pengembalian Buku</h3>
        <a href="transaksi.php" class="btn btn-secondary">Batal</a>
    </div>
    <div class="card-body">
        <div class="info-box">
            <p><strong>Kode:</strong> <?php echo htmlspecialchars($returnData['kode_transaksi']); ?></p>
            <p><strong>Peminjam:</strong> <?php echo htmlspecialchars($returnData['nama_lengkap']); ?></p>
            <p><strong>Buku:</strong> <?php echo htmlspecialchars($returnData['judul']); ?> (<?php echo htmlspecialchars($returnData['kode_buku']); ?>)</p>
            <p><strong>Jumlah:</strong> <?php echo (int) $returnData['jumlah']; ?> buku</p>
            <p><strong>Tgl Pinjam:</strong> <?php echo htmlspecialchars($returnData['tanggal_pinjam']); ?></p>
            <p><strong>Batas Kembali:</strong> <?php echo htmlspecialchars($returnData['tanggal_kembali']); ?></p>
            <p><strong>Kondisi Saat Dipinjam:</strong> <?php echo kondisiBadge($returnData['kondisi_sebelum']); ?></p>
        </div>
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="aksi" value="kembalikan">
            <input type="hidden" name="id_transaksi" value="<?php echo (int) $returnData['id_transaksi']; ?>">
            <div class="form-group">
                <label>Tanggal Dikembalikan</label>
                <input type="date" name="tanggal_dikembalikan" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Kondisi Buku Saat Dikembalikan</label>
                <select name="kondisi_sesudah" required>
                    <option value="Baik" selected>Baik</option>
                    <option value="Rusak Ringan">Rusak Ringan</option>
                    <option value="Rusak Berat">Rusak Berat</option>
                    <option value="Hilang">Hilang</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Proses Pengembalian</button>
                <a href="transaksi.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Grafik Transaksi (SVG digambar langsung oleh PHP di server, tanpa JS/library luar) -->
<div class="chart-grid">
    <div class="card">
        <div class="card-header">
            <h3>📈 Tren Peminjaman (6 Bulan Terakhir)</h3>
        </div>
        <div class="card-body">
            <?php
            // --- Hitung geometri grafik batang ---
            $svgW = 640; $svgH = 260;
            $padL = 36; $padR = 16; $padT = 24; $padB = 34;
            $chartW = $svgW - $padL - $padR;
            $chartH = $svgH - $padT - $padB;
            $n = max(1, count($monthlyLabels));
            $gap = 16;
            $barW = ($chartW - $gap * ($n - 1)) / $n;
            $maxMonthly = max(1, max($monthlyData));
            $niceMax = $maxMonthly <= 5 ? 5 : (int) (ceil($maxMonthly / 5) * 5);
            ?>
            <svg viewBox="0 0 <?php echo $svgW; ?> <?php echo $svgH; ?>" class="chart-svg" role="img" aria-label="Grafik tren peminjaman 6 bulan terakhir" preserveAspectRatio="xMidYMid meet">
                <?php for ($g = 0; $g <= 4; $g++):
                    $gy = $padT + $chartH - ($chartH * $g / 4);
                    $gval = (int) round($niceMax * $g / 4);
                ?>
                <line x1="<?php echo $padL; ?>" y1="<?php echo round($gy, 1); ?>" x2="<?php echo $padL + $chartW; ?>" y2="<?php echo round($gy, 1); ?>" class="chart-grid-line" />
                <text x="<?php echo $padL - 8; ?>" y="<?php echo round($gy + 4, 1); ?>" class="chart-axis-label" text-anchor="end"><?php echo $gval; ?></text>
                <?php endfor; ?>
                <?php $i = 0; foreach ($monthlyLabels as $ym => $label): $val = $monthlyData[$ym];
                    $barH = ($val / $niceMax) * $chartH;
                    $x = $padL + $i * ($barW + $gap);
                    $y = $padT + $chartH - $barH;
                ?>
                <rect x="<?php echo round($x, 1); ?>" y="<?php echo round($y, 1); ?>" width="<?php echo round($barW, 1); ?>" height="<?php echo round($barH, 1); ?>" rx="5" class="chart-bar" />
                <?php if ($val > 0): ?>
                <text x="<?php echo round($x + $barW / 2, 1); ?>" y="<?php echo round($y - 8, 1); ?>" class="chart-bar-value" text-anchor="middle"><?php echo $val; ?></text>
                <?php endif; ?>
                <text x="<?php echo round($x + $barW / 2, 1); ?>" y="<?php echo $padT + $chartH + 20; ?>" class="chart-axis-label" text-anchor="middle"><?php echo htmlspecialchars($label); ?></text>
                <?php $i++; endforeach; ?>
                <line x1="<?php echo $padL; ?>" y1="<?php echo $padT + $chartH; ?>" x2="<?php echo $padL + $chartW; ?>" y2="<?php echo $padT + $chartH; ?>" class="chart-axis-line" />
            </svg>
            <table class="table table-sm">
                <thead><tr><th>Bulan</th><th>Jumlah Peminjaman</th></tr></thead>
                <tbody>
                    <?php foreach ($monthlyLabels as $ym => $label): ?>
                    <tr><td><?php echo htmlspecialchars($label); ?></td><td><?php echo $monthlyData[$ym]; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>🥧 Distribusi Status Transaksi</h3>
        </div>
        <div class="card-body">
            <?php
            $totalStatus = array_sum($statusCounts);
            $statusLabels = ['menunggu' => 'Menunggu Verifikasi', 'dipinjam' => 'Dipinjam', 'dikembalikan' => 'Dikembalikan', 'terlambat' => 'Terlambat', 'ditolak' => 'Ditolak'];
            $statusColor  = ['menunggu' => 'purple', 'dipinjam' => 'orange', 'dikembalikan' => 'green', 'terlambat' => 'red', 'ditolak' => 'gray'];
            $statusHex    = ['menunggu' => '#9b59b6', 'dipinjam' => '#f0ad4e', 'dikembalikan' => '#28a745', 'terlambat' => '#dc3545', 'ditolak' => '#95a5a6'];
            $cx = 100; $cy = 100; $r = 68; $strokeW = 30;
            $circumference = 2 * M_PI * $r;
            $offsetAcc = 0;
            ?>
            <div class="donut-wrap">
                <svg viewBox="0 0 200 200" class="donut-svg" role="img" aria-label="Grafik distribusi status transaksi">
                    <?php if ($totalStatus === 0): ?>
                    <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>" fill="none" stroke="#eee" stroke-width="<?php echo $strokeW; ?>" />
                    <?php else: ?>
                        <?php foreach ($statusCounts as $key => $val):
                            if ($val <= 0) continue;
                            $frac = $val / $totalStatus;
                            $dash = $frac * $circumference;
                            $gapLen = $circumference - $dash;
                        ?>
                    <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>" fill="none"
                        stroke="<?php echo $statusHex[$key]; ?>" stroke-width="<?php echo $strokeW; ?>"
                        stroke-dasharray="<?php echo round($dash, 2); ?> <?php echo round($gapLen, 2); ?>"
                        stroke-dashoffset="<?php echo round(-$offsetAcc, 2); ?>"
                        transform="rotate(-90 <?php echo $cx; ?> <?php echo $cy; ?>)" />
                        <?php $offsetAcc += $dash; endforeach; ?>
                    <?php endif; ?>
                    <text x="<?php echo $cx; ?>" y="<?php echo $cy - 4; ?>" text-anchor="middle" class="donut-center-value"><?php echo $totalStatus; ?></text>
                    <text x="<?php echo $cx; ?>" y="<?php echo $cy + 16; ?>" text-anchor="middle" class="donut-center-label">Total</text>
                </svg>
                <div class="donut-legend">
                    <?php foreach ($statusCounts as $key => $val): $pct = $totalStatus > 0 ? round($val / $totalStatus * 100) : 0; ?>
                    <div class="donut-legend-item">
                        <span class="donut-legend-dot" style="background: <?php echo $statusHex[$key]; ?>;"></span>
                        <?php echo $statusLabels[$key]; ?>: <strong><?php echo $val; ?></strong> (<?php echo $pct; ?>%)
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <table class="table status-table">
                <thead><tr><th>Status</th><th>Jumlah</th><th>Persentase</th></tr></thead>
                <tbody>
                    <?php foreach ($statusCounts as $key => $val): $pct = $totalStatus > 0 ? round(($val / $totalStatus) * 100) : 0; ?>
                    <tr>
                        <td><span class="badge badge-<?php echo $statusColor[$key]; ?>"><?php echo $statusLabels[$key]; ?></span></td>
                        <td><?php echo $val; ?></td>
                        <td>
                            <div class="status-bar-track"><div class="status-bar-fill status-bar-<?php echo $statusColor[$key]; ?>" style="width: <?php echo $pct; ?>%;"></div></div>
                            <span class="status-bar-pct"><?php echo $pct; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($totalStatus === 0): ?>
                    <tr><td colspan="3" class="text-center">Belum ada data transaksi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Daftar Transaksi -->
<div class="card">
    <div class="card-header">
        <h3>📋 Daftar Transaksi (<?php echo mysqli_num_rows($transaksi); ?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Cari kode transaksi, judul, peminjam..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="menunggu" <?php echo $filterStatus === 'menunggu' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                <option value="dipinjam" <?php echo $filterStatus === 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                <option value="dikembalikan" <?php echo $filterStatus === 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                <option value="terlambat" <?php echo $filterStatus === 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
                <option value="ditolak" <?php echo $filterStatus === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="transaksi.php" class="btn btn-secondary">Reset</a>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>No</th><th>Kode</th><th>Peminjam</th><th>Buku</th><th>Jumlah</th><th>Tgl Pinjam</th><th>Batas</th><th>Dikembalikan</th><th>Status</th><th>Denda</th><th>Kondisi Sebelum</th><th>Kondisi Sesudah</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($transaksi)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['kode_transaksi']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                        <td><?php echo (int) $row['jumlah']; ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_pinjam']); ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_kembali']); ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_dikembalikan'] ?: '-'); ?></td>
                        <td>
                            <?php echo statusBadge($row['status']); ?>
                            <?php if ($row['status'] === 'ditolak' && !empty($row['catatan_petugas'])): ?>
                                <br><small style="color:#999;" title="<?php echo htmlspecialchars($row['catatan_petugas']); ?>">Alasan: <?php echo htmlspecialchars(mb_strimwidth($row['catatan_petugas'], 0, 40, '…')); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['denda'] > 0 ? 'Rp ' . number_format($row['denda'], 0, ',', '.') : '-'; ?></td>
                        <td><?php echo kondisiBadge($row['kondisi_sebelum']); ?></td>
                        <td><?php echo kondisiBadge($row['kondisi_sesudah']); ?></td>
                        <td class="action-btns">
                            <?php if ($row['status'] === 'menunggu'): ?>
                                <a href="verifikasi.php" class="btn btn-sm btn-primary">🛎️ Verifikasi</a>
                            <?php endif; ?>
                            <?php if ($row['status'] === 'dipinjam'): ?>
                                <a href="?return=<?php echo (int) $row['id_transaksi']; ?>" class="btn btn-sm btn-success">📦 Kembalikan</a>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_transaksi" value="<?php echo (int) $row['id_transaksi']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($transaksi) === 0): ?>
                    <tr><td colspan="13" class="text-center">Tidak ada data transaksi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>function toggleForm(id) { const el = document.getElementById(id); el.style.display = el.style.display === 'none' ? 'block' : 'none'; }</script>
<?php include '../includes/footer.php'; ?>