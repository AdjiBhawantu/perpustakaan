-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 30 Des 2025 pada 02.03
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_perpustakaan_v2`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_Ajukan_Peminjaman` (IN `p_Id_Pengajuan` VARCHAR(50), IN `p_Id_Anggota` VARCHAR(50), IN `p_Id_Buku` VARCHAR(50), IN `p_Tgl_Pinjam` DATE, IN `p_Tgl_Kembali` DATE, IN `p_Catatan` TEXT, OUT `p_Result` VARCHAR(200))   BEGIN
    DECLARE v_Status_Verifikasi VARCHAR(20);
    DECLARE v_Status_Akun VARCHAR(20);
    DECLARE v_Jumlah_Tersedia INT;
    DECLARE v_Durasi INT;
    
    -- Cek status anggota
    SELECT Status_Verifikasi, Status_Akun INTO v_Status_Verifikasi, v_Status_Akun
    FROM Anggota
    WHERE Id_Anggota = p_Id_Anggota;
    
    IF v_Status_Verifikasi != 'Terverifikasi' THEN
        SET p_Result = 'ERROR: Akun belum terverifikasi';
    ELSEIF v_Status_Akun != 'Aktif' THEN
        SET p_Result = 'ERROR: Akun tidak aktif';
    ELSE
        -- Cek ketersediaan buku
        SELECT Jumlah_Tersedia INTO v_Jumlah_Tersedia
        FROM Buku
        WHERE Id_Buku = p_Id_Buku;
        
        IF v_Jumlah_Tersedia < 1 THEN
            SET p_Result = 'ERROR: Buku tidak tersedia';
        ELSE
            SET v_Durasi = DATEDIFF(p_Tgl_Kembali, p_Tgl_Pinjam);
            
            INSERT INTO Pengajuan_Peminjaman (
                Id_Pengajuan, Id_Anggota, Id_Buku, 
                Tgl_Pinjam_Diinginkan, Tgl_Kembali_Diinginkan, 
                Durasi_Hari, Catatan_Anggota
            ) VALUES (
                p_Id_Pengajuan, p_Id_Anggota, p_Id_Buku,
                p_Tgl_Pinjam, p_Tgl_Kembali, 
                v_Durasi, p_Catatan
            );
            
            INSERT INTO Log_Aktivitas (User_Type, User_Id, Aktivitas, Deskripsi)
            VALUES ('Anggota', p_Id_Anggota, 'Pengajuan Peminjaman', 
                    CONCAT('Mengajukan peminjaman buku ', p_Id_Buku));
            
            SET p_Result = 'SUCCESS: Pengajuan berhasil, menunggu persetujuan admin';
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_Setujui_Peminjaman` (IN `p_Id_Pengajuan` VARCHAR(50), IN `p_Id_Admin` VARCHAR(50), IN `p_Id_Peminjaman` VARCHAR(50), IN `p_Status` ENUM('Disetujui','Ditolak'), IN `p_Alasan_Penolakan` TEXT, OUT `p_Result` VARCHAR(200))   BEGIN
    DECLARE v_Status_Pengajuan VARCHAR(20);
    DECLARE v_Id_Anggota VARCHAR(50);
    DECLARE v_Id_Buku VARCHAR(50);
    DECLARE v_Tgl_Pinjam DATE;
    DECLARE v_Tgl_Kembali DATE;
    DECLARE v_Durasi INT;
    DECLARE v_Jumlah_Tersedia INT;
    
    -- Ambil data pengajuan
    SELECT Status_Pengajuan, Id_Anggota, Id_Buku, 
           Tgl_Pinjam_Diinginkan, Tgl_Kembali_Diinginkan, Durasi_Hari
    INTO v_Status_Pengajuan, v_Id_Anggota, v_Id_Buku, 
         v_Tgl_Pinjam, v_Tgl_Kembali, v_Durasi
    FROM Pengajuan_Peminjaman
    WHERE Id_Pengajuan = p_Id_Pengajuan;
    
    IF v_Status_Pengajuan != 'Menunggu' THEN
        SET p_Result = 'ERROR: Pengajuan sudah diproses sebelumnya';
    ELSE
        -- Update status pengajuan
        UPDATE Pengajuan_Peminjaman
        SET Status_Pengajuan = p_Status,
            Alasan_Penolakan = p_Alasan_Penolakan,
            Diproses_Oleh = p_Id_Admin,
            Diproses_At = CURRENT_TIMESTAMP
        WHERE Id_Pengajuan = p_Id_Pengajuan;
        
        IF p_Status = 'Disetujui' THEN
            -- Cek ulang ketersediaan
            SELECT Jumlah_Tersedia INTO v_Jumlah_Tersedia
            FROM Buku WHERE Id_Buku = v_Id_Buku;
            
            IF v_Jumlah_Tersedia < 1 THEN
                UPDATE Pengajuan_Peminjaman
                SET Status_Pengajuan = 'Ditolak',
                    Alasan_Penolakan = 'Buku tidak tersedia saat diproses'
                WHERE Id_Pengajuan = p_Id_Pengajuan;
                
                SET p_Result = 'ERROR: Buku tidak tersedia';
            ELSE
                -- Buat peminjaman
                INSERT INTO Peminjaman (
                    Id_Peminjaman, Id_Pengajuan, Id_Anggota, Id_Buku,
                    Tgl_Pinjam, Tgl_Kembali_Rencana, Durasi_Hari,
                    Dipinjamkan_Oleh
                ) VALUES (
                    p_Id_Peminjaman, p_Id_Pengajuan, v_Id_Anggota, v_Id_Buku,
                    v_Tgl_Pinjam, v_Tgl_Kembali, v_Durasi,
                    p_Id_Admin
                );
                
                SET p_Result = 'SUCCESS: Peminjaman disetujui dan dibuat';
            END IF;
        ELSE
            SET p_Result = 'SUCCESS: Peminjaman ditolak';
        END IF;
        
        INSERT INTO Log_Aktivitas (User_Type, User_Id, Aktivitas, Deskripsi)
        VALUES ('Admin', p_Id_Admin, 'Proses Pengajuan', 
                CONCAT('Memproses pengajuan ', p_Id_Pengajuan, ' dengan status: ', p_Status));
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_Verifikasi_Anggota` (IN `p_Id_Anggota` VARCHAR(50), IN `p_Id_Admin` VARCHAR(50), IN `p_Status` ENUM('Terverifikasi','Ditolak'), IN `p_Alasan_Penolakan` TEXT, OUT `p_Result` VARCHAR(200))   BEGIN
    DECLARE v_Status_Current VARCHAR(20);
    
    SELECT Status_Verifikasi INTO v_Status_Current
    FROM Anggota
    WHERE Id_Anggota = p_Id_Anggota;
    
    IF v_Status_Current != 'Pending' THEN
        SET p_Result = 'ERROR: Anggota sudah diproses sebelumnya';
    ELSE
        UPDATE Anggota
        SET Status_Verifikasi = p_Status,
            Status_Akun = CASE WHEN p_Status = 'Terverifikasi' THEN 'Aktif' ELSE 'Nonaktif' END,
            Alasan_Penolakan = p_Alasan_Penolakan,
            Verified_By = p_Id_Admin,
            Verified_At = CURRENT_TIMESTAMP
        WHERE Id_Anggota = p_Id_Anggota;
        
        INSERT INTO Log_Aktivitas (User_Type, User_Id, Aktivitas, Deskripsi)
        VALUES ('Admin', p_Id_Admin, 'Verifikasi Anggota', 
                CONCAT('Memverifikasi anggota ', p_Id_Anggota, ' dengan status: ', p_Status));
        
        SET p_Result = CONCAT('SUCCESS: Anggota berhasil di', p_Status);
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `Id_Admin` varchar(50) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Nama_Lengkap` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `No_Telepon` varchar(20) DEFAULT NULL,
  `Foto` varchar(255) DEFAULT NULL,
  `Role` enum('Super Admin','Staff') DEFAULT 'Staff',
  `Status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Last_Login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`Id_Admin`, `Username`, `Password`, `Nama_Lengkap`, `Email`, `No_Telepon`, `Foto`, `Role`, `Status`, `Created_At`, `Last_Login`) VALUES
