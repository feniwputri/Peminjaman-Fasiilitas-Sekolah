-- =====================================================
-- DATABASE: db_peminjaman_fasilitas
-- Sistem Informasi Peminjaman dan Monitoring Fasilitas Sekolah
-- MySQL / MariaDB - dapat diimport melalui phpMyAdmin
-- =====================================================

CREATE DATABASE IF NOT EXISTS db_peminjaman_fasilitas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_peminjaman_fasilitas;

-- Hapus tabel jika ingin mengulang import dari awal.
-- Urutan penghapusan mengikuti ketergantungan foreign key.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS detail_pengembalian;
DROP TABLE IF EXISTS pengembalian;
DROP TABLE IF EXISTS detail_peminjaman;
DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS variasi_fasilitas;
DROP TABLE IF EXISTS fasilitas;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS
CREATE TABLE users (
    id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru') NOT NULL DEFAULT 'guru',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. KATEGORI
CREATE TABLE kategori (
    id_kategori INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. FASILITAS
CREATE TABLE fasilitas (
    id_fasilitas INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT UNSIGNED NOT NULL,
    nama_fasilitas VARCHAR(150) NOT NULL,
    merek VARCHAR(100) NULL,
    tipe VARCHAR(100) NULL,
    deskripsi TEXT NULL,
    foto VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fasilitas_kategori
      FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. VARIASI FASILITAS
-- Barang tanpa variasi khusus tetap memiliki satu baris variasi default.
-- Contoh laptop: jenis_budaya dan ukuran NULL.
-- Contoh pakaian tari: isi jenis_budaya dan ukuran.
CREATE TABLE variasi_fasilitas (
    id_variasi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_fasilitas INT UNSIGNED NOT NULL,
    jenis_budaya VARCHAR(100) NULL,
    ukuran VARCHAR(20) NULL,
    stok_total INT UNSIGNED NOT NULL DEFAULT 0,
    stok_tersedia INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_variasi_fasilitas
      FOREIGN KEY (id_fasilitas) REFERENCES fasilitas(id_fasilitas)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_stok_variasi
      CHECK (stok_tersedia <= stok_total)
) ENGINE=InnoDB;

-- 5. PEMINJAMAN
-- id_admin NULL selama pengajuan belum diproses.
CREATE TABLE peminjaman (
    id_peminjaman INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    id_admin INT UNSIGNED NULL,
    tanggal_pengajuan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    keperluan TEXT NOT NULL,
    status ENUM('menunggu', 'disetujui', 'ditolak', 'selesai')
      NOT NULL DEFAULT 'menunggu',
    tanggal_persetujuan DATETIME NULL,
    catatan_admin TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_peminjaman_guru
      FOREIGN KEY (id_user) REFERENCES users(id_user)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_peminjaman_admin
      FOREIGN KEY (id_admin) REFERENCES users(id_user)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 6. DETAIL PEMINJAMAN
-- Satu pengajuan dapat memiliki banyak baris barang.
CREATE TABLE detail_peminjaman (
    id_detail INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT UNSIGNED NOT NULL,
    id_variasi INT UNSIGNED NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    CONSTRAINT fk_detail_peminjaman
      FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_variasi
      FOREIGN KEY (id_variasi) REFERENCES variasi_fasilitas(id_variasi)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_jumlah_pinjam CHECK (jumlah > 0),
    UNIQUE KEY uq_peminjaman_variasi (id_peminjaman, id_variasi)
) ENGINE=InnoDB;

-- 7. PENGEMBALIAN
-- Satu pengajuan memiliki maksimal satu catatan pengembalian.
CREATE TABLE pengembalian (
    id_pengembalian INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT UNSIGNED NOT NULL UNIQUE,
    id_admin INT UNSIGNED NOT NULL,
    tanggal_pengembalian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pengembalian_peminjaman
      FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pengembalian_admin
      FOREIGN KEY (id_admin) REFERENCES users(id_user)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 8. DETAIL PENGEMBALIAN
-- Satu detail peminjaman memiliki maksimal satu detail pengembalian.
CREATE TABLE detail_pengembalian (
    id_detail_pengembalian INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengembalian INT UNSIGNED NOT NULL,
    id_detail INT UNSIGNED NOT NULL UNIQUE,
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat', 'hilang')
      NOT NULL DEFAULT 'baik',
    foto_bukti VARCHAR(255) NULL,
    catatan TEXT NULL,
    CONSTRAINT fk_detail_pengembalian_header
      FOREIGN KEY (id_pengembalian) REFERENCES pengembalian(id_pengembalian)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_pengembalian_detail
      FOREIGN KEY (id_detail) REFERENCES detail_peminjaman(id_detail)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- SELESAI
-- Catatan:
-- 1. Password harus disimpan dari PHP menggunakan password_hash().
-- 2. Login memverifikasi password dengan password_verify().
-- 3. Jangan memasukkan password teks biasa.
-- 4. Stok diperbarui melalui proses PHP saat admin menyetujui
--    dan saat pengembalian dikonfirmasi, bukan melalui input SQL manual.
-- 5. Validasi stok harus dilakukan dalam transaksi database
--    (START TRANSACTION / COMMIT / ROLLBACK).
-- =====================================================
