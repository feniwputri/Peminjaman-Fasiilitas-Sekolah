<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id'], $_SESSION['user']['role']);
}

function redirect_by_role(): never
{
    if (!is_logged_in()) {
        header('Location: /peminjaman_faslitas_sekolah/login.php');
        exit;
    }

    $destination = match ($_SESSION['user']['role']) {
        'admin' => '/peminjaman_faslitas_sekolah/admin/dashboard.php',
        'guru'  => '/peminjaman_faslitas_sekolah/guru/dashboard.php',
        default => null,
    };

    if ($destination === null) {
        $_SESSION = [];
        session_destroy();
        header('Location: /peminjaman_faslitas_sekolah/login.php');
        exit;
    }

    header('Location: ' . $destination);
    exit;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /peminjaman_faslitas_sekolah/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();

    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        exit('403 - Kamu tidak memiliki akses ke halaman ini.');
    }
}
