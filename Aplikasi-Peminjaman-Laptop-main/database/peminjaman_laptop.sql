-- ========================================================
-- DATABASE SKEMA: SISTEM PEMINJAMAN LAPTOP SEKOLAH
-- Disesuaikan dengan Jobsheet RPL & ERD Relasional
-- ========================================================

CREATE DATABASE IF NOT EXISTS `db_peminjaman_laptop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_peminjaman_laptop`;

-- 1. TABEL USERS (Autentikasi Multi-Role)
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'petugas', 'siswa') NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. TABEL SISWA
CREATE TABLE IF NOT EXISTS `siswa` (
  `id_siswa` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NULL,
  `nis` VARCHAR(20) NOT NULL UNIQUE,
  `nama_siswa` VARCHAR(100) NOT NULL,
  `kelas` VARCHAR(20) NOT NULL,
  `jurusan` VARCHAR(30) NOT NULL,
  `no_hp` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `photo` LONGTEXT DEFAULT NULL,
  `status` ENUM('Aktif', 'Nonaktif') DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3. TABEL PETUGAS / LABORAN
CREATE TABLE IF NOT EXISTS `petugas` (
  `id_petugas` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NULL,
  `nip` VARCHAR(30) NOT NULL UNIQUE,
  `nama_petugas` VARCHAR(100) NOT NULL,
  `role` ENUM('Admin', 'Laboran') DEFAULT 'Laboran',
  `status` ENUM('Aktif', 'Nonaktif') DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 4. TABEL LAPTOP (Inventaris Aset)
CREATE TABLE IF NOT EXISTS `laptop` (
  `id_laptop` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_aset` VARCHAR(30) NOT NULL UNIQUE,
  `nama_unit` VARCHAR(100) NOT NULL,
  `merk_tipe` VARCHAR(100) NOT NULL,
  `nomor_seri` VARCHAR(50) NOT NULL,
  `processor` VARCHAR(50) NOT NULL,
  `ram` VARCHAR(20) NOT NULL,
  `storage` VARCHAR(30) NOT NULL,
  `kondisi` ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Perlu diperiksa') DEFAULT 'Baik',
  `status_ketersediaan` ENUM('Tersedia', 'Dipinjam', 'Maintenance', 'Rusak') DEFAULT 'Tersedia',
  `lokasi` VARCHAR(100) DEFAULT 'Lab RPL',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. TABEL PEMINJAMAN
CREATE TABLE IF NOT EXISTS `peminjaman` (
  `id_peminjaman` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_peminjaman` VARCHAR(30) NOT NULL UNIQUE,
  `id_siswa` INT NOT NULL,
  `id_laptop` INT NOT NULL,
  `id_petugas` INT NULL,
  `tanggal_pengajuan` DATE NOT NULL,
  `tanggal_pinjam` DATE DEFAULT NULL,
  `tanggal_rencana_kembali` DATE NOT NULL,
  `tanggal_dikembalikan` DATE DEFAULT NULL,
  `keperluan` TEXT NOT NULL,
  `status` ENUM('Menunggu', 'Dipinjam', 'Dikembalikan', 'Ditolak') DEFAULT 'Menunggu',
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_siswa`) REFERENCES `siswa`(`id_siswa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`id_laptop`) REFERENCES `laptop`(`id_laptop`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`id_petugas`) REFERENCES `petugas`(`id_petugas`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. TABEL PENGEMBALIAN & PENGECEKAN KONDISI
CREATE TABLE IF NOT EXISTS `pengembalian` (
  `id_pengembalian` INT AUTO_INCREMENT PRIMARY KEY,
  `id_peminjaman` INT NOT NULL,
  `id_petugas` INT NULL,
  `tanggal_kembali` DATE NOT NULL,
  `kondisi_akhir` ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Perlu diperiksa') NOT NULL,
  `catatan_kerusakan` TEXT DEFAULT NULL,
  `denda` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman`(`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_petugas`) REFERENCES `petugas`(`id_petugas`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ========================================================
-- DATA SEED AWAL DENGAN HASH PASSWORD
-- ========================================================

-- Akun demo (password_hash default: admin123, petugas123, siswa123)
INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `nama`) VALUES
(1, 'admin', '$2y$10$wH2ZfX3jKlm3KxW3Y1OveuvYyv6lB6sW3/7.9xY47.4i0X6qZJj0q', 'admin', 'Administrator Lab'),
(2, 'petugas', '$2y$10$QW7K8V2x2O9eZ47aB9tJt.i29WzL6N8Gj5eW9L5mQ8uI7b3mH2.uW', 'petugas', 'Budi Santoso, S.Kom'),
(3, 'siswa', '$2y$10$eO7T1X7u7Z2wG9zF9rP3d.u16TzJ4N5Dk3bV8L3mS7uI6a1mG1.eS', 'siswa', 'Andi Pratama')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Data Siswa
INSERT INTO `siswa` (`id_siswa`, `id_user`, `nis`, `nama_siswa`, `kelas`, `jurusan`, `no_hp`, `alamat`, `status`) VALUES
(1, 3, '2026001', 'Andi Pratama', 'XII RPL 1', 'RPL', '081234567891', 'Jl. Merdeka No. 12', 'Aktif'),
(2, NULL, '2026002', 'Siti Aulia', 'XII RPL 1', 'RPL', '081234567892', 'Jl. Pemuda No. 45', 'Aktif'),
(3, NULL, '2026003', 'Rizky Maulana', 'XII RPL 2', 'RPL', '081234567893', 'Jl. Sudirman No. 88', 'Aktif'),
(4, NULL, '2026004', 'Nadia Putri', 'XII RPL 2', 'RPL', '081234567894', 'Jl. Kartini No. 19', 'Aktif'),
(5, NULL, '2026005', 'Fajar Hidayat', 'XII TKJ 1', 'TKJ', '081234567895', 'Jl. Diponegoro No. 05', 'Aktif')
ON DUPLICATE KEY UPDATE `nis`=`nis`;

-- Data Petugas
INSERT INTO `petugas` (`id_petugas`, `id_user`, `nip`, `nama_petugas`, `role`, `status`) VALUES
(1, 2, '1989001', 'Budi Santoso, S.Kom', 'Laboran', 'Aktif'),
(2, NULL, '1989002', 'Dewi Lestari, A.Md', 'Laboran', 'Aktif')
ON DUPLICATE KEY UPDATE `nip`=`nip`;

-- Data Laptop
INSERT INTO `laptop` (`id_laptop`, `kode_aset`, `nama_unit`, `merk_tipe`, `nomor_seri`, `processor`, `ram`, `storage`, `kondisi`, `status_ketersediaan`, `lokasi`) VALUES
(1, 'LT001', 'Laptop 01', 'Lenovo ThinkPad E14', 'SN-LN-99201', 'Core i5-1135G7', '8 GB', '256 GB SSD', 'Baik', 'Tersedia', 'Lab RPL'),
(2, 'LT002', 'Laptop 02', 'Acer Aspire 5', 'SN-AC-88120', 'Core i5-1135G7', '8 GB', '512 GB SSD', 'Baik', 'Dipinjam', 'Lab RPL'),
(3, 'LT003', 'Laptop 03', 'ASUS VivoBook 14', 'SN-AS-77312', 'Core i3-1115G4', '8 GB', '256 GB SSD', 'Baik', 'Tersedia', 'Lab RPL'),
(4, 'LT004', 'Laptop 04', 'HP 14s', 'SN-HP-66431', 'Core i5-10210U', '8 GB', '512 GB SSD', 'Perlu diperiksa', 'Maintenance', 'Ruang Teknisi'),
(5, 'LT005', 'Laptop 05', 'Lenovo IdeaPad Slim 3', 'SN-LN-55214', 'Ryzen 5 5500U', '8 GB', '512 GB SSD', 'Baik', 'Tersedia', 'Lab RPL'),
(6, 'LT006', 'Laptop 06', 'Acer TravelMate', 'SN-AC-44109', 'Core i5-1035G1', '8 GB', '256 GB SSD', 'Rusak Berat', 'Rusak', 'Ruang Teknisi'),
(7, 'LT007', 'Laptop 07', 'ASUS ExpertBook', 'SN-AS-33219', 'Core i5-1135G7', '16 GB', '512 GB SSD', 'Baik', 'Tersedia', 'Lab RPL')
ON DUPLICATE KEY UPDATE `kode_aset`=`kode_aset`;

-- Data Peminjaman
INSERT INTO `peminjaman` (`id_peminjaman`, `kode_peminjaman`, `id_siswa`, `id_laptop`, `id_petugas`, `tanggal_pengajuan`, `tanggal_pinjam`, `tanggal_rencana_kembali`, `tanggal_dikembalikan`, `keperluan`, `status`, `catatan`) VALUES
(1, 'PJM001', 1, 2, 1, '2026-09-10', '2026-09-11', '2026-09-18', NULL, 'Praktik pemrograman framework Laravel', 'Dipinjam', 'Unit dipinjam lengkap dengan charger'),
(2, 'PJM002', 2, 3, NULL, '2026-09-14', NULL, '2026-09-20', NULL, 'Pengerjaan tugas proyek RPL', 'Menunggu', ''),
(3, 'PJM003', 3, 1, 1, '2026-08-20', '2026-08-21', '2026-08-25', '2026-08-25', 'Uji coba aplikasi web lokal', 'Dikembalikan', 'Kondisi baik, charger lengkap'),
(4, 'PJM004', 4, 6, 1, '2026-08-28', NULL, '2026-09-02', NULL, 'Praktik instalasi sistem operasi', 'Ditolak', 'Laptop sedang mengalami kerusakan hardware')
ON DUPLICATE KEY UPDATE `kode_peminjaman`=`kode_peminjaman`;

-- Data Pengembalian
INSERT INTO `pengembalian` (`id_pengembalian`, `id_peminjaman`, `id_petugas`, `tanggal_kembali`, `kondisi_akhir`, `catatan_kerusakan`, `denda`) VALUES
(1, 3, 1, '2026-08-25', 'Baik', 'Dikembalikan tepat waktu dalam kondisi fisik dan fungsional normal', 0.00)
ON DUPLICATE KEY UPDATE `id_peminjaman`=`id_peminjaman`;
