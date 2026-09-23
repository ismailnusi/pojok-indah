<?php
ob_start();
session_start();
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../funcs/gambar.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Tombol ganti tema situs (gelap <-> terang)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_tema'])) {
    $baru = ($APP_TEMA === 'terang') ? 'gelap' : 'terang';
    set_setting('tema', $baru);
    $APP_TEMA = $baru;
    header('Location: index.php?page=dashboard');
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$halaman = array('dashboard', 'produk', 'tambah_produk', 'edit_produk', 'hapus_produk', 'pesanan', 'detail_pesanan', 'kategori', 'logout');
if (!in_array($page, $halaman)) {
    $page = 'dashboard';
}
?>

<?php if ($page == 'logout'): ?>
<?php
session_destroy();
header('Location: login.php');
exit;
?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Percetakan Pojok Indah</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= filemtime('../assets/css/admin.css') ?>">
</head>
<body class="admin-body<?= $APP_TEMA === 'terang' ? ' terang' : '' ?>">

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="../index.php" class="logo admin-logo">
            <img src="../assets/img/logo.svg" alt="Percetakan Pojok Indah" class="logo-img">
            <span class="logo-text">Percetakan <span>Pojok Indah</span></span>
        </a>
        <nav class="admin-nav">
            <a href="index.php?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="index.php?page=produk" class="<?= in_array($page, ['produk', 'tambah_produk', 'edit_produk']) ? 'active' : '' ?>">📦 Produk</a>
            <a href="index.php?page=pesanan" class="<?= in_array($page, ['pesanan', 'detail_pesanan']) ? 'active' : '' ?>">🧾 Pesanan</a>
            <a href="index.php?page=kategori" class="<?= $page == 'kategori' ? 'active' : '' ?>">🏷️ Kategori</a>
            <a href="../index.php" target="_blank">🌐 Lihat Toko</a>
            <a href="index.php?page=logout" onclick="return confirm('Yakin mau logout?')">🚪 Logout</a>
        </nav>
        <form method="post" style="padding: 0 24px; margin: 12px 0 4px;">
            <button type="submit" name="ganti_tema" value="1" class="btn <?= $APP_TEMA === 'terang' ? 'btn-outline' : 'btn-primary' ?> btn-sm btn-block" title="Ganti tema tampilan situs">
                <?= $APP_TEMA === 'terang' ? '🌙 Mode Gelap' : '☀️ Mode Terang' ?>
            </button>
        </form>
        <div class="admin-user">
            <strong><?= htmlspecialchars($_SESSION['admin_nama'] ?? $_SESSION['admin_username']) ?></strong><br>
            <span>🎨 Tema situs: <?= $APP_TEMA === 'terang' ? 'Terang' : 'Gelap' ?></span>
        </div>
    </aside>

    <!-- Konten -->
    <main class="admin-content">
        <?php

        // ============ DASHBOARD ============
        if ($page == 'dashboard'):
            $r = q_row("SELECT COUNT(*) j FROM produk");
            $j_produk = $r ? $r['j'] : 0;
            $r = q_row("SELECT COUNT(*) j FROM kategori");
            $j_kategori = $r ? $r['j'] : 0;
            $r = q_row("SELECT COUNT(*) j FROM pesanan");
            $j_pesanan = $r ? $r['j'] : 0;
            $r = q_row("SELECT COUNT(*) j FROM pesanan WHERE status='pending'");
            $j_pending = $r ? $r['j'] : 0;
            $r = q_row("SELECT COALESCE(SUM(total_harga),0) j FROM pesanan WHERE status != 'dibatalkan'");
            $omzet = $r ? $r['j'] : 0;
            $r = q_row("SELECT COALESCE(SUM(total_harga),0) j FROM pesanan WHERE status != 'dibatalkan' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
            $omzet_bulan = $r ? $r['j'] : 0;

            $pesanan_terbaru = q("SELECT * FROM pesanan ORDER BY id DESC LIMIT 5");
        ?>
            <h1 class="admin-title">Dashboard</h1>
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div>
                        <p>Omzet Bulan Ini</p>
                        <strong><?= rupiah($omzet_bulan) ?></strong>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div>
                        <p>Total Produk</p>
                        <strong><?= $j_produk ?></strong>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🧾</div>
                    <div>
                        <p>Total Pesanan</p>
                        <strong><?= $j_pesanan ?></strong>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div>
                        <p>Pesanan Pending</p>
                        <strong><?= $j_pending ?></strong>
                    </div>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-head">
                    <h3>Pesanan Terbaru</h3>
                    <a href="index.php?page=pesanan" class="btn btn-outline btn-sm">Lihat Semua</a>
                </div>
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pemesan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$pesanan_terbaru || $pesanan_terbaru->num_rows == 0): ?>
                            <tr><td colspan="6" class="text-center">Belum ada pesanan.</td></tr>
                        <?php else: while ($po = $pesanan_terbaru->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($po['kode_pesanan']) ?></strong></td>
                                <td><?= htmlspecialchars($po['nama_pemesan']) ?></td>
                                <td><?= rupiah($po['total_harga']) ?></td>
                                <?php $bd = status_badge($po['status']); ?>
                                <td><span class="badge <?= $bd[0] ?>"><?= $bd[1] ?></span></td>
                                <td><?= date('d M Y', strtotime($po['created_at'])) ?></td>
                                <td><a href="index.php?page=detail_pesanan&id=<?= $po['id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php
        // ============ PRODUK ============
        if ($page == 'produk'):
            $list = q("SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id ORDER BY p.id DESC");
        ?>
            <div class="admin-head-row">
                <h1 class="admin-title">Daftar Produk</h1>
                <a href="index.php?page=tambah_produk" class="btn btn-primary">+ Tambah Produk</a>
            </div>
            <div class="admin-panel">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Min.</th>
                            <th>Unggulan</th>
                            <th>Aktif</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$list || $list->num_rows == 0): ?>
                            <tr><td colspan="10" class="text-center">Belum ada produk. <a href="index.php?page=tambah_produk">Tambah sekarang</a>.</td></tr>
                        <?php else: while ($pr = $list->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $pr['id'] ?></td>
                                <td><img src="../<?= htmlspecialchars(get_gambar_src($pr)) ?>" alt="" class="thumb-admin" loading="lazy" onerror="this.src='../assets/img/default.svg'"></td>
                                <td><strong><?= htmlspecialchars($pr['nama_produk']) ?></strong></td>
                                <td><?= htmlspecialchars($pr['nama_kategori']) ?></td>
                                <td><?= rupiah($pr['harga']) ?></td>
                                <td><?= $pr['stok'] ?></td>
                                <td><?= $pr['minimal_order'] ?></td>
                                <td><?= $pr['is_unggulan'] ? '⭐' : '-' ?></td>
                                <td><?= $pr['is_aktif'] ? '✅' : '❌' ?></td>
                                <td class="actions">
                                    <a href="index.php?page=edit_produk&id=<?= $pr['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="index.php?page=hapus_produk&id=<?= $pr['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus produk ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php
        // ============ TAMBAH / EDIT PRODUK ============
        if ($page == 'tambah_produk' || $page == 'edit_produk'):
            $edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $data = array('id_kategori' => 1, 'nama_produk' => '', 'deskripsi' => '', 'harga' => 0, 'satuan' => 'pcs', 'stok' => 0, 'minimal_order' => 1, 'waktu_pengerjaan' => '1-2 hari', 'gambar' => '', 'is_unggulan' => 0, 'is_aktif' => 1);
            $judul = 'Tambah Produk';
            if ($page == 'edit_produk' && $edit_id) {
                $row = q_row("SELECT * FROM produk WHERE id = $edit_id");
                if ($row) {
                    $data = $row;
                    $judul = 'Edit Produk';
                }
            }

            $pesan_form = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nama = bersihkan($_POST['nama_produk']);
                $desc = bersihkan($_POST['deskripsi']);
                $harga = intval($_POST['harga']);
                $satuan = bersihkan($_POST['satuan']);
                $stok = intval($_POST['stok']);
                $min = intval($_POST['minimal_order']);
                $waktu = bersihkan($_POST['waktu_pengerjaan']);
                $kat = intval($_POST['id_kategori']);
                $unggulan = isset($_POST['is_unggulan']) ? 1 : 0;
                $aktif = isset($_POST['is_aktif']) ? 1 : 0;

                if ($nama == '' || $harga <= 0) {
                    $pesan_form = '<div class="alert alert-danger">Nama produk dan harga wajib diisi/valid.</div>';
                } elseif ($page == 'tambah_produk') {
                    // Upload 1-3 gambar sekaligus; gambar pertama jadi sampul
                    $err_up = null;
                    $files_up = upload_gambar_multi('gambar_multi', GALERI_MAKS, $err_up);
                    $gambar_ins = $files_up ? $files_up[0] : 'default.svg';
                    $stmt = $conn->prepare("INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, satuan, stok, minimal_order, waktu_pengerjaan, gambar, is_unggulan, is_aktif) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('issdsiissii', $kat, $nama, $desc, $harga, $satuan, $stok, $min, $waktu, $gambar_ins, $unggulan, $aktif);
                    if ($stmt->execute()) {
                        $id_baru = $stmt->insert_id;
                        $stmt->close();
                        $u = 0;
                        foreach ($files_up as $fn) {
                            $fe = bersihkan($fn);
                            $conn->query("INSERT INTO produk_gambar (id_produk, nama_file, urutan) VALUES ($id_baru, '$fe', $u)");
                            $u++;
                        }
                        if ($err_up) {
                            $pesan_form = '<div class="alert alert-success">Produk tersimpan, namun ada gambar yang gagal: ' . htmlspecialchars($err_up) . '</div>';
                        } else {
                            header('Location: index.php?page=produk');
                            exit;
                        }
                    } else {
                        foreach ($files_up as $fn) {
                            hapus_gambar_produk($fn);
                        }
                        $pesan_form = '<div class="alert alert-danger">Gagal menyimpan: ' . $stmt->error . '</div>';
                        $stmt->close();
                    }
                } else {
                    // EDIT: kelola galeri (hapus + sampul + tambah sisa slot)
                    $gal_edit = get_gallery($edit_id);

                    // 1) Hapus yang dicentang
                    if (!empty($_POST['hapus_gambar']) && is_array($_POST['hapus_gambar'])) {
                        foreach ($_POST['hapus_gambar'] as $hid) {
                            $hid = (int)$hid;
                            $cek = q_row("SELECT nama_file FROM produk_gambar WHERE id = $hid AND id_produk = $edit_id");
                            if ($cek) {
                                hapus_gambar_produk($cek['nama_file']);
                                $conn->query("DELETE FROM produk_gambar WHERE id = $hid");
                            }
                        }
                        $gal_edit = get_gallery($edit_id);
                    }

                    // 2) Tambah gambar baru sesuai sisa slot
                    $err_up = null;
                    $sisa = GALERI_MAKS - count($gal_edit);
                    if ($sisa > 0) {
                        $files_up = upload_gambar_multi('gambar_multi', $sisa, $err_up);
                        if ($files_up) {
                            $mx = q_row("SELECT COALESCE(MAX(urutan), -1) m FROM produk_gambar WHERE id_produk = $edit_id");
                            $u = $mx ? ((int)$mx['m'] + 1) : 0;
                            foreach ($files_up as $fn) {
                                $fe = bersihkan($fn);
                                $conn->query("INSERT INTO produk_gambar (id_produk, nama_file, urutan) VALUES ($edit_id, '$fe', $u)");
                                $u++;
                            }
                            $gal_edit = get_gallery($edit_id);
                        }
                    }

                    // 3) Jadikan sampul (gambar urutan pertama)
                    if (isset($_POST['sampul_id']) && is_numeric($_POST['sampul_id'])) {
                        $sid = (int)$_POST['sampul_id'];
                        $cek = q_row("SELECT id FROM produk_gambar WHERE id = $sid AND id_produk = $edit_id");
                        if ($cek) {
                            $conn->query("UPDATE produk_gambar SET urutan = id WHERE id_produk = $edit_id");
                            $conn->query("UPDATE produk_gambar SET urutan = 0 WHERE id = $sid");
                            $gal_edit = get_gallery($edit_id);
                        }
                    }
                    sync_cover($edit_id);

                    // 4) Update data produk (sampul sudah disinkron)
                    $stmt = $conn->prepare("UPDATE produk SET id_kategori=?, nama_produk=?, deskripsi=?, harga=?, satuan=?, stok=?, minimal_order=?, waktu_pengerjaan=?, is_unggulan=?, is_aktif=? WHERE id=?");
                    $stmt->bind_param('issdsisiiii', $kat, $nama, $desc, $harga, $satuan, $stok, $min, $waktu, $unggulan, $aktif, $edit_id);
                    if ($stmt->execute()) {
                        $stmt->close();
                        if ($err_up) {
                            $data = q_row("SELECT * FROM produk WHERE id = $edit_id");
                            $pesan_form = '<div class="alert alert-success">Produk tersimpan, namun ada gambar yang gagal: ' . htmlspecialchars($err_up) . '</div>';
                        } else {
                            header('Location: index.php?page=produk');
                            exit;
                        }
                    } else {
                        $pesan_form = '<div class="alert alert-danger">Gagal mengupdate: ' . $stmt->error . '</div>';
                        $stmt->close();
                    }
                }
            }

            // Muat galeri; migrasikan sampul lama bila belum ada baris galeri
            $galeri = ($page == 'edit_produk' && $edit_id) ? get_gallery($edit_id) : array();
            if ($page == 'edit_produk' && $edit_id && empty($galeri) && !empty($data['gambar'])) {
                $cf = basename($data['gambar']);
                if (is_file(__DIR__ . '/../assets/img/produk/' . $cf)) {
                    $ce = bersihkan($cf);
                    $conn->query("INSERT INTO produk_gambar (id_produk, nama_file, urutan) VALUES ($edit_id, '$ce', 0)");
                    $galeri = get_gallery($edit_id);
                }
            }

            $kategori_opsi = q("SELECT * FROM kategori ORDER BY nama_kategori");
        ?>
            <h1 class="admin-title"><?= $judul ?></h1>
            <?= $pesan_form ?>
            <div class="admin-panel">
                <form method="post" class="admin-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_produk" class="form-control" required value="<?= htmlspecialchars($data['nama_produk']) ?>">
                    </div>
                    <?php if ($page == 'tambah_produk'): ?>
                    <div class="form-group">
                        <label>Gambar Produk (maks 3)</label>
                        <input type="file" name="gambar_multi[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg" multiple>
                        <small class="form-hint">Pilih 1–3 gambar sekaligus. JPG / PNG / WebP / SVG, masing-masing maks 2 MB. Gambar pertama jadi sampul katalog.</small>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label>Galeri Produk (maks <?= GALERI_MAKS ?> gambar)</label>
                        <?php if (!empty($galeri)): ?>
                            <div class="galeri-admin">
                                <?php foreach ($galeri as $gi => $g): ?>
                                    <div class="galeri-item<?= $gi === 0 ? ' is-sampul' : '' ?>">
                                        <img src="../<?= htmlspecialchars($g['url']) ?>" alt="" loading="lazy" onerror="this.src='../assets/img/default.svg'">
                                        <?php if ($gi === 0): ?><span class="galeri-tag">Sampul</span><?php endif; ?>
                                        <div class="galeri-acts">
                                            <label title="Jadikan sampul katalog"><input type="radio" name="sampul_id" value="<?= (int)$g['id'] ?>" <?= $gi === 0 ? 'checked' : '' ?>> Sampul</label>
                                            <label title="Hapus gambar ini"><input type="checkbox" name="hapus_gambar[]" value="<?= (int)$g['id'] ?>"> Hapus</label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="form-hint">Belum ada gambar untuk produk ini.</p>
                        <?php endif; ?>
                        <?php $sisa_slot = GALERI_MAKS - count($galeri); ?>
                        <?php if ($sisa_slot > 0): ?>
                            <input type="file" name="gambar_multi[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg" multiple style="margin-top:10px">
                            <small class="form-hint">Tambah gambar (sisa <?= $sisa_slot ?> slot). JPG / PNG / WebP / SVG, maks 2 MB per file.</small>
                        <?php else: ?>
                            <small class="form-hint">Slot penuh (3/3). Centang Hapus pada gambar lalu Simpan untuk mengganti.</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="id_kategori" class="form-control" required>
                            <?php if ($kategori_opsi): while ($kt = $kategori_opsi->fetch_assoc()): ?>
                                <option value="<?= $kt['id'] ?>" <?= $data['id_kategori'] == $kt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($kt['nama_kategori']) ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($data['deskripsi']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Harga (Rp)</label>
                            <input type="number" name="harga" class="form-control" required value="<?= $data['harga'] ?>">
                        </div>
                        <div class="form-group">
                            <label>Satuan</label>
                            <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($data['satuan']) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number" name="stok" class="form-control" value="<?= $data['stok'] ?>">
                        </div>
                        <div class="form-group">
                            <label>Minimal Order</label>
                            <input type="number" name="minimal_order" class="form-control" value="<?= $data['minimal_order'] ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Waktu Pengerjaan</label>
                        <input type="text" name="waktu_pengerjaan" class="form-control" value="<?= htmlspecialchars($data['waktu_pengerjaan']) ?>">
                    </div>
                    <div class="form-check-group">
                        <label class="form-check">
                            <input type="checkbox" name="is_unggulan" <?= $data['is_unggulan'] ? 'checked' : '' ?>> ⭐ Produk Unggulan
                        </label>
                        <label class="form-check">
                            <input type="checkbox" name="is_aktif" <?= $data['is_aktif'] ? 'checked' : '' ?>> ✅ Tampilkan di web
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="index.php?page=produk" class="btn btn-outline">Batal</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php
        // ============ HAPUS PRODUK ============
        if ($page == 'hapus_produk'):
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id > 0) {
                $del = q_row("SELECT gambar FROM produk WHERE id = $id");
                foreach (get_gallery($id) as $gf) {
                    hapus_gambar_produk($gf['nama_file']);
                }
                if ($del && !empty($del['gambar'])) {
                    hapus_gambar_produk($del['gambar']);
                }
                $conn->query("DELETE FROM produk WHERE id = $id");
            }
            header('Location: index.php?page=produk');
            exit;
        endif;
        ?>

        <?php
        // ============ PESANAN ============
        if ($page == 'pesanan'):
            $status_f = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
            $status_allow = array('pending', 'diproses', 'selesai', 'dibatalkan');
            if ($status_f !== '' && !in_array($status_f, $status_allow, true)) {
                $status_f = '';
            }
            $where = $status_f !== '' ? "WHERE status = '" . bersihkan($status_f) . "'" : '';
            $list = q("SELECT * FROM pesanan $where ORDER BY id DESC");
            $statuses = array('pending', 'diproses', 'selesai', 'dibatalkan');
        ?>
            <div class="admin-head-row">
                <h1 class="admin-title">Daftar Pesanan</h1>
            </div>
            <div class="filter-tabs">
                <a href="index.php?page=pesanan" class="<?= $status_f == '' ? 'active' : '' ?>">Semua</a>
                <?php foreach ($statuses as $s): ?>
                    <a href="index.php?page=pesanan&status=<?= $s ?>" class="<?= $status_f == $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="admin-panel">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pemesan</th>
                            <th>No. HP</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$list || $list->num_rows == 0): ?>
                            <tr><td colspan="7" class="text-center">Belum ada pesanan.</td></tr>
                        <?php else: while ($po = $list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($po['kode_pesanan']) ?></strong></td>
                                <td><?= htmlspecialchars($po['nama_pemesan']) ?></td>
                                <td><?= htmlspecialchars($po['no_hp']) ?></td>
                                <td><?= rupiah($po['total_harga']) ?></td>
                                <?php $bd = status_badge($po['status']); ?>
                                <td><span class="badge <?= $bd[0] ?>"><?= $bd[1] ?></span></td>
                                <td><?= date('d M Y H:i', strtotime($po['created_at'])) ?></td>
                                <td><a href="index.php?page=detail_pesanan&id=<?= $po['id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php
        // ============ DETAIL PESANAN ============
        if ($page == 'detail_pesanan'):
            $pid = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $po = q_row("SELECT * FROM pesanan WHERE id = $pid");
            if (!$po) {
                header('Location: index.php?page=pesanan');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $status_baru = bersihkan($_POST['status']);
                $statuses_valid = array('pending', 'diproses', 'selesai', 'dibatalkan');
                if (in_array($status_baru, $statuses_valid)) {
                    $stmt = $conn->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
                    $stmt->bind_param('si', $status_baru, $pid);
                    $stmt->execute();
                    $stmt->close();
                }
                header("Location: index.php?page=detail_pesanan&id=$pid");
                exit;
            }

            $items = q("SELECT dp.*, p.nama_produk, p.satuan FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id WHERE dp.id_pesanan = $pid");
        ?>
            <div class="admin-head-row">
                <h1 class="admin-title">Detail Pesanan <?= htmlspecialchars($po['kode_pesanan']) ?></h1>
                <a href="index.php?page=pesanan" class="btn btn-outline">← Kembali</a>
            </div>

            <div class="admin-panel">
                <h3>Data Pemesan</h3>
                <div class="tracking-details">
                    <div class="info-item"><span>👤 Nama</span><strong><?= htmlspecialchars($po['nama_pemesan']) ?></strong></div>
                    <div class="info-item"><span>📞 No. HP</span><strong><?= htmlspecialchars($po['no_hp']) ?></strong></div>
                    <div class="info-item"><span>✉️ Email</span><strong><?= htmlspecialchars($po['email'] ?: '-') ?></strong></div>
                    <div class="info-item"><span>📍 Alamat</span><strong><?= htmlspecialchars($po['alamat']) ?></strong></div>
                    <div class="info-item"><span>💳 Metode</span><strong><?= htmlspecialchars($po['metode_bayar']) ?></strong></div>
                    <div class="info-item"><span>📝 Catatan</span><strong><?= htmlspecialchars($po['catatan'] ?: '-') ?></strong></div>
                </div>
            </div>

            <div class="admin-panel">
                <h3>Item Pesanan</h3>
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items): while ($it = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($it['nama_produk']) ?></td>
                                <td><?= rupiah($it['harga_satuan']) ?></td>
                                <td><?= $it['jumlah'] ?> <?= htmlspecialchars($it['satuan']) ?></td>
                                <td><?= rupiah($it['subtotal']) ?></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                            <td><strong><?= rupiah($po['total_harga']) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="admin-panel">
                <h3>Update Status</h3>
                <form method="post" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <select name="status" class="form-control">
                                <?php foreach (array('pending', 'diproses', 'selesai', 'dibatalkan') as $s): ?>
                                    <option value="<?= $s ?>" <?= $po['status'] == $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php
        // ============ KATEGORI ============
        if ($page == 'kategori'):
            $pesan_kat = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
                $nama = bersihkan($_POST['nama_kategori']);
                $icon = bersihkan($_POST['icon'] ?? '📦');
                if ($nama != '') {
                    $conn->query("INSERT INTO kategori (nama_kategori, icon) VALUES ('$nama', '$icon')");
                    $pesan_kat = '<div class="alert alert-success">Kategori ditambahkan.</div>';
                }
            }
            if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
                $hapus_id = intval($_GET['hapus']);
                $conn->query("DELETE FROM kategori WHERE id = $hapus_id");
                $pesan_kat = '<div class="alert alert-success">Kategori dihapus.</div>';
            }
            $list = q("SELECT k.*, (SELECT COUNT(*) FROM produk p WHERE p.id_kategori = k.id) jml_produk FROM kategori k ORDER BY k.id");
        ?>
            <h1 class="admin-title">Kategori Produk</h1>
            <?= $pesan_kat ?>
            <div class="admin-panel">
                <form method="post" class="admin-form form-inline">
                    <input type="text" name="nama_kategori" class="form-control" placeholder="Nama kategori baru" required>
                    <input type="text" name="icon" class="form-control" placeholder="Icon (emoji)" maxlength="8" style="max-width:100px">
                    <button type="submit" name="tambah_kategori" class="btn btn-primary">Tambah</button>
                </form>
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Kategori</th>
                            <th>Jumlah Produk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($list): while ($kt = $list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $kt['id'] ?></td>
                                <td><?= $kt['icon'] ?></td>
                                <td><strong><?= htmlspecialchars($kt['nama_kategori']) ?></strong></td>
                                <td><?= $kt['jml_produk'] ?></td>
                                <td><a href="index.php?page=kategori&hapus=<?= $kt['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus kategori? Produk terkait ikut terhapus.')">Hapus</a></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>