<?php
// Lupa Password via Pertanyaan Keamanan (khusus admin).
// Tanpa kode via WhatsApp: jawaban hanya diketahui pemilik akun.
require __DIR__ . '/../config.php';
aman_session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$tahap = 1;           // 1 = username, 2 = jawab pertanyaan + password baru
$info = '';
$error = '';
$username = '';
$pertanyaan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lanjut'])) {
    csrf_wajib();
    $username = trim((string)($_POST['username'] ?? ''));
    $adm = null;
    if ($username !== '') {
        $stmt = $conn->prepare("SELECT username FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $adm = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$adm) {
        // Pesan generik agar username tidak bisa ditebak
        $info = 'Jika username terdaftar dan pertanyaan keamanan sudah diatur, lanjutkan ke verifikasi.';
        $tahap = 2;
        $pertanyaan = '';
    } else {
        $q = get_setting('recovery_q', '');
        $a = get_setting('recovery_a', '');
        if ($q === '' || $a === '') {
            $error = 'Pertanyaan keamanan belum diatur. Reset manual via phpMyAdmin (tabel admin).';
        } else {
            $tahap = 2;
            $pertanyaan = $q;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi'])) {
    csrf_wajib();
    $tahap = 2;
    $username = trim((string)($_POST['username'] ?? ''));
    $jawaban  = trim((string)($_POST['jawaban'] ?? ''));
    $baru     = (string)($_POST['pw_baru'] ?? '');
    $konf     = (string)($_POST['pw_konf'] ?? '');
    $pertanyaan = get_setting('recovery_q', '');

    // Rate limit jawaban: maks 5x salah per 15 menit
    $kunci = 'reset:' . $username;
    if (!boleh_login($kunci)) {
        $error = 'Terlalu banyak percobaan salah. Coba lagi dalam 15 menit.';
        catat('reset_terkunci', $username);
    } else {
        $cocok = false;
        $adm = null;
        if ($username !== '') {
            $stmt = $conn->prepare("SELECT username FROM admin WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $adm = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        $a_hash = get_setting('recovery_a', '');
        if ($adm && $a_hash !== '' && $jawaban !== '' && password_verify($jawaban, $a_hash)) {
            $cocok = true;
        } else {
            catat_gagal($kunci);
        }
        if (!$cocok) {
            $error = 'Username atau jawaban salah.';
            catat('reset_gagal', $username);
        } elseif (strlen($baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($baru !== $konf) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            $hash = password_hash($baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
            $stmt->bind_param('ss', $hash, $username);
            $stmt->execute();
            $stmt->close();
            reset_gagal($kunci);
            reset_gagal($username);
            catat('reset_sukses', $username);
            header('Location: login.php?reset=ok');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Percetakan Pojok Indah</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>">
</head>
<body class="auth-page<?= $APP_TEMA === 'terang' ? ' terang' : '' ?>">
<script src="../assets/js/tema.js?v=<?= filemtime('../assets/js/tema.js') ?>"></script>
<script>ppiTemaAwal();document.addEventListener('DOMContentLoaded',ppiTemaCat);</script>

<div class="auth-box">
    <a href="../index.php" class="logo auth-logo">
        <img src="../assets/img/logo.svg" alt="Percetakan Pojok Indah" class="logo-img">
        <span class="logo-text">Percetakan <span>Pojok Indah</span></span>
    </a>
    <h2>Lupa Password</h2>
    <p>Verifikasi pertanyaan keamanan</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($info): ?>
        <div class="alert alert-success"><?= htmlspecialchars($info) ?></div>
    <?php endif; ?>

    <?php if ($tahap === 1): ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Username Admin</label>
                <input type="text" name="username" class="form-control" placeholder="Username" required value="<?= htmlspecialchars($username) ?>">
            </div>
            <button type="submit" name="lanjut" class="btn btn-primary btn-block">Lanjut</button>
        </form>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
            <div class="form-group">
                <label><?= $pertanyaan !== '' ? htmlspecialchars($pertanyaan) : 'Jawaban Keamanan' ?></label>
                <input type="text" name="jawaban" class="form-control" placeholder="Jawaban rahasia" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password Baru (min. 6 karakter)</label>
                <input type="password" name="pw_baru" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="pw_konf" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
            <button type="submit" name="verifikasi" class="btn btn-primary btn-block">🔑 Reset Password</button>
        </form>
        <p style="text-align:center;margin-top:14px;font-size:0.85rem"><a href="lupa_password.php" style="color:var(--cyan)">← Ulangi dari awal</a></p>
    <?php endif; ?>

    <p style="text-align:center;margin-top:14px;font-size:0.85rem"><a href="login.php" style="color:var(--cyan)">Kembali ke Login</a></p>
</div>

</body>
</html>