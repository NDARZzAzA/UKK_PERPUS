<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$pageTitle = 'Katalog Buku - E-Perpustakaan SD N 1 Plebengan';
$currentPublicPage = 'katalog';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, trim($_GET['kategori'])) : '';

$where = [];
if ($search !== '') {
    $where[] = "(judul LIKE '%$search%' OR penulis LIKE '%$search%' OR kode_buku LIKE '%$search%')";
}
if ($kategoriFilter !== '') {
    $where[] = "kategori = '$kategoriFilter'";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$bukuList = mysqli_query($conn, "SELECT * FROM buku $whereSql ORDER BY judul ASC");
$totalHasil = mysqli_num_rows($bukuList);

$kategoriList = mysqli_query($conn, "SELECT kategori, COUNT(*) as jumlah FROM buku GROUP BY kategori ORDER BY kategori ASC");
?>
<?php include 'includes/public_header.php'; ?>

<section class="catalog-hero">
    <div class="catalog-hero-inner">
        <h1>📖 Katalog Buku Perpustakaan</h1>
        <p>Jelajahi seluruh koleksi buku SD N 1 Plebengan. Masuk sebagai anggota untuk mulai meminjam.</p>
        <form method="GET" action="katalog.php" class="catalog-search-form">
            <input type="text" name="search" placeholder="Cari judul, penulis, atau kode buku..." value="<?php echo htmlspecialchars($search); ?>">
            <?php if ($kategoriFilter): ?><input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategoriFilter); ?>"><?php endif; ?>
            <button type="submit" class="btn btn-primary">🔍 Cari</button>
        </form>
    </div>
</section>

<section class="public-section">
    <div class="public-section-inner catalog-layout">
        <aside class="catalog-sidebar">
            <h3>Kategori</h3>
            <a href="katalog.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" class="category-filter-item <?php echo $kategoriFilter === '' ? 'active' : ''; ?>">
                Semua Kategori
            </a>
            <?php while ($k = mysqli_fetch_assoc($kategoriList)): ?>
                <a href="katalog.php?kategori=<?php echo urlencode($k['kategori']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                   class="category-filter-item <?php echo $kategoriFilter === $k['kategori'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($k['kategori']); ?>
                    <span><?php echo (int) $k['jumlah']; ?></span>
                </a>
            <?php endwhile; ?>
        </aside>

        <div class="catalog-main">
            <div class="catalog-result-info">
                Menampilkan <strong><?php echo $totalHasil; ?></strong> buku
                <?php if ($kategoriFilter): ?> dalam kategori <strong><?php echo htmlspecialchars($kategoriFilter); ?></strong><?php endif; ?>
                <?php if ($search): ?> untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"<?php endif; ?>
                <?php if ($search || $kategoriFilter): ?> &mdash; <a href="katalog.php">Reset filter</a><?php endif; ?>
            </div>

            <div class="books-grid">
                <?php while ($b = mysqli_fetch_assoc($bukuList)): ?>
                <div class="book-card">
                    <div class="book-cover"<?php $src = sampulUrl($b['sampul']); if ($src): ?> style="background-image:url('<?php echo htmlspecialchars($src); ?>')"<?php endif; ?>>
                        <span class="book-category"><?php echo htmlspecialchars($b['kategori']); ?></span>
                        <span class="book-stock"><?php echo $b['stok'] > 0 ? (int) $b['stok'] . ' stok' : 'Kosong'; ?></span>
                    </div>
                    <div class="book-detail">
                        <h3 class="book-title"><?php echo htmlspecialchars($b['judul']); ?></h3>
                        <p class="book-author">✍️ <?php echo htmlspecialchars($b['penulis']); ?></p>
                        <p class="book-publisher"><?php echo htmlspecialchars($b['penerbit']); ?> &middot; <?php echo (int) $b['tahun_terbit']; ?></p>
                        <p class="book-code">#<?php echo htmlspecialchars($b['kode_buku']); ?></p>
                    </div>
                    <?php if ($b['stok'] > 0): ?>
                        <a href="login.php?as=user" class="btn btn-primary btn-full">Masuk untuk Meminjam</a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-full" disabled>Stok Habis</button>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                <?php if ($totalHasil === 0): ?>
                    <p class="text-center">Tidak ada buku yang cocok dengan pencarian kamu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
