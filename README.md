# Sistem Presensi & Penggajian Karyawan - CV. Surya Gemilang Mojopahit (SGM)

Aplikasi web Enterprise Presensi & Penggajian Karyawan berbasis **Geolokasi GPS**, **Verifikasi Kamera WebRTC Pintar**, **Sistem Kontrol Lembur (SPL)**, dan **Payroll Dinamis** yang dibangun dengan arsitektur **Pure Native PHP Modern MVC (Model-View-Controller)** berkinerja tinggi (*zero external framework bloat*).

---

## 📌 Gambaran Umum Sistem

Sistem Presensi SGM dikembangkan khusus untuk menjawab tantangan operasional harian perusahaan dengan multi-cabang/lokasi kerja. Sistem ini memadukan kemudahan absensi mandiri dari smartphone pegawai dengan sistem kontrol manajerial yang ketat guna mencegah kecurangan presensi, klaim lembur liar, maupun manipulasi waktu kehadiran.

---

## 🚀 Fitur Utama & Modul Sistem

### 1. Presensi Masuk & Keluar Cerdas (*Smart Attendance*)
* **Verifikasi GPS Geolocation (Geofencing):** Menghitung jarak presisi menggunakan *Haversine Formula* antara posisi GPS smartphone pegawai dengan titik koordinat kantor cabang/pusat. Presensi otomatis ditolak jika berada di luar batas radius meter yang ditentukan.
* **Kamera Pintar WebRTC:**
  * Pengambilan foto *selfie* instan langsung dari peramban ponsel/laptop tanpa dependensi aplikasi pihak ketiga.
  * **Dual Camera Switcher:** Mendukung pergantian kamera depan (*selfie*) dan kamera belakang secara mulus.
  * **Smart Orientation Mirroring:** Kamera depan di-mirror otomatis agar alami seperti cermin, sedangkan kamera belakang tidak di-mirror agar tulisan/dokumen latar belakang terbaca jelas.
  * **Dual-Channel Sensor Alert:** Peringatan panduan interaktif jika izin akses kamera atau GPS browser belum diaktifkan pengguna.
* **Kompresi Gambar Sisi Klien (*Client-Side Canvas Downscaling*):**
  * Foto kamera beresolusi tinggi (hingga 25 MB) secara instan dikompresi di peramban ke resolusi 640px JPEG kualitas 0.70 (~35 KB – 50 KB) sebelum dikirim ke server. Sangat hemat kuota internet dan menghemat ruang disk hosting hingga 98%.

### 2. Sistem Kontrol Lembur / Surat Perintah Lembur (SPL)
* **Pencegahan Klaim Lembur Liar:** Mengatasi kekhawatiran pegawai sengaja pulang terlambat demi upah lembur. Sistem mewajibkan adanya **Surat Perintah Lembur (SPL)** resmi yang diterbitkan oleh Admin/HRD sebelum lembur dapat dihitung.
* **Penugasan Berbasis Lokasi & Multi-Select Pegawai:** Admin dapat memilih lokasi kantor kerja, lalu memilih daftar pegawai yang ditugaskan lembur dengan opsi seleksi individual maupun *Checklist All*.
* **Parameter SPL Lengkap:** Setiap penugasan lembur memuat Nomor Dokumen Unik (`SPL-YYYYMMDD-XXX`), jam mulai, jam selesai, batas maksimal jam lembur, uraian instruksi tugas, dan status.
* **Notifikasi Penugasan Instan:** Pegawai yang dipilih otomatis menerima notifikasi *real-time* dan kartu banner peringatan penugasan lembur di dashboard mereka.
* **Integrasi Payroll Otomatis:** Hanya jam lembur yang sah dan sesuai SPL yang dihitung ke dalam kalkulasi upah lembur bulanan.

### 3. Manajemen Ketidakhadiran (Izin, Sakit & Cuti)
* **Pengajuan Mandiri Pegawai:** Form pengajuan izin, sakit, atau cuti dengan rentang tanggal dan unggah dokumen bukti (misal: surat dokter / bukti dinas).
* **Alur Persetujuan (Approval Workflow):** Admin dapat meninjau, menyetujui, atau menolak permohonan disertai catatan feedback resmi.
* **Ekspor Laporan Excel:** Unduh rekapitulasi data ketidakhadiran dalam format spreadsheet rapi siap cetak untuk arsip personalia.

