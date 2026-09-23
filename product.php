<?php
include 'config.php';
require 'funcs/gambar.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = intval($_GET['id']);
$p = q_row("SELECT p.*, k.nama_kategori, k.icon FROM produk p JOIN kategori k ON p.id_kategori = k.id WHERE p.id = $id AND p.is_aktif = 1");

if (!$p) {
    header('Location: index.php');
    exit;
}

// Produk terkait
$kat_id = (int)$p['id_kategori'];
$produk_lain = q("SELECT * FROM produk WHERE id_kategori = $kat_id AND id != $id AND is_aktif = 1 LIMIT 4");

// Galeri produk (maks 3 gambar, klik untuk memperbesar)
$glist = get_gambar_list($p);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['nama_produk']) ?> - Percetakan Pojok Indah</title>
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
        <button class="menu-toggle" onclick="document.body.classList.toggle('nav-open')">☰</button>
    </div>
</header>

<main>
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Beranda</a> &raquo;
            <a href="index.php?kat=<?= $p['id_kategori'] ?>"><?= htmlspecialchars($p['nama_kategori']) ?></a> &raquo;
            <span><?= htmlspecialchars($p['nama_produk']) ?></span>
        </div>

        <div class="product-detail">
            <div>
                <div class="product-detail-img gal-main" onclick="lbOpen(GAL_IMGS, GAL_I, GAL_CAP)" title="Klik gambar untuk memperbesar">
                    <img id="galMain" src="<?= htmlspecialchars($glist[0]) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>" onerror="this.src='assets/img/default.svg'">
                    <?php if ($p['is_unggulan']): ?>
                        <span class="badge badge-featured">⭐ Unggulan</span>
                    <?php endif; ?>
                    <span class="gal-hint">🔍 Klik untuk memperbesar</span>
                </div>
                <?php if (count($glist) > 1): ?>
                    <div class="gal-thumbs">
                        <?php foreach ($glist as $gi => $gu): ?>
                            <button type="button" class="gal-thumb<?= $gi === 0 ? ' active' : '' ?>" onclick="galSwap(<?= $gi ?>)" title="Lihat gambar <?= $gi + 1 ?>">
                                <img src="<?= htmlspecialchars($gu) ?>" alt="" loading="lazy" onerror="this.src='assets/img/default.svg'">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-detail-info">
                <div class="card-cat"><?= htmlspecialchars($p['nama_kategori']) ?></div>
                <h1><?= htmlspecialchars($p['nama_produk']) ?></h1>
                <p class="detail-price"><?= rupiah($p['harga']) ?> <small>/ <?= htmlspecialchars($p['satuan']) ?></small></p>

                <div class="detail-meta">
                    <div><span>⏱ Waktu Pengerjaan</span><strong><?= htmlspecialchars($p['waktu_pengerjaan']) ?></strong></div>
                    <div><span>📦 Minimal Order</span><strong><?= $p['minimal_order'] ?> <?= htmlspecialchars($p['satuan']) ?></strong></div>
                    <div><span>📦 Stok Tersedia</span><strong><?= $p['stok'] ?></strong></div>
                </div>

                <div class="detail-desc">
                    <h3>Deskripsi Produk</h3>
                    <p><?= nl2br(htmlspecialchars((string)($p['deskripsi'] ?? ''))) ?></p>
                </div>

                <a href="order.php?produk=<?= $p['id'] ?>" class="btn btn-primary btn-lg">🛒 Order Sekarang</a>
            </div>
        </div>

        <?php if ($produk_lain && $produk_lain->num_rows > 0): ?>
        <div class="section related">
            <div class="section-head">
                <h2>Produk <span class="text-gradient-red">Lainnya</span></h2>
            </div>
            <div class="grid grid-4">
                <?php while ($rp = $produk_lain->fetch_assoc()): ?>
                    <div class="card">
                        <a href="product.php?id=<?= $rp['id'] ?>" class="card-image">
                            <img src="<?= get_gambar_src($rp) ?>" alt="<?= htmlspecialchars($rp['nama_produk']) ?>" onerror="this.src='assets/img/default.svg'">
                            <span class="card-quick"><span>👁 Lihat Detail</span></span>
                        </a>
                        <div class="card-body">
                            <h3 class="card-title"><a href="product.php?id=<?= $rp['id'] ?>"><?= htmlspecialchars($rp['nama_produk']) ?></a></h3>
                            <div class="card-foot">
                                <div class="card-price"><?= rupiah($rp['harga']) ?></div>
                                <a href="order.php?produk=<?= $rp['id'] ?>" class="btn-buy">🛒 Beli</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<footer class="site-footer" id="kontak">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="index.php" class="logo">
                <img src="assets/img/logo.svg" alt="Percetakan Pojok Indah" class="logo-img">
                <span class="logo-text">Percetakan <span>Pojok Indah</span></span>
            </a>
            <p style="margin-top:12px">📍 Jl. Percetakan No. 123, Jakarta</p>
            <p>📞 0812-3456-7890</p>
        </div>
        <div>
            <h4>Menu</h4>
            <a href="index.php">Beranda</a>
            <a href="index.php#katalog">Katalog Produk</a>
            <a href="orders.php">Lacak Pesanan</a>
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
                <li>📍 Jl. Percetakan No. 123, Jakarta</li>
                <li>📞 0812-3456-7890</li>
                <li>✉️ halo@pojokindah.id</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">&copy; <?= date('Y') ?> Percetakan Pojok Indah. All rights reserved.</div>
    </div>