('ADM-001', 'superadmin', '25d55ad283aa400af464c76d713c07ad', 'AdjiBhawantu', 'superadmin@libraryhub.com', '081234567890', 'ADM_1767025611.jpg', 'Super Admin', 'Aktif', '2025-12-20 11:36:26', '2025-12-29 16:46:00'),
('ADM-1767025677', 'intanaja', '3fc0a7acf087f549ac2b266baf94b8b1', 'Intan Dwi Aini Erovand', 'intanaja@gmail.com', '081234567890', 'ADM_1767026498.jpeg', 'Super Admin', 'Aktif', '2025-12-29 16:27:57', '2025-12-29 16:53:41'),
('STF-001', 'staff01', '25d55ad283aa400af464c76d713c07ad', 'Staff Perpustakaan', 'staff@libraryhub.com', '081298765432', NULL, 'Staff', 'Aktif', '2025-12-20 11:36:26', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggota`
--

CREATE TABLE `anggota` (
  `Id_Anggota` varchar(50) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Nama_Lengkap` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `No_Telepon` varchar(20) NOT NULL,
  `Jenis_Kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `Alamat` text NOT NULL,
  `Tanggal_Lahir` date NOT NULL,
  `Foto_KTP` varchar(255) DEFAULT NULL,
  `No_KTP` varchar(20) NOT NULL,
  `Status_Verifikasi` enum('Pending','Terverifikasi','Ditolak') DEFAULT 'Pending',
  `Alasan_Penolakan` text DEFAULT NULL,
  `Verified_By` varchar(50) DEFAULT NULL,
  `Verified_At` timestamp NULL DEFAULT NULL,
  `Status_Akun` enum('Aktif','Nonaktif','Suspend') DEFAULT 'Nonaktif',
  `Tanggal_Daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  `Last_Login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `anggota`
--

INSERT INTO `anggota` (`Id_Anggota`, `Username`, `Password`, `Nama_Lengkap`, `Email`, `No_Telepon`, `Jenis_Kelamin`, `Alamat`, `Tanggal_Lahir`, `Foto_KTP`, `No_KTP`, `Status_Verifikasi`, `Alasan_Penolakan`, `Verified_By`, `Verified_At`, `Status_Akun`, `Tanggal_Daftar`, `Last_Login`) VALUES
('AGT-001', 'anggota01', '25d55ad283aa400af464c76d713c07ad', 'Budi Santoso', 'budi@gmail.com', '085678901234', 'Laki-laki', 'Jl. Merdeka No. 45, Jakarta', '2000-01-01', NULL, '3201234567890001', 'Terverifikasi', NULL, NULL, NULL, 'Aktif', '2025-12-20 11:36:26', NULL),
('AGT-1766904141', 'qwerty', '25d55ad283aa400af464c76d713c07ad', 'Intan', 'intanaja@gmail.com', '', 'Laki-laki', '', '0000-00-00', NULL, '', 'Terverifikasi', NULL, NULL, NULL, 'Aktif', '2025-12-28 06:42:21', '2025-12-29 16:49:45'),
('AGT-1766905306', 'pouye', '4428c6c474502e61151877825bb41961', 'clarissa', 'icajelek@gmail.com', '0897654765321', 'Perempuan', 'SUKARAME', '0000-00-00', NULL, '187134671426478937', 'Terverifikasi', NULL, NULL, NULL, 'Aktif', '2025-12-28 07:01:46', NULL),
('AGT-1766905388', 'INIAIIII', 'bac76b0feb747e3bde11269cf367c97b', 'AI', 'airopiah@gmail.com', '0812345678', 'Perempuan', 'kedaton', '0000-00-00', NULL, '18716545327865743675', 'Terverifikasi', NULL, NULL, NULL, 'Aktif', '2025-12-28 07:03:08', NULL),
('AGT-1766918177', 'pandxy', '3fc0a7acf087f549ac2b266baf94b8b1', 'Adji Bhawantu', 'adjibhawantu22@gmail.com', '08227267217', 'Laki-laki', 'Labuhan Dalam', '0000-00-00', NULL, '187111220805', 'Terverifikasi', NULL, NULL, NULL, 'Aktif', '2025-12-28 10:36:17', '2025-12-29 16:49:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku`
--

CREATE TABLE `buku` (
  `Id_Buku` varchar(50) NOT NULL,
  `Kode_Buku` varchar(50) NOT NULL,
  `Judul` varchar(200) NOT NULL,
  `Penulis` varchar(100) NOT NULL,
  `Penerbit` varchar(100) DEFAULT NULL,
  `Tahun_Terbit` year(4) DEFAULT NULL,
  `ISBN` varchar(20) DEFAULT NULL,
  `Id_Kategori` varchar(50) DEFAULT NULL,
  `Jumlah_Total` int(11) DEFAULT 0,
  `Jumlah_Tersedia` int(11) DEFAULT 0,
  `Jumlah_Dipinjam` int(11) DEFAULT 0,
  `Jumlah_Rusak` int(11) DEFAULT 0,
  `Jumlah_Hilang` int(11) DEFAULT 0,
  `Lokasi_Rak` varchar(50) DEFAULT NULL,
  `Deskripsi` text DEFAULT NULL,
  `Cover_Buku` varchar(255) DEFAULT NULL,
  `Status` enum('Tersedia','Habis','Tidak Tersedia') DEFAULT 'Tersedia',
  `Created_By` varchar(50) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_By` varchar(50) DEFAULT NULL,
  `Updated_At` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `buku`
--

INSERT INTO `buku` (`Id_Buku`, `Kode_Buku`, `Judul`, `Penulis`, `Penerbit`, `Tahun_Terbit`, `ISBN`, `Id_Kategori`, `Jumlah_Total`, `Jumlah_Tersedia`, `Jumlah_Dipinjam`, `Jumlah_Rusak`, `Jumlah_Hilang`, `Lokasi_Rak`, `Deskripsi`, `Cover_Buku`, `Status`, `Created_By`, `Created_At`, `Updated_By`, `Updated_At`) VALUES
('BK-251228232', 'BK-251228232', 'ips', 'ai', 'pidi', '2019', '98756547', 'KAT-251228329', 14, 10, 4, 0, 0, 'Rak B 0-3', '', '1766911814_1236664.jpg', 'Tersedia', 'ADM-001', '2025-12-28 08:30:58', NULL, '2025-12-29 16:46:22'),
('BK-251228329', 'BK-251228329', 'fisika', 'ica', 'gatau', '2018', '123456789O', 'KAT-251228329', 8, 5, 3, 0, 0, 'Rak B 0-3', '', '1766910870_Kaspin_logo - Copy.png', 'Tersedia', 'ADM-001', '2025-12-28 08:23:03', NULL, '2025-12-29 16:46:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `denda`
--

CREATE TABLE `denda` (
  `Id_Denda` varchar(50) NOT NULL,
  `Id_Peminjaman` varchar(50) NOT NULL,
  `Jenis_Denda` enum('Keterlambatan','Kerusakan','Kehilangan') NOT NULL,
  `Jumlah_Hari_Terlambat` int(11) DEFAULT 0,
  `Tarif_Per_Hari` decimal(10,2) DEFAULT 1000.00,
  `Biaya_Kerusakan` decimal(10,2) DEFAULT 0.00,
  `Biaya_Kehilangan` decimal(10,2) DEFAULT 0.00,
  `Total_Denda` decimal(10,2) NOT NULL,
  `Status_Pembayaran` enum('Belum Dibayar','Dibayar Sebagian','Lunas') DEFAULT 'Belum Dibayar',
  `Jumlah_Dibayar` decimal(10,2) DEFAULT 0.00,
  `Sisa_Denda` decimal(10,2) DEFAULT NULL,
  `Catatan` text DEFAULT NULL,
  `Dibuat_Oleh` varchar(50) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `denda`
--

INSERT INTO `denda` (`Id_Denda`, `Id_Peminjaman`, `Jenis_Denda`, `Jumlah_Hari_Terlambat`, `Tarif_Per_Hari`, `Biaya_Kerusakan`, `Biaya_Kehilangan`, `Total_Denda`, `Status_Pembayaran`, `Jumlah_Dibayar`, `Sisa_Denda`, `Catatan`, `Dibuat_Oleh`, `Created_At`) VALUES
('DND-1766917159286', 'PJ-251228321', 'Keterlambatan', 0, 1000.00, 50000.00, 0.00, 50000.00, 'Lunas', 50000.00, 0.00, '', 'ADM-001', '2025-12-28 10:19:19'),
('DND-1766917943788', 'PJ-251228997', 'Keterlambatan', 7, 1000.00, 0.00, 0.00, 7000.00, 'Lunas', 7000.00, 0.00, NULL, 'ADM-001', '2025-12-28 10:32:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_buku`
--

CREATE TABLE `kategori_buku` (
  `Id_Kategori` varchar(50) NOT NULL,
  `Nama_Kategori` varchar(100) NOT NULL,
  `Deskripsi` text DEFAULT NULL,
  `Created_By` varchar(50) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori_buku`
--

INSERT INTO `kategori_buku` (`Id_Kategori`, `Nama_Kategori`, `Deskripsi`, `Created_By`, `Created_At`) VALUES
('KAT-251228329', 'Pelajaran', '', 'ADM-001', '2025-12-28 08:21:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `Id_Log` int(11) NOT NULL,
  `User_Type` enum('Admin','Anggota') NOT NULL,
  `User_Id` varchar(50) NOT NULL,
  `Aktivitas` varchar(255) NOT NULL,
  `Deskripsi` text DEFAULT NULL,
  `IP_Address` varchar(45) DEFAULT NULL,
  `User_Agent` text DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`Id_Log`, `User_Type`, `User_Id`, `Aktivitas`, `Deskripsi`, `IP_Address`, `User_Agent`, `Created_At`) VALUES
(1, 'Anggota', 'AG-1766228932', 'Registrasi', 'Pendaftaran anggota baru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:08:52'),
(2, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:37:12'),
(3, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:54:28'),
(4, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:54:30'),
(5, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 12:03:19'),
(6, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 12:03:21'),
(7, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 13:55:35'),
(8, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 13:55:47'),
(9, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 06:08:53'),
(10, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 06:41:38'),
(11, 'Anggota', 'AGT-1766904141', 'Registrasi', 'Pendaftaran baru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 06:42:21'),
(12, 'Anggota', 'AGT-1766905306', 'Registrasi', 'Pendaftaran anggota baru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:01:46'),
(13, 'Anggota', 'AGT-1766905388', 'Registrasi', 'Pendaftaran anggota baru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:03:08'),
(14, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:03:18'),
(15, 'Admin', 'ADM-001', 'Approve Anggota', 'Memproses verifikasi anggota ID: AGT-1766904141 -> Terverifikasi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:03:30'),
(16, 'Admin', 'ADM-001', 'Approve Anggota', 'Memproses verifikasi anggota ID: AGT-1766905306 -> Terverifikasi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:03:36'),
(17, 'Admin', 'ADM-001', 'Approve Anggota', 'Memproses verifikasi anggota ID: AGT-1766905388 -> Terverifikasi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:03:41'),
(18, 'Admin', 'ADM-001', 'Suspend Anggota', 'Mengubah status menjadi Nonaktif. Alasan: [AGT-1766905388] terlambat mengembalikan buku', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:47:17'),
(19, 'Admin', 'ADM-001', 'Reaktivasi Anggota', 'Mengubah status menjadi Aktif. Alasan: [AGT-1766905388] udah gitu lah', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:47:38'),
(20, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:07:37'),
(21, 'Anggota', 'AGT-1766904141', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-28 09:20:44'),
(22, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:21:33'),
(23, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:21:41'),
(24, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228285 dengan status: Disetujui', NULL, NULL, '2025-12-28 09:22:05'),
(25, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:29:20'),
(26, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:48:18'),
(27, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:48:25'),
(28, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:52:47'),
(29, 'Anggota', 'AGT-1766904141', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-28 09:53:16'),
(30, 'Anggota', 'AGT-1766904141', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228329', NULL, NULL, '2025-12-28 09:53:31'),
(31, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:53:38'),
(32, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:53:58'),
(33, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:54:05'),
(34, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228853 dengan status: Disetujui', NULL, NULL, '2025-12-28 09:54:26'),
(35, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228780 dengan status: Disetujui', NULL, NULL, '2025-12-28 09:54:31'),
(36, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:55:42'),
(37, 'Anggota', 'AGT-1766904141', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-28 09:56:17'),
(38, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:56:21'),
(39, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:56:28'),
(40, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228532 dengan status: Disetujui', NULL, NULL, '2025-12-28 09:56:47'),
(41, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:57:24'),
(42, 'Anggota', 'AGT-1766904141', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-28 09:57:38'),
(43, 'Anggota', 'AGT-1766904141', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-28 09:57:56'),
(44, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:57:59'),
(45, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:58:05'),
(46, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228973 dengan status: Disetujui', NULL, NULL, '2025-12-28 09:58:18'),
(47, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228127 dengan status: Disetujui', NULL, NULL, '2025-12-28 09:58:24'),
(48, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:01:06'),
(49, 'Anggota', 'AGT-1766904141', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-28 10:10:59'),
(50, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:11:04'),
(51, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:11:12'),
(52, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228920 dengan status: Disetujui', NULL, NULL, '2025-12-28 10:11:29'),
(53, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:35:06'),
(54, 'Anggota', 'AGT-1766918177', 'Registrasi', 'Pendaftaran anggota baru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:36:17'),
(55, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:37:32'),
(56, 'Admin', 'ADM-001', 'Approve Anggota', 'Memproses verifikasi anggota ID: AGT-1766918177 -> Terverifikasi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:38:02'),
(57, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:38:20'),
(58, 'Anggota', 'AGT-1766918177', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-28 10:38:45'),
(59, 'Anggota', 'AGT-1766918177', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228329', NULL, NULL, '2025-12-28 10:38:55'),
(60, 'Anggota', 'AGT-1766918177', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:48:19'),
(61, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:48:43'),
(62, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228732 dengan status: Disetujui', NULL, NULL, '2025-12-28 10:49:02'),
(63, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251228934 dengan status: Disetujui', NULL, NULL, '2025-12-28 10:49:06'),
(64, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:49:13'),
(65, 'Anggota', 'AGT-1766918177', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:56:21'),
(66, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:56:28'),
(67, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:05:15'),
(68, 'Admin', 'ADM-001', 'Update Profil', 'Mengubah data profil akun.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:26:51'),
(69, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:33:26'),
(70, 'Admin', 'ADM-1767025677', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:33:37'),
(71, 'Admin', 'ADM-1767025677', 'Update Profil', 'Mengubah data profil akun.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:41:38'),
(72, 'Admin', 'ADM-1767025677', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:44:18'),
(73, 'Anggota', 'AGT-1766918177', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-29 16:44:52'),
(74, 'Anggota', 'AGT-1766918177', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228232', NULL, NULL, '2025-12-29 16:44:58'),
(75, 'Anggota', 'AGT-1766918177', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228329', NULL, NULL, '2025-12-29 16:45:03'),
(76, 'Anggota', 'AGT-1766918177', 'Pengajuan Peminjaman', 'Mengajukan peminjaman buku BK-251228329', NULL, NULL, '2025-12-29 16:45:09'),
(77, 'Anggota', 'AGT-1766918177', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:45:26'),
(78, 'Admin', 'ADM-001', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:46:00'),
(79, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251229550 dengan status: Disetujui', NULL, NULL, '2025-12-29 16:46:17'),
(80, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251229867 dengan status: Disetujui', NULL, NULL, '2025-12-29 16:46:22'),
(81, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251229958 dengan status: Disetujui', NULL, NULL, '2025-12-29 16:46:26'),
(82, 'Admin', 'ADM-001', 'Proses Pengajuan', 'Memproses pengajuan REQ-251229569 dengan status: Disetujui', NULL, NULL, '2025-12-29 16:46:30'),
(83, 'Admin', 'ADM-001', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:49:05'),
(84, 'Anggota', 'AGT-1766918177', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:49:36'),
(85, 'Anggota', 'AGT-1766904141', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:53:02'),
(86, 'Admin', 'ADM-1767025677', 'Login Berhasil', 'Login sebagai Super Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:53:41'),
(87, 'Admin', 'ADM-1767025677', 'Logout', 'User keluar sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:54:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran_denda`
--

CREATE TABLE `pembayaran_denda` (
  `Id_Pembayaran` varchar(50) NOT NULL,
  `Id_Denda` varchar(50) NOT NULL,
  `Jumlah_Bayar` decimal(10,2) NOT NULL,
  `Metode_Pembayaran` enum('Tunai','Transfer','E-Wallet') NOT NULL,
  `Bukti_Pembayaran` varchar(255) DEFAULT NULL,
  `Diterima_Oleh` varchar(50) DEFAULT NULL,
  `Tanggal_Bayar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran_denda`
--

INSERT INTO `pembayaran_denda` (`Id_Pembayaran`, `Id_Denda`, `Jumlah_Bayar`, `Metode_Pembayaran`, `Bukti_Pembayaran`, `Diterima_Oleh`, `Tanggal_Bayar`) VALUES
('PAY-1766917206435', 'DND-1766917159286', 50000.00, 'Tunai', NULL, 'ADM-001', '2025-12-28 10:20:06'),
('PAY-1766917969927', 'DND-1766917943788', 7000.00, 'Tunai', NULL, 'ADM-001', '2025-12-28 10:32:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
--

CREATE TABLE `peminjaman` (
  `Id_Peminjaman` varchar(50) NOT NULL,
  `Id_Pengajuan` varchar(50) NOT NULL,
  `Id_Anggota` varchar(50) NOT NULL,
  `Id_Buku` varchar(50) NOT NULL,
  `Tgl_Pinjam` date NOT NULL,
  `Tgl_Kembali_Rencana` date NOT NULL,
  `Tgl_Kembali_Aktual` date DEFAULT NULL,
  `Durasi_Hari` int(11) DEFAULT NULL,
  `Status_Peminjaman` enum('Dipinjam','Dikembalikan','Terlambat','Hilang') DEFAULT 'Dipinjam',
  `Kondisi_Pinjam` enum('Baik','Rusak Ringan','Rusak Berat') DEFAULT 'Baik',
  `Kondisi_Kembali` enum('Baik','Rusak Ringan','Rusak Berat','Hilang') DEFAULT NULL,
  `Catatan_Peminjaman` text DEFAULT NULL,
  `Catatan_Pengembalian` text DEFAULT NULL,
  `Dipinjamkan_Oleh` varchar(50) DEFAULT NULL,
  `Diterima_Oleh` varchar(50) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peminjaman`
--

INSERT INTO `peminjaman` (`Id_Peminjaman`, `Id_Pengajuan`, `Id_Anggota`, `Id_Buku`, `Tgl_Pinjam`, `Tgl_Kembali_Rencana`, `Tgl_Kembali_Aktual`, `Durasi_Hari`, `Status_Peminjaman`, `Kondisi_Pinjam`, `Kondisi_Kembali`, `Catatan_Peminjaman`, `Catatan_Pengembalian`, `Dipinjamkan_Oleh`, `Diterima_Oleh`, `Created_At`, `Updated_At`) VALUES
('PJ-251228217', 'REQ-251228732', 'AGT-1766918177', 'BK-251228232', '2025-12-28', '2026-01-04', '2025-12-29', 7, 'Dikembalikan', 'Baik', 'Baik', NULL, NULL, 'ADM-001', 'ADM-001', '2025-12-28 10:49:02', '2025-12-29 16:07:23'),
('PJ-251228321', 'REQ-251228920', 'AGT-1766904141', 'BK-251228232', '2025-12-28', '2025-12-29', NULL, 1, 'Dipinjam', 'Baik', NULL, NULL, NULL, 'ADM-001', NULL, '2025-12-28 10:11:29', NULL),
('PJ-251228436', 'REQ-251228780', 'AGT-1766904141', 'BK-251228329', '2025-12-28', '2025-12-30', NULL, 2, 'Dipinjam', 'Baik', NULL, NULL, NULL, 'ADM-001', NULL, '2025-12-28 09:54:31', NULL),
('PJ-251228574', 'REQ-251228934', 'AGT-1766918177', 'BK-251228329', '2025-12-28', '2025-12-31', '2025-12-29', 3, 'Dikembalikan', 'Baik', 'Baik', NULL, NULL, 'ADM-001', 'ADM-001', '2025-12-28 10:49:06', '2025-12-29 16:07:08'),
('PJ-251228580', 'REQ-251228973', 'AGT-1766904141', 'BK-251228232', '2025-12-11', '2026-01-04', NULL, 24, 'Dipinjam', 'Baik', NULL, NULL, NULL, 'ADM-001', NULL, '2025-12-28 09:58:18', NULL),
('PJ-251228784', 'REQ-251228285', 'AGT-1766904141', 'BK-251228232', '2025-12-28', '2026-01-01', '2025-12-28', 4, 'Dikembalikan', 'Baik', 'Baik', NULL, NULL, 'ADM-001', 'ADM-001', '2025-12-28 09:22:05', '2025-12-28 09:52:13'),
('PJ-251228824', 'REQ-251228853', 'AGT-1766904141', 'BK-251228232', '2025-12-28', '2025-12-29', '2025-12-28', 1, 'Dikembalikan', 'Baik', 'Baik', NULL, NULL, 'ADM-001', 'ADM-001', '2025-12-28 09:54:26', '2025-12-28 09:55:24'),
('PJ-251228971', 'REQ-251228532', 'AGT-1766904141', 'BK-251228232', '2025-12-17', '2026-01-26', '2025-12-28', 40, 'Dikembalikan', 'Baik', 'Baik', NULL, NULL, 'ADM-001', 'ADM-001', '2025-12-28 09:56:47', '2025-12-28 09:57:18'),
('PJ-251228997', 'REQ-251228127', 'AGT-1766904141', 'BK-251228232', '2025-12-10', '2025-12-21', '2025-12-28', 11, 'Dikembalikan', 'Baik', 'Baik', NULL, NULL, 'ADM-001', 'ADM-001', '2025-12-28 09:58:24', '2025-12-28 10:32:23'),
('PJ-251229408', 'REQ-251229867', 'AGT-1766918177', 'BK-251228232', '2025-12-29', '2026-01-05', NULL, 7, 'Dipinjam', 'Baik', NULL, NULL, NULL, 'ADM-001', NULL, '2025-12-29 16:46:22', NULL),
('PJ-251229647', 'REQ-251229958', 'AGT-1766918177', 'BK-251228329', '2025-12-29', '2026-01-05', NULL, 7, 'Dipinjam', 'Baik', NULL, NULL, NULL, 'ADM-001', NULL, '2025-12-29 16:46:26', NULL),
('PJ-251229761', 'REQ-251229550', 'AGT-1766918177', 'BK-251228232', '2025-12-29', '2026-01-05', NULL, 7, 'Dipinjam', 'Baik', NULL, NULL, NULL, 'ADM-001', NULL, '2025-12-29 16:46:17', NULL),
('PJ-251229955', 'REQ-251229569', 'AGT-1766918177', 'BK-251228329', '2025-12-29', '2026-01-05', NULL, 7, 'Dipinjam', 'Baik', NULL, NULL, NULL, 'ADM-001', NULL, '2025-12-29 16:46:30', NULL);

--
-- Trigger `peminjaman`
--
DELIMITER $$
CREATE TRIGGER `After_Peminjaman_Insert` AFTER INSERT ON `peminjaman` FOR EACH ROW BEGIN
    UPDATE Buku 
    SET Jumlah_Tersedia = Jumlah_Tersedia - 1,
        Jumlah_Dipinjam = Jumlah_Dipinjam + 1,
        Status = CASE 
            WHEN (Jumlah_Tersedia - 1) > 0 THEN 'Tersedia'
            ELSE 'Habis'
        END
    WHERE Id_Buku = NEW.Id_Buku;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `After_Peminjaman_Update` AFTER UPDATE ON `peminjaman` FOR EACH ROW BEGIN
    IF NEW.Status_Peminjaman = 'Dikembalikan' AND OLD.Status_Peminjaman = 'Dipinjam' THEN
        IF NEW.Kondisi_Kembali = 'Hilang' THEN
            UPDATE Buku 
            SET Jumlah_Total = Jumlah_Total - 1,
                Jumlah_Dipinjam = Jumlah_Dipinjam - 1,
                Jumlah_Hilang = Jumlah_Hilang + 1
            WHERE Id_Buku = NEW.Id_Buku;
        ELSEIF NEW.Kondisi_Kembali IN ('Rusak Ringan', 'Rusak Berat') THEN
            UPDATE Buku 
            SET Jumlah_Dipinjam = Jumlah_Dipinjam - 1,
                Jumlah_Rusak = Jumlah_Rusak + 1
            WHERE Id_Buku = NEW.Id_Buku;
        ELSE
            UPDATE Buku 
            SET Jumlah_Tersedia = Jumlah_Tersedia + 1,
                Jumlah_Dipinjam = Jumlah_Dipinjam - 1,
                Status = 'Tersedia'
            WHERE Id_Buku = NEW.Id_Buku;
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan_peminjaman`
--

CREATE TABLE `pengajuan_peminjaman` (
  `Id_Pengajuan` varchar(50) NOT NULL,
  `Id_Anggota` varchar(50) NOT NULL,
  `Id_Buku` varchar(50) NOT NULL,
  `Tgl_Pengajuan` timestamp NOT NULL DEFAULT current_timestamp(),
  `Tgl_Pinjam_Diinginkan` date NOT NULL,
  `Tgl_Kembali_Diinginkan` date NOT NULL,
  `Durasi_Hari` int(11) DEFAULT NULL,
  `Catatan_Anggota` text DEFAULT NULL,
  `Status_Pengajuan` enum('Menunggu','Disetujui','Ditolak','Dibatalkan') DEFAULT 'Menunggu',
  `Alasan_Penolakan` text DEFAULT NULL,
  `Diproses_Oleh` varchar(50) DEFAULT NULL,
  `Diproses_At` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengajuan_peminjaman`
--

INSERT INTO `pengajuan_peminjaman` (`Id_Pengajuan`, `Id_Anggota`, `Id_Buku`, `Tgl_Pengajuan`, `Tgl_Pinjam_Diinginkan`, `Tgl_Kembali_Diinginkan`, `Durasi_Hari`, `Catatan_Anggota`, `Status_Pengajuan`, `Alasan_Penolakan`, `Diproses_Oleh`, `Diproses_At`) VALUES
('REQ-251228127', 'AGT-1766904141', 'BK-251228232', '2025-12-28 09:57:56', '2025-12-10', '2025-12-21', 11, 'tugas\r\n', 'Disetujui', '', 'ADM-001', '2025-12-28 09:58:24'),
('REQ-251228285', 'AGT-1766904141', 'BK-251228232', '2025-12-28 09:20:44', '2025-12-28', '2026-01-01', 4, 'penelitian', 'Disetujui', '', 'ADM-001', '2025-12-28 09:22:05'),
('REQ-251228532', 'AGT-1766904141', 'BK-251228232', '2025-12-28 09:56:17', '2025-12-17', '2026-01-26', 40, 'tugas\r\n', 'Disetujui', '', 'ADM-001', '2025-12-28 09:56:47'),
('REQ-251228732', 'AGT-1766918177', 'BK-251228232', '2025-12-28 10:38:45', '2025-12-28', '2026-01-04', 7, 'ANU', 'Disetujui', '', 'ADM-001', '2025-12-28 10:49:02'),
('REQ-251228780', 'AGT-1766904141', 'BK-251228329', '2025-12-28 09:53:31', '2025-12-28', '2025-12-30', 2, 'tugas\r\n', 'Disetujui', '', 'ADM-001', '2025-12-28 09:54:31'),
('REQ-251228853', 'AGT-1766904141', 'BK-251228232', '2025-12-28 09:53:16', '2025-12-28', '2025-12-29', 1, 'tugas', 'Disetujui', '', 'ADM-001', '2025-12-28 09:54:26'),
('REQ-251228920', 'AGT-1766904141', 'BK-251228232', '2025-12-28 10:10:59', '2025-12-28', '2025-12-29', 1, 'tugas', 'Disetujui', '', 'ADM-001', '2025-12-28 10:11:29'),
('REQ-251228934', 'AGT-1766918177', 'BK-251228329', '2025-12-28 10:38:55', '2025-12-28', '2025-12-31', 3, 'Anu', 'Disetujui', '', 'ADM-001', '2025-12-28 10:49:06'),
('REQ-251228973', 'AGT-1766904141', 'BK-251228232', '2025-12-28 09:57:38', '2025-12-11', '2026-01-04', 24, '', 'Disetujui', '', 'ADM-001', '2025-12-28 09:58:18'),
('REQ-251229550', 'AGT-1766918177', 'BK-251228232', '2025-12-29 16:44:52', '2025-12-29', '2026-01-05', 7, '', 'Disetujui', '', 'ADM-001', '2025-12-29 16:46:17'),
('REQ-251229569', 'AGT-1766918177', 'BK-251228329', '2025-12-29 16:45:09', '2025-12-29', '2026-01-05', 7, '', 'Disetujui', '', 'ADM-001', '2025-12-29 16:46:30'),
('REQ-251229867', 'AGT-1766918177', 'BK-251228232', '2025-12-29 16:44:58', '2025-12-29', '2026-01-05', 7, '', 'Disetujui', '', 'ADM-001', '2025-12-29 16:46:22'),
('REQ-251229958', 'AGT-1766918177', 'BK-251228329', '2025-12-29 16:45:03', '2025-12-29', '2026-01-05', 7, '', 'Disetujui', '', 'ADM-001', '2025-12-29 16:46:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `Id` int(11) NOT NULL,
  `Nama_Perpus` varchar(100) DEFAULT 'Perpustakaan Digital',
  `Alamat` text DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `No_Telepon` varchar(20) DEFAULT NULL,
  `Denda_Per_Hari` int(11) DEFAULT 1000,
  `Durasi_Pinjam` int(11) DEFAULT 7,
  `Max_Pinjam` int(11) DEFAULT 3,
  `Biaya_Rusak_Ringan` int(11) DEFAULT 20000,
  `Biaya_Rusak_Berat` int(11) DEFAULT 50000,
  `Biaya_Hilang` int(11) DEFAULT 100000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`Id`, `Nama_Perpus`, `Alamat`, `Email`, `No_Telepon`, `Denda_Per_Hari`, `Durasi_Pinjam`, `Max_Pinjam`, `Biaya_Rusak_Ringan`, `Biaya_Rusak_Berat`, `Biaya_Hilang`) VALUES
(1, 'LibraryHub', NULL, NULL, NULL, 1000, 7, 3, 20000, 50000, 100000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_stok`
--

CREATE TABLE `riwayat_stok` (
  `Id_Riwayat` int(11) NOT NULL,
  `Id_Buku` varchar(50) NOT NULL,
  `Jenis_Transaksi` enum('Tambah','Kurang','Rusak','Hilang','Perbaikan') NOT NULL,
  `Jumlah` int(11) NOT NULL,
  `Keterangan` text DEFAULT NULL,
  `Dilakukan_Oleh` varchar(50) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_stok`
--

INSERT INTO `riwayat_stok` (`Id_Riwayat`, `Id_Buku`, `Jenis_Transaksi`, `Jumlah`, `Keterangan`, `Dilakukan_Oleh`, `Created_At`) VALUES
(1, 'BK-251228232', 'Tambah', 8, 'baik', 'ADM-001', '2025-12-28 08:31:45');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_anggota_pending`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_anggota_pending` (
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_ketersediaan_buku`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_ketersediaan_buku` (
`Id_Buku` varchar(50)
,`Kode_Buku` varchar(50)
,`Judul` varchar(200)
,`Penulis` varchar(100)
,`Penerbit` varchar(100)
,`Nama_Kategori` varchar(100)
,`Jumlah_Total` int(11)
,`Jumlah_Tersedia` int(11)
,`Jumlah_Dipinjam` int(11)
,`Jumlah_Rusak` int(11)
,`Jumlah_Hilang` int(11)
,`Lokasi_Rak` varchar(50)
,`Status` enum('Tersedia','Habis','Tidak Tersedia')
,`Status_Ketersediaan` varchar(14)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_peminjaman_aktif`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_peminjaman_aktif` (
`Id_Peminjaman` varchar(50)
,`Id_Anggota` varchar(50)
,`Nama_Anggota` varchar(100)
,`Telepon_Anggota` varchar(20)
,`Id_Buku` varchar(50)
,`Judul_Buku` varchar(200)
,`Penulis` varchar(100)
,`Tgl_Pinjam` date
,`Tgl_Kembali_Rencana` date
,`Status_Peminjaman` enum('Dipinjam','Dikembalikan','Terlambat','Hilang')
,`Hari_Terlambat` int(7)
,`Status_Keterlambatan` varchar(18)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_pengajuan_menunggu`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_pengajuan_menunggu` (
`Id_Pengajuan` varchar(50)
,`Id_Anggota` varchar(50)
,`Nama_Anggota` varchar(100)
,`Email_Anggota` varchar(100)
,`Id_Buku` varchar(50)
,`Judul_Buku` varchar(200)
,`Jumlah_Tersedia` int(11)
,`Tgl_Pengajuan` timestamp
,`Tgl_Pinjam_Diinginkan` date
,`Tgl_Kembali_Diinginkan` date
,`Durasi_Hari` int(11)
,`Catatan_Anggota` text
,`Status_Pengajuan` enum('Menunggu','Disetujui','Ditolak','Dibatalkan')
,`Hari_Menunggu` int(7)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `view_anggota_pending`
--
DROP TABLE IF EXISTS `view_anggota_pending`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_anggota_pending`  AS SELECT `a`.`Id_Anggota` AS `Id_Anggota`, `a`.`Username` AS `Username`, `a`.`Nama_Lengkap` AS `Nama_Lengkap`, `a`.`Email` AS `Email`, `a`.`No_Telepon` AS `No_Telepon`, `a`.`No_KTP` AS `No_KTP`, `a`.`Foto_KTP` AS `Foto_KTP`, `a`.`Jenis_Kelamin` AS `Jenis_Kelamin`, `a`.`Alamat` AS `Alamat`, `a`.`Status_Verifikasi` AS `Status_Verifikasi`, `a`.`Created_At` AS `Created_At`, to_days(current_timestamp()) - to_days(`a`.`Created_At`) AS `Hari_Menunggu` FROM `anggota` AS `a` WHERE `a`.`Status_Verifikasi` = 'Pending' ORDER BY `a`.`Created_At` ASC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_ketersediaan_buku`
--
DROP TABLE IF EXISTS `view_ketersediaan_buku`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_ketersediaan_buku`  AS SELECT `b`.`Id_Buku` AS `Id_Buku`, `b`.`Kode_Buku` AS `Kode_Buku`, `b`.`Judul` AS `Judul`, `b`.`Penulis` AS `Penulis`, `b`.`Penerbit` AS `Penerbit`, `k`.`Nama_Kategori` AS `Nama_Kategori`, `b`.`Jumlah_Total` AS `Jumlah_Total`, `b`.`Jumlah_Tersedia` AS `Jumlah_Tersedia`, `b`.`Jumlah_Dipinjam` AS `Jumlah_Dipinjam`, `b`.`Jumlah_Rusak` AS `Jumlah_Rusak`, `b`.`Jumlah_Hilang` AS `Jumlah_Hilang`, `b`.`Lokasi_Rak` AS `Lokasi_Rak`, `b`.`Status` AS `Status`, CASE WHEN `b`.`Jumlah_Tersedia` > 0 THEN 'Tersedia' WHEN `b`.`Jumlah_Total` > 0 AND `b`.`Jumlah_Tersedia` = 0 THEN 'Dipinjam Semua' ELSE 'Tidak Tersedia' END AS `Status_Ketersediaan` FROM (`buku` `b` left join `kategori_buku` `k` on(`b`.`Id_Kategori` = `k`.`Id_Kategori`)) ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_peminjaman_aktif`
--
DROP TABLE IF EXISTS `view_peminjaman_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_peminjaman_aktif`  AS SELECT `p`.`Id_Peminjaman` AS `Id_Peminjaman`, `p`.`Id_Anggota` AS `Id_Anggota`, `a`.`Nama_Lengkap` AS `Nama_Anggota`, `a`.`No_Telepon` AS `Telepon_Anggota`, `p`.`Id_Buku` AS `Id_Buku`, `b`.`Judul` AS `Judul_Buku`, `b`.`Penulis` AS `Penulis`, `p`.`Tgl_Pinjam` AS `Tgl_Pinjam`, `p`.`Tgl_Kembali_Rencana` AS `Tgl_Kembali_Rencana`, `p`.`Status_Peminjaman` AS `Status_Peminjaman`, to_days(curdate()) - to_days(`p`.`Tgl_Kembali_Rencana`) AS `Hari_Terlambat`, CASE WHEN to_days(curdate()) - to_days(`p`.`Tgl_Kembali_Rencana`) > 0 THEN 'Terlambat' WHEN to_days(`p`.`Tgl_Kembali_Rencana`) - to_days(curdate()) <= 3 THEN 'Segera Jatuh Tempo' ELSE 'Normal' END AS `Status_Keterlambatan` FROM ((`peminjaman` `p` join `anggota` `a` on(`p`.`Id_Anggota` = `a`.`Id_Anggota`)) join `buku` `b` on(`p`.`Id_Buku` = `b`.`Id_Buku`)) WHERE `p`.`Status_Peminjaman` = 'Dipinjam' ORDER BY `p`.`Tgl_Kembali_Rencana` ASC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_pengajuan_menunggu`
--
DROP TABLE IF EXISTS `view_pengajuan_menunggu`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_pengajuan_menunggu`  AS SELECT `p`.`Id_Pengajuan` AS `Id_Pengajuan`, `p`.`Id_Anggota` AS `Id_Anggota`, `a`.`Nama_Lengkap` AS `Nama_Anggota`, `a`.`Email` AS `Email_Anggota`, `p`.`Id_Buku` AS `Id_Buku`, `b`.`Judul` AS `Judul_Buku`, `b`.`Jumlah_Tersedia` AS `Jumlah_Tersedia`, `p`.`Tgl_Pengajuan` AS `Tgl_Pengajuan`, `p`.`Tgl_Pinjam_Diinginkan` AS `Tgl_Pinjam_Diinginkan`, `p`.`Tgl_Kembali_Diinginkan` AS `Tgl_Kembali_Diinginkan`, `p`.`Durasi_Hari` AS `Durasi_Hari`, `p`.`Catatan_Anggota` AS `Catatan_Anggota`, `p`.`Status_Pengajuan` AS `Status_Pengajuan`, to_days(current_timestamp()) - to_days(`p`.`Tgl_Pengajuan`) AS `Hari_Menunggu` FROM ((`pengajuan_peminjaman` `p` join `anggota` `a` on(`p`.`Id_Anggota` = `a`.`Id_Anggota`)) join `buku` `b` on(`p`.`Id_Buku` = `b`.`Id_Buku`)) WHERE `p`.`Status_Pengajuan` = 'Menunggu' ORDER BY `p`.`Tgl_Pengajuan` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Id_Admin`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_username` (`Username`),
  ADD KEY `idx_email` (`Email`);

--
-- Indeks untuk tabel `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`Id_Anggota`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `No_KTP` (`No_KTP`),
  ADD KEY `Verified_By` (`Verified_By`),
  ADD KEY `idx_username` (`Username`),
  ADD KEY `idx_email` (`Email`),
  ADD KEY `idx_status_verifikasi` (`Status_Verifikasi`),
  ADD KEY `idx_no_ktp` (`No_KTP`);

--
-- Indeks untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`Id_Buku`),
  ADD UNIQUE KEY `Kode_Buku` (`Kode_Buku`),
  ADD UNIQUE KEY `ISBN` (`ISBN`),
  ADD KEY `Created_By` (`Created_By`),
  ADD KEY `Updated_By` (`Updated_By`),
  ADD KEY `idx_judul` (`Judul`),
  ADD KEY `idx_penulis` (`Penulis`),
  ADD KEY `idx_kategori` (`Id_Kategori`),
  ADD KEY `idx_kode_buku` (`Kode_Buku`),
  ADD KEY `idx_status` (`Status`);

--
-- Indeks untuk tabel `denda`
--
ALTER TABLE `denda`
  ADD PRIMARY KEY (`Id_Denda`),
  ADD KEY `Dibuat_Oleh` (`Dibuat_Oleh`),
  ADD KEY `idx_peminjaman` (`Id_Peminjaman`),
  ADD KEY `idx_status` (`Status_Pembayaran`);

--
-- Indeks untuk tabel `kategori_buku`
--
ALTER TABLE `kategori_buku`
  ADD PRIMARY KEY (`Id_Kategori`),
  ADD UNIQUE KEY `Nama_Kategori` (`Nama_Kategori`),
  ADD KEY `Created_By` (`Created_By`),
  ADD KEY `idx_nama_kategori` (`Nama_Kategori`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`Id_Log`),
  ADD KEY `idx_user` (`User_Type`,`User_Id`),
  ADD KEY `idx_tanggal` (`Created_At`);

--
-- Indeks untuk tabel `pembayaran_denda`
--
ALTER TABLE `pembayaran_denda`
  ADD PRIMARY KEY (`Id_Pembayaran`),
  ADD KEY `Diterima_Oleh` (`Diterima_Oleh`),
  ADD KEY `idx_denda` (`Id_Denda`),
  ADD KEY `idx_tanggal` (`Tanggal_Bayar`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`Id_Peminjaman`),
  ADD UNIQUE KEY `Id_Pengajuan` (`Id_Pengajuan`),
  ADD KEY `Dipinjamkan_Oleh` (`Dipinjamkan_Oleh`),
  ADD KEY `Diterima_Oleh` (`Diterima_Oleh`),
  ADD KEY `idx_anggota` (`Id_Anggota`),
  ADD KEY `idx_buku` (`Id_Buku`),
  ADD KEY `idx_status` (`Status_Peminjaman`),
  ADD KEY `idx_tgl_pinjam` (`Tgl_Pinjam`),
  ADD KEY `idx_tgl_kembali` (`Tgl_Kembali_Rencana`);

--
-- Indeks untuk tabel `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD PRIMARY KEY (`Id_Pengajuan`),
  ADD KEY `Diproses_Oleh` (`Diproses_Oleh`),
  ADD KEY `idx_anggota` (`Id_Anggota`),
  ADD KEY `idx_buku` (`Id_Buku`),
  ADD KEY `idx_status` (`Status_Pengajuan`),
  ADD KEY `idx_tanggal` (`Tgl_Pengajuan`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`Id`);

--
-- Indeks untuk tabel `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD PRIMARY KEY (`Id_Riwayat`),
  ADD KEY `Dilakukan_Oleh` (`Dilakukan_Oleh`),
  ADD KEY `idx_buku` (`Id_Buku`),
  ADD KEY `idx_tanggal` (`Created_At`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `Id_Log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT untuk tabel `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  MODIFY `Id_Riwayat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `anggota`
--
ALTER TABLE `anggota`
  ADD CONSTRAINT `anggota_ibfk_1` FOREIGN KEY (`Verified_By`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`Id_Kategori`) REFERENCES `kategori_buku` (`Id_Kategori`) ON DELETE SET NULL,
  ADD CONSTRAINT `buku_ibfk_2` FOREIGN KEY (`Created_By`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL,
  ADD CONSTRAINT `buku_ibfk_3` FOREIGN KEY (`Updated_By`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `denda`
--
ALTER TABLE `denda`
  ADD CONSTRAINT `denda_ibfk_1` FOREIGN KEY (`Id_Peminjaman`) REFERENCES `peminjaman` (`Id_Peminjaman`),
  ADD CONSTRAINT `denda_ibfk_2` FOREIGN KEY (`Dibuat_Oleh`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `kategori_buku`
--
ALTER TABLE `kategori_buku`
  ADD CONSTRAINT `kategori_buku_ibfk_1` FOREIGN KEY (`Created_By`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pembayaran_denda`
--
ALTER TABLE `pembayaran_denda`
  ADD CONSTRAINT `pembayaran_denda_ibfk_1` FOREIGN KEY (`Id_Denda`) REFERENCES `denda` (`Id_Denda`),
  ADD CONSTRAINT `pembayaran_denda_ibfk_2` FOREIGN KEY (`Diterima_Oleh`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`Id_Pengajuan`) REFERENCES `pengajuan_peminjaman` (`Id_Pengajuan`),
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`Id_Anggota`) REFERENCES `anggota` (`Id_Anggota`),
  ADD CONSTRAINT `peminjaman_ibfk_3` FOREIGN KEY (`Id_Buku`) REFERENCES `buku` (`Id_Buku`),
  ADD CONSTRAINT `peminjaman_ibfk_4` FOREIGN KEY (`Dipinjamkan_Oleh`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL,
  ADD CONSTRAINT `peminjaman_ibfk_5` FOREIGN KEY (`Diterima_Oleh`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_1` FOREIGN KEY (`Id_Anggota`) REFERENCES `anggota` (`Id_Anggota`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_2` FOREIGN KEY (`Id_Buku`) REFERENCES `buku` (`Id_Buku`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_3` FOREIGN KEY (`Diproses_Oleh`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD CONSTRAINT `riwayat_stok_ibfk_1` FOREIGN KEY (`Id_Buku`) REFERENCES `buku` (`Id_Buku`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_stok_ibfk_2` FOREIGN KEY (`Dilakukan_Oleh`) REFERENCES `admin` (`Id_Admin`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