### 4. Pusat Notifikasi & In-App Alerts Terpadu
* **Lonceng Notifikasi Interaktif:** Dilengkapi indikator badge *unread count* dan dropdown pratinjau notifikasi terbaru di bar navigasi atas.
* **Smart Routing Berbasis Peran (*Role-Based Notification Center*):**
  * **Pegawai:** Tombol *"Lihat Semua Notifikasi →"* otomatis mengarahkan pegawai ke **Riwayat Presensi & Aktivitas (`/riwayat`)** sebagai pusat aktivitas tunggal terpadu lengkap dengan metrik jam kerja dan filter bulanan, mencegah kebingungan akibat duplikasi halaman.
  * **Admin:** Mengakses portal **Pusat Notifikasi (`/notifikasi`)** yang difokuskan untuk monitoring operasional dan verifikasi pengajuan seluruh kantor, dilengkapi filter kategori (*Semua, Izin & Cuti, Presensi, Lembur*) berbasis siklus hidup Hotwire Turbo Drive yang responsif.
* **Unified Activity Stream di Dashboard Admin:** Panel aktivitas terkini di Command Center Admin secara *real-time* menggabungkan log kehadiran fisik, permohonan izin/cuti baru, serta Surat Perintah Lembur (SPL) aktif dalam kartu polimorfik interaktif dengan aksi cepat.

### 5. Halaman Mandiri Riwayat Presensi & Aktivitas (`/riwayat`)
* **Linimasa Aktivitas Terpadu Pegawai:** Menggabungkan rekaman kehadiran fisik, perizinan cuti/sakit, dan penugasan lembur (SPL) resmi dalam satu linimasa visual yang harmonis.
* **5 Kartu KPI Statistik Ringkas:** Pemantauan langsung *Total Hadir Fisik*, *Tepat Waktu*, *Terlambat*, *Izin & Cuti*, serta akumulasi *Jam Kerja & Lembur* bulanan.
* **Filter Pills Kategori:** Filter cepat (*Semua Aktivitas*, *Hadir Fisik*, *Izin & Cuti*, dan *Penugasan Lembur*).
* **Filter Periode Dinamis:** Navigasi bulan dan tahun untuk memudahkan audit rekam jejak kerja pegawai.

### 6. Dashboard Pegawai Berbasis Mobile-First Ergonomics
* **Penataan Ulang Khusus Smartphone (*Mobile Reordering*):**
  * Pada layar ponsel, widget **Presensi Hari Ini** (Jam Digital, Status Jam Masuk/Pulang, dan Tombol Absen) secara otomatis diangkat ke **urutan paling atas** tepat di bawah banner pegawai. Pegawai dapat langsung absen saat tiba di kantor tanpa perlu scrolling.
  * Di layar desktop (PC/Laptop), tata letak tetap dipertahankan dalam format 2-kolom analitik elegan.
* **Jam Digital Tabular:** Menggunakan `font-variant-numeric: tabular-nums` dan font *JetBrains Mono* agar angka detik berjalan stabil tanpa getaran layout (*no layout jitter*).
* **Donut Chart SVG Native:** Grafik lingkaran distribusi status kehadiran bulanan berbasis SVG murni yang ringan dan responsif tanpa dependensi library grafik berat.

### 7. Penggajian & Integrasi Spreadsheet (*Payroll*)
* **Kalkulasi Gaji Otomatis:** Menghitung gaji pokok, tunjangan makan berbasis hari hadir, tunjangan lembur terverifikasi per jam, serta potongan alpa/izin.
* **Pengaturan Komponen Fleksibel:** Pengaturan tarif uang makan, uang lembur, dan tunjangan dapat disesuaikan per periode.
* **Slip Gaji Digital:** Akses cetak dan unduh slip gaji perorangan maupun *bulk export* siap distribusi.
* **Integrasi Google Apps Script Webhook:** Sinkronisasi rekap kehadiran dan penggajian langsung ke Google Spreadsheet via cURL Webhook.

### 8. Manajemen Master Data & Pemeliharaan Penyimpanan
* **Master Lokasi Interaktif Leaflet.js:** Konfigurasi multi-kantor dengan pratinjau visual radius lingkaran di peta OpenStreetMap secara interaktif.
* **Master Jabatan & Pegawai:** Pengaturan hierarki organisasi dengan fitur import data pegawai via Excel/CSV dan unduh template resmi.
* **Panel Audit Storage (`/master/storage`):** Memantau penggunaan disk server dan dilengkapi tombol pembersihan aman berkas foto kadaluarsa.

---

