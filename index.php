<?php
include 'config.php';
require 'funcs/gambar.php';

// Filter kategori
$where = "AND p.is_aktif = 1";
$kat_dipilih = 0;
if (isset($_GET['kat']) && is_numeric($_GET['kat'])) {
    $kat_dipilih = intval($_GET['kat']);
    $where .= " AND p.id_kategori = $kat_dipilih";
}
$kat_dipilih = max(0, $kat_dipilih);

// Pencarian
$cari = '';
if (isset($_GET['c']) && $_GET['c'] != '') {
    $cari = bersihkan($_GET['c']);
    $where .= " AND (p.nama_produk LIKE '%$cari%' OR p.deskripsi LIKE '%$cari%')";
}

// Pagination
$per_halaman = 12;
$row_total = q_row("SELECT COUNT(*) as jml FROM produk p WHERE 1 $where");
$total = $row_total ? (int)$row_total['jml'] : 0;
$total_halaman = max(1, (int)ceil($total / $per_halaman));
$hal = isset($_GET['hal']) ? max(1, intval($_GET['hal'])) : 1;
$hal = min($hal, $total_halaman);
$offset = ($hal - 1) * $per_halaman;

$produk = q("SELECT p.*, k.nama_kategori, k.icon, k.id as kid
    FROM produk p JOIN kategori k ON p.id_kategori = k.id
    WHERE 1 $where
    ORDER BY p.is_unggulan DESC, p.id ASC
    LIMIT $offset, $per_halaman");

// Kumpulkan baris + preload galeri (1 query untuk semua produk halaman ini)
$rows = array();
if ($produk) {
    while ($r = $produk->fetch_assoc()) {
        $rows[] = $r;
    }
}
$gal_map = array();
$gal_ids = array();
foreach ($rows as $r) {
    $gal_ids[] = (int)$r['id'];
}
if ($gal_ids) {
    $gr = q("SELECT id_produk, nama_file FROM produk_gambar WHERE id_produk IN (" . implode(',', $gal_ids) . ") ORDER BY urutan ASC, id ASC");
    if ($gr) {
        while ($g = $gr->fetch_assoc()) {
            $f = basename((string)$g['nama_file']);
            if ($f !== '' && is_file(__DIR__ . '/assets/img/produk/' . $f)) {
                $gal_map[(int)$g['id_produk']][] = 'assets/img/produk/' . $f;
            }
        }
    }
}

$produk_unggulan = q("SELECT * FROM produk WHERE is_unggulan = 1 AND is_aktif = 1 ORDER BY id DESC LIMIT 4");

// Sorotan hero: 1 produk unggulan + nama kategorinya
$highlight = q_row("SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id WHERE p.is_unggulan = 1 AND p.is_aktif = 1 ORDER BY p.id DESC LIMIT 1");

// Statistik hero
$sr = q_row("SELECT COUNT(*) jml FROM produk WHERE is_aktif = 1");
$stat_produk = $sr ? (int)$sr['jml'] : 0;
$sr = q_row("SELECT COUNT(*) jml FROM kategori");
$stat_kat = $sr ? (int)$sr['jml'] : 0;
$sr = q_row("SELECT COUNT(*) jml FROM pesanan WHERE status != 'dibatalkan'");
$stat_order = $sr ? (int)$sr['jml'] : 0;

$nama_kategori = '';
if ($kat_dipilih > 0) {
    $r = q_row("SELECT nama_kategori FROM kategori WHERE id = $kat_dipilih");
    $nama_kategori = $r ? $r['nama_kategori'] : '';
}

$kat_populer = q("SELECT * FROM kategori ORDER BY id ASC LIMIT 4");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Percetakan Pojok Indah - Katalog Produk</title>
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
            <input type="text" name="c" placeholder="Cari banner, kartu nama, kaos..." value="<?= htmlspecialchars($cari) ?>">
        </form>
        <nav class="main-nav">
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' && !$kat_dipilih && $cari == '' ? 'active' : '' ?>">Beranda</a>
            <a href="index.php#katalog">Katalog</a>
            <a href="index.php#promo">Promo<span class="nav-badge-hot">HOT</span></a>
            <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">Pesanan Saya</a>
            <a href="admin/" class="btn btn-outline btn-sm">Admin</a>
        </nav>
        <button class="menu-toggle" onclick="document.body.classList.toggle('nav-open')">☰</button>
    </div>
</header>

<main>

    <!-- Hero -->
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <div class="hero-badge"><span class="dot"></span> Percetakan Online Terpercaya</div>
                <h1>Cetak Kebutuhan<br><span class="text-gradient-red">Usaha &amp; Bisnismu</span></h1>
                <p>Banner, spanduk, kartu nama, brosur, stiker, kaos, mug, dan masih banyak lagi. Kualitas terbaik, harga terjangkau, pengerjaan cepat.</p>
                <div class="hero-actions">
                    <a href="#katalog" class="btn btn-primary">Lihat Katalog ➜</a>
                    <a href="orders.php" class="btn btn-cyan">Lacak Pesanan</a>
                </div>
                <div class="hero-stats">
                    <div><strong class="red"><?= $stat_produk ?>+</strong><span>Produk Siap</span></div>
                    <div><strong class="cyan"><?= $stat_kat ?></strong><span>Kategori Cetak</span></div>
                    <div><strong><?= $stat_order ?>+</strong><span>Pesanan Masuk</span></div>
                </div>
            </div>
            <?php if ($highlight): ?>
            <div class="hero-highlight">
                <div class="highlight-card">
                    <span class="highlight-tag">Highlight</span>
                    <img src="<?= get_gambar_src($highlight) ?>" alt="<?= htmlspecialchars($highlight['nama_produk']) ?>" onerror="this.src='assets/img/default.svg'">
                    <div class="highlight-cat"><?= htmlspecialchars($highlight['nama_kategori']) ?></div>
                    <h3><?= htmlspecialchars($highlight['nama_produk']) ?></h3>
                    <div class="highlight-foot">
                        <div class="highlight-price"><?= rupiah($highlight['harga']) ?></div>
                        <a href="order.php?produk=<?= $highlight['id'] ?>" class="btn-buy">🛒 Order</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Katalog Produk -->
    <section class="section" id="katalog">
        <div class="container">
            <div class="catalog-head">
                <div>
                    <h2>Katalog <span class="text-gradient-red">Unggulan</span></h2>
                    <p><?= $total ?> produk tersedia<?= $nama_kategori ? ' • ' . htmlspecialchars($nama_kategori) : '' ?></p>
                </div>
            </div>

            <?php include 'partials/category_pills.php'; ?>

            <form method="get" class="search-form" action="index.php">
                <?php if ($kat_dipilih): ?>
                    <input type="hidden" name="kat" value="<?= $kat_dipilih ?>">
                <?php endif; ?>
                <input type="text" name="c" placeholder="🔍 Cari produk..." value="<?= htmlspecialchars($cari) ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
            </form>

            <?php if (!empty($rows)): ?>
                <div class="grid grid-4">
                    <?php foreach ($rows as $p): ?>
                        <div class="card">
                            <div class="card-image">
                                <a href="product.php?id=<?= $p['id'] ?>" class="card-link">
                                    <img src="<?= get_gambar_src($p) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>" onerror="this.src='assets/img/default.svg'">
                                    <span class="card-quick"><span>👁 Lihat Detail</span></span>
                                </a>
                                <?php if ($p['is_unggulan']): ?>
                                    <span class="badge badge-featured">⭐ Unggulan</span>
                                <?php endif; ?>
                                <?php if ($p['stok'] > 0 && $p['stok'] < 20): ?>
                                    <span class="badge badge-stok">Sisa <?= $p['stok'] ?></span>
                                <?php endif; ?>
                                <?php $glist = (isset($gal_map[$p['id']]) && $gal_map[$p['id']]) ? $gal_map[$p['id']] : array(get_gambar_src($p)); ?>
                                <button type="button" class="card-zoom" data-imgs="<?= htmlspecialchars(json_encode($glist), ENT_QUOTES, 'UTF-8') ?>" data-title="<?= htmlspecialchars($p['nama_produk']) ?>" title="Perbesar gambar">🔍</button>
                            </div>
                            <div class="card-body">
                                <div class="card-cat"><?= htmlspecialchars($p['nama_kategori']) ?></div>
                                <h3 class="card-title"><a href="product.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_produk']) ?></a></h3>
                                <p class="card-desc"><?= htmlspecialchars(mb_substr((string)($p['deskripsi'] ?? ''), 0, 70)) ?>...</p>
                                <div class="card-meta">
                                    <span>⏱ <?= htmlspecialchars($p['waktu_pengerjaan']) ?></span>
                                    <span>📦 Min. <?= $p['minimal_order'] ?> <?= htmlspecialchars($p['satuan']) ?></span>
                                </div>
                                <div class="card-foot">
                                    <div class="card-price"><?= rupiah($p['harga']) ?><small>/ <?= htmlspecialchars($p['satuan']) ?></small></div>
                                    <a href="order.php?produk=<?= $p['id'] ?>" class="btn-buy">🛒 Beli</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_halaman > 1): ?>
                    <div class="pagination">
                        <?php if ($hal > 1): ?>
                            <a href="?hal=<?= $hal - 1 ?>&kat=<?= $kat_dipilih ?>&c=<?= urlencode($cari) ?>">&laquo; Prev</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="?hal=<?= $i ?>&kat=<?= $kat_dipilih ?>&c=<?= urlencode($cari) ?>" class="<?= $i == $hal ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($hal < $total_halaman): ?>
                            <a href="?hal=<?= $hal + 1 ?>&kat=<?= $kat_dipilih ?>&c=<?= urlencode($cari) ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>Produk tidak ditemukan</h3>
                    <p>Coba kata kunci lain atau reset filter.</p>
                    <a href="index.php" class="btn btn-primary">Lihat Semua Produk</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Promo -->
    <section class="section" id="promo">
        <div class="container">
            <div class="promo-banner">
                <div>
                    <span class="promo-tag">Diskon Terbatas</span>
                    <h2>FLASH SALE <span class="text-gradient-red">POJOK INDAH</span></h2>
                    <p>Order hari ini dan amankan antrian cetakmu. Konfirmasi desain <strong>GRATIS</strong> untuk setiap pemesanan katalog.</p>
                    <div class="countdown">
                        <div class="count-box"><b id="cd-h">08</b><span>Jam</span></div>
                        <div class="count-box"><b id="cd-m">45</b><span>Menit</span></div>
                        <div class="count-box"><b id="cd-s" class="tick-red">30</b><span>Detik</span></div>
                    </div>
                    <a href="#katalog" class="btn btn-primary">Klaim Sekarang</a>
                </div>
                <div class="promo-visual">
                    <img src="assets/img/banner.svg" alt="Promo cetak" onerror="this.src='assets/img/default.svg'">
                    <div class="promo-off"><small>Desain</small>GRATIS</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Keunggulan -->
    <section class="section features">
        <div class="container">
            <div class="section-head">
                <h2>Kenapa <span class="text-gradient-red-blue">Kami?</span></h2>
            </div>
            <div class="feature-row">
                <div class="feature-item">
                    <div class="feature-icon">🚀</div>
                    <h4>Cepat</h4>
                    <p>Pengerjaan mulai dari 1 hari</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💎</div>
                    <h4>Berkualitas</h4>
                    <p>Mesin cetak modern &amp; bahan premium</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <h4>Harga Terjangkau</h4>
                    <p>Harga langsung dari kami, tanpa perantara</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🤝</div>
                    <h4>Customer Service</h4>
                    <p>Konsultasi gratis sebelum cetak</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cara Pesan -->
    <section class="section howto">
        <div class="container">
            <div class="section-head">
                <h2>📋 Cara Pemesanan</h2>
                <p>Mudah, hanya 4 langkah</p>
            </div>
            <div class="howto-row">
                <div class="howto-item">
                    <div class="howto-num">1</div>
                    <h4>Pilih Produk</h4>
                    <p>Lihat katalog dan pilih produk yang kamu butuhkan</p>
                </div>
                <div class="howto-item">
                    <div class="howto-num">2</div>
                    <h4>Isi Form Order</h4>
                    <p>Masukkan jumlah, data diri, dan catatan desain</p>
                </div>
                <div class="howto-item">
                    <div class="howto-num">3</div>
                    <h4>Kami Proses</h4>
                    <p>Tim kami hubungi kamu untuk konfirmasi desain</p>
                </div>
                <div class="howto-item">
                    <div class="howto-num">4</div>
                    <h4>Terima Barang</h4>
                    <p>Pesanan dikirim atau diambil langsung di toko</p>
                </div>
            </div>
        </div>
    </section>
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
            <a href="orders.php">Lacak Pesanan</a>
            <a href="admin/">Login Admin</a>
        </div>
        <div>
            <h4>Kategori</h4>
            <?php if ($kat_populer): while ($kp = $kat_populer->fetch_assoc()): ?>
                <a href="index.php?kat=<?= (int)$kp['id'] ?>"><?= htmlspecialchars($kp['nama_kategori']) ?></a>
            <?php endwhile; endif; ?>
        </div>
        <div>
            <h4>Kontak</h4>
            <ul class="footer-contact" style="list-style:none">
                <li>📍 Jl. Jl. Prof. Dr. H. Mansoer Pateda No.Desa, Pentadio Timur</li>
                <li>📞 0822-9075-26378</li>
                <li>✉️ ismailnusi02@gmail.com</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            &copy; <?= date('Y') ?> Percetakan Pojok Indah. All rights reserved.
        </div>
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
// Lightbox galeri
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
document.addEventListener('click', function (e) {
    var z = e.target && e.target.closest ? e.target.closest('.card-zoom') : null;
    if (z) {
        try {
            lbOpen(JSON.parse(z.getAttribute('data-imgs')), 0, z.getAttribute('data-title'));
        } catch (err) {}
        return;
    }
    var lb = document.getElementById('lightbox');
    if (lb && e.target === lb) lbClose();
});
</script>

<script>
// Countdown ke tengah malam
(function () {
    var h = document.getElementById('cd-h'),
        m = document.getElementById('cd-m'),
        s = document.getElementById('cd-s');
    if (!h || !m || !s) return;
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
        var now = new Date(),
            end = new Date(now);
        end.setHours(23, 59, 59, 999);
        var d = Math.max(0, Math.floor((end - now) / 1000));
        h.textContent = pad(Math.floor(d / 3600));
        m.textContent = pad(Math.floor((d % 3600) / 60));
        s.textContent = pad(d % 60);
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

</body>
</html>