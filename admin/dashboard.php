<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/layout.php';

/* ---------- Data ringkasan ---------- */

$totalFasilitas = (int) $pdo->query('SELECT COUNT(*) FROM fasilitas')->fetchColumn();

$stok = $pdo->query(
    'SELECT COALESCE(SUM(stok_total), 0) AS total, COALESCE(SUM(stok_tersedia), 0) AS tersedia
     FROM variasi_fasilitas'
)->fetch();
$unitTotal = (int) $stok['total'];
$unitTersedia = (int) $stok['tersedia'];
$unitDipinjam = max(0, $unitTotal - $unitTersedia);
$persenTersedia = persen($unitTersedia, $unitTotal);

$statusCount = ['menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS jumlah FROM peminjaman GROUP BY status') as $row) {
    $statusCount[$row['status']] = (int) $row['jumlah'];
}
$totalPeminjaman = array_sum($statusCount);

$bermasalah = (int) $pdo->query(
    "SELECT COUNT(*) FROM detail_pengembalian WHERE kondisi <> 'baik'"
)->fetchColumn();

/* ---------- Ketersediaan per kategori ---------- */

$kategori = $pdo->query(
    'SELECT k.nama_kategori,
            COUNT(DISTINCT f.id_fasilitas) AS jenis,
            COALESCE(SUM(v.stok_total), 0) AS total,
            COALESCE(SUM(v.stok_tersedia), 0) AS tersedia
     FROM kategori k
     LEFT JOIN fasilitas f ON f.id_kategori = k.id_kategori
     LEFT JOIN variasi_fasilitas v ON v.id_fasilitas = f.id_fasilitas
     GROUP BY k.id_kategori, k.nama_kategori
     ORDER BY k.nama_kategori'
)->fetchAll();

/* ---------- Pengajuan menunggu (perlu tindakan) ---------- */

$menunggu = $pdo->query(
    "SELECT p.id_peminjaman, p.tanggal_pengajuan, p.tanggal_pinjam, p.keperluan, u.nama
     FROM peminjaman p
     JOIN users u ON u.id_user = p.id_user
     WHERE p.status = 'menunggu'
     ORDER BY p.tanggal_pengajuan ASC
     LIMIT 4"
)->fetchAll();

/* ---------- Peminjaman terbaru ---------- */

$terbaru = $pdo->query(
    "SELECT p.id_peminjaman, p.tanggal_pinjam, p.tanggal_kembali, p.status, u.nama,
            (SELECT GROUP_CONCAT(
                        CONCAT(f.nama_fasilitas,
                               IF(v.jenis_budaya IS NULL, '', CONCAT(' ', v.jenis_budaya)),
                               IF(v.ukuran IS NULL, '', CONCAT(' ', v.ukuran)),
                               ' x', d.jumlah)
                        SEPARATOR ', ')
             FROM detail_peminjaman d
             JOIN variasi_fasilitas v ON v.id_variasi = d.id_variasi
             JOIN fasilitas f ON f.id_fasilitas = v.id_fasilitas
             WHERE d.id_peminjaman = p.id_peminjaman) AS barang
     FROM peminjaman p
     JOIN users u ON u.id_user = p.id_user
     ORDER BY p.tanggal_pengajuan DESC
     LIMIT 6"
)->fetchAll();

/* ---------- Stok menipis ---------- */

$menipis = $pdo->query(
    'SELECT f.nama_fasilitas, v.jenis_budaya, v.ukuran, v.stok_total, v.stok_tersedia
     FROM variasi_fasilitas v
     JOIN fasilitas f ON f.id_fasilitas = v.id_fasilitas
     WHERE v.stok_total > 0
       AND v.stok_tersedia <= GREATEST(1, FLOOR(v.stok_total * 0.25))
     ORDER BY (v.stok_tersedia / v.stok_total) ASC, f.nama_fasilitas ASC
     LIMIT 5'
)->fetchAll();

$namaAdmin = (string) $_SESSION['user']['nama'];

layout_start('Dashboard Admin', 'dashboard', 'admin');
?>

<section class="hero">
    <div class="hero-text">
        <p class="hero-eyebrow">AREA ADMIN</p>
        <h2>Halo, <?= e($namaAdmin) ?>!</h2>
        <p>
            <?php if ($statusCount['menunggu'] > 0): ?>
                Ada <strong><?= angka($statusCount['menunggu']) ?> pengajuan</strong> yang menunggu persetujuanmu.
            <?php else: ?>
                Tidak ada pengajuan yang menunggu. Semua sudah beres.
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="stat-grid">
    <article class="stat-card">
        <div class="stat-icon tone-green"><?= icon('box') ?></div>
        <div>
            <p class="stat-label">Jenis Fasilitas</p>
            <p class="stat-value"><?= angka($totalFasilitas) ?></p>
            <p class="stat-sub"><?= angka($unitTotal) ?> unit keseluruhan</p>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon tone-emerald"><?= icon('check') ?></div>
        <div>
            <p class="stat-label">Unit Tersedia</p>
            <p class="stat-value"><?= angka($unitTersedia) ?></p>
            <p class="stat-sub"><?= angka($unitDipinjam) ?> unit sedang dipinjam</p>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon tone-amber"><?= icon('clock') ?></div>
        <div>
            <p class="stat-label">Menunggu Persetujuan</p>
            <p class="stat-value"><?= angka($statusCount['menunggu']) ?></p>
            <p class="stat-sub">dari <?= angka($totalPeminjaman) ?> total pengajuan</p>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon tone-blue"><?= icon('swap') ?></div>
        <div>
            <p class="stat-label">Peminjaman Aktif</p>
            <p class="stat-value"><?= angka($statusCount['disetujui']) ?></p>
            <p class="stat-sub"><?= angka($statusCount['selesai']) ?> selesai, <?= angka($statusCount['ditolak']) ?> ditolak</p>
        </div>
    </article>