## 🛡️ Arsitektur Keamanan Sistem (*Security Architecture*)

Sistem Presensi SGM dirancang dengan prinsip **Defense-in-Depth** untuk menjamin kerahasiaan (*confidentiality*), integritas (*integrity*), dan ketersediaan (*availability*) data:

```
┌─────────────────────────────────────────────────────────────┐
│                      PENGGUNA (PERAMBAN)                    │
│   • GPS Sensor API   • WebRTC Camera   • Canvas Compression │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTPS (TLS Encrypted)
┌──────────────────────────────▼──────────────────────────────┐
│                    KONTROL AKSES & SESI                      │
│   • Single Active Device (1 Akun = 1 Perangkat Aktif)       │
│   • RBAC Middleware (AuthController::checkAdmin / checkAuth)│
│   • Anti-Session Fixation & Session Rotation                │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│                    VALIDASI & BOUNDARY CHECK                │
│   • Server-Side Geofence Verification (Haversine Formula)   │
│   • Anti-Tamper Server-Time (WIB Jakarta Clock Verification)│
│   • MIME-Type & Image Validation (Anti-Webshell / Traversal)│
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│                    PERSISTENSI & BASIS DATA                 │
│   • 100% PDO Prepared Statements (Anti-SQL Injection)       │
│   • Output Sanitization / htmlspecialchars (Anti-XSS)       │
│   • Relational Integrity & Anti-Cascade Deletion Guard      │
└─────────────────────────────────────────────────────────────┘
```

1. **Pencegahan SQL Injection (100% Prepared Statements):**
   * Seluruh interaksi basis data menggunakan `PDO::prepare()` dengan parameter binding eksplisit (`?` atau named parameter). Tidak ada parameter input pengguna yang digabungkan (*concatenated*) langsung ke dalam string query SQL.
2. **Role-Based Access Control (RBAC):**
   * Hak akses dipisahkan secara tegas antara Administrator dan Pegawai di tingkat middleware/controller (`AuthController::checkAdmin()` dan `AuthController::checkAuth()`). Akses tidak sah akan langsung dialihkan ke halaman login atau diblokir.
3. **Single Active Device Session Guard (Anti-Titip Absen):**
   * Mencegah kecurangan titip absen antar pegawai. Sistem melacak token sesi aktif di basis data; jika akun yang sama login di perangkat ponsel lain, sesi di perangkat pertama langsung diputus secara otomatis (*kickout*).
4. **Verifikasi Geofence Sisi Server (*Server-Side Boundary Check*):**
   * Perhitungan jarak koordinat tidak dipercayakan pada frontend. Server menghitung ulang jarak koordinat GPS menggunakan *Haversine Formula* terhadap koordinat kantor yang tersimpan di basis data sebelum transaksi presensi disimpan.
5. **Anti-Tamper Server Time:**
   * Jam presensi dan durasi lembur murni diambil dari jam server (*WIB - Asia/Jakarta*), sehingga pegawai tidak dapat memanipulasi waktu absen dengan mengubah setelan jam di smartphone mereka.
6. **Sanitasi File Upload & Anti-Traversal:**
   * Foto presensi didekode dari stream Base64, diverifikasi integritas gambarnya via ekstensi GD, dan disimpan dengan penamaan acak unik (*timestamp + hash user ID*). Direktori upload dilindungi dari eksekusi skrip berbahaya (.php/.phtml/.phar) via aturan `.htaccess`.
7. **Pencegahan Cross-Site Scripting (XSS):**
   * Semua data input teks yang dirender pada view melewati fungsi `htmlspecialchars()` dengan flag `ENT_QUOTES` untuk menetralkan injeksi karakter HTML/JavaScript berbahaya.
8. **Integritas Arsip Audit (*Anti-Cascade Deletion*):**
   * Pegawai atau lokasi yang telah memiliki rekam jejak presensi, lembur, atau penggajian dilindungi dari penghapusan fisik tidak sengaja untuk menjaga keutuhan riwayat audit keuangan dan personalia.
