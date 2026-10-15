<?php
// ============================================================
// SESSION GUARD — pengaman terpusat seluruh halaman folder admin.
//
// CARA PAKAI (baris pertama setiap file admin yang wajib login):
//     require __DIR__ . '/auth.php';
//
// - Otomatis: koneksi DB, session aman, cek login, idle-timeout.
// - Belum login ($_SESSION['admin_id'] / ['admin_logged_in'] kosong)
//   → langsung redirect ke login.php, tidak ada konten yang bocor.
// - JANGAN di-include di halaman publik: login.php, lupa_password.php.
// ============================================================
ob_start();
require __DIR__ . '/../config.php';
aman_session_start();

// 1) Wajib login (dukung kunci lama admin_id & kunci standar admin_logged_in)
if (empty($_SESSION['admin_id']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// 2) Auto-logout bila idle 30 menit
if (isset($_SESSION['last_aktif']) && (time() - (int)$_SESSION['last_aktif'] > 30 * 60)) {
    if (function_exists('catat')) {
        catat('logout_idle');
    }
    session_unset();
    session_destroy();
    header('Location: login.php?idle=1');
    exit;
}
$_SESSION['last_aktif'] = time();