</section>

<section class="grid-2">
    <article class="panel">
        <header class="panel-head">
            <h3>Peminjaman Terbaru</h3>
            <span class="panel-note"><?= angka(count($terbaru)) ?> pengajuan terakhir</span>
        </header>

        <?php if (count($terbaru) === 0): ?>
            <p class="empty">Belum ada data peminjaman.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Peminjam</th>
                            <th>Fasilitas</th>
                            <th>Tanggal Pinjam</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terbaru as $row): ?>
                            <tr>
                                <td data-label="Peminjam"><strong><?= e($row['nama']) ?></strong></td>
                                <td data-label="Fasilitas" class="cell-barang"><?= e($row['barang'] ?? '-') ?></td>
                                <td data-label="Tanggal Pinjam">
                                    <?= e(tgl_id($row['tanggal_pinjam'])) ?>
                                    <?php if ($row['tanggal_kembali'] !== $row['tanggal_pinjam']): ?>
                                        <small class="muted">s/d <?= e(tgl_id($row['tanggal_kembali'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status"><?= badge_status((string) $row['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <header class="panel-head">
            <h3>Status Unit</h3>
            <span class="panel-note">Monitoring fasilitas</span>
        </header>

        <div class="donut-wrap">
            <div class="donut" style="--p: <?= $persenTersedia ?>;">
                <div class="donut-center">
                    <strong><?= $persenTersedia ?>%</strong>
                    <span>tersedia</span>
                </div>
            </div>
            <ul class="legend">
                <li><i class="dot dot-green"></i> Tersedia <strong><?= angka($unitTersedia) ?></strong></li>
                <li><i class="dot dot-amber"></i> Dipinjam <strong><?= angka($unitDipinjam) ?></strong></li>
                <li><i class="dot dot-red"></i> Kembali bermasalah <strong><?= angka($bermasalah) ?></strong></li>
            </ul>
        </div>
    </article>
</section>

<section class="grid-3">
    <article class="panel">
        <header class="panel-head">
            <h3>Ketersediaan per Kategori</h3>
        </header>

        <?php if (count($kategori) === 0): ?>
            <p class="empty">Belum ada kategori.</p>
        <?php else: ?>
            <ul class="bar-list">
                <?php foreach ($kategori as $row): ?>
                    <?php $pct = persen((int) $row['tersedia'], (int) $row['total']); ?>
                    <li>
                        <div class="bar-head">
                            <span><?= e($row['nama_kategori']) ?>
                                <small class="muted">(<?= angka((int) $row['jenis']) ?> jenis)</small>
                            </span>
                            <strong><?= angka((int) $row['tersedia']) ?>/<?= angka((int) $row['total']) ?></strong>
                        </div>
                        <div class="bar"><span style="width: <?= $pct ?>%"></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="panel">
        <header class="panel-head">
            <h3>Perlu Persetujuan</h3>
            <span class="count-pill"><?= angka($statusCount['menunggu']) ?></span>
        </header>

        <?php if (count($menunggu) === 0): ?>
            <p class="empty">Tidak ada pengajuan menunggu.</p>
        <?php else: ?>
            <ul class="task-list">
                <?php foreach ($menunggu as $row): ?>
                    <li>
                        <span class="avatar avatar-sm"><?= e(inisial((string) $row['nama'])) ?></span>
                        <div>
                            <strong><?= e($row['nama']) ?></strong>
                            <p><?= e($row['keperluan']) ?></p>
                            <small class="muted">Pinjam <?= e(tgl_id($row['tanggal_pinjam'])) ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="panel">
        <header class="panel-head">
            <h3>Stok Menipis</h3>
            <span class="panel-note">Sisa 25% atau kurang</span>
        </header>

        <?php if (count($menipis) === 0): ?>
            <p class="empty">Semua stok masih aman.</p>
        <?php else: ?>
            <ul class="task-list">
                <?php foreach ($menipis as $row): ?>
                    <?php
                    $label = $row['nama_fasilitas'];
                    if ($row['jenis_budaya'] !== null) {
                        $label .= ' ' . $row['jenis_budaya'];
                    }
                    if ($row['ukuran'] !== null) {
                        $label .= ' ' . $row['ukuran'];
                    }
                    ?>
                    <li>
                        <span class="stat-icon tone-red icon-sm"><?= icon('alert') ?></span>
                        <div>
                            <strong><?= e($label) ?></strong>
                            <p>Sisa <?= angka((int) $row['stok_tersedia']) ?> dari <?= angka((int) $row['stok_total']) ?> unit</p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<?php layout_end(); ?>