9. **Perlindungan Anti-Brute Force & Rate Limiter (Anti-DoS / Anti-Spam):**
   * Dilengkapi engine rate limiting asli (*zero third-party bloat*) dengan atomic upsert MySQL untuk melindungi endpoint sensitif:
     * **Autentikasi Login:** Maksimal 5x percobaan gagal per IP & username dalam rentang 5 menit (300 detik) dengan *lockout* countdown dinamis dan kepatuhan standar RFC 6585 (`HTTP 429 Too Many Requests` + header `Retry-After`).
     * **Transaksi Presensi (Masuk & Keluar):** Maksimal 5 request per 30 detik untuk menolak serangan *HTTP flood*, kamera *payload spam*, dan skrip otomatisasi liar.
     * **Pengajuan Ketidakhadiran (Izin / Sakit / Cuti):** Maksimal 4 pengajuan per 60 detik untuk mencegah duplikasi atau formulir spam.
     * **Self-Purging:** Sistem pembersihan mandiri berbasis probabilitas (*lottery garbage collection 2%*) sehingga tabel `rate_limits` tetap ramping tanpa memerlukan cron job tambahan.
10. **Penguatan Perimeter Web Server & Isolasi Berkas CLI (*Hardened Web Perimeter*):**
    * **Nonaktifkan Penjelajahan Direktori (`Options -Indexes`):** Browser dilarang menampilkan daftar isi berkas jika index halaman tidak tersedia.
    * **Isolasi Berkas & Folder Tersembunyi:** Memblokir seluruh akses HTTP ke direktori `.git/`, `.agents/`, `.env`, dan file rahasia lainnya (`^\.(?!well-known/)`) untuk mencegah serangan kebocoran repositori (*Git-dumper extraction*).
    * **Isolasi Folder Internal Non-Publik:** Mengunci akses langsung via URL ke folder `/scripts/`, `/database/`, `/docs_migrasi/`, `/scratch/`, dan `/app/` dengan respon `403 Forbidden`.
    * **Proteksi Berkas Sensitif:** Memblokir akses web ke `migrate.php`, spreadsheet (`*.xlsx`), skrip basis data (`*.sql`), dan dokumentasi internal (`*.md`).
    * **Pertahanan Berlapis (*Defense-in-Depth CLI Guard*):** Skrip pemeliharaan dan migrasi (`migrate.php`, `cleanup_photos.php`, `align_existing_jabatans.php`) dilengkapi proteksi SAPI tingkat kode (`php_sapi_name() === 'cli'`) sehingga mustahil dipicu dari peramban web bahkan jika konfigurasi web server terlewati.

---

## 🛠️ Tumpukan Teknologi (*Technology Stack*)

| Lapisan | Komponen / Pustaka | Keterangan |
| :--- | :--- | :--- |
| **Backend** | Native PHP 8.1+ | Pure MVC, OOP, Strict Types, Zero Framework Bloat |
| **Basis Data** | MySQL 5.7+ / MariaDB 10.4+ | InnoDB Engine, Foreign Keys, Prepared Statements PDO |
| **Frontend** | HTML5, Modern Vanilla CSS | CSS Grid, Flexbox, HSL Design Tokens, Micro-transitions |
| **Interaktivitas** | Vanilla JavaScript (ES6+) | WebRTC API, Canvas API, Geolocation API, No jQuery |
| **Peta Digital** | Leaflet.js 1.9.4 | OpenStreetMap Tile Layer |
| **Ikonografi** | Custom SVG Helper (`Icon.php`) | Ikon vektor monokrom ringan tanpa webfont eksternal |

---

## 📁 Struktur Direktori Repositori

```text
absensi_sgm/
├── app/
│   ├── Config/              # Konfigurasi PDO Database, Base URL, & Keamanan Sesi
│   ├── Controllers/         # Controller alur MVC (Auth, Presensi, Lembur, Gaji, dll)
│   ├── Helpers/             # Helper RateLimiter, Gambar, Ikon SVG, Notifikasi, Spreadsheet
│   ├── Models/              # Model data MySQL (User, Presensi, PenugasanLembur, dll)
│   └── Views/               # Tampilan antarmuka pengguna (Dashboard, Presensi, Lembur, dll)
├── assets/
│   ├── css/                 # Stylesheet responsif utama & dark mode
│   ├── js/                  # JavaScript aplikasi modular & WebRTC camera handler
│   └── images/              # Logo aplikasi, favicons, dan folder direktori upload
├── database/
│   └── absensi_sgm.sql      # Skema database MySQL lengkap siap pakai
├── scripts/
│   ├── cleanup_photos.php       # Skrip CLI retensi & pembersihan arsip foto
│   ├── align_existing_jabatans.php # Skrip CLI sinkronisasi & penyelarasan master jabatan
│   └── test_ddos_protection.js  # Utilitas Node.js pengujian simulasi DDoS & Brute Force
├── .htaccess                # Konfigurasi rewrite engine, perimeter guard, & gzip
├── index.php                # Front Controller & HTTP Router utama
└── migrate.php              # Skrip CLI migrasi database & registrasi skema
```

