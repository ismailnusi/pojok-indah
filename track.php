<?php
include 'config.php';

$p = null;
$items = null;
$badge = status_badge('pending');
$cari_kode = isset($_GET['kode']) ? trim((string)$_GET['kode']) : '';
$cari_hp = isset($_GET['hp']) ? trim((string)$_GET['hp']) : '';

if ($cari_kode !== '') {
    $kode = bersihkan($cari_kode);
    $p = q_row("SELECT * FROM pesanan WHERE kode_pesanan = '$kode'");
    if ($p && $cari_hp !== '') {
        $hp = bersihkan($cari_hp);
        $cek = q_row("SELECT id FROM pesanan WHERE kode_pesanan = '$kode' AND no_hp LIKE '%$hp%'");
        if (!$cek) {
            $p = null;
        }
    }
}

if ($p) {
    $pid = (int)$p['id'];
    if (empty($p['is_custom'])) {
        $items = q("SELECT dp.*, p.nama_produk, p.satuan FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id WHERE dp.id_pesanan = $pid");
    }
    $badge = status_badge($p['status']);
}

// Langkah timeline: 0 Diterima, 1 Diproses, 2 Selesai (-1 Dibatalkan)
$step = -2;
if ($p) {
    $step = array('pending' => 0, 'diproses' => 1, 'selesai' => 2, 'dibatalkan' => -1);
    $step = isset($step[$p['status']]) ? $step[$p['status']] : 0;
}
$steps = array(
    array('⏳', 'Diterima', 'Menunggu konfirmasi admin'),
    array('🖨️', 'Diproses', 'Sedang dicetak / dikerjakan'),
    array('✅', 'Selesai', 'Siap diambil / dikirim'),
);

$wa_link = '';
if ($p) {
    $teks = 'Halo Admin Percetakan Pojok Indah, saya ingin bertanya tentang pesanan ' . $p['kode_pesanan'] . ' (' . $p['nama_pemesan'] . '). Terima kasih.';
    $wa_link = 'https://wa.me/' . wa_admin() . '?text=' . urlencode($teks);
}

$CHAT_KODE = $p ? $p['kode_pesanan'] : $cari_kode;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan - Percetakan Pojok Indah</title>
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
            <a href="track.php" class="active">Lacak</a>
            <button type="button" class="theme-toggle theme-icon-only" onclick="toggleTema()" title="Ganti mode gelap / terang"><?= $APP_TEMA === 'terang' ? '🌙' : '☀️' ?></button>
            <a href="admin/" class="btn btn-outline btn-sm">Admin</a>
        </nav>
        <button class="menu-toggle" onclick="document.body.classList.toggle('nav-open')">☰</button>
    </div>
</header>

<main>
    <div class="container">
        <div class="section-head text-center" style="margin-top:34px">
            <h2>🔍 Lacak <span class="text-gradient-red">Pesanan</span></h2>
            <p>Masukkan kode pesanan dari struk / WhatsApp admin</p>
        </div>

        <form method="get" class="search-form tracking-form" action="track.php">
            <input type="text" name="kode" placeholder="Kode Pesanan (contoh: PS-20261009-XXXX)" value="<?= htmlspecialchars($cari_kode) ?>" required>
            <input type="text" name="hp" placeholder="No. HP (opsional)" value="<?= htmlspecialchars($cari_hp) ?>">
            <button type="submit" class="btn btn-primary">Lacak</button>
        </form>

        <?php if ($p): ?>
            <div class="tracking-result">
                <div class="tracking-head">
                    <div>
                        <h3>Pesanan <?= htmlspecialchars($p['kode_pesanan']) ?></h3>
                        <p>Dipesan pada <?= date('d M Y, H:i', strtotime($p['created_at'])) ?></p>
                    </div>
                    <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                </div>

                <?php if ($step === -1): ?>
                    <div class="alert alert-danger" style="margin-bottom:20px">❌ Pesanan ini <strong>dibatalkan</strong>. Hubungi admin via WhatsApp untuk info lebih lanjut.</div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($steps as $i => $s): ?>
                            <?php $cls = $i < $step ? 'done' : ($i == $step ? 'active' : ''); ?>
                            <div class="tl-step <?= $cls ?>">
                                <div class="tl-dot"><?= $i < $step ? '✓' : $s[0] ?></div>
                                <div class="tl-text"><strong><?= $s[1] ?></strong><span><?= $s[2] ?></span></div>
                                <?php if ($i < count($steps) - 1): ?>
                                    <div class="tl-line <?= $i < $step ? 'done' : '' ?>"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="tracking-items">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($p['is_custom'])): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['custom_nama'] ?: 'Pesanan Custom') ?> <span class="badge badge-proses">Custom</span></td>
                                    <td><?= rupiah($p['custom_harga']) ?></td>
                                    <td><?= (int)$p['custom_qty'] ?></td>
                                    <td><?= rupiah($p['total_harga']) ?></td>
                                </tr>
                            <?php elseif ($items): while ($it = $items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($it['nama_produk']) ?></td>
                                    <td><?= rupiah($it['harga_satuan']) ?></td>
                                    <td><?= (int)$it['jumlah'] ?> <?= htmlspecialchars($it['satuan']) ?></td>
                                    <td><?= rupiah($it['subtotal']) ?></td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Total Biaya</strong></td>
                                <td><strong><?= rupiah($p['total_harga']) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="tracking-details">
                    <div class="info-item"><span>👤 Pemesan</span><strong><?= htmlspecialchars($p['nama_pemesan']) ?></strong></div>
                    <div class="info-item"><span>📞 No. HP</span><strong><?= htmlspecialchars($p['no_hp']) ?></strong></div>
                    <div class="info-item"><span>📍 Alamat</span><strong><?= htmlspecialchars($p['alamat']) ?></strong></div>
                    <div class="info-item"><span>💳 Metode Bayar</span><strong><?= htmlspecialchars($p['metode_bayar']) ?></strong></div>
                    <?php if (!empty($p['catatan'])): ?>
                        <div class="info-item"><span>📝 Catatan</span><strong><?= htmlspecialchars($p['catatan']) ?></strong></div>
                    <?php endif; ?>
                </div>

                <div class="track-actions">
                    <a href="<?= htmlspecialchars($wa_link) ?>" target="_blank" rel="noopener" class="btn btn-primary">💬 Chat via WhatsApp Admin</a>
                    <a href="index.php" class="btn btn-outline">← Kembali ke Katalog</a>
                </div>
            </div>
        <?php elseif ($cari_kode !== ''): ?>
            <div class="empty-state">
                <div class="empty-icon">🔎</div>
                <h3>Pesanan tidak ditemukan</h3>
                <p>Periksa kembali kode pesanan atau nomor HP kamu.</p>
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
            <a href="track.php">Lacak Pesanan</a>
            <a href="admin/">Login Admin</a>
        </div>
        <div>
            <h4>Bantuan</h4>
            <a href="track.php">Cek Status Pesanan</a>
            <a href="<?= 'https://wa.me/' . wa_admin() ?>" target="_blank" rel="noopener">WhatsApp Admin</a>
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

<?php include __DIR__ . '/partials/chat_widget.php'; ?>

</body>
</html>