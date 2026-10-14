<?php
require __DIR__ . '/../config.php';
aman_session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = bersihkan($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!boleh_login($username)) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.';
        catat('login_terkunci', $username);
    } else {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            reset_gagal($username);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];
            $_SESSION['last_aktif'] = time();
            catat('login_sukses', $username);
            header('Location: index.php');
            exit;
        } else {
            catat_gagal($username);
            catat('login_gagal', $username);
            $error = 'Username atau password salah!';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Percetakan Pojok Indah</title>
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
    <h2>Login Admin</h2>
    <p>Masuk untuk mengelola toko</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['idle'])): ?>
        <div class="alert alert-danger">Sesi berakhir karena tidak aktif. Silakan login kembali.</div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>
</div>

</body>
</html>