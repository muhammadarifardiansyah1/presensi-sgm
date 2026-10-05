-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: absensi_sgm
-- ------------------------------------------------------
-- Server version	5.5.5-10.11.0-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `jabatan`
--

DROP TABLE IF EXISTS `jabatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jabatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_jabatan` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_jabatan` (`nama_jabatan`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jabatan`
--

LOCK TABLES `jabatan` WRITE;
/*!40000 ALTER TABLE `jabatan` DISABLE KEYS */;
INSERT INTO `jabatan` VALUES (1,'Super Admin','2026-10-02 11:30:23','2026-10-02 11:30:23'),(2,'IT Support','2026-10-02 11:30:23','2026-10-02 11:30:23'),(3,'Staff Operasional','2026-10-02 11:30:23','2026-10-02 11:30:23'),(4,'Finance & Accounting','2026-10-02 11:30:23','2026-10-02 11:30:23'),(5,'Human Resource (HRD)','2026-10-02 11:30:23','2026-10-02 11:30:23');
/*!40000 ALTER TABLE `jabatan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ketidakhadiran`
--

DROP TABLE IF EXISTS `ketidakhadiran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ketidakhadiran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `kategori` enum('izin','sakit','cuti') NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `keterangan` text NOT NULL,
  `dokumen_bukti` varchar(255) DEFAULT NULL,
  `status_approval` enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_ketidakhadiran_approval_dates` (`status_approval`,`tanggal_mulai`,`tanggal_selesai`),
  KEY `idx_ketidakhadiran_user_status` (`user_id`,`status_approval`),
  CONSTRAINT `ketidakhadiran_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ketidakhadiran`
--

LOCK TABLES `ketidakhadiran` WRITE;
/*!40000 ALTER TABLE `ketidakhadiran` DISABLE KEYS */;
INSERT INTO `ketidakhadiran` VALUES (1,2,'sakit','2026-10-03','2026-10-07','sakit hati  cik.','doc_2_1790944700.jpeg','disetujui','Disetujui oleh Administrator','2026-10-02 12:38:20','2026-10-02 12:38:35'),(2,7,'sakit','2026-10-03','2026-10-08','sakit','doc_7_1790957273_28d13f02.jpg','ditolak','Pengajuan ditolak','2026-10-02 16:07:53','2026-10-02 16:08:33');
/*!40000 ALTER TABLE `ketidakhadiran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lokasi_presensi`
--

DROP TABLE IF EXISTS `lokasi_presensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lokasi_presensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(100) NOT NULL,
  `alamat_lokasi` text NOT NULL,
  `tipe_lokasi` enum('Pusat','Cabang') NOT NULL DEFAULT 'Pusat',
  `latitude` decimal(11,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius` int(11) NOT NULL DEFAULT 500,
  `zona_waktu` varchar(10) NOT NULL DEFAULT 'WIB',
  `jam_masuk` time NOT NULL DEFAULT '08:00:00',
  `jam_pulang` time NOT NULL DEFAULT '17:00:00',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lokasi_presensi`
--

LOCK TABLES `lokasi_presensi` WRITE;
/*!40000 ALTER TABLE `lokasi_presensi` DISABLE KEYS */;
INSERT INTO `lokasi_presensi` VALUES (1,'Kantor Pusat Surya Gading Mandiri','Jl. Jenderal Sudirman No. 45, Jakarta Pusat','Pusat',-6.20876340,106.84559900,500,'WIB','08:00:00','17:00:00','2026-10-02 04:48:47','2026-10-04 20:03:39'),(2,'Kantor Cabang Palembang','Jl. R. Soekamto No. 12, Palembang','Cabang',-2.97607350,104.75783300,1000,'WIB','08:00:00','17:00:00','2026-10-02 04:48:47','2026-10-02 04:48:47'),(3,'Kantor Utama Demo SGM','Konoha Barat Daya','Pusat',-4.01771108,103.23932119,20000,'WIB','08:00:00','17:00:00','2026-10-02 04:48:47','2026-10-02 04:48:47'),(4,'Kantor Utama','Konoha Barat Daya','Pusat',-4.01771108,103.23932119,1000,'WIB','08:00:00','17:00:00','2026-10-02 11:52:30','2026-10-02 11:52:30'),(5,'Asrama Banua','Jl. Puncak Trikora No.09','Pusat',-7.96092031,112.60016806,1000,'WIB','18:57:00','19:05:00','2026-10-02 11:55:39','2026-10-02 11:55:39'),(6,'UB','Lowokwaru','Cabang',-7.95218342,112.61368773,500,'WIB','07:00:00','17:00:00','2026-10-03 07:24:22','2026-10-03 07:24:22'),(7,'Pipir Lepen','jalan cr','Cabang',-7.97659747,112.63084396,220,'WIB','08:00:00','17:00:00','2026-10-04 04:25:01','2026-10-04 04:25:01');
/*!40000 ALTER TABLE `lokasi_presensi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan_gaji`
--

DROP TABLE IF EXISTS `pengaturan_gaji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan_gaji` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jabatan` varchar(100) NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL DEFAULT 3500000.00,
  `uang_makan_harian` decimal(12,2) NOT NULL DEFAULT 25000.00,
  `tarif_lembur_per_jam` decimal(12,2) NOT NULL DEFAULT 25000.00,
  `potongan_alpa_per_hari` decimal(12,2) NOT NULL DEFAULT 100000.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `jabatan` (`jabatan`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan_gaji`
--

LOCK TABLES `pengaturan_gaji` WRITE;
/*!40000 ALTER TABLE `pengaturan_gaji` DISABLE KEYS */;
INSERT INTO `pengaturan_gaji` VALUES (1,'Super Admin',5500000.00,35000.00,35000.00,150000.00,'2026-10-03 01:07:29','2026-10-03 01:07:29'),(2,'IT Support',4500000.00,25000.00,30000.00,120000.00,'2026-10-03 01:07:29','2026-10-03 01:07:29'),(3,'Finance & Accounting',4800000.00,30000.00,30000.00,120000.00,'2026-10-03 01:07:29','2026-10-03 01:07:29'),(4,'Human Resource (HRD)',4800000.00,30000.00,30000.00,120000.00,'2026-10-03 01:07:29','2026-10-03 01:07:29'),(5,'Staff Operasional',3800000.00,25000.00,25000.00,100000.00,'2026-10-03 01:07:29','2026-10-03 01:07:29'),(6,'Pegawai',3500000.00,25000.00,25000.00,100000.00,'2026-10-03 01:07:29','2026-10-03 01:07:29');
/*!40000 ALTER TABLE `pengaturan_gaji` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan_sistem`
--

DROP TABLE IF EXISTS `pengaturan_sistem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan_sistem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kunci` varchar(100) NOT NULL,
  `nilai` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kunci` (`kunci`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan_sistem`
--

LOCK TABLES `pengaturan_sistem` WRITE;
/*!40000 ALTER TABLE `pengaturan_sistem` DISABLE KEYS */;
INSERT INTO `pengaturan_sistem` VALUES (1,'spreadsheet_url','https://docs.google.com/spreadsheets/d/1P1f94UXfxIgrZPOE-i0LLDi0s8MAeoXQ-p26Ls4UEzY/edit?gid=0#gid=0','2026-10-03 01:07:29','2026-10-03 02:54:40'),(2,'spreadsheet_webhook_url','https://script.google.com/macros/s/AKfycbywkx9ewiQxnK59uyEqhNNRPhHeo8q0MAt9ecpf0fhJXcHJqVTuDXf2DHK6vPvVAok3uw/exec','2026-10-03 01:07:29','2026-10-03 14:25:06');
/*!40000 ALTER TABLE `pengaturan_sistem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presensi`
--

DROP TABLE IF EXISTS `presensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lokasi_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `foto_masuk` varchar(255) DEFAULT NULL,
  `lat_masuk` decimal(11,8) DEFAULT NULL,
  `long_masuk` decimal(11,8) DEFAULT NULL,
  `jarak_masuk` decimal(10,2) DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `foto_keluar` varchar(255) DEFAULT NULL,
  `lat_keluar` decimal(11,8) DEFAULT NULL,
  `long_keluar` decimal(11,8) DEFAULT NULL,
  `jarak_keluar` decimal(10,2) DEFAULT NULL,
  `total_jam_kerja` varchar(50) DEFAULT NULL,
  `total_terlambat` varchar(50) DEFAULT NULL,
  `jam_lembur` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status_kehadiran` enum('hadir','terlambat','alpa','izin','sakit','cuti') NOT NULL DEFAULT 'hadir',
  `keterangan_admin` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_presensi_user_tanggal` (`user_id`,`tanggal`),
  KEY `user_id` (`user_id`),
  KEY `tanggal` (`tanggal`),
  KEY `idx_presensi_tanggal_status` (`tanggal`,`status_kehadiran`),
  CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presensi`
--

LOCK TABLES `presensi` WRITE;
/*!40000 ALTER TABLE `presensi` DISABLE KEYS */;
INSERT INTO `presensi` VALUES (2,2,5,'2026-10-02','19:08:46','masuk_2_1790942926_fbcba592.jpg',-7.96166255,112.60085214,111.75,'19:08:57','keluar_2_1790942937_50bbcf9f.jpg',-7.96166255,112.60085214,111.75,'0 jam 0 menit','0 jam 11 menit',0.00,'terlambat',NULL,'2026-10-02 12:08:46','2026-10-02 12:08:57'),(3,5,5,'2026-10-02','21:09:55','masuk_5_1790950195_a2cd65ff.jpg',-7.96203423,112.60102081,155.44,'21:10:04','keluar_5_1790950204_86299618.jpg',-7.96164897,112.60088837,113.39,'0 jam 0 menit','2 jam 12 menit',0.00,'terlambat',NULL,'2026-10-02 14:09:55','2026-10-02 14:10:04'),(4,6,5,'2026-10-02','22:42:36','masuk_6_1790955756_79b7f620.jpg',-7.96203423,112.60102081,155.44,'22:44:24','keluar_6_1790955864_fdb6c6a9.jpg',-7.96164972,112.60089965,114.32,'0 jam 1 menit','3 jam 45 menit',0.00,'terlambat',NULL,'2026-10-02 15:42:36','2026-10-02 15:44:24'),(5,7,5,'2026-10-02','23:06:21','masuk_7_1790957181_586c95ea.jpg',-7.96164972,112.60089965,114.32,'23:06:47','keluar_7_1790957207_d5e7e924.jpg',-7.96164972,112.60089965,114.32,'0 jam 0 menit','4 jam 9 menit',0.00,'terlambat',NULL,'2026-10-02 16:06:21','2026-10-02 16:06:47'),(7,6,5,'2026-10-03','21:46:50','masuk_6_1791038810_84fbb3cd.jpg',-7.96166785,112.60082543,110.23,NULL,NULL,NULL,NULL,NULL,NULL,'2 jam 49 menit',0.00,'terlambat',NULL,'2026-10-03 14:46:50','2026-10-03 14:46:50'),(14,2,5,'2026-10-04','11:55:46','2026/10/masuk_2_1791089746_911a7441.jpg',-7.96203423,112.60102081,155.44,'11:56:41','2026/10/keluar_2_1791089801_076b7fc3.jpg',-7.96203423,112.60102081,155.44,'0 jam 0 menit','Tepat Waktu',0.00,'hadir',NULL,'2026-10-04 04:55:46','2026-10-04 04:56:41'),(16,6,5,'2026-10-05','08:24:59','2026/10/masuk_6_1791163499_524ec01b.jpg',-7.96164500,112.60087600,112.12,NULL,NULL,NULL,NULL,NULL,NULL,'Tepat Waktu',0.00,'hadir',NULL,'2026-10-05 01:24:59','2026-10-05 01:24:59');
/*!40000 ALTER TABLE `presensi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nik` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL DEFAULT 'Laki-laki',
  `alamat` text DEFAULT NULL,
  `no_handphone` varchar(30) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pegawai') NOT NULL DEFAULT 'pegawai',
  `jabatan` varchar(50) NOT NULL DEFAULT 'Pegawai',
  `lokasi_id` int(11) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `session_id` varchar(128) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_active_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nik` (`nik`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'ADM-0001','Administrator','Laki-laki','','081234567890','admin','$2y$10$EJKFbns6WdPP9Po8FYNzdesoSao5gxT/ft9CqAwmgk9igZo.0dsfi','admin','Super Admin',NULL,'pegawai_1790947736_520.jpeg','aktif','86dd1b04f0619be8acd906f0ff9f9fa8','2026-10-05 09:43:15','2026-10-05 09:43:15','2026-10-02 04:48:47','2026-10-05 02:43:15'),(2,'PEG-0002','Kasep','Laki-laki','Konoha Barat Daya','081234567890','kasep','$2y$10$O7emDFDRL37H0H98HEqqfOMOoKCazfkbNrVpVyhXYiR.f1Dp6k1gK','pegawai','IT Support',7,'pegawai_1790942260_634.jpeg','aktif',NULL,NULL,NULL,'2026-10-02 04:48:47','2026-10-04 04:46:40'),(5,'PEG-0005','Dandi Kaswanda','Laki-laki','Jl. Puncak Trikora','0812345678','dandi','$2y$12$QuAe4iCYVUbw/rv4Sf/xn.1QaQn2SXebt1Vh4RMC3WRet.xOStAqa','pegawai','Finance & Accounting',5,'pegawai_1790950139_939.jpeg','aktif',NULL,NULL,NULL,'2026-10-02 14:08:59','2026-10-02 14:08:59'),(6,'PEG-0006','Muhammad Rafli Pradipta','Laki-laki','asrama banua','081234567890','dipta','$2y$12$C1sRNsIJpOrZx6RBOg51PuZnp4u1vkZ1Esn.tDJ1G6k6B3S/ARBsa','pegawai','Human Resource (HRD)',7,'pegawai_1790955716_1790955716_44060689.jpg','aktif','087d00bf7961d616e128b61c015e0ca5','2026-10-05 09:48:22','2026-10-05 09:48:22','2026-10-02 15:41:56','2026-10-05 02:48:22'),(7,'PEG-0007','Thomas','Laki-laki','jl puncak trikora','0812345678','thomas','$2y$12$fr2F6ZrDb2qexbAr7VMYkugyPRA5X2yHtg/.M0hGaXCqIXYjGQ3pO','pegawai','Human Resource (HRD)',5,'pegawai_1790957073_1790957073_ca7fd486.jpg','aktif',NULL,NULL,NULL,'2026-10-02 16:04:34','2026-10-02 16:04:34'),(8,'PEG-0008','Andi Wijaya','Laki-laki','Jl. Melati No. 5','0811223344','andi.wijaya','$2y$10$fpJKO7uG2Eq.nqc0T23vAO24fUXCw2BjnF0aQUED40P5Rp4h3hsIq','pegawai','IT Support',5,NULL,'aktif',NULL,NULL,NULL,'2026-10-02 16:29:42','2026-10-05 02:44:29'),(9,'PEG-0009','Rina Kartika','Perempuan','Jl. Mawar No. 10','0855667788','rina.kartika','$2y$10$qRCx5N9314b/v/IRwCWJFOW0Aj3xy45g6xGmEw0D5hgbRk5V0L1zi','pegawai','Staff Operasional',5,NULL,'aktif',NULL,NULL,NULL,'2026-10-02 16:29:42','2026-10-02 16:29:42');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-05 10:00:07
