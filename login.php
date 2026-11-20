<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_by_role();
}

require_once __DIR__ . '/config/koneksi.php';

$error = '';
$username = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Nama tabel/kolom berasal dari konfigurasi lokal, bukan input pengguna.
        $identifiers = [
            USER_TABLE, USER_ID_COLUMN, USER_NAME_COLUMN,
            USER_USERNAME_COLUMN, USER_PASSWORD_COLUMN, USER_ROLE_COLUMN
        ];

        foreach ($identifiers as $identifier) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
                throw new RuntimeException('Konfigurasi nama tabel/kolom tidak valid.');
            }
        }

        $sql = sprintf(
            'SELECT `%s` AS id, `%s` AS nama, `%s` AS username,
                    `%s` AS password_hash, `%s` AS role
             FROM `%s` WHERE `%s` = :username LIMIT 1',
            USER_ID_COLUMN,
            USER_NAME_COLUMN,
            USER_USERNAME_COLUMN,
            USER_PASSWORD_COLUMN,
            USER_ROLE_COLUMN,
            USER_TABLE,
            USER_USERNAME_COLUMN
        );

        $statement = $pdo->prepare($sql);
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        $validRole = $user && in_array($user['role'], ['admin', 'guru'], true);
        $validPassword = $user && password_verify($password, $user['password_hash']);

        if ($validRole && $validPassword) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => $user['id'],
                'nama' => $user['nama'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];

            unset($_SESSION['csrf_token']);
            redirect_by_role();
        }

        $error = 'Username atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Peminjaman Fasilitas Sekolah</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page login-split">
    <div class="login-wrap">
        <aside class="login-hero" aria-hidden="true">
            <p class="hero-eyebrow">SMA NEGERI 2 PONTIANAK</p>
            <h2>Peminjaman &amp; Monitoring Fasilitas Sekolah</h2>
            <p>Ajukan, pantau, dan kelola peminjaman fasilitas sekolah dengan lebih tertib dan transparan.</p>
        </aside>

        <section class="login-panel">
            <main class="login-card has-logo">
                <img class="school-logo" src="assets/img/logo-sman2.png" alt="Logo SMA Negeri 2 Pontianak">
                <p class="eyebrow">SISTEM INFORMASI SEKOLAH</p>
                <h1>Selamat datang</h1>
                <p class="subtitle">Masuk untuk mengelola peminjaman fasilitas sekolah.</p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus
                    >

                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >

                    <button type="submit" class="btn-primary">Masuk</button>
                </form>

                <p class="login-note">Gunakan akun yang telah terdaftar oleh administrator.</p>
            </main>
        </section>
    </div>
</body>
</html>