---

## 💻 Panduan Instalasi Lokal (Laragon / XAMPP)

### Prasyarat Sistem
* Web Server (Apache / Nginx) dengan modul `mod_rewrite` aktif.
* PHP versi **8.1** atau lebih tinggi.
* Ekstensi PHP wajib aktif: `pdo_mysql`, `gd`, `curl`, `fileinfo`, `mbstring`, `zip`.
* MySQL versi **5.7+** atau MariaDB **10.4+**.

### Langkah Pemasangan
1. **Clone Repositori:**
   ```bash
   git clone https://github.com/Diptaaaa/absensi_sgm.git
   cd absensi_sgm
   ```
   *(Atau letakkan di `C:\laragon\www\absensi_sgm` / `C:\xampp\htdocs\absensi_sgm`)*

2. **Setup Database:**
   * Buat database baru bernama `absensi_sgm` di MySQL/phpMyAdmin.
   * Import file database:
     ```bash
     mysql -u root -p absensi_sgm < database/absensi_sgm.sql
     ```
   * Jalankan migrasi untuk memastikan seluruh tabel terbaru sinkron:
     ```bash
     php migrate.php
     ```

3. **Konfigurasi `Config.php`:**
   Sesuaikan pengaturan di `app/Config/Config.php`:
   ```php
   public const BASE_URL = 'http://localhost/absensi_sgm/';
   public const DB_HOST  = '127.0.0.1';
   public const DB_PORT  = '3306';
   public const DB_NAME  = 'absensi_sgm';
   public const DB_USER  = 'root';
   public const DB_PASS  = ''; // Sesuaikan dengan password database Anda
   ```

4. **Jalankan Aplikasi:**
   Buka browser dan kunjungi:
   ```text
   http://localhost/absensi_sgm/
   ```

---

## 🔑 Akun Default untuk Pengujian

| Peran (Role) | Username | Password | Deskripsi Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Akses penuh: Master data, kelola SPL, persetujuan cuti, payroll, audit storage |
| **Pegawai Demo** | `dipta` | `dipta123` | Akses mandiri: Presensi masuk/pulang GPS, riwayat SPL, pengajuan izin, profil |

---

## 🌐 Panduan Deployment ke Server Produksi (Hosting / VPS)

1. **Upload Berkas:** Unggah seluruh isi repositori ke folder root domain (misal `public_html` pada cPanel).
2. **Import Database:** Buat database MySQL di cPanel dan import berkas `database/absensi_sgm.sql`.
3. **Ubah `Config.php`:** Sesuaikan `BASE_URL` ke domain produksi (misal `https://absensi.suryagemilangmojopahit.com/`) dan masukkan kredensial database hosting.
4. **Wajib Pasang SSL / HTTPS:** Pasang sertifikat SSL (Let's Encrypt / Cloudflare). Fitur kamera WebRTC dan geolokasi GPS browser **hanya dapat diakses melalui protokol HTTPS aman**.
5. **Jadwalkan Pembersihan Berkala (Cron Job):**
   Tambahkan baris Cron Job di cPanel untuk membersihkan foto presensi yang telah melewati masa retensi arsip:
   ```bash
   /usr/local/bin/php /home/USER_CPANEL/public_html/scripts/cleanup_photos.php >/dev/null 2>&1
   ```

---

## 🧪 Utilitas Pengujian Keamanan & Simulasi Serangan (Security Testing)

Untuk memvalidasi ketahanan sistem terhadap serangan di lingkungan lokal maupun staging, repositori ini dilengkapi skrip pengujian berbasis Node.js murni (*Zero Dependency*):

```bash
# Jalankan seluruh simulasi serangan (Brute-Force & HTTP Flood):
node scripts/test_ddos_protection.js

# Opsi parameter pengujian:
node scripts/test_ddos_protection.js --brute   # Hanya uji tebak password berulang
node scripts/test_ddos_protection.js --flood   # Hanya uji banjir request paralel (DoS)
node scripts/test_ddos_protection.js --reset   # Kosongkan tabel rate_limits di database
```

---

## 📄 Lisensi & Hak Cipta
Hak Cipta © 2026 **CV. Surya Gemilang Mojopahit (SGM)**. Seluruh hak cipta dilindungi undang-undang.
