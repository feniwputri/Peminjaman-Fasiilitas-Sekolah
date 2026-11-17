-- DATA DUMMY untuk db_peminjaman_fasilitas
-- Import setelah db_peminjaman_fasilitas.sql
USE db_peminjaman_fasilitas;

-- Akun demo: password semua akun = Demo12345!
-- Gunakan password_verify() di PHP untuk memeriksa password.
INSERT INTO users (id_user, nama, username, password, role) VALUES
(1, 'Administrator Sekolah', 'admin', '$2y$12$9VrMbJUIOvMFe9n28GDU.u7aOpLK7qLOBouT2Bd7u.jmrkDKmxEvK', 'admin'),
(2, 'Andi Saputra', 'andi', '$2y$12$9VrMbJUIOvMFe9n28GDU.u7aOpLK7qLOBouT2Bd7u.jmrkDKmxEvK', 'guru'),
(3, 'Siti Rahmawati', 'siti', '$2y$12$9VrMbJUIOvMFe9n28GDU.u7aOpLK7qLOBouT2Bd7u.jmrkDKmxEvK', 'guru'),
(4, 'Budi Santoso', 'budi', '$2y$12$9VrMbJUIOvMFe9n28GDU.u7aOpLK7qLOBouT2Bd7u.jmrkDKmxEvK', 'guru');

INSERT INTO kategori (id_kategori, nama_kategori, deskripsi) VALUES
(1, 'Elektronik', 'Perangkat elektronik sekolah'),
(2, 'Peralatan Presentasi', 'Peralatan untuk kegiatan presentasi'),
(3, 'Pakaian Adat dan Tari', 'Kostum tari berdasarkan budaya dan ukuran'),
(4, 'Peralatan Olahraga', 'Peralatan kegiatan olahraga'),
(5, 'Peralatan Kegiatan', 'Peralatan pendukung kegiatan sekolah');

-- 10 data fasilitas dummy
INSERT INTO fasilitas (id_fasilitas, id_kategori, nama_fasilitas, merek, tipe, deskripsi, foto) VALUES
(1, 1, 'Laptop', 'Lenovo', 'ThinkPad E14', 'Laptop untuk kegiatan pembelajaran dan administrasi', 'uploads/fasilitas/laptop-lenovo.jpg'),
(2, 1, 'Kamera Digital', 'Canon', 'EOS 1500D', 'Kamera untuk dokumentasi kegiatan sekolah', 'uploads/fasilitas/kamera-canon.jpg'),
(3, 2, 'Proyektor', 'Epson', 'EB-X500', 'Proyektor untuk presentasi kelas', 'uploads/fasilitas/proyektor-epson.jpg'),
(4, 2, 'Layar Proyektor', 'Generic', 'Tripod Screen', 'Layar untuk menampilkan materi presentasi', 'uploads/fasilitas/layar-proyektor.jpg'),
(5, 3, 'Pakaian Tari', NULL, NULL, 'Kostum tari tradisional Melayu', 'uploads/fasilitas/pakaian-tari-melayu.jpg'),
(6, 3, 'Pakaian Tari', NULL, NULL, 'Kostum tari tradisional Dayak', 'uploads/fasilitas/pakaian-tari-dayak.jpg'),
(7, 4, 'Bola Basket', 'Molten', 'BG2000', 'Bola untuk kegiatan olahraga basket', 'uploads/fasilitas/bola-basket.jpg'),
(8, 4, 'Bola Voli', 'Mikasa', 'MVA300', 'Bola untuk kegiatan olahraga voli', 'uploads/fasilitas/bola-voli.jpg'),
(9, 5, 'Mikrofon', 'Shure', 'SV100', 'Mikrofon untuk kegiatan sekolah', 'uploads/fasilitas/mikrofon.jpg'),
(10, 5, 'Speaker Aktif', 'Polytron', 'PAS 8C', 'Speaker untuk kegiatan sekolah', 'uploads/fasilitas/speaker.jpg');

-- Barang tanpa variasi khusus memakai jenis_budaya dan ukuran NULL.
-- Stok tersedia pada contoh ini sudah memperhitungkan peminjaman berstatus disetujui.
INSERT INTO variasi_fasilitas (id_variasi, id_fasilitas, jenis_budaya, ukuran, stok_total, stok_tersedia) VALUES
(1, 1, NULL, NULL, 5, 4),
(2, 2, NULL, NULL, 2, 2),
(3, 3, NULL, NULL, 3, 3),
(4, 4, NULL, NULL, 3, 3),
(5, 5, 'Melayu', 'S', 5, 5),
(6, 5, 'Melayu', 'M', 8, 6),
(7, 5, 'Melayu', 'L', 4, 4),
(8, 6, 'Dayak', 'M', 6, 6),
(9, 7, NULL, NULL, 6, 6),
(10, 8, NULL, NULL, 6, 6),
(11, 9, NULL, NULL, 4, 4),
(12, 10, NULL, NULL, 3, 3);

-- Contoh status: menunggu, disetujui, ditolak, selesai.
INSERT INTO peminjaman
(id_peminjaman, id_user, id_admin, tanggal_pengajuan, tanggal_pinjam, tanggal_kembali, keperluan, status, tanggal_persetujuan, catatan_admin)
VALUES
(1, 2, NULL, '2026-11-15 08:30:00', '2026-11-20', '2026-11-20', 'Presentasi kegiatan kelas', 'menunggu', NULL, NULL),
(2, 3, 1, '2026-11-14 09:00:00', '2026-11-18', '2026-11-19', 'Kegiatan pembelajaran dan dokumentasi', 'disetujui', '2026-11-14 10:00:00', 'Pengajuan disetujui'),
(3, 4, 1, '2026-11-13 10:15:00', '2026-11-16', '2026-11-16', 'Kegiatan olahraga sekolah', 'ditolak', '2026-11-13 11:00:00', 'Jadwal fasilitas tidak tersedia'),
(4, 2, 1, '2026-11-10 08:00:00', '2026-11-12', '2026-11-12', 'Dokumentasi acara sekolah', 'selesai', '2026-11-10 09:00:00', 'Pengajuan disetujui dan telah dikembalikan');

INSERT INTO detail_peminjaman (id_detail, id_peminjaman, id_variasi, jumlah) VALUES
(1, 1, 3, 1),
(2, 1, 4, 1),
(3, 2, 1, 1),
(4, 2, 6, 2),
(5, 3, 9, 2),
(6, 4, 11, 1),
(7, 4, 10, 1);

INSERT INTO pengembalian (id_pengembalian, id_peminjaman, id_admin, tanggal_pengembalian, catatan) VALUES
(1, 4, 1, '2026-11-12 15:30:00', 'Seluruh barang telah dikembalikan');

INSERT INTO detail_pengembalian
(id_detail_pengembalian, id_pengembalian, id_detail, kondisi, foto_bukti, catatan)
VALUES
(1, 1, 6, 'baik', 'uploads/pengembalian/mikrofon-kembali.jpg', 'Mikrofon kembali dalam kondisi baik'),
(2, 1, 7, 'baik', NULL, 'Speaker kembali dalam kondisi baik');

-- Catatan: data dummy hanya untuk pengembangan/demo.
-- Pastikan folder uploads/fasilitas dan uploads/pengembalian dibuat.
-- File foto contoh belum disediakan; path dapat diganti saat upload foto asli.