</footer>

<!-- Lightbox pembesar gambar -->
<div class="lightbox" id="lightbox" aria-hidden="true">
    <button type="button" class="lightbox-close" onclick="lbClose()" title="Tutup">✕</button>
    <button type="button" class="lightbox-nav prev" onclick="lbNav(-1)" title="Sebelumnya">‹</button>
    <img id="lightboxImg" src="" alt="Pratinjau gambar">
    <button type="button" class="lightbox-nav next" onclick="lbNav(1)" title="Berikutnya">›</button>
    <div class="lightbox-bar"><span id="lightboxCap"></span><span id="lightboxCount"></span></div>
</div>

<script>
var GAL_IMGS = <?= json_encode($glist) ?>;
var GAL_I = 0;
var GAL_CAP = <?= json_encode($p['nama_produk'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
function galSwap(i) {
    GAL_I = i;
    document.getElementById('galMain').src = GAL_IMGS[i];
    var ts = document.querySelectorAll('.gal-thumb');
    for (var j = 0; j < ts.length; j++) {
        ts[j].classList.toggle('active', j === i);
    }
}
var LB_IMGS = [], LB_I = 0;
function lbOpen(imgs, idx, cap) {
    if (!imgs || !imgs.length) return;
    LB_IMGS = imgs;
    LB_I = Math.min(Math.max(0, idx || 0), imgs.length - 1);
    document.getElementById('lightboxCap').textContent = cap || '';
    lbShow();
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function lbShow() {
    var m = document.getElementById('lightboxImg');
    m.onerror = function () { this.onerror = null; this.src = 'assets/img/default.svg'; };
    m.src = LB_IMGS[LB_I];
    document.getElementById('lightboxCount').textContent = (LB_I + 1) + ' / ' + LB_IMGS.length;
}
function lbNav(d) {
    if (!LB_IMGS.length) return;
    LB_I = (LB_I + d + LB_IMGS.length) % LB_IMGS.length;
    lbShow();
}
function lbClose() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function (e) {
    var lb = document.getElementById('lightbox');
    if (!lb || !lb.classList.contains('open')) return;
    if (e.key === 'Escape') lbClose();
    else if (e.key === 'ArrowLeft') lbNav(-1);
    else if (e.key === 'ArrowRight') lbNav(1);
});
document.getElementById('lightbox').addEventListener('click', function (e) {
    if (e.target === this) lbClose();
});
</script>

</body>
</html>