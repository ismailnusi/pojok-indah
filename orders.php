<?php
include 'config.php';
require 'funcs/gambar.php';

$p = null;
$items = null;
$badge = status_badge('pending');
$cari_kode = isset($_GET['kode']) ? trim((string)$_GET['kode']) : '';
$cari_hp = isset($_GET['hp']) ? trim((string)$_GET['hp']) : '';

if ($cari_kode !== '') {
    $kode = bersihkan($cari_kode);
    $p = q_row("SELECT * FROM pesanan WHERE kode_pesanan = '$kode'");

    // Bila user menyertakan no HP, cocokkan sebagai verifikasi tambahan
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
    $items = q("SELECT dp.*, p.nama_produk, p.satuan FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id WHERE dp.id_pesanan = $pid");
    $badge = status_badge($p['status']);
}
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
            <a href="orders.php" class="active">Pesanan Saya</a>
            <a href="admin/" class="btn btn-outline btn-sm">Admin</a>
        </nav>
    </div>
</header>

<main>
    <div class="container">
        <div class="section-head text-center">
            <h2>🔍 Lacak Pesanan Kamu</h2>
            <p>Masukkan kode pesanan untuk melihat status (no. HP opsional sebagai verifikasi)</p>
        </div>

        <form method="get" class="search-form tracking-form">
            <input type="text" name="kode" placeholder="Kode Pesanan (contoh: PS-20260101-XXXX)" value="<?= htmlspecialchars($cari_kode) ?>" required>
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
                            <?php if ($items): while ($it = $items->fetch_assoc()): ?>
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
                                <td colspan="3" class="text-right"><strong>Total</strong></td>
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

</body>
</html>