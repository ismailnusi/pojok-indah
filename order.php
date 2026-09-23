<?php
include 'config.php';
require 'funcs/gambar.php';

$produk_selected = null;
if (isset($_GET['produk']) && is_numeric($_GET['produk'])) {
    $pid = intval($_GET['produk']);
    $produk_selected = q_row("SELECT * FROM produk WHERE id = $pid AND is_aktif = 1");
}

$semua_produk = q("SELECT * FROM produk WHERE is_aktif = 1 ORDER BY nama_produk ASC");

$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produk_id   = isset($_POST['produk_id']) ? intval($_POST['produk_id']) : 0;
    $jumlah      = isset($_POST['jumlah']) ? intval($_POST['jumlah']) : 0;
    $nama        = bersihkan($_POST['nama'] ?? '');
    $no_hp       = bersihkan($_POST['no_hp'] ?? '');
    $email       = bersihkan($_POST['email'] ?? '');
    $alamat      = bersihkan($_POST['alamat'] ?? '');
    $catatan     = bersihkan($_POST['catatan'] ?? '');
    $metode      = bersihkan($_POST['metode'] ?? 'COD');

    $detail = q_row("SELECT * FROM produk WHERE id = $produk_id AND is_aktif = 1");

    // Validasi
    if (!$detail) {
        $error = 'Produk tidak ditemukan.';
    } elseif ($jumlah < $detail['minimal_order']) {
        $error = 'Jumlah pesanan minimal ' . $detail['minimal_order'] . ' ' . $detail['satuan'] . '.';
    } elseif ($jumlah > $detail['stok']) {
        $error = 'Stok tidak mencukupi. Stok tersedia: ' . $detail['stok'] . ' ' . $detail['satuan'] . '.';
    } elseif ($nama == '' || $no_hp == '' || $alamat == '') {
        $error = 'Nama, No. HP, dan Alamat wajib diisi.';
    } else {
        $total = $detail['harga'] * $jumlah;

        // Buat kode pesanan unik: PS-YYYYMMDD-XXX
        $kode = 'PS-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));

        $stmt = $conn->prepare("INSERT INTO pesanan (kode_pesanan, nama_pemesan, no_hp, email, alamat, catatan, total_harga, metode_bayar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssds', $kode, $nama, $no_hp, $email, $alamat, $catatan, $total, $metode);
        $stmt->execute();
        $id_pesanan = $stmt->insert_id;
        $stmt->close();

        $stmt2 = $conn->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        $detail_id = (int)$detail['id'];
        $jml = intval($jumlah);
        $hrg = floatval($detail['harga']);
        $sub = floatval($total);
        $stmt2->bind_param('iiidd', $id_pesanan, $detail_id, $jml, $hrg, $sub);
        $stmt2->execute();
        $stmt2->close();

        // Kurangi stok
        $jml_stok = intval($jumlah);
        $conn->query("UPDATE produk SET stok = stok - $jml_stok WHERE id = $detail_id");

        header("Location: order_success.php?kode=$kode");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemesanan - Percetakan Pojok Indah</title>
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
            <a href="product.php?id=<?= $produk_selected['id'] ?? 0 ?>"><?= htmlspecialchars($produk_selected['nama_produk'] ?? 'Produk') ?></a> &raquo;
            <span>Form Pemesanan</span>
        </div>

        <div class="section-head text-center">
            <h2>📝 Form Pemesanan</h2>
            <p>Lengkapi data di bawah ini, tim kami akan menghubungi kamu</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($pesan): ?>
            <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>

        <div class="order-layout">
            <!-- Form -->
            <form method="post" class="order-form" id="orderForm" action="">
                <div class="form-section">
                    <h4>1️⃣ Pilih Produk</h4>

                    <div class="form-group">
                        <label>Produk</label>
                        <?php if ($produk_selected): ?>
                            <input type="hidden" name="produk_id" value="<?= $produk_selected['id'] ?>">
                            <div class="selected-product">
                                <div>
                                    <strong><?= htmlspecialchars($produk_selected['nama_produk']) ?></strong>
                                    <small><?= rupiah($produk_selected['harga']) ?> / <?= htmlspecialchars($produk_selected['satuan']) ?> • Min. <?= $produk_selected['minimal_order'] ?> • Stok <?= $produk_selected['stok'] ?></small>
                                </div>
                                <a href="index.php#katalog" class="btn btn-outline btn-sm">Ganti</a>
                            </div>
                        <?php else: ?>
                            <select name="produk_id" id="produkSelect" class="form-control" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php if ($semua_produk): while ($sp = $semua_produk->fetch_assoc()): ?>
                                    <option value="<?= $sp['id'] ?>" data-harga="<?= $sp['harga'] ?>" data-min="<?= $sp['minimal_order'] ?>" data-stok="<?= $sp['stok'] ?>" data-satuan="<?= htmlspecialchars($sp['satuan']) ?>">
                                        <?= htmlspecialchars($sp['nama_produk']) ?> | <?= rupiah($sp['harga']) ?>/<?= htmlspecialchars($sp['satuan']) ?>
                                    </option>
                                <?php endwhile; else: ?>
                                    <option value="" disabled>Produk belum tersedia</option>
                                <?php endif; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" id="infoProduk" style="display:<?= $produk_selected ? 'flex' : 'none' ?>">
                        <div class="info-item">💵 Harga: <strong id="infoHarga"><?= $produk_selected ? rupiah($produk_selected['harga']) : '-' ?></strong></div>
                        <div class="info-item">📦 Minimal: <strong id="infoMin"><?= $produk_selected ? $produk_selected['minimal_order'] : '-' ?></strong></div>
                        <div class="info-item">📦 Stok: <strong id="infoStok"><?= $produk_selected ? $produk_selected['stok'] : '-' ?></strong></div>
                    </div>

                    <div class="form-group">
                        <label for="jumlah">Jumlah Pemesanan</label>
                        <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" value="<?= $produk_selected ? $produk_selected['minimal_order'] : 1 ?>" required>
                        <small class="form-hint">Minimal pemesanan sesuai info produk di atas.</small>
                    </div>
                </div>

                <div class="form-section">
                    <h4>2️⃣ Data Pemesan</h4>
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" name="nama" id="nama" class="form-control" placeholder="Nama kamu" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="no_hp">No. WhatsApp / HP</label>
                            <input type="tel" name="no_hp" id="no_hp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email (opsional)</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="email@contoh.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea name="alamat" id="alamat" class="form-control" rows="3" placeholder="Alamat lengkap untuk pengiriman / ambil di toko" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="catatan">Catatan / Detail Desain</label>
                        <textarea name="catatan" id="catatan" class="form-control" rows="3" placeholder="Contoh: ukuran 3x1 meter, warna merah kuning, sertakan logo file..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                        <small class="form-hint">Sertakan detail ukuran, warna, bahan, atau link file desain.</small>
                    </div>
                </div>

                <div class="form-section">
                    <h4>3️⃣ Metode Pembayaran</h4>
                    <div class="form-group">
                        <select name="metode" class="form-control" required>
                            <option value="COD">COD / Bayar di Tempat</option>
                            <option value="Transfer">Transfer Bank (BCA / BNI / Mandiri)</option>
                            <option value="OVO">OVO / Dana / GoPay</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">✅ Submit Pesanan</button>
            </form>

            <!-- Rincian -->
            <aside class="order-summary">
                <h4>🧾 Rincian Pesanan</h4>
                <?php if ($produk_selected): ?>
                    <div class="summary-item">
                        <span class="summary-label">Produk</span>
                        <span class="summary-value"><?= htmlspecialchars($produk_selected['nama_produk']) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Harga Satuan</span>
                        <span class="summary-value"><?= rupiah($produk_selected['harga']) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Jumlah</span>
                        <span class="summary-value"><span id="sJumlah"><?= $produk_selected['minimal_order'] ?></span> <?= htmlspecialchars($produk_selected['satuan']) ?></span>
                    </div>
                    <div class="summary-item summary-total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value" id="sTotal"><?= rupiah($produk_selected['harga'] * $produk_selected['minimal_order']) ?></span>
                    </div>
                    <div class="summary-note">
                        💡 Total akan dikonfirmasi kembali oleh tim kami jika ada biaya tambahan (desain, ongkir, dll).
                    </div>
                <?php else: ?>
                    <div class="summary-empty">
                        Pilih produk terlebih dahulu untuk melihat rincian.
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>

<footer class="site-footer" id="kontak">
    <div class="container footer-grid">
        <div class="footer-brand">
            <div class="logo"><img src="assets/img/logo.svg" alt="Percetakan Pojok Indah" class="logo-img"><span class="logo-text">Percetakan <span>Pojok Indah</span></span></div>
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

<script>
var produkData = {};
<?php
// Diisi dengan data produk untuk perhitungan otomatis
$smt = q("SELECT id, harga, minimal_order, stok, satuan, nama_produk FROM produk WHERE is_aktif = 1");
if ($smt) {
    while ($row = $smt->fetch_assoc()) {
        $rid = (int)$row['id'];
        $rh = (float)$row['harga'];
        $rm = (int)$row['minimal_order'];
        $rs = (int)$row['stok'];
        echo "produkData[$rid] = {harga: $rh, min: $rm, stok: $rs};\n";
    }
}
?>

function updateRincian() {
    var select = document.getElementById('produkSelect');
    var jumlahEl = document.getElementById('jumlah');
    var jumlah = parseInt(jumlahEl.value) || 0;

    <?php if (!$produk_selected): ?>
    if (!select || !select.value) return;
    var data = produkData[select.value];
    if (!data) return;
    document.getElementById('infoProduk').style.display = 'flex';
    document.getElementById('infoHarga').textContent = 'Rp ' + data.harga.toLocaleString('id-ID');
    document.getElementById('infoMin').textContent = data.min;
    document.getElementById('infoStok').textContent = data.stok;
    jumlahEl.min = data.min;

    if (jumlah < data.min) {
        jumlahEl.value = data.min;
        jumlah = data.min;
    }
    if (jumlah > data.stok) {
        jumlahEl.value = data.stok;
        jumlah = data.stok;
    }
    // Tidak ada panel rincian pada mode pilih-produk; cukup update info
    var sJ = document.getElementById('sJumlah');
    var sT = document.getElementById('sTotal');
    if (sJ && sT) {
        var harga = data.harga * jumlah;
        sJ.textContent = jumlah;
        sT.textContent = 'Rp ' + harga.toLocaleString('id-ID');
    }
    <?php else: ?>
    if (jumlah < 1) { jumlah = 1; jumlahEl.value = 1; }
    var harga = <?= (float)$produk_selected['harga'] ?> * jumlah;
    document.getElementById('sJumlah').textContent = jumlah;
    document.getElementById('sTotal').textContent = 'Rp ' + harga.toLocaleString('id-ID');
    <?php endif; ?>
}

var select = document.getElementById('produkSelect');
if (select) select.addEventListener('change', updateRincian);
document.getElementById('jumlah').addEventListener('input', updateRincian);
updateRincian();
</script>

</body>
</html>