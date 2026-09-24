<?php
// =============================================
// FUNGSI NOTIFIKASI
// Notifikasi dalam-aplikasi (bell icon) untuk anggota & petugas/admin.
// Membutuhkan $conn (config/database.php) dan session (includes/auth.php)
// sudah dimuat lebih dulu oleh halaman yang memanggil file ini.
// =============================================

// Fungsi: Buat satu notifikasi untuk seorang user
// $link diisi path RELATIF TERHADAP ROOT aplikasi, mis. 'user/my_transactions.php'
// atau 'admin/verifikasi.php', supaya bisa dipakai balik oleh notif_baca.php di root.
function notifBuat($conn, $id_user, $judul, $pesan, $tipe = 'info', $link = null) {
    $id_user = (int) $id_user;
    $judul   = mysqli_real_escape_string($conn, $judul);
    $pesan   = mysqli_real_escape_string($conn, $pesan);
    $tipe    = in_array($tipe, ['info', 'sukses', 'ditolak'], true) ? $tipe : 'info';
    $linkSql = $link !== null ? "'" . mysqli_real_escape_string($conn, $link) . "'" : 'NULL';
    mysqli_query($conn, "INSERT INTO notifikasi (id_user, judul, pesan, tipe, link) 
        VALUES ($id_user, '$judul', '$pesan', '$tipe', $linkSql)");
}

// Fungsi: Kirim notifikasi yang sama ke SEMUA petugas & admin (dipakai saat ada permintaan baru)
function notifBuatUntukStaf($conn, $judul, $pesan, $tipe = 'info', $link = null) {
    $staf = mysqli_query($conn, "SELECT id_user FROM users WHERE role IN ('admin', 'petugas')");
    if (!$staf) return;
    while ($row = mysqli_fetch_assoc($staf)) {
        notifBuat($conn, $row['id_user'], $judul, $pesan, $tipe, $link);
    }
}

// Fungsi: Hitung jumlah notifikasi belum dibaca milik seorang user
function notifHitungBelumDibaca($conn, $id_user) {
    $id_user = (int) $id_user;
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM notifikasi WHERE id_user = $id_user AND is_read = 0"));
    return $r ? (int) $r['c'] : 0;
}

// Fungsi: Ambil daftar notifikasi terbaru milik seorang user
function notifDaftar($conn, $id_user, $limit = null) {
    $id_user = (int) $id_user;
    $sql = "SELECT * FROM notifikasi WHERE id_user = $id_user ORDER BY created_at DESC";
    if ($limit !== null) $sql .= " LIMIT " . (int) $limit;
    return mysqli_query($conn, $sql);
}

// Fungsi: Tandai satu notifikasi sudah dibaca (memastikan milik user yang benar)
function notifTandaiDibaca($conn, $id_notifikasi, $id_user) {
    $id_notifikasi = (int) $id_notifikasi;
    $id_user = (int) $id_user;
    mysqli_query($conn, "UPDATE notifikasi SET is_read = 1 WHERE id_notifikasi = $id_notifikasi AND id_user = $id_user");
}

// Fungsi: Tandai SEMUA notifikasi milik user sudah dibaca
function notifTandaiSemuaDibaca($conn, $id_user) {
    $id_user = (int) $id_user;
    mysqli_query($conn, "UPDATE notifikasi SET is_read = 1 WHERE id_user = $id_user AND is_read = 0");
}

// Fungsi: Format waktu relatif sederhana (Bahasa Indonesia) untuk daftar notifikasi
function notifWaktuRelatif($datetime) {
    $selisih = time() - strtotime($datetime);
    if ($selisih < 60) return 'Baru saja';
    if ($selisih < 3600) return floor($selisih / 60) . ' menit lalu';
    if ($selisih < 86400) return floor($selisih / 3600) . ' jam lalu';
    if ($selisih < 604800) return floor($selisih / 86400) . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}
?>
