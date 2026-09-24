<?php
// Header publik (Beranda & Katalog) - tidak memerlukan login
$currentPublicPage = isset($currentPublicPage) ? $currentPublicPage : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'E-Perpustakaan SD N 1 Plebengan'; ?></title>
    <meta name="description" content="Perpustakaan online SD N 1 Plebengan - jelajahi katalog buku, pinjam, dan kelola koleksi perpustakaan secara digital.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260827">
</head>
<body class="public-body">
<header class="public-navbar">
    <div class="public-navbar-inner">
        <a href="index.php" class="public-logo"><span class="public-logo-mark">📚</span> E-Perpustakaan <span class="public-logo-sub">SD N 1 Plebengan</span></a>
        <nav class="public-nav-links">
            <a href="index.php" class="<?php echo $currentPublicPage === 'beranda' ? 'active' : ''; ?>">Beranda</a>
            <a href="katalog.php" class="<?php echo $currentPublicPage === 'katalog' ? 'active' : ''; ?>">Katalog Buku</a>
            <a href="index.php#tentang" >Tentang</a>
            <a href="index.php#ulasan" >Ulasan</a>
        </nav>
        <div class="public-nav-actions">
            <a href="login.php" class="btn btn-secondary btn-sm">Masuk</a>
            <a href="register.php" class="btn btn-primary btn-sm">Daftar Anggota</a>
        </div>
        <button class="public-nav-toggle" onclick="document.querySelector('.public-nav-links').classList.toggle('open')">☰</button>
    </div>
</header>
