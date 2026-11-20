<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_role('guru');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <main class="dashboard-card">
        <p class="eyebrow">AREA GURU</p>
        <h1>Halo, <?= htmlspecialchars($_SESSION['user']['nama'], ENT_QUOTES, 'UTF-8') ?>!</h1>
        <p>Login sebagai Guru berhasil.</p>
        <a class="btn-link" href="../logout.php">Keluar</a>
    </main>
</body>
</html>
