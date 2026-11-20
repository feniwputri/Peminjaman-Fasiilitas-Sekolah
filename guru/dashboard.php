<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_role('guru');

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/layout.php';

$idUser = (int) $_SESSION['user']['id'];
$namaGuru = (string) $_SESSION['user']['nama'];

/* ---------- Ringkasan pengajuan milik guru ---------- */

$statusCount = ['menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
$stmt = $pdo->prepare('SELECT status, COUNT(*) AS jumlah FROM peminjaman WHERE id_user = :id GROUP BY status');
$stmt->execute(['id' => $idUser]);
foreach ($stmt->fetchAll() as $row) {
    $statusCount[$row['status']] = (int) $row['jumlah'];
}
$totalPengajuan = array_sum($statusCount);

/* ---------- Ketersediaan fasilitas ---------- */

$fasilitas = $pdo->query(
    'SELECT f.id_fasilitas, f.nama_fasilitas, f.merek, f.tipe, k.nama_kategori,
            GROUP_CONCAT(DISTINCT v.jenis_budaya ORDER BY v.jenis_budaya SEPARATOR ", ") AS budaya,
            COALESCE(SUM(v.stok_total), 0) AS total,
            COALESCE(SUM(v.stok_tersedia), 0) AS tersedia
     FROM fasilitas f
     JOIN kategori k ON k.id_kategori = f.id_kategori
     LEFT JOIN variasi_fasilitas v ON v.id_fasilitas = f.id_fasilitas
     GROUP BY f.id_fasilitas, f.nama_fasilitas, f.merek, f.tipe, k.nama_kategori
     ORDER BY k.nama_kategori, f.nama_fasilitas'
)->fetchAll();

$kategoriList = array_values(array_unique(array_column($fasilitas, 'nama_kategori')));

/* ---------- Pinjaman terbaru milik guru ---------- */

$stmt = $pdo->prepare(
    "SELECT p.id_peminjaman, p.tanggal_pinjam, p.tanggal_kembali, p.status, p.keperluan, p.catatan_admin,
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
     WHERE p.id_user = :id
     ORDER BY p.tanggal_pengajuan DESC
     LIMIT 5"
);
$stmt->execute(['id' => $idUser]);
$pinjamanSaya = $stmt->fetchAll();

layout_start('Dashboard Guru', 'dashboard', 'guru');
?>

<section class="hero hero-guru">
    <div class="hero-text">
        <p class="hero-eyebrow">AREA GURU</p>
        <h2>Selamat datang, <?= e($namaGuru) ?>!</h2>
        <p>Cek ketersediaan fasilitas sekolah dan pantau status pengajuan peminjamanmu di sini.</p>
        <div class="hero-actions">
            <span class="btn btn-light is-soon" title="Halaman ini segera hadir">
                <?= icon('plus') ?> Ajukan Peminjaman
            </span>
            <a class="btn btn-ghost" href="#ketersediaan"><?= icon('search') ?> Cek Ketersediaan</a>
        </div>
    </div>
</section>

<section class="stat-grid">
    <article class="stat-card">
        <div class="stat-icon tone-green"><?= icon('layers') ?></div>
        <div>
            <p class="stat-label">Total Pengajuan</p>
            <p class="stat-value"><?= angka($totalPengajuan) ?></p>
            <p class="stat-sub">seluruh pengajuanmu</p>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon tone-amber"><?= icon('clock') ?></div>
        <div>
            <p class="stat-label">Menunggu</p>
            <p class="stat-value"><?= angka($statusCount['menunggu']) ?></p>
            <p class="stat-sub">belum diproses admin</p>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon tone-emerald"><?= icon('check') ?></div>
        <div>
            <p class="stat-label">Disetujui</p>
            <p class="stat-value"><?= angka($statusCount['disetujui']) ?></p>
            <p class="stat-sub">sedang kamu pinjam</p>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon tone-blue"><?= icon('history') ?></div>
        <div>
            <p class="stat-label">Selesai</p>
            <p class="stat-value"><?= angka($statusCount['selesai']) ?></p>
            <p class="stat-sub"><?= angka($statusCount['ditolak']) ?> pengajuan ditolak</p>
        </div>
    </article>
</section>

<section class="panel" id="ketersediaan">
    <header class="panel-head panel-head-wrap">
        <div>
            <h3>Ketersediaan Fasilitas</h3>
            <span class="panel-note">Stok tersedia saat ini</span>
        </div>
        <div class="filters">
            <label class="search-box">
                <?= icon('search') ?>
                <input type="search" id="cariFasilitas" placeholder="Cari fasilitas..." autocomplete="off">
            </label>
            <select id="filterKategori" aria-label="Filter kategori">
                <option value="">Semua kategori</option>
                <?php foreach ($kategoriList as $nama): ?>
                    <option value="<?= e((string) $nama) ?>"><?= e((string) $nama) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </header>

    <?php if (count($fasilitas) === 0): ?>
        <p class="empty">Belum ada data fasilitas.</p>
    <?php else: ?>
        <div class="fas-grid" id="fasGrid">
            <?php foreach ($fasilitas as $row): ?>
                <?php
                $tersedia = (int) $row['tersedia'];
                $total = (int) $row['total'];
                $pct = persen($tersedia, $total);

                if ($tersedia <= 0) {
                    $kelas = 'habis';
                    $teks = 'Habis';
                } elseif ($pct <= 25) {
                    $kelas = 'menipis';
                    $teks = 'Terbatas';
                } else {
                    $kelas = 'ada';
                    $teks = 'Tersedia';
                }

                $judul = $row['nama_fasilitas'];
                if ($row['budaya'] !== null && $row['budaya'] !== '') {
                    $judul .= ' ' . $row['budaya'];
                }

                $detail = trim(((string) $row['merek']) . ' ' . ((string) $row['tipe']));
                ?>
                <article class="fas-card" data-nama="<?= e(mb_strtolower($judul . ' ' . $detail)) ?>"
                         data-kategori="<?= e((string) $row['nama_kategori']) ?>">
                    <div class="fas-top">
                        <span class="fas-cat"><?= e($row['nama_kategori']) ?></span>
                        <span class="badge badge-<?= $kelas ?>"><?= e($teks) ?></span>
                    </div>
                    <h4><?= e($judul) ?></h4>
                    <p class="fas-detail"><?= $detail !== '' ? e($detail) : '&nbsp;' ?></p>
                    <div class="bar"><span class="bar-<?= $kelas ?>" style="width: <?= $pct ?>%"></span></div>
                    <p class="fas-stok"><strong><?= angka($tersedia) ?></strong> dari <?= angka($total) ?> unit tersedia</p>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="empty is-hidden" id="fasKosong">Tidak ada fasilitas yang cocok dengan pencarianmu.</p>
    <?php endif; ?>
</section>

<section class="panel">
    <header class="panel-head">
        <h3>Pinjaman Terbaru Saya</h3>
        <span class="panel-note"><?= angka(count($pinjamanSaya)) ?> pengajuan terakhir</span>
    </header>

    <?php if (count($pinjamanSaya) === 0): ?>
        <p class="empty">Kamu belum pernah mengajukan peminjaman.</p>
    <?php else: ?>
        <ul class="loan-list">
            <?php foreach ($pinjamanSaya as $row): ?>
                <li>
                    <div class="loan-main">
                        <strong><?= e($row['barang'] ?? '-') ?></strong>
                        <p><?= e($row['keperluan']) ?></p>
                        <?php if ($row['status'] === 'ditolak' && $row['catatan_admin'] !== null && $row['catatan_admin'] !== ''): ?>
                            <p class="loan-note">Catatan admin: <?= e($row['catatan_admin']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="loan-meta">
                        <span class="loan-date">
                            <?= icon('calendar') ?>
                            <?= e(tgl_id($row['tanggal_pinjam'])) ?>
                            <?php if ($row['tanggal_kembali'] !== $row['tanggal_pinjam']): ?>
                                - <?= e(tgl_id($row['tanggal_kembali'])) ?>
                            <?php endif; ?>
                        </span>
                        <?= badge_status((string) $row['status']) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<script>
(function () {
    var cari = document.getElementById('cariFasilitas');
    var kategori = document.getElementById('filterKategori');
    var cards = document.querySelectorAll('#fasGrid .fas-card');
    var kosong = document.getElementById('fasKosong');
    if (!cari || !kategori || !kosong) { return; }

    function terapkan() {
        var kata = cari.value.trim().toLowerCase();
        var kat = kategori.value;
        var tampil = 0;

        cards.forEach(function (card) {
            var cocokNama = card.dataset.nama.indexOf(kata) !== -1;
            var cocokKat = kat === '' || card.dataset.kategori === kat;
            var show = cocokNama && cocokKat;
            card.classList.toggle('is-hidden', !show);
            if (show) { tampil++; }
        });

        kosong.classList.toggle('is-hidden', tampil !== 0);
    }

    cari.addEventListener('input', terapkan);
    kategori.addEventListener('change', terapkan);
})();
</script>

<?php layout_end(); ?>
