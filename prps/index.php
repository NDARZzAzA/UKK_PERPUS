<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Jika sudah login, arahkan langsung ke dashboard masing-masing (bukan paksa logout)
if (isLoggedIn()) {
    header('Location: ' . (isStaff() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}

$pageTitle = 'Beranda - E-Perpustakaan SD N 1 Plebengan';
$currentPublicPage = 'beranda';

// =============================================
// ULASAN & RATING PENGUNJUNG
// =============================================
$ulasanError = '';
$ulasanSuccess = '';
$oldUlasanInput = ['nama_pengulas' => '', 'peran' => '', 'rating' => 0, 'komentar' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ulasan'])) {
    verifyCsrfToken();

    $nama_pengulas = trim($_POST['nama_pengulas'] ?? '');
    $peran = trim($_POST['peran'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    $oldUlasanInput = compact('nama_pengulas', 'peran', 'rating', 'komentar');

    if ($nama_pengulas === '' || $komentar === '') {
        $ulasanError = 'Nama dan ulasan wajib diisi!';
    } elseif (mb_strlen($nama_pengulas) > 100) {
        $ulasanError = 'Nama terlalu panjang (maksimal 100 karakter).';
    } elseif (mb_strlen($komentar) > 500) {
        $ulasanError = 'Ulasan maksimal 500 karakter.';
    } elseif ($rating < 1 || $rating > 5) {
        $ulasanError = 'Silakan pilih rating bintang (1-5) terlebih dahulu!';
    } else {
        $namaEsc = mysqli_real_escape_string($conn, $nama_pengulas);
        $peranEsc = mysqli_real_escape_string($conn, $peran !== '' ? $peran : 'Pengunjung');
        $komentarEsc = mysqli_real_escape_string($conn, $komentar);

        $okUlasan = mysqli_query($conn, "INSERT INTO ulasan (nama_pengulas, peran, rating, komentar) VALUES ('$namaEsc', '$peranEsc', $rating, '$komentarEsc')");
        if ($okUlasan) {
            $_SESSION['ulasan_success'] = 'Terima kasih, ' . htmlspecialchars($nama_pengulas) . '! Ulasan kamu berhasil dikirim.';
            header('Location: index.php#ulasan');
            exit();
        } else {
            $ulasanError = 'Gagal mengirim ulasan, silakan coba lagi.';
        }
    }
}

// Tampilkan pesan sukses (session flash) setelah redirect, supaya form tidak submit ulang saat refresh
if (!empty($_SESSION['ulasan_success'])) {
    $ulasanSuccess = $_SESSION['ulasan_success'];
    unset($_SESSION['ulasan_success']);
}

// Urutan ulasan (default: terbaru)
$sortUlasan = $_GET['sort'] ?? 'terbaru';
if (!in_array($sortUlasan, ['terbaru', 'tertinggi', 'terendah'], true)) {
    $sortUlasan = 'terbaru';
}
$orderByUlasan = [
    'terbaru'   => 'created_at DESC',
    'tertinggi' => 'rating DESC, created_at DESC',
    'terendah'  => 'rating ASC, created_at DESC',
][$sortUlasan];

// Jumlah ulasan yang ditampilkan (untuk tombol "muat lebih banyak")
$jumlahTampil = isset($_GET['jumlah']) ? (int) $_GET['jumlah'] : 6;
if ($jumlahTampil < 3) { $jumlahTampil = 3; }
if ($jumlahTampil > 60) { $jumlahTampil = 60; }

// Ambil ulasan yang statusnya tampil, sesuai urutan & batas yang dipilih
$ulasanList = mysqli_query($conn, "SELECT * FROM ulasan WHERE status = 'tampil' ORDER BY $orderByUlasan LIMIT $jumlahTampil");

// Ringkasan rating rata-rata & sebaran per bintang (dari SEMUA ulasan tampil, bukan cuma yang dimuat)
$ringkasanRating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, COALESCE(AVG(rating),0) as rata FROM ulasan WHERE status = 'tampil'"));
$rataRating = round((float) $ringkasanRating['rata'], 1);
$totalUlasan = (int) $ringkasanRating['total'];

$sebaranRating = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$sebaranQuery = mysqli_query($conn, "SELECT rating, COUNT(*) as jumlah FROM ulasan WHERE status = 'tampil' GROUP BY rating");
while ($s = mysqli_fetch_assoc($sebaranQuery)) {
    $sebaranRating[(int) $s['rating']] = (int) $s['jumlah'];
}

// Statistik publik
$totalBuku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(stok),0) as total FROM buku"))['total'];
$totalJudul = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM buku"))['total'];
$totalAnggota = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM anggota"))['total'];
$totalKategori = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT kategori) as total FROM buku"))['total'];

// Buku pilihan untuk ditampilkan di beranda
$bukuPilihan = mysqli_query($conn, "SELECT * FROM buku ORDER BY stok DESC, created_at DESC LIMIT 8");

// Daftar kategori untuk quick-links
$kategoriList = mysqli_query($conn, "SELECT kategori, COUNT(*) as jumlah FROM buku GROUP BY kategori ORDER BY jumlah DESC");
?>
<?php include 'includes/public_header.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <span class="hero-badge">🎉 Perpustakaan Digital Sekolah</span>
            <h1>Jelajahi Dunia Lewat Buku, <br>Kapan Saja &amp; Di Mana Saja</h1>
            <p>E-Perpustakaan SD N 1 Plebengan menghadirkan katalog buku, peminjaman, dan pengembalian
            secara online untuk seluruh siswa, petugas, dan pengelola sekolah.</p>
            <div class="hero-actions">
                <a href="katalog.php" class="btn btn-primary btn-lg">📖 Lihat Katalog Buku</a>
                <a href="register.php" class="btn btn-outline btn-lg">Daftar Jadi Anggota</a>
            </div>
            <div class="hero-trust">
                <span>👦🧒👧</span>
                <span>Sudah dipakai <strong><?php echo (int) $totalAnggota; ?> anggota</strong> untuk pinjam buku setiap hari</span>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-photo-frame">
                <video class="hero-photo" autoplay muted loop playsinline preload="auto" poster="assets/img/sekolah/gerbang-sd-plebengan.jpg">
                    <source src="assets/video/hero-sekolah.mp4" type="video/mp4">
                </video>

                <span class="hero-float-badge hero-float-badge-1">🏫 SD Negeri Plebengan</span>
                <span class="hero-float-badge hero-float-badge-2">📚 Yuk Membaca!</span>

                <svg class="hero-float-wifi" viewBox="0 0 60 44" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <ellipse cx="30" cy="24" rx="20" ry="15" fill="#ffffff" opacity="0.95" />
                    <path d="M20 20 Q30 8 40 20" stroke="#667eea" stroke-width="3.4" fill="none" stroke-linecap="round" />
                    <path d="M24 24 Q30 17 36 24" stroke="#667eea" stroke-width="3.4" fill="none" stroke-linecap="round" />
                    <circle cx="30" cy="27" r="2.2" fill="#667eea" />
                </svg>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="public-stats">
    <div class="public-stats-inner">
        <div class="public-stat">
            <div class="public-stat-number"><?php echo (int) $totalBuku; ?></div>
            <div class="public-stat-label">Eksemplar Buku</div>
        </div>
        <div class="public-stat">
            <div class="public-stat-number"><?php echo (int) $totalJudul; ?></div>
            <div class="public-stat-label">Judul Koleksi</div>
        </div>
        <div class="public-stat">
            <div class="public-stat-number"><?php echo (int) $totalKategori; ?></div>
            <div class="public-stat-label">Kategori Buku</div>
        </div>
        <div class="public-stat">
            <div class="public-stat-number"><?php echo (int) $totalAnggota; ?></div>
            <div class="public-stat-label">Anggota Terdaftar</div>
        </div>
    </div>
</section>

<!-- FITUR UNGGULAN -->
<section class="public-section">
    <div class="public-section-inner">
        <h2 class="section-title">Kenapa Siswa &amp; Petugas Menyukai Sistem Ini</h2>
        <p class="section-subtitle">Semua kemudahan meminjam dan mengelola buku, dirancang khusus untuk kebutuhan sekolah dasar</p>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">📖</div>
                <h3>Katalog Selalu Terkini</h3>
                <p>Stok dan judul buku diperbarui otomatis, jadi kamu tahu buku mana yang masih tersedia sebelum ke rak.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🖥️</div>
                <h3>Pinjam Tanpa Antre</h3>
                <p>Ajukan peminjaman dan pengembalian langsung dari akun sendiri, petugas tinggal konfirmasi transaksinya.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🗂️</div>
                <h3>Riwayat Baca Rapi</h3>
                <p>Semua buku yang pernah dan sedang dipinjam tersimpan otomatis, mudah dicek kembali di dashboard anggota.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Login Ramah Siswa SD</h3>
                <p>Cukup masuk pakai NIS/NISN, dan setiap transaksi tetap diawasi langsung oleh petugas perpustakaan.</p>
            </div>
        </div>
    </div>
</section>

<!-- KATEGORI -->
<section class="public-section public-section-alt">
    <div class="public-section-inner">
        <h2 class="section-title">Jelajahi Berdasarkan Kategori</h2>
        <p class="section-subtitle">Temukan bacaan favoritmu dari berbagai kategori koleksi perpustakaan</p>
        <div class="category-pills">
            <?php while ($k = mysqli_fetch_assoc($kategoriList)): ?>
                <a href="katalog.php?kategori=<?php echo urlencode($k['kategori']); ?>" class="category-pill">
                    <?php echo htmlspecialchars($k['kategori']); ?>
                    <span><?php echo (int) $k['jumlah']; ?></span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- BUKU PILIHAN -->
<section class="public-section">
    <div class="public-section-inner">
        <div class="section-header-row">
            <div>
                <h2 class="section-title">Koleksi Buku Pilihan</h2>
                <p class="section-subtitle">Beberapa koleksi yang tersedia di perpustakaan kami</p>
            </div>
            <a href="katalog.php" class="btn btn-secondary">Lihat Semua Buku &rarr;</a>
        </div>
        <div class="books-grid">
            <?php while ($b = mysqli_fetch_assoc($bukuPilihan)): ?>
            <div class="book-card">
                <div class="book-cover"<?php $src = sampulUrl($b['sampul']); if ($src): ?> style="background-image:url('<?php echo htmlspecialchars($src); ?>')"<?php endif; ?>>
                    <span class="book-category"><?php echo htmlspecialchars($b['kategori']); ?></span>
                    <span class="book-stock"><?php echo (int) $b['stok']; ?> stok</span>
                </div>
                <div class="book-detail">
                    <h3 class="book-title"><?php echo htmlspecialchars($b['judul']); ?></h3>
                    <p class="book-author">✍️ <?php echo htmlspecialchars($b['penulis']); ?></p>
                    <p class="book-publisher"><?php echo htmlspecialchars($b['penerbit']); ?> &middot; <?php echo (int) $b['tahun_terbit']; ?></p>
                    <p class="book-code">#<?php echo htmlspecialchars($b['kode_buku']); ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- CARA KERJA -->
<section class="public-section public-section-alt" id="tentang">
    <div class="public-section-inner">
        <h2 class="section-title">Cara Menggunakan E-Perpustakaan</h2>
        <p class="section-subtitle">Tiga langkah mudah untuk mulai meminjam buku secara online</p>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Daftar Akun Anggota</h3>
                <p>Siswa mendaftar secara mandiri menggunakan NIS/NISN untuk mendapatkan akun anggota.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Jelajahi Katalog</h3>
                <p>Cari dan pilih buku favorit lewat katalog online lengkap dengan kategori dan ketersediaan stok.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Pinjam &amp; Kembalikan</h3>
                <p>Ajukan peminjaman dan pengembalian langsung dari akun anggota, dicatat otomatis oleh petugas.</p>
            </div>
        </div>
    </div>
</section>

<!-- ROLE INFO -->
<section class="public-section">
    <div class="public-section-inner">
        <h2 class="section-title">Akses Sesuai Peran</h2>
        <p class="section-subtitle">Sistem ini melayani tiga jenis pengguna dengan hak akses berbeda</p>
        <div class="role-grid">
            <div class="role-card">
                <div class="role-icon">🎒</div>
                <h3>Anggota</h3>
                <p>Siswa dapat menjelajahi katalog, meminjam, dan mengembalikan buku, serta melihat riwayat peminjaman.</p>
            </div>
            <div class="role-card role-petugas">
                <div class="role-icon">🧑‍💼</div>
                <h3>Petugas</h3>
                <p>Staf perpustakaan mengelola data buku, anggota, dan mencatat transaksi peminjaman/pengembalian sehari-hari.</p>
            </div>
            <div class="role-card role-admin">
                <div class="role-icon">🛡️</div>
                <h3>Admin</h3>
                <p>Pengelola sistem memiliki akses penuh, termasuk mengelola akun petugas dan seluruh data perpustakaan.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONI -->
<section class="public-section public-section-alt">
    <div class="public-section-inner">
        <h2 class="section-title">Apa Kata Mereka</h2>
        <p class="section-subtitle">Cerita singkat dari siswa, petugas, dan wali murid yang sudah memakai sistem ini</p>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <span class="testimonial-quote-mark">&ldquo;</span>
                <p class="testimonial-text">Sekarang aku bisa cek dulu bukunya ada apa nggak, jadi nggak perlu bolak-balik ke rak buku.</p>
                <div class="testimonial-person">
                    <span class="testimonial-avatar">🎒</span>
                    <div>
                        <div class="testimonial-name">Naila</div>
                        <div class="testimonial-role">Siswa Kelas V</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <span class="testimonial-quote-mark">&ldquo;</span>
                <p class="testimonial-text">Rekap peminjaman jadi lebih rapi, saya tidak perlu tulis manual di buku besar lagi.</p>
                <div class="testimonial-person">
                    <span class="testimonial-avatar">🧑‍💼</span>
                    <div>
                        <div class="testimonial-name">Bu Sri</div>
                        <div class="testimonial-role">Petugas Perpustakaan</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <span class="testimonial-quote-mark">&ldquo;</span>
                <p class="testimonial-text">Anak saya jadi lebih semangat baca karena bisa lihat sendiri koleksi yang tersedia dari rumah.</p>
                <div class="testimonial-person">
                    <span class="testimonial-avatar">👨‍👧</span>
                    <div>
                        <div class="testimonial-name">Pak Adi</div>
                        <div class="testimonial-role">Wali Murid</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ULASAN & RATING PENGUNJUNG -->
<section class="public-section" id="ulasan">
    <div class="public-section-inner">
        <h2 class="section-title">Ulasan &amp; Rating Pengunjung</h2>
        <p class="section-subtitle">Bagikan pengalamanmu memakai E-Perpustakaan, atau baca ulasan dari pengunjung lain</p>

        <?php if ($ulasanSuccess): ?>
            <div class="alert alert-success"><?php echo $ulasanSuccess; ?></div>
        <?php endif; ?>

        <!-- Ringkasan rating: skor besar + sebaran per bintang, gaya umum situs ulasan -->
        <div class="rating-overview">
            <div class="rating-overview-score">
                <div class="rating-overview-badge">
                    <div class="rating-overview-number"><?php echo $totalUlasan > 0 ? number_format($rataRating, 1) : '-'; ?></div>
                </div>
                <div class="rating-overview-stars" aria-hidden="true">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= round($rataRating) ? 'star-filled' : 'star-empty'; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <div class="rating-overview-count">
                    <?php echo $totalUlasan > 0 ? $totalUlasan . ' ulasan' : 'Belum ada ulasan'; ?>
                </div>
            </div>
            <div class="rating-overview-divider" aria-hidden="true"></div>
            <div class="rating-overview-bars">
                <?php for ($bintang = 5; $bintang >= 1; $bintang--):
                    $jumlahBintang = $sebaranRating[$bintang];
                    $persenBintang = $totalUlasan > 0 ? round(($jumlahBintang / $totalUlasan) * 100) : 0;
                ?>
                <div class="rating-bar-row">
                    <span class="rating-bar-label"><?php echo $bintang; ?> ★</span>
                    <div class="rating-bar-track">
                        <div class="rating-bar-fill" style="width: <?php echo $persenBintang; ?>%;"></div>
                    </div>
                    <span class="rating-bar-count"><?php echo $jumlahBintang; ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Toolbar: tombol tulis ulasan + urutkan -->
        <div class="ulasan-toolbar">
            <button type="button" class="btn btn-primary" onclick="toggleUlasanForm()">✍️ Tulis Ulasan</button>
            <form method="GET" action="index.php#ulasan" class="ulasan-sort-form">
                <label for="sort">Urutkan:</label>
                <select id="sort" name="sort" onchange="this.form.submit()">
                    <option value="terbaru" <?php echo $sortUlasan === 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                    <option value="tertinggi" <?php echo $sortUlasan === 'tertinggi' ? 'selected' : ''; ?>>Rating Tertinggi</option>
                    <option value="terendah" <?php echo $sortUlasan === 'terendah' ? 'selected' : ''; ?>>Rating Terendah</option>
                </select>
            </form>
        </div>

        <!-- Form kirim ulasan (tersembunyi sampai tombol "Tulis Ulasan" diklik) -->
        <div class="ulasan-form-card" id="ulasan-form-card" style="display: <?php echo $ulasanError ? 'block' : 'none'; ?>;">
            <div class="ulasan-form-card-header">
                <h3>✍️ Tulis Ulasanmu</h3>
                <button type="button" class="btn-close" onclick="toggleUlasanForm()">&times;</button>
            </div>
            <?php if ($ulasanError): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($ulasanError); ?></div>
            <?php endif; ?>
            <form method="POST" action="index.php#ulasan">
                <?php echo csrfField(); ?>
                <input type="hidden" name="submit_ulasan" value="1">

                <div class="form-group">
                    <label>Rating Bintang</label>
                    <fieldset class="star-rating-input">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"
                                <?php echo ((int) $oldUlasanInput['rating'] === $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> bintang">★</label>
                        <?php endfor; ?>
                    </fieldset>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_pengulas">Nama</label>
                        <input type="text" id="nama_pengulas" name="nama_pengulas" placeholder="Nama kamu"
                            value="<?php echo htmlspecialchars($oldUlasanInput['nama_pengulas']); ?>" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="peran">Sebagai</label>
                        <select id="peran" name="peran">
                            <?php
                            $pilihanPeran = ['Siswa', 'Orang Tua Siswa', 'Petugas Perpustakaan', 'Pengunjung'];
                            foreach ($pilihanPeran as $p):
                            ?>
                                <option value="<?php echo htmlspecialchars($p); ?>" <?php echo ($oldUlasanInput['peran'] === $p) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="komentar">Ulasan</label>
                    <textarea id="komentar" name="komentar" rows="4" maxlength="500"
                        placeholder="Ceritakan pengalamanmu memakai E-Perpustakaan..."
                        required><?php echo htmlspecialchars($oldUlasanInput['komentar']); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleUlasanForm()">Batal</button>
                </div>
            </form>
        </div>

        <!-- Daftar ulasan -->
        <div class="ulasan-list">
            <?php if (mysqli_num_rows($ulasanList) === 0): ?>
                <div class="ulasan-empty">
                    <span>💬</span>
                    <p>Belum ada ulasan. Yuk, jadi yang pertama berbagi cerita di sini!</p>
                </div>
            <?php else: ?>
                <?php $ulasanIdx = 0; while ($u = mysqli_fetch_assoc($ulasanList)): $ulasanIdx++; ?>
                    <div class="ulasan-card accent-<?php echo (($ulasanIdx - 1) % 4) + 1; ?>">
                        <span class="ulasan-quote-mark" aria-hidden="true">&ldquo;</span>
                        <div class="ulasan-card-header">
                            <div class="ulasan-avatar"><?php echo strtoupper(substr($u['nama_pengulas'], 0, 1)); ?></div>
                            <div class="ulasan-card-headtext">
                                <div class="ulasan-name"><?php echo htmlspecialchars($u['nama_pengulas']); ?></div>
                                <div class="ulasan-meta">
                                    <span class="ulasan-stars" aria-label="<?php echo (int) $u['rating']; ?> dari 5 bintang">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= (int) $u['rating'] ? 'star-filled' : 'star-empty'; ?>">★</span>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="ulasan-role-pill"><?php echo htmlspecialchars($u['peran']); ?></span>
                                    <span class="ulasan-date"><?php echo htmlspecialchars(date('d M Y', strtotime($u['created_at']))); ?></span>
                                </div>
                            </div>
                        </div>
                        <p class="ulasan-text"><?php echo nl2br(htmlspecialchars($u['komentar'])); ?></p>
                    </div>
                <?php endwhile; ?>

                <?php if ($totalUlasan > $jumlahTampil): ?>
                    <div class="ulasan-load-more">
                        <a href="index.php?sort=<?php echo urlencode($sortUlasan); ?>&jumlah=<?php echo $jumlahTampil + 6; ?>#ulasan" class="btn btn-secondary">
                            Muat Lebih Banyak Ulasan
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>
function toggleUlasanForm() {
    var el = document.getElementById('ulasan-form-card');
    var show = el.style.display === 'none';
    el.style.display = show ? 'block' : 'none';
    if (show) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
}
</script>

<!-- CTA BANNER -->
<section class="cta-banner">
    <div class="cta-banner-inner">
        <h2>Yuk, Mulai Jelajahi Rak Buku Digital Kami</h2>
        <p>Daftar sebagai anggota dalam hitungan menit dan mulai pinjam buku favoritmu hari ini.</p>
        <div class="cta-banner-actions">
            <a href="register.php" class="btn btn-primary btn-lg">Daftar Jadi Anggota</a>
            <a href="katalog.php" class="btn btn-outline btn-lg">Lihat Katalog Buku</a>
        </div>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
