<?php
// Lupa Password via WhatsApp (khusus admin).
// Alur: masukkan username → kode 6 digit dibuat → kirim kode ke WA admin
// via tombol → masukkan kode + password baru. Kedaluwarsa 10 menit.
require __DIR__ . '/../config.php';
aman_session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$tahap = 1;           // 1 = minta kode, 2 = verifikasi + password baru
$info = '';
$error = '';
$username = '';
$wa_link = '';
$wa_tampil = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['minta_kode'])) {
    csrf_wajib();
    $username = bersihkan($_POST['username'] ?? '');
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
        $info = 'Jika username terdaftar, kode reset telah dibuat. Lanjut ke verifikasi bila kamu menerima kode di WhatsApp admin.';
    } else {
        // Batasi: maks 3 kode per jam per username
        $u = $conn->real_escape_string($username);
        $c = q_row("SELECT COUNT(*) j FROM password_reset WHERE username = '$u' AND created_at > (NOW() - INTERVAL 1 HOUR)");
        if ($c && (int)$c['j'] >= 3) {
            $error = 'Terlalu sering meminta kode. Coba lagi 1 jam lagi.';
        } else {
            $conn->query("UPDATE password_reset SET terpakai = 1 WHERE username = '$u' AND terpakai = 0");
            $kode = (string)random_int(100000, 999999);
            $hash = password_hash($kode, PASSWORD_DEFAULT);
            $ip = mb_substr(ip_client(), 0, 45);
            $stmt = $conn->prepare("INSERT INTO password_reset (username, kode_hash, kedaluwarsa, ip) VALUES (?, ?, (NOW() + INTERVAL 10 MINUTE), ?)");
            $stmt->bind_param('sss', $username, $hash, $ip);
            $stmt->execute();
            $stmt->close();
            catat('reset_minta', $username);
            $tahap = 2;
            $no_wa = wa_admin();
            $wa_tampil = $no_wa;
            $teks = 'Halo, saya meminta KODE RESET PASSWORD admin (' . $username . '). Kode saya: *' . $kode . '* (berlaku 10 menit, jangan bagikan ke siapa pun).';
            $wa_link = 'https://wa.me/' . $no_wa . '?text=' . urlencode($teks);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi'])) {
    csrf_wajib();
    $tahap = 2;
    $username = bersihkan($_POST['username'] ?? '');
    $kode_in  = preg_replace('/[^0-9]/', '', (string)($_POST['kode'] ?? ''));
    $baru     = (string)($_POST['pw_baru'] ?? '');
    $konf     = (string)($_POST['pw_konf'] ?? '');
    $no_wa = wa_admin();
    $wa_tampil = $no_wa;
    $wa_link = 'https://wa.me/' . $no_wa;

    $u = $conn->real_escape_string($username);
    $row = q_row("SELECT * FROM password_reset WHERE username = '$u' AND terpakai = 0 ORDER BY id DESC LIMIT 1");
    $valid = false;
    if ($row && strtotime($row['kedaluwarsa']) >= time() && (int)$row['percobaan'] < 5) {
        if ($kode_in !== '' && password_verify($kode_in, (string)$row['kode_hash'])) {
            $valid = true;
        } else {
            $conn->query("UPDATE password_reset SET percobaan = percobaan + 1 WHERE id = " . (int)$row['id']);
        }
    }
    if (!$valid) {
        $error = 'Kode salah, kedaluwarsa, atau sudah dipakai. Minta kode baru bila perlu.';
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
        $conn->query("UPDATE password_reset SET terpakai = 1 WHERE username = '$u' AND terpakai = 0");
        reset_gagal($username);
        catat('reset_sukses', $username);
        header('Location: login.php?reset=ok');
        exit;
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
    <p>Reset via kode WhatsApp admin</p>

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
            <button type="submit" name="minta_kode" class="btn btn-primary btn-block">Buat Kode Reset</button>
        </form>
    <?php else: ?>
        <div class="alert alert-success">
            Kode 6 digit telah dibuat (berlaku <strong>10 menit</strong>). Klik tombol di bawah untuk mengirimkannya ke WhatsApp admin <strong><?= htmlspecialchars($wa_tampil) ?></strong>, lalu masukkan kode yang diterima ke form ini.
        </div>
        <a href="<?= htmlspecialchars($wa_link) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-block" style="background:#22c55e;margin-bottom:16px">📲 Kirim Kode via WhatsApp</a>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
            <div class="form-group">
                <label>Kode 6 Digit dari WhatsApp</label>
                <input type="text" name="kode" class="form-control" inputmode="numeric" maxlength="6" placeholder="Contoh: 482913" required>
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
        <p style="text-align:center;margin-top:14px;font-size:0.85rem"><a href="lupa_password.php" style="color:var(--cyan)">← Minta kode baru</a></p>
    <?php endif; ?>

    <p style="text-align:center;margin-top:14px;font-size:0.85rem"><a href="login.php" style="color:var(--cyan)">Kembali ke Login</a></p>
</div>

</body>
</html>