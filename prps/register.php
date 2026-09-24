<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap'] ?? ''));
    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $passwordInput = $_POST['password'] ?? '';
    $konfirmasiInput = $_POST['konfirmasi_password'] ?? '';
    $nis = mysqli_real_escape_string($conn, trim($_POST['nis'] ?? ''));
    $kelas = mysqli_real_escape_string($conn, trim($_POST['kelas'] ?? ''));
    $no_telepon = mysqli_real_escape_string($conn, trim($_POST['no_telepon'] ?? ''));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));

    // Validasi
    if (empty($nama_lengkap) || empty($username) || empty($passwordInput) || empty($nis)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($passwordInput !== $konfirmasiInput) {
        $error = 'Password dan konfirmasi tidak cocok!';
    } elseif (strlen($passwordInput) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek username sudah ada
        $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek) > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Cek NIS sudah ada
            $cekNis = mysqli_query($conn, "SELECT nis FROM anggota WHERE nis = '$nis'");
            if (mysqli_num_rows($cekNis) > 0) {
                $error = 'NIS sudah terdaftar!';
            } else {
                $password = password_hash($passwordInput, PASSWORD_DEFAULT);

                // Gunakan transaksi DB agar insert users + anggota konsisten (all-or-nothing)
                mysqli_begin_transaction($conn);
                try {
                    $okUser = mysqli_query($conn, "INSERT INTO users (nama_lengkap, username, password, role) VALUES ('$nama_lengkap', '$username', '$password', 'user')");
                    if (!$okUser) {
                        throw new Exception(mysqli_error($conn));
                    }
                    $id_user = mysqli_insert_id($conn);

                    $okAnggota = mysqli_query($conn, "INSERT INTO anggota (nis, nama_anggota, kelas, alamat, no_telepon, id_user) VALUES ('$nis', '$nama_lengkap', '$kelas', '$alamat', '$no_telepon', $id_user)");
                    if (!$okAnggota) {
                        throw new Exception(mysqli_error($conn));
                    }

                    mysqli_commit($conn);

                    $_SESSION['success'] = 'Registrasi berhasil! Silakan login.';
                    header('Location: login.php');
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = 'Registrasi gagal, silakan coba lagi.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Perpustakaan SD N 1 Plebengan</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260827">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card register-card">
            <a href="index.php" class="back-home-link">&larr; Kembali ke Beranda</a>
            <div class="auth-header">
                <h1>📚 Registrasi Anggota</h1>
                <p>E-Perpustakaan SD N 1 Plebengan</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Nama lengkap siswa" required>
                    </div>
                    <div class="form-group">
                        <label for="nis">NIS / NISN</label>
                        <input type="text" id="nis" name="nis" placeholder="Nomor Induk Siswa" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Username untuk login" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label for="konfirmasi_password">Konfirmasi Password</label>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="kelas">Kelas</label>
                        <select id="kelas" name="kelas" required>
                            <option value="">-- Pilih Kelas --</option>
                            <option>Kelas 1</option><option>Kelas 2</option>
                            <option>Kelas 3</option><option>Kelas 4</option>
                            <option>Kelas 5</option><option>Kelas 6</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="no_telepon">No. Telepon Ortu</label>
                        <input type="text" id="no_telepon" name="no_telepon" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="alamat" placeholder="Alamat rumah">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Daftar</button>
                <p class="auth-link">Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </form>
        </div>
    </div>
</body>
</html>