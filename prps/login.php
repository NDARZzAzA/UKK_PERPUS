<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$error = '';
$activeTab = $_POST['login_role'] ?? ($_GET['as'] ?? 'user');
if (!in_array($activeTab, ['admin', 'petugas', 'user'], true)) {
    $activeTab = 'user';
}

$roleLabels = [
    'user'    => 'Anggota',
    'petugas' => 'Petugas',
    'admin'   => 'Admin',
];

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $passwordInput = $_POST['password'] ?? '';

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    $user = ($result && mysqli_num_rows($result) === 1) ? mysqli_fetch_assoc($result) : null;
    $loginBerhasil = false;

    if ($user) {
        $hash = $user['password'];
        // Hash bcrypt modern (dibuat via password_hash)
        if (password_verify($passwordInput, $hash)) {
            $loginBerhasil = true;
        }
        // Kompatibilitas mundur: data lama yang masih memakai MD5 (32 karakter hex)
        elseif (preg_match('/^[a-f0-9]{32}$/', $hash) && hash_equals($hash, md5($passwordInput))) {
            $loginBerhasil = true;
            // Tingkatkan otomatis ke hash yang aman
            $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
            $idEsc = (int) $user['id_user'];
            mysqli_query($conn, "UPDATE users SET password = '" . mysqli_real_escape_string($conn, $newHash) . "' WHERE id_user = $idEsc");
        }
    }

    if ($loginBerhasil && $user['role'] !== $activeTab) {
        // Password benar, tapi login lewat tab yang salah (mis. anggota coba tab Admin)
        $loginBerhasil = false;
        $error = 'Akun ini terdaftar sebagai "' . $roleLabels[$user['role']] . '". Silakan pilih tab "' . $roleLabels[$user['role']] . '" untuk masuk.';
    }

    if ($loginBerhasil) {
        // Regenerasi session ID untuk mencegah session fixation
        session_regenerate_id(true);

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin' || $user['role'] === 'petugas') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit();
    } elseif (!$error) {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan Online SD N 1 Plebengan</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260827">
</head>
<body class="auth-page auth-page--split">
    <div class="auth-split">
        <!-- Panel kiri: branding ala landing page -->
        <div class="auth-brand-panel">
            <svg class="auth-bg-decor" viewBox="0 0 1600 900" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <!-- jaringan titik digital, kesan "online" -->
                <g stroke="#ffffff" stroke-width="1.4" opacity="0.14">
                    <line x1="120" y1="140" x2="300" y2="230" />
                    <line x1="300" y1="230" x2="220" y2="380" />
                    <line x1="300" y1="230" x2="480" y2="180" />
                    <line x1="1180" y1="620" x2="1340" y2="560" />
                    <line x1="1340" y1="560" x2="1420" y2="680" />
                    <line x1="1180" y1="620" x2="1080" y2="740" />
                </g>
                <g fill="#ffffff" opacity="0.22">
                    <circle cx="120" cy="140" r="5" />
                    <circle cx="300" cy="230" r="6" />
                    <circle cx="220" cy="380" r="4" />
                    <circle cx="480" cy="180" r="4.5" />
                    <circle cx="1180" cy="620" r="5" />
                    <circle cx="1340" cy="560" r="6" />
                    <circle cx="1420" cy="680" r="4" />
                    <circle cx="1080" cy="740" r="4.5" />
                </g>

                <!-- laptop menampilkan rak buku digital, pojok kiri atas -->
                <g transform="translate(70,70)" opacity="0.16">
                    <rect x="0" y="0" width="230" height="150" rx="12" fill="none" stroke="#ffffff" stroke-width="4" />
                    <rect x="16" y="16" width="198" height="118" rx="4" fill="#ffffff" opacity="0.5" />
                    <rect x="30" y="80" width="12" height="46" fill="#ffffff" />
                    <rect x="48" y="60" width="12" height="66" fill="#ffffff" />
                    <rect x="66" y="90" width="12" height="36" fill="#ffffff" />
                    <rect x="84" y="70" width="12" height="56" fill="#ffffff" />
                    <rect x="102" y="55" width="12" height="71" fill="#ffffff" />
                    <rect x="120" y="85" width="12" height="41" fill="#ffffff" />
                    <rect x="138" y="65" width="12" height="61" fill="#ffffff" />
                    <rect x="156" y="95" width="12" height="31" fill="#ffffff" />
                    <path d="M-22 150 h274 l-20 26 h-234 z" fill="none" stroke="#ffffff" stroke-width="4" />
                </g>

                <!-- awan + sinyal wifi, pojok kanan atas -->
                <g transform="translate(1280,90)" opacity="0.18">
                    <ellipse cx="0" cy="24" rx="34" ry="24" fill="#ffffff" />
                    <ellipse cx="30" cy="10" rx="26" ry="22" fill="#ffffff" />
                    <ellipse cx="-28" cy="14" rx="22" ry="18" fill="#ffffff" />
                    <rect x="-40" y="20" width="98" height="26" rx="13" fill="#ffffff" />
                    <path d="M-14 -14 Q16 -42 46 -14" stroke="#ffffff" stroke-width="5" fill="none" stroke-linecap="round" />
                    <path d="M-4 -6 Q16 -22 36 -6" stroke="#ffffff" stroke-width="5" fill="none" stroke-linecap="round" />
                    <circle cx="16" cy="0" r="3.5" fill="#ffffff" />
                </g>

                <!-- buku terbuka, pojok kiri bawah -->
                <g transform="translate(130,700)" opacity="0.16">
                    <path d="M0 0 C40 -18 80 -18 120 0 L120 90 C80 72 40 72 0 90 Z" fill="none" stroke="#ffffff" stroke-width="4" />
                    <path d="M120 0 C160 -18 200 -18 240 0 L240 90 C200 72 160 72 120 90 Z" fill="none" stroke="#ffffff" stroke-width="4" />
                    <line x1="60" y1="14" x2="60" y2="80" stroke="#ffffff" stroke-width="2.5" />
                    <line x1="180" y1="14" x2="180" y2="80" stroke="#ffffff" stroke-width="2.5" />
                </g>

                <!-- tumpukan buku melayang, pojok kanan bawah -->
                <g transform="translate(1360,660) rotate(-8)" opacity="0.17">
                    <rect x="0" y="40" width="130" height="26" rx="5" fill="#ffffff" />
                    <rect x="10" y="14" width="110" height="24" rx="5" fill="#ffffff" opacity="0.85" />
                    <rect x="20" y="-10" width="90" height="22" rx="5" fill="#ffffff" opacity="0.7" />
                </g>

                <!-- lingkaran lembut untuk kedalaman -->
                <circle cx="1500" cy="820" r="220" fill="#ffffff" opacity="0.05" />
                <circle cx="30" cy="850" r="160" fill="#ffffff" opacity="0.05" />
            </svg>

            <div class="auth-brand-content">
                <span class="hero-badge">🎉 Perpustakaan Digital Sekolah</span>
                <h1>📚 E-Perpustakaan<br>SD N 1 Plebengan</h1>
                <p class="auth-brand-tagline">Jelajahi ribuan koleksi buku, pinjam dan kembalikan secara online, kapan saja &amp; di mana saja.</p>
                <ul class="auth-brand-features">
                    <li>📖 Katalog buku lengkap &amp; mudah dicari</li>
                    <li>🔄 Peminjaman &amp; pengembalian online</li>
                    <li>📊 Pantau riwayat &amp; status peminjamanmu</li>
                </ul>
                <a href="index.php" class="auth-brand-link">&larr; Kembali ke Beranda</a>
            </div>
        </div>

        <!-- Panel kanan: form login -->
        <div class="auth-form-panel">
            <div class="auth-container">
                <div class="auth-card">
                    <a href="index.php" class="back-home-link back-home-link--mobile-only">&larr; Kembali ke Beranda</a>
            <div class="auth-header">
                <h1>📚 E-Perpustakaan</h1>
                <p>SD N 1 Plebengan &mdash; Sistem Perpustakaan Online</p>
            </div>

            <div class="login-tabs">
                <button type="button" class="login-tab <?php echo $activeTab === 'user' ? 'active' : ''; ?>" data-role="user">
                    🎒 Anggota
                </button>
                <button type="button" class="login-tab <?php echo $activeTab === 'petugas' ? 'active' : ''; ?>" data-role="petugas">
                    🧑‍💼 Petugas
                </button>
                <button type="button" class="login-tab <?php echo $activeTab === 'admin' ? 'active' : ''; ?>" data-role="admin">
                    🛡️ Admin
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="login_role" id="login_role" value="<?php echo htmlspecialchars($activeTab); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                        <button type="button" class="toggle-pass" onclick="togglePassword(this)">👁️</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full" id="submitBtn">Masuk sebagai Anggota</button>
                <p class="auth-link" id="registerHint">Belum punya akun? <a href="register.php">Daftar sebagai anggota</a></p>
            </form>

            <div class="auth-info" id="demoInfo">
                <p><strong>🔎 Demo Login (Anggota):</strong></p>
                <p>Username: <code>ahmad</code> &nbsp;|&nbsp; Password: <code>user123</code></p>
            </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function togglePassword(btn) {
        const input = btn.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
        } else {
            input.type = 'password';
            btn.textContent = '👁️';
        }
    }

    const roleData = {
        user:    { label: 'Anggota', demoUser: 'ahmad',   demoPass: 'user123',    hint: 'Belum punya akun? <a href="register.php">Daftar sebagai anggota</a>' },
        petugas: { label: 'Petugas', demoUser: 'petugas', demoPass: 'petugas123', hint: 'Akun petugas dibuat oleh Admin melalui menu Kelola Petugas.' },
        admin:   { label: 'Admin',   demoUser: 'admin',   demoPass: 'admin123',   hint: 'Akun admin dikelola langsung oleh pengelola sistem.' }
    };

    const tabs = document.querySelectorAll('.login-tab');
    const roleInput = document.getElementById('login_role');
    const submitBtn = document.getElementById('submitBtn');
    const demoInfo = document.getElementById('demoInfo');
    const registerHint = document.getElementById('registerHint');

    function setActiveTab(role) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.role === role));
        roleInput.value = role;
        const d = roleData[role];
        submitBtn.textContent = 'Masuk sebagai ' + d.label;
        demoInfo.innerHTML = '<p><strong>🔎 Demo Login (' + d.label + '):</strong></p><p>Username: <code>' + d.demoUser + '</code> &nbsp;|&nbsp; Password: <code>' + d.demoPass + '</code></p>';
        registerHint.innerHTML = d.hint;
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => setActiveTab(tab.dataset.role));
    });

    setActiveTab(roleInput.value);
    </script>
</body>
</html>
