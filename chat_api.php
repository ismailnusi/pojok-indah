<?php
// API JSON untuk Live Chat Pelanggan <-> Admin
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/config.php';

function jkeluar($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function admin_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_id']);
}

$aksi = isset($_REQUEST['aksi']) ? trim((string)$_REQUEST['aksi']) : '';

// ---- Pelanggan: kirim pesan ----
if ($aksi === 'kirim' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim((string)($_POST['nama'] ?? ''));
    $hp    = preg_replace('/[^0-9]/', '', (string)($_POST['no_hp'] ?? ''));
    $kode  = trim((string)($_POST['kode'] ?? ''));
    $pesan = trim((string)($_POST['pesan'] ?? ''));
    if ($nama === '' || $hp === '' || $pesan === '') {
        jkeluar(array('ok' => false, 'error' => 'Nama, No. HP, dan pesan wajib diisi.'));
    }
    if (mb_strlen($pesan) > 1000) {
        $pesan = mb_substr($pesan, 0, 1000);
    }
    $stmt = $conn->prepare("INSERT INTO chat (nama, no_hp, kode_pesanan, pesan, dari, dibaca) VALUES (?, ?, ?, ?, 'pelanggan', 0)");
    if (!$stmt) {
        jkeluar(array('ok' => false, 'error' => 'DB error.'));
    }
    $kn = $kode !== '' ? $kode : null;
    $stmt->bind_param('ssss', $nama, $hp, $kn, $pesan);
    $ok = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    jkeluar(array('ok' => $ok, 'id' => $id));
}

// ---- Pelanggan: ambil pesan baru (polling) ----
if ($aksi === 'ambil') {
    $hp   = preg_replace('/[^0-9]/', '', (string)($_GET['no_hp'] ?? ''));
    $last = isset($_GET['last']) ? intval($_GET['last']) : 0;
    if ($hp === '') {
        jkeluar(array('ok' => true, 'rows' => array()));
    }
    $rows = array();
    $stmt = $conn->prepare("SELECT id, nama, pesan, dari, kode_pesanan, created_at FROM chat WHERE no_hp = ? AND id > ? ORDER BY id ASC LIMIT 100");
    if ($stmt) {
        $stmt->bind_param('si', $hp, $last);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
    }
    // Tandai balasan admin sudah dibaca pelanggan
    $conn->query("UPDATE chat SET dibaca = 1 WHERE no_hp = '" . $conn->real_escape_string($hp) . "' AND dari = 'admin'");
    jkeluar(array('ok' => true, 'rows' => $rows));
}

// ---- Berikutnya khusus admin ----
if (!admin_login()) {
    jkeluar(array('ok' => false, 'error' => 'Unauthorized.'));
}

// Jumlah pesan pelanggan yang belum dibaca
if ($aksi === 'unread_admin') {
    $r = q_row("SELECT COUNT(*) j FROM chat WHERE dari = 'pelanggan' AND dibaca = 0");
    jkeluar(array('ok' => true, 'unread' => $r ? (int)$r['j'] : 0));
}

// Daftar percakapan (grup per no HP)
if ($aksi === 'daftar') {
    $rows = array();
    $res = q("SELECT no_hp, MAX(nama) nama, MAX(id) last_id, MAX(created_at) last_at,
              SUM(dari = 'pelanggan' AND dibaca = 0) unread,
              (SELECT pesan FROM chat c2 WHERE c2.no_hp = chat.no_hp ORDER BY id DESC LIMIT 1) last_pesan
              FROM chat GROUP BY no_hp ORDER BY last_id DESC LIMIT 100");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    jkeluar(array('ok' => true, 'rows' => $rows));
}

// Isi thread 1 pelanggan + tandai dibaca
if ($aksi === 'thread') {
    $hp = preg_replace('/[^0-9]/', '', (string)($_GET['no_hp'] ?? ''));
    $rows = array();
    if ($hp !== '') {
        $stmt = $conn->prepare("SELECT id, nama, pesan, dari, kode_pesanan, created_at FROM chat WHERE no_hp = ? ORDER BY id ASC LIMIT 200");
        if ($stmt) {
            $stmt->bind_param('s', $hp);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $stmt->close();
        }
        $conn->query("UPDATE chat SET dibaca = 1 WHERE no_hp = '" . $conn->real_escape_string($hp) . "' AND dari = 'pelanggan'");
    }
    jkeluar(array('ok' => true, 'rows' => $rows));
}

// Admin membalas
if ($aksi === 'balas' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hp    = preg_replace('/[^0-9]/', '', (string)($_POST['no_hp'] ?? ''));
    $pesan = trim((string)($_POST['pesan'] ?? ''));
    $kode  = trim((string)($_POST['kode'] ?? ''));
    if ($hp === '' || $pesan === '') {
        jkeluar(array('ok' => false, 'error' => 'Pesan kosong.'));
    }
    if (mb_strlen($pesan) > 1000) {
        $pesan = mb_substr($pesan, 0, 1000);
    }
    $nama_admin = isset($_SESSION['admin_nama']) ? (string)$_SESSION['admin_nama'] : 'Admin';
    $stmt = $conn->prepare("INSERT INTO chat (nama, no_hp, kode_pesanan, pesan, dari, dibaca) VALUES (?, ?, ?, ?, 'admin', 0)");
    if (!$stmt) {
        jkeluar(array('ok' => false, 'error' => 'DB error.'));
    }
    $kn = $kode !== '' ? $kode : null;
    $stmt->bind_param('ssss', $nama_admin, $hp, $kn, $pesan);
    $ok = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    jkeluar(array('ok' => $ok, 'id' => $id));
}

jkeluar(array('ok' => false, 'error' => 'Aksi tidak dikenal.'));
