<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'web_penjualan');

// Koneksi ke database (otomatis buat DB bila belum ada)
function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) {
        die('Koneksi gagal: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    // Buat database bila belum ada (biar tidak fatal saat database.sql belum diimport)
    $conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    if (!$conn->select_db(DB_NAME)) {
        die('Gagal memilih database "' . DB_NAME . '". Import file database.sql via phpMyAdmin terlebih dahulu.');
    }
    return $conn;
}

// Ambil koneksi
$conn = db_connect();

// Zona waktu
date_default_timezone_set('Asia/Jakarta');

// Format Rupiah
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Sanitasi input
function bersihkan($data) {
    global $conn;
    return $conn->real_escape_string(trim((string)$data));
}

// Query aman: kembalikan mysqli_result atau false tanpa fatal
function q($sql) {
    global $conn;
    $res = $conn->query($sql);
    if ($res === false) {
        error_log('SQL error: ' . $conn->error . ' | ' . $sql);
    }
    return $res;
}

// Ambil 1 baris aman (null bila query gagal / kosong)
function q_row($sql) {
    $res = q($sql);
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return null;
}

// Badge status pesanan (terpusat agar konsisten dengan CSS)
function status_badge($status) {
    $map = array(
        'pending'    => array('badge-pending', '⏳ Menunggu Konfirmasi'),
        'diproses'   => array('badge-proses', '🔧 Sedang Diproses'),
        'selesai'    => array('badge-selesai', '✅ Selesai'),
        'dibatalkan' => array('badge-batal', '❌ Dibatalkan'),
    );
    return isset($map[$status]) ? $map[$status] : $map['pending'];
}

// Helper URL
function base_url() {
    return '/WEBTES';
}

// ============ PENGATURAN TEMA (gelap / terang) ============
// Tabel dibuat otomatis bila belum ada, agar tidak fatal di install baru.
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan (
    kunci VARCHAR(50) NOT NULL PRIMARY KEY,
    nilai TEXT
) ENGINE=InnoDB");
$conn->query("INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES ('tema', 'gelap')");

function get_setting($kunci, $default = '') {
    global $conn;
    $k = $conn->real_escape_string((string)$kunci);
    $res = $conn->query("SELECT nilai FROM pengaturan WHERE kunci = '$k' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return $row['nilai'];
    }
    return $default;
}

function set_setting($kunci, $nilai) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    if (!$stmt) {
        return false;
    }
    $k = (string)$kunci;
    $v = (string)$nilai;
    $stmt->bind_param('ss', $k, $v);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// Tema aktif situs: 'gelap' atau 'terang'
$APP_TEMA = get_setting('tema', 'gelap');
if ($APP_TEMA !== 'terang' && $APP_TEMA !== 'gelap') {
    $APP_TEMA = 'gelap';
}

// ============ UPLOAD GAMBAR PRODUK ============
define('UPLOAD_DIR_PRODUK', __DIR__ . '/assets/img/produk');
define('UPLOAD_MAX_BYTE', 2 * 1024 * 1024);

// Upload file gambar produk. Return nama file bila sukses,
// null bila tidak ada file, false bila validasi gagal (cek $error).
function upload_gambar_produk($field = 'gambar', &$error = null) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload gagal (kode ' . (int)$f['error'] . ').';
        return false;
    }
    if ($f['size'] > UPLOAD_MAX_BYTE) {
        $error = 'Ukuran gambar maksimal 2 MB.';
        return false;
    }
    $allow = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'svg' => 'image/svg+xml');
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!isset($allow[$ext])) {
        $error = 'Format gambar harus JPG, PNG, WebP, atau SVG.';
        return false;
    }
    // Verifikasi isi file benar-benar gambar
    $kepala = (string)@file_get_contents($f['tmp_name'], false, null, 0, 512);
    if ($ext === 'svg') {
        if (stripos($kepala, '<svg') === false) {
            $error = 'File SVG tidak valid.';
            return false;
        }
    } else {
        $info = @getimagesize($f['tmp_name']);
        if ($info === false) {
            $error = 'File bukan gambar yang valid.';
            return false;
        }
    }
    if (!is_dir(UPLOAD_DIR_PRODUK)) {
        @mkdir(UPLOAD_DIR_PRODUK, 0755, true);
    }
    $nama = 'produk_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR_PRODUK . '/' . $nama)) {
        $error = 'Gagal menyimpan file gambar.';
        return false;
    }
    return $nama;
}

// Hapus file upload milik produk (tidak menyentuh placeholder/logo)
function hapus_gambar_produk($nama_file) {
    if (!$nama_file) {
        return;
    }
    $path = UPLOAD_DIR_PRODUK . '/' . basename((string)$nama_file);
    if (is_file($path)) {
        @unlink($path);
    }
}

// ============ GALERI PRODUK (maks 3 gambar) ============
define('GALERI_MAKS', 3);
$conn->query("CREATE TABLE IF NOT EXISTS produk_gambar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB");

// Upload banyak file sekaligus (input multiple). Return array nama file tersimpan.
function upload_gambar_multi($field = 'gambar_multi', $maks = 3, &$error = null) {
    $hasil = array();
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return $hasil;
    }
    $n = count($_FILES[$field]['name']);
    for ($i = 0; $i < $n && count($hasil) < $maks; $i++) {
        if ($_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $_FILES['_satu_tmp'] = array(
            'name'     => $_FILES[$field]['name'][$i],
            'type'     => $_FILES[$field]['type'][$i],
            'tmp_name' => $_FILES[$field]['tmp_name'][$i],
            'error'    => $_FILES[$field]['error'][$i],
            'size'     => $_FILES[$field]['size'][$i],
        );
        $e = null;
        $r = upload_gambar_produk('_satu_tmp', $e);
        if ($r === false && $e) {
            $error = $e;
        } elseif ($r) {
            $hasil[] = $r;
        }
    }
    unset($_FILES['_satu_tmp']);
    return $hasil;
}

// Ambil daftar galeri produk (hanya file yang benar-benar ada)
function get_gallery($id_produk) {
    $id = (int)$id_produk;
    $res = q("SELECT * FROM produk_gambar WHERE id_produk = $id ORDER BY urutan ASC, id ASC");
    $out = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $f = basename((string)$r['nama_file']);
            if ($f !== '' && is_file(UPLOAD_DIR_PRODUK . '/' . $f)) {
                $r['url'] = 'assets/img/produk/' . $f;
                $out[] = $r;
            }
        }
    }
    return $out;
}

// Samakan kolom sampul (produk.gambar) dengan gambar urutan pertama
function sync_cover($id_produk) {
    global $conn;
    $id = (int)$id_produk;
    $row = q_row("SELECT nama_file FROM produk_gambar WHERE id_produk = $id ORDER BY urutan ASC, id ASC LIMIT 1");
    $cover = ($row && !empty($row['nama_file'])) ? basename($row['nama_file']) : 'default.svg';
    $c = $conn->real_escape_string($cover);
    $conn->query("UPDATE produk SET gambar = '$c' WHERE id = $id");
}
?>