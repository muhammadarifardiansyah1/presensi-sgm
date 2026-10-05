# Sistem Presensi & Penggajian Karyawan - PT Surya Gading Mandiri (SGM)

Aplikasi web presensi karyawan berbasis **Geolokasi GPS**, **Verifikasi Wajah / Kamera Pintar**, dan **Sistem Penggajian (Payroll)** yang dibangun dengan arsitektur **Native PHP Modern Pure MVC (Model-View-Controller)** berkinerja tinggi (*zero external framework bloat*).

---

## 🚀 Fitur Utama Sistem

### 1. Presensi Masuk & Keluar Cerdas (*Smart Attendance*)
* **Verifikasi GPS Geolocation:** Menghitung jarak presisi (*Haversine Formula*) antara posisi perangkat pegawai dengan titik koordinat kantor cabang/pusat. Presensi ditolak jika berada di luar batas radius.
* **Kamera Pintar WebRTC:**
  * Pengambilan foto *selfie* instan langsung dari peramban ponsel/laptop tanpa dependensi aplikasi pihak ketiga.
  * **Dual Camera Switcher:** Mendukung pergantian kamera depan (*selfie*) dan kamera belakang secara instan.
  * **Smart Orientation Mirroring:** Kamera depan di-mirror secara otomatis agar terasa alami, sedangkan kamera belakang otomatis tidak di-mirror agar tulisan/dokumen latar belakang tidak terbalik.
  * **Dual-Channel Sensor Alert:** Notifikasi pemulihan izin interaktif jika kamera atau GPS belum diizinkan oleh pengguna peramban (Chrome Android & Safari iOS).
* **Kompresi Gambar Sisi Klien (*Client-Side Canvas Downscaling*):**
  * Foto kamera beresolusi tinggi (hingga 25 MB) secara instan dikompresi di peramban ke resolusi 640px JPEG kualitas 0.70 (~35 KB – 50 KB) sebelum dikirim ke server. Sangat hemat bandwidth dan storage server.

### 2. Keamanan & Kontrol Sesi (*Anti-Fraud & Session Guard*)
* **Single Active Device Session (1 Akun = 1 Perangkat):**
  * Mencegah kecurangan titip absen antar pegawai. Jika akun login di perangkat baru, sesi di perangkat lama langsung terputus secara otomatis (*kickout*).
* **Manajemen Masa Aktif Sesi:**
  * Sesi pegawai berlaku 12 jam (sesuai jam shift kerja) untuk kenyamanan presensi pulang tanpa terputus di tengah hari.
  * Sesi admin dibatasi *idle timeout* 2 jam untuk keamanan manajerial.
  * Pembersihan *garbage collection* otomatis aktif untuk mencegah kebocoran *inode* disk server.

### 3. Manajemen Master Data & Perlindungan Integritas (*Audit Protection*)
* **Perlindungan Hapus Pegawai (*Anti-Cascade Deletion*):**
  * Pegawai yang memiliki catatan presensi atau riwayat izin dilindungi dari penghapusan fisik agar arsip audit penggajian tidak hilang.
  * Tersedia fitur **Nonaktifkan / Aktifkan Akun** instan untuk menutup akses login tanpa merusak data historis.
* **Master Lokasi & Pratinjau Radius Leaflet:**
  * Input koordinat Latitude & Longitude divalidasi ketat (-90 s/d 90 dan -180 s/d 180).
  * Dilengkapi peta interaktif Leaflet.js dengan lingkaran radius (*radius circle*) dinamis yang menyesuaikan angka meter secara *real-time*.
  * Pembatasan radius minimal 25 meter untuk menjamin stabilitas akurasi GPS smartphone.
* **Master Jabatan Terproteksi:** Jabatan yang masih aktif digunakan oleh pegawai dilindungi dari penghapusan tidak sengaja.

### 4. Penggajian & Integrasi Spreadsheet (*Payroll & Export*)
* **Kalkulasi Gaji Otomatis:** Perhitungan gaji pokok, uang makan kehadiran, tunjangan lembur per jam, dan potongan ketidakhadiran (alpa/izin).
* **Slip Gaji:** Akses cetak dan unduh slip gaji perorangan maupun *bulk export* yang diamankan khusus untuk hak akses Admin/HRD.
* **Ekspor Rekapitulasi Excel & CSV:** Rekap harian dan bulanan dapat diunduh ke format Excel/CSV dengan pembatasan rentang aman 31 hari per unduhan untuk mencegah kehabisan memori server (*memory exhaustion*).
* **Integrasi Google Apps Script Webhook:** Sinkronisasi rekap kehadiran dan penggajian langsung ke Google Spreadsheet via cURL Webhook dengan validasi URL dan penanganan *timeout*.

### 5. Pemeliharaan Penyimpanan (*Storage Optimization*)
* Dilengkapi skrip pemeliharaan otomatis (`scripts/cleanup_photos.php`) untuk membersihkan foto presensi yang sudah lewat masa retensi (misal > 90 hari) via Cron Job hosting.

---

## 🛠️ Arsitektur & Teknologi

* **Bahasa Pemrograman:** PHP 8.1 / 8.2 / 8.3 (Native OOP MVC, Strict Types, Prepared Statements PDO)
* **Basis Data:** MySQL 5.7+ / MariaDB 10.4+ (InnoDB, Foreign Keys, Unique Integrity Constraints)
* **Frontend:** HTML5 Semantik, Vanilla CSS Modern (CSS Grid/Flexbox, HSL Design Tokens, Glassmorphism, Micro-animations), Vanilla JavaScript (ES6+, WebRTC, Canvas API, Geolocation API)
* **Peta Interaktif:** Leaflet.js 1.9.4 & OpenStreetMap
* **Ikonografi:** SVG Native Icons

---

## 📁 Struktur Direktori

```text
absensi_sgm/
├── app/
│   ├── Config/              # Konfigurasi Database PDO, Base URL, & Pengaturan Sesi
│   ├── Controllers/         # Controller alur bisnis (Auth, Presensi, Pegawai, Lokasi, Gaji, dll)
│   ├── Helpers/             # Helper kompresi gambar, ikon SVG, & ekspor spreadsheet
│   ├── Models/              # Model data MySQL PDO (User, Presensi, Lokasi, Jabatan, Gaji, dll)
│   └── Views/               # Tampilan antarmuka pengguna (Dashboard, Presensi, Master, Gaji, dll)
├── assets/
│   ├── css/                 # Stylesheet antarmuka responsif
│   ├── js/                  # JavaScript modular
│   └── images/              # Logo aplikasi dan folder upload foto (profil, presensi, dokumen)
├── database/
│   └── absensi_sgm.sql      # Skema database MySQL lengkap siap import
├── scripts/
│   └── cleanup_photos.php   # Skrip CLI / Cron Job pembersihan foto kadaluarsa
├── .htaccess                # Konfigurasi URL rewriting & kompresi Gzip web server
├── index.php                # Front Controller & Router utama aplikasi
└── migrate.php              # Skrip migrasi dan seeder database otomatis via CLI
```

---

## 💻 Panduan Instalasi Lokal (Laragon / XAMPP)

### Prasyarat Sistem
* Web Server (Apache / Nginx) dengan modul `mod_rewrite` aktif.
* PHP versi **8.1** atau lebih baru.
* Ekstensi PHP wajib aktif: `pdo_mysql`, `gd` (atau `imagick`), `curl`, `fileinfo`, `mbstring`, `zip`.
* MySQL versi **5.7+** atau MariaDB **10.4+**.

### Langkah-Langkah Pemasangan
1. **Clone Repositori:**
   ```bash
   git clone https://github.com/USERNAME/absensi_sgm.git
   cd absensi_sgm
   ```
   *(Atau letakkan folder proyek di `C:\laragon\www\absensi_sgm` atau `C:\xampp\htdocs\absensi_sgm`)*

2. **Setup Database:**
   * Buat database baru bernama `absensi_sgm` di MySQL/phpMyAdmin.
   * Import file database:
     ```bash
     mysql -u root -p absensi_sgm < database/absensi_sgm.sql
     ```
     *Atau jalankan skrip migrasi bawaan:*
     ```bash
     php migrate.php
     ```

3. **Konfigurasi Aplikasi:**
   Buka file `app/Config/Config.php` dan sesuaikan koneksi database dan Base URL lokal Anda:
   ```php
   public const BASE_URL = 'http://localhost/absensi_sgm/';
   public const DB_HOST  = '127.0.0.1';
   public const DB_PORT  = '3306';
   public const DB_NAME  = 'absensi_sgm';
   public const DB_USER  = 'root';
   public const DB_PASS  = 'root'; // Sesuaikan password database lokal Anda
   ```

4. **Jalankan Aplikasi:**
   Buka peramban dan akses alamat:
   ```text
   http://localhost/absensi_sgm/
   ```

---

## 🔑 Akun Default Pengujian

| Peran (Role) | Username | Password | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Akses penuh: Master data, rekap, slip gaji, & konfigurasi |
| **Pegawai Demo** | `dipta` | `123456` | Akses presensi masuk/pulang, riwayat presensi, & izin |

---

## 🌐 Panduan Deployment ke Server Hosting

1. **Upload Berkas:** Unggah seluruh isi repositori ke folder `public_html` pada cPanel hosting.
2. **Import Database:** Buat database MySQL di cPanel dan import berkas `database/absensi_sgm.sql`.
3. **Ubah `Config.php`:** Sesuaikan `BASE_URL` ke domain produksi Anda (misal `https://absensi-sgm.com/`) dan isi kredensial database hosting.
4. **HTTPS / SSL (Wajib):** Pasang sertifikat SSL (Let's Encrypt / Cloudflare SSL). Fitur kamera HP dan geolokasi GPS browser **hanya aktif di protokol HTTPS**.
5. **Jadwalkan Cron Job:** Tambahkan baris cron job di cPanel untuk pembersihan berkas foto secara berkala:
   ```bash
   /usr/local/bin/php /home/USER_CPANEL/public_html/scripts/cleanup_photos.php >/dev/null 2>&1
   ```

---

## 📄 Lisensi
Hak Cipta © 2026 PT Surya Gading Mandiri (SGM). Seluruh hak cipta dilindungi undang-undang.
