<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Menu sidebar per role.
 * Item dengan 'href' => null belum dibuat halamannya (tampil "Segera").
 */
function menu_items(string $role): array
{
    if ($role === 'admin') {
        return [
            ['key' => 'dashboard',  'label' => 'Dashboard',            'icon' => 'dashboard', 'href' => 'dashboard.php'],
            ['key' => 'fasilitas',  'label' => 'Data Fasilitas',       'icon' => 'box',       'href' => null],
            ['key' => 'peminjaman', 'label' => 'Peminjaman',           'icon' => 'swap',      'href' => null],
            ['key' => 'riwayat',    'label' => 'Riwayat Peminjaman',   'icon' => 'history',   'href' => null],
            ['key' => 'monitoring', 'label' => 'Monitoring Fasilitas', 'icon' => 'monitor',   'href' => null],
            ['key' => 'pengguna',   'label' => 'Data Pengguna',        'icon' => 'users',     'href' => null],
            ['key' => 'pengaturan', 'label' => 'Pengaturan',           'icon' => 'settings',  'href' => null],
        ];
    }

    return [
        ['key' => 'dashboard',    'label' => 'Dashboard',           'icon' => 'dashboard', 'href' => 'dashboard.php'],
        ['key' => 'ketersediaan', 'label' => 'Cek Ketersediaan',    'icon' => 'search',    'href' => 'dashboard.php#ketersediaan'],
        ['key' => 'ajukan',       'label' => 'Ajukan Peminjaman',   'icon' => 'plus',      'href' => null],
        ['key' => 'pinjaman',     'label' => 'Pinjaman Saya',       'icon' => 'swap',      'href' => null],
        ['key' => 'riwayat',      'label' => 'Riwayat Saya',        'icon' => 'history',   'href' => null],
        ['key' => 'pengaturan',   'label' => 'Pengaturan',          'icon' => 'settings',  'href' => null],
    ];
}

/**
 * Buka halaman: <html>, sidebar, topbar.
 * Dipanggil dari admin/ dan guru/ (satu tingkat di bawah root).
 */
function layout_start(string $title, string $active, string $role): void
{
    $user = $_SESSION['user'];
    $nama = (string) $user['nama'];
    $roleLabel = $role === 'admin' ? 'Administrator' : 'Guru';
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | Peminjaman Fasilitas Sekolah</title>
    <link rel="icon" type="image/png" href="../assets/img/logo-sman2.png">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<div class="app" id="app">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="../assets/img/logo-sman2.png" alt="Logo SMA Negeri 2 Pontianak">
            <div>
                <strong>SIP Fasilitas</strong>
                <span>SMA Negeri 2 Pontianak</span>
            </div>
        </div>

        <p class="nav-label"><?= $role === 'admin' ? 'MENU ADMIN' : 'MENU GURU' ?></p>
        <nav class="nav" aria-label="Menu utama">
            <?php foreach (menu_items($role) as $item): ?>
                <?php if ($item['href'] === null): ?>
                    <span class="nav-item is-soon" title="Halaman ini segera hadir">
                        <?= icon($item['icon']) ?>
                        <span class="nav-text"><?= e($item['label']) ?></span>
                        <em class="soon-tag">Segera</em>
                    </span>
                <?php else: ?>
                    <a class="nav-item<?= $item['key'] === $active ? ' is-active' : '' ?>"
                       href="<?= e($item['href']) ?>">
                        <?= icon($item['icon']) ?>
                        <span class="nav-text"><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <a class="nav-item nav-logout" href="../logout.php">
            <?= icon('logout') ?>
            <span class="nav-text">Keluar</span>
        </a>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu">
                <?= icon('menu') ?>
            </button>
            <div class="topbar-title">
                <h1><?= e($title) ?></h1>
                <p><?= e(hari_ini()) ?></p>
            </div>
            <div class="user-chip">
                <span class="avatar"><?= e(inisial($nama)) ?></span>
                <span class="user-meta">
                    <strong><?= e($nama) ?></strong>
                    <small><?= e($roleLabel) ?></small>
                </span>
            </div>
        </header>

        <main class="content">
<?php
}

/** Tutup halaman + script kecil untuk sidebar mobile. */
function layout_end(): void
{
    ?>
        </main>
    </div>
</div>
<script>
(function () {
    var app = document.getElementById('app');
    var toggle = document.getElementById('menuToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!app || !toggle) { return; }
    toggle.addEventListener('click', function () { app.classList.toggle('menu-open'); });
    backdrop.addEventListener('click', function () { app.classList.remove('menu-open'); });
})();
</script>
</body>
</html>
<?php
}
