<?php
include 'config.php';

if (!isset($_GET['kode']) || trim((string)$_GET['kode']) === '') {
    header('Location: index.php');
    exit;
}
$kode_raw = trim((string)$_GET['kode']);
$kode = bersihkan($kode_raw);
$pesanan = q_row("SELECT kode_pesanan, no_hp FROM pesanan WHERE kode_pesanan = '$kode'");
if (!$pesanan) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - <?= htmlspecialchars($pesanan['kode_pesanan']) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body class="<?= $APP_TEMA === 'terang' ? 'terang' : '' ?>">

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <img src="assets/img/logo.svg" alt="Percetakan Pojok Indah" class="logo-img">
            <span class="logo-text">Percetakan <span>Pojok Indah</span></span>
        </a>
        <form method="get" action="index.php" class="header-search">
            <span class="search-icon">🔍</span>
            <input type="text" name="c" placeholder="Cari banner, kartu nama, kaos...">
        </form>
        <nav class="main-nav">
            <a href="index.php">Beranda</a>
            <a href="index.php#katalog">Katalog</a>
            <a href="index.php#promo">Promo<span class="nav-badge-hot">HOT</span></a>
            <a href="orders.php">Pesanan Saya</a>
            <a href="admin/" class="btn btn-outline btn-sm">Admin</a>
        </nav>
    </div>
</header>

<main>
    <div class="container">
        <div class="success-box">
            <div class="success-icon">🎉</div>
            <h2>Pesanan Berhasil Dibuat!</h2>
            <p>Terima kasih! Pesanan kamu sudah kami terima.</p>

            <div class="success-kode">
                <span>Kode Pesanan Kamu</span>
                <strong><?= htmlspecialchars($pesanan['kode_pesanan']) ?></strong>
            </div>

            <p class="success-note">
                Simpan kode pesanan di atas. Tim kami akan segera menghubungi kamu via
                WhatsApp <strong><?= htmlspecialchars($pesanan['no_hp']) ?></strong> untuk konfirmasi
                detail desain dan pembayaran.
            </p>

            <div class="success-actions">
                <a href="orders.php?kode=<?= urlencode($pesanan['kode_pesanan']) ?>" class="btn btn-outline">Lacak Pesanan</a>
                <a href="index.php" class="btn btn-primary">Kembali ke Katalog</a>
            </div>
        </div>
    </div>
</main>

</body>
</html>