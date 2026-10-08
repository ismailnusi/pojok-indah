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
<script src="assets/js/tema.js?v=<?= filemtime('assets/js/tema.js') ?>"></script>
<script>ppiTemaAwal();document.addEventListener('DOMContentLoaded',ppiTemaCat);</script>

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
            <a href="track.php">Lacak</a>
            <button type="button" class="theme-toggle theme-icon-only" onclick="toggleTema()" title="Ganti mode gelap / terang"><?= $APP_TEMA === 'terang' ? '🌙' : '☀️' ?></button>
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
                <a href="track.php?kode=<?= urlencode($pesanan['kode_pesanan']) ?>" class="btn btn-outline">Lacak Pesanan</a>
                <a href="index.php" class="btn btn-primary">Kembali ke Katalog</a>
            </div>
        </div>
    </div>
</main>

<footer class="site-footer" id="kontak">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="index.php" class="logo">
                <img src="assets/img/logo.svg" alt="Percetakan Pojok Indah" class="logo-img">
                <span class="logo-text">Percetakan <span>Pojok Indah</span></span>
            </a>
            <p style="margin-top:12px">Percetakan online lengkap untuk kebutuhan usaha &amp; bisnis kamu. Kualitas terbaik, harga terjangkau.</p>
            <div class="social-row">
                <a href="#" title="Instagram">📸</a>
                <a href="#" title="WhatsApp">💬</a>
                <a href="#" title="YouTube">▶️</a>
            </div>
        </div>
        <div>
            <h4>Menu</h4>
            <a href="index.php">Beranda</a>
            <a href="index.php#katalog">Katalog Produk</a>
            <a href="track.php">Lacak Pesanan</a>
            <a href="admin/">Login Admin</a>
        </div>
        <div>
            <h4>Kategori Populer</h4>
            <a href="index.php?kat=1">Banner &amp; Spanduk</a>
            <a href="index.php?kat=2">Kartu Nama</a>
            <a href="index.php?kat=6">Mug Custom</a>
            <a href="index.php?kat=5">Kaos &amp; Sablon</a>
        </div>
        <div>
            <h4>Kontak</h4>
            <ul class="footer-contact" style="list-style:none">
                <li>📍 Jl. Prof. Dr. H. Mansoer Pateda No.Desa, Pentadio Timur</li>
                <li>📞 0822-9075-2637</li>
                <li>✉️ ismailnusi02@gmail.com</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">&copy; <?= date('Y') ?> Percetakan Pojok Indah. All rights reserved.</div>
    </div>
</footer>

<?php $CHAT_KODE = $pesanan['kode_pesanan']; include __DIR__ . '/partials/chat_widget.php'; ?>

</body>
</html>