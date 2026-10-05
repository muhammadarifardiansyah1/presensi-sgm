# Dokumen Analisis Human Error & Manajemen Risiko Sistem
**Aplikasi:** Presensi & Penggajian Karyawan - PT Surya Gading Mandiri (SGM)  
**Versi Sistem:** 1.0.0  
**Tanggal Dokumen:** Oktober 2026  
**Klasifikasi:** Audit Ketahanan Sistem & Panduan Mitigasi Operasional (*Operational Risk Assessment*)

---

## 📌 Ringkasan Eksekutif

Aplikasi Presensi & Penggajian PT Surya Gading Mandiri telah dioptimasi dari sisi arsitektur kode (*native MVC PHP*), optimasi query agregasi database, pengerasan koneksi jaringan, hingga kompresi dan *browser caching*. 

Namun, dalam operasional tahap produksi (*live hosting*), sistem tidak hanya berhadapan dengan beban komputasi server, melainkan juga dengan **kesalahan manusia (*Human Error*)** yang dilakukan oleh para aktor sistem:
1. **Operator / Admin / HRD** (Pengguna dengan hak akses kelola data tertinggi).
2. **Pegawai / Karyawan** (Pengguna harian presensi berbasis perangkat mobile).
3. **DevOps / Webmaster / SysAdmin** (Pengelola konfigurasi server hosting dan database).

Kesalahan operasional dari ketiga aktor ini berpotensi memicu kegagalan sistem (*system outage*), kehilangan data permanen (*data loss*), kepanikan masal saat jam presensi, atau pembengkakan kapasitas penyimpanan disk hosting (*inode/disk exhaustion*).

Dokumen ini memetakan seluruh titik rawan *human error* tersebut secara komprehensif beserta **skenario dampak teknis dan rekomendasi mitigasi pengamanannya**.

---

```
┌───────────────────────────────────────────────────────────────────────────────┐
│                    PETA DISTRIBUSI RISIKO HUMAN ERROR                         │
├───────────────────────────────────────────────────────────────────────────────┤
│  1. Operator / Admin / HRD  : Kesalahan Master Data, Hapus Data, Input Anomali│
│  2. Pegawai / End-User      : Akses GPS Ditolak, Double-Click, Lampiran Jumbo │
│  3. DevOps / Webmaster      : Kapasitas Inode Disk, Izin Berkas, Konfigurasi  │
└───────────────────────────────────────────────────────────────────────────────┘
```

---

## 1. Human Error Operator / Admin / HRD (Tingkat Dampak: TINGGI 🔴)

### A. Menghapus Pegawai yang Memiliki Riwayat Absensi (*Accidental Cascade Deletion*)
* **Kasus Nyata:**  
  Pegawai mengundurkan diri (*resign*) atau diberhentikan. Admin membuka menu Master Data Pegawai dan menekan tombol **"Hapus"** pada akun pegawai tersebut.
* **Dampak Database:**  
  Pada skema database tabel `presensi` dan `ketidakhadiran`, terdapat relasi kunci asing dengan opsi cascade (`ON DELETE CASCADE`). MySQL secara otomatis akan **menghapus seluruh riwayat presensi, lembur, dan cuti pegawai tersebut dari hari pertama bekerja hingga sekarang**. Rekap penggajian historis dan data audit perusahaan akan **hilang permanen tanpa jejak**.
* **Dampak Hosting & Storage:**  
  Proses penghapusan fisik via MySQL `CASCADE` tidak memicu unlinking berkas fisik PHP, menyebabkan ribuan foto presensi tertinggal sebagai berkas sampah (*orphaned files*) selamanya di disk server.
* **Status Solusi:** **Telah Diterapkan ✅**
  * **Audit Guard:** Menambahkan method `User::hasRiwayat(int $userId)` dan subquery agregasi `total_presensi` & `total_izin` pada `User::getAllPegawai()`.
  * **Intersepsi Penghapusan Fisik:** Pada `PegawaiController::hapus()`, jika pegawai terdeteksi memiliki catatan riwayat presensi atau permohonan izin, penghapusan fisik diblokir total dan dialihkan dengan banner:  
    *"⚠️ Perlindungan Integritas Data Historis: Pegawai [Nama] tidak dapat dihapus permanen karena memiliki catatan riwayat presensi/ketidakhadiran. Silakan nonaktifkan akun agar akses pegawai ditutup tanpa merusak riwayat historis."*
  * **Soft-Toggle Status Instan:** Menyediakan fitur dan tombol cepat **`Nonaktifkan` / `Aktifkan`** di setiap baris tabel pegawai (`pegawai/toggle-status?id=...`). Saat dinonaktifkan, sesi login aktif pegawai tersebut langsung dibersihkan (`User::clearSession()`) sehingga tidak dapat login kembali, namun seluruh berkas dan data historis penggajian tetap utuh 100%.
  * **Pembersihan Berkas:** Jika pegawai yang dihapus adalah akun baru (0 riwayat presensi), file foto profil di server otomatis di-unlink secara bersih.

---

### B. Kesalahan Input Koordinat GPS & Radius Kantor (Master Lokasi)
* **Kasus Nyata:**  
  Admin memindahkan atau membuat kantor cabang baru di menu Master Lokasi, namun salah menginput data koordinat terbalik/salah ketik, atau mengisi radius terlalu kecil (contoh: 5–10 meter).
* **Dampak Sistem:**  
  Pada jam masuk kerja, seluruh pegawai cabang tersebut serentak gagal presensi masuk karena dianggap di luar radius akibat deviasi akurasi sinyal GPS smartphone (*rush hour panic*).
* **Status Solusi:** **Telah Diterapkan ✅**
  * **Boundary Validation:** Memasang validasi ketat di `LokasiController::validateInput()`: Latitude wajib berada di rentang `-90.0 s/d 90.0`, Longitude `-180.0 s/d 180.0`, dan nilai `0` ditolak.
  * **Radius Clamping:** Radius presensi dibatasi minimal **25 meter** (dengan anjuran sistem 50–500 meter) dan maksimal 50.000 meter.
  * **Jam Kerja Validation:** Memvalidasi format waktu serta menolak jika jam masuk dan jam pulang kantor diisi sama persis.
  * **Integrity Guard:** Menambahkan `Lokasi::isUsed()` pada `LokasiController::hapus()`. Lokasi yang sedang digunakan oleh pegawai aktif atau memiliki catatan presensi ditolak untuk dihapus.
  * **Pratinjau Peta Interaktif Leaflet:** Mengintegrasikan visualisasi peta Leaflet dengan **lingkaran radius biru (`L.circle`)** dinamis di `app/Views/master/lokasi_form.php`. Mengubah nilai radius atau menggeser pin koordinat langsung memperbarui lingkaran radius secara real-time di layar Admin.

---

### C. Input Presensi Manual Duplikat pada Tanggal yang Sama (*Uncaught Integrity Violation*)
* **Kasus Nyata:**  
  Admin memasukkan presensi manual untuk pegawai yang sebelumnya sudah tercatat hadir pada tanggal tersebut, atau Admin tidak sengaja menekan tombol simpan dua kali.
* **Dampak Database & Web:**  
  Pelanggaran constraint unik `uq_presensi_user_tanggal` memicu PHP melempar `PDOException: SQLSTATE[23000]: 1062 Duplicate entry` yang berujung pada layar putih *500 Internal Server Error*.
* **Status Solusi:** **Telah Diterapkan ✅**
  * **Pre-Check Duplikasi:** Menambahkan pengecekan `Presensi::findByUserAndTanggal($userId, $tanggal)` pada method `Presensi::createManual()` dan `LaporanController::tambahPresensi()`.
  * **Graceful Exception Handling:** Query `INSERT INTO presensi` dibungkus dalam blok `try...catch (PDOException $e)`. Jika terjadi benturan duplikat, sistem tidak crash melainkan mengembalikan status `false`.
  * **Alert Ramah Admin:** Menampilkan banner notifikasi di `app/Views/laporan/rekap_harian.php`:  
    *"⚠️ Input Presensi Gagal: Pegawai yang dipilih sudah memiliki catatan presensi pada tanggal tersebut. Silakan gunakan tombol Edit pada tabel untuk memperbarui data."*

---

### D. Ekspor Laporan Rentang Tanggal Ekstrem (*Memory Exhaustion*)
* **Kasus Nyata:**  
  Admin memilih rentang tanggal bertahun-tahun pada Rekap Harian tanpa filter pegawai lalu mengklik **"Download Excel"**.
* **Dampak Hosting & Server:**  
  MySQL menarik puluhan ribu baris data sekaligus ke memori PHP, memicu `Fatal error: Allowed memory size exhausted` (128 MB hosting limit) dan *504 Gateway Timeout*.
* **Status Solusi:** **Telah Diterapkan ✅**
  * **31-Day Range Clamping:** Pada `LaporanController::exportExcelHarian()`, sistem menghitung selisih hari (`DateTime::diff()`). Rentang ekspor dibatasi maksimal **31 hari** per unduhan file Excel.
  * **Alert Pengalihan:** Jika rentang melebihi 31 hari, proses dihentikan secara elegan dan Admin diarahkan kembali dengan notifikasi:  
    *"⚠️ Rentang Ekspor Dibatasi: Ekspor harian dibatasi maksimal 31 hari per unduhan demi menjaga performa server hosting. Untuk rentang lebih panjang, gunakan menu Rekap Bulanan."*

---

### E. Kesalahan Konfigurasi Google Spreadsheet Webhook URL & Anomali Tarif Gaji
* **Kasus Nyata:**  
  1. Admin memasukkan link Google Docs/Sheets biasa ke kolom Webhook Google Apps Script, atau salah mengetik URL.
  2. Admin salah memasukkan angka tarif gaji (misal angka minus/negatif pada gaji pokok atau potongan).
* **Dampak Sistem:**  
  Proses sinkronisasi cURL menggantung atau gagal berulang kali, dan angka minus merusak total kalkulasi slip gaji.
* **Status Solusi:** **Telah Diterapkan ✅**
  * **Webhook URL Validation:** Pada `GajiController::simpanPengaturan()` dan `syncSpreadsheet()`, URL divalidasi dengan `FILTER_VALIDATE_URL` dan wajib berawalan `https://script.google.com/macros/s/`. Link spreadsheet biasa (`docs.google.com/spreadsheets`) ditolak dengan pesan peringatan spesifik.
  * **Database Sanitization Guard:** URL yang tidak valid tidak akan pernah disimpan ke database tabel `pengaturan_sistem`.
  * **Negative Rate Clamping:** Seluruh input nominal gaji pokok, uang makan, tarif lembur, dan potongan alpa diproteksi dengan fungsi `max(0, (float)$val)` sehingga nilai negatif tidak akan pernah masuk ke database penggajian.

---

## 2. Human Error Pegawai / End-User (Tingkat Dampak: SEDANG 🟡)

### A. Izin Akses Lokasi GPS Ditolak / Diblokir (*Geolocation Permission Denied*)
* **Kasus Nyata:**  
  Pegawai baru pertama kali login di browser HP (Chrome/Safari) dan secara refleks menekan tombol **"Block" / "Tolak"** saat browser memunculkan dialog pop-up *Allow Location Access*.
* **Dampak:**  
  Browser tidak akan pernah mengirimkan koordinat GPS ke JavaScript dan tidak memunculkan pop-up izin lagi pada refresh berikutnya. Pegawai tidak akan bisa menekan tombol Masuk dan mengeluh ke kantor: *"Aplikasi presensi error / rusak"*.
* **Status Solusi:** **Telah Diterapkan ✅**
  * Deteksi kode error geolokasi standar (`err.code === 1` untuk `PERMISSION_DENIED`, `2` untuk `POSITION_UNAVAILABLE`, dan `3` untuk `TIMEOUT`).
  * Jika izin ditolak, sistem menampilkan kotak panduan interaktif langkah demi langkah:
    - **Chrome Android:** *Ketuk ikon Setelan/Gembok 🔒 di kiri URL > Izin Situs > Lokasi > Izinkan.*
    - **Safari iOS:** *Pengaturan HP > Safari > Lokasi > Izinkan.*
  * Dilengkapi tombol **"🔄 Coba Deteksi Ulang GPS"** sehingga pegawai tidak perlu me-refresh seluruh halaman.
  * Dilengkapi mekanisme *auto-fallback* ke mode toleransi *low-accuracy* (triangulasi BTS/Wi-Fi) jika sinyal satelit GPS lemah/timeout.

---

### B. Double-Click / Double-Submission Form (*Rapid Double-Tap*)
* **Kasus Nyata:**  
  Pegawai menekan tombol *Kirim Pengajuan* atau *Absen* dua kali sangat cepat (*double-tap*), atau jaringan seluler lambat membuat pegawai mengklik tombol submit berulang-ulang.
* **Dampak Database:**  
  Sebelumnya pada form pengajuan ketidakhadiran (izin/sakit/cuti), dua request HTTP POST paralel terkirim ke server dan menyebabkan **tersimpannya 2 baris data izin kembar** untuk pegawai dan rentang tanggal yang persis sama.
* **Status Solusi:** **Telah Diterapkan ✅**
  * **Frontend Form Lock:** Memasang flag `isSubmitting` di level form submission event listener yang langsung mengunci form dan mengubah tombol menjadi status `⏳ Mengirim Pengajuan...` dengan pointer dinonaktifkan (`cursor: not-allowed`).
  * **Backend Idempotency Guard:** Menambahkan method `checkDuplicate()` di model [Ketidakhadiran.php](file:///c:/laragon/www/absensi_sgm/app/Models/Ketidakhadiran.php) dan verifikasi di [KetidakhadiranController.php](file:///c:/laragon/www/absensi_sgm/app/Controllers/KetidakhadiranController.php). Jika terdapat pengajuan berstatus `pending` atau `disetujui` pada rentang tanggal yang sama, sistem langsung mencegah insert ganda dan menampilkan notifikasi ramah: *"Anda sudah memiliki pengajuan aktif pada rentang tanggal tersebut."*

---

### C. Pengunggahan Foto Surat Dokter / Bukti Izin Berukuran Sangat Besar (*Lampiran Jumbo*)
* **Kasus Nyata:**  
  Pegawai mengambil foto surat dokter menggunakan kamera smartphone flagship modern (resolusi 50 MP - 108 MP) yang menghasilkan file gambar berukuran **15 MB – 25 MB**, lalu mengunggahnya pada form izin ketidakhadiran.
* **Dampak Hosting:**  
  Jika setting PHP hosting memiliki `upload_max_filesize = 2M` atau `post_max_size = 8M` (nilai default cPanel), web server akan langsung membuang request tersebut sebelum mencapai PHP (*Connection reset* atau *Payload Too Large 413*). Jika dilarang via JavaScript kaku, pegawai biasa tidak tahu cara mengecilkan foto di HP mereka.
* **Status Solusi:** **Telah Diterapkan ✅**
  * Menerapkan kompresi sisi klien (*Client-side Canvas Image Compression*) menggunakan **HTML5 Canvas native (0 library eksternal)** di [app/Views/ketidakhadiran/form.php](file:///c:/laragon/www/absensi_sgm/app/Views/ketidakhadiran/form.php).
  * Foto kamera resolusi raksasa (hingga 25 MB) secara instan di-*downscale* ke dimensi ideal dokumen (maksimal 1280px) dan dikompresi ke JPEG kualitas 0.78 (~250 KB – 400 KB) langsung di browser pengguna sebelum diunggah.
  * Menampilkan badge informasi penghematan data langsung ke pegawai: *"✅ Foto otomatis dioptimasi dari 14.8 MB menjadi 310 KB (98% lebih hemat, instan terkirim)."*
  * Dokumen PDF dibatasi maksimal 2 MB dengan validasi instan.

---

### D. Akses Kamera Ditolak, Kamera Terkunci, & Kamera Depan Rusak (*Camera Failure & Switcher*)
* **Kasus Nyata:**  
  1. Pegawai tidak sengaja menekan "Block" pada dialog izin kamera browser, atau kamera terkunci karena aplikasi lain (WhatsApp/Zoom) sedang berjalan di background.
  2. Kamera depan HP pegawai buram, tergores, atau rusak sehingga tidak dapat mengambil foto selfie yang jelas.
* **Dampak Sistem:**  
  Pegawai tidak bisa menekan tombol submit presensi dan tidak mengetahui cara menyalakan ulang kamera tanpa me-refresh halaman. Jika kamera depan rusak, pegawai terhambat presensi sama sekali.
* **Status Solusi:** **Telah Diterapkan ✅**
  * **Error Handling & Pemulihan WebRTC Lengkap:** Menangkap secara akurat `NotAllowedError` (izin diblokir), `NotReadableError` (kamera terkunci aplikasi lain), dan `NotFoundError` (perangkat tanpa kamera), disertai kotak panduan visual per browser (Chrome Android & Safari iOS) serta tombol **`[🔄 Coba Deteksi Ulang Kamera]`**.
  * **Dual Camera Switcher (Kamera Depan & Belakang):** Tombol switch mengambang (*floating glassmorphism button*) di sudut kanan atas video preview yang memungkinkan pegawai beralih antara kamera depan (*user*) dan kamera belakang (*environment*).
  * **Smart Orientation Mirroring:** Kamera depan secara otomatis di-mirror (`transform: scaleX(-1)` dan `ctx.scale(-1, 1)`) agar terasa alami, sedangkan kamera belakang otomatis di-unmirror (`transform: none` dan `ctx.setTransform(1, 0, 0, 1, 0, 0)`) agar tulisan di latar kantor tidak terbalik.
  * **Safe Hardware Release:** Pembersihan stream aktif via `track.stop()` sebelum pergantian kamera untuk mencegah hardware lock / freeze di browser mobile.

---

### E. Akun Dibuka Bersamaan di Banyak HP & Sesi Menggantung (*Multi-Device Fraud & Session Inode Leak*)
* **Kasus Nyata:**  
  1. Pegawai membagikan akun (username & password) kepada rekan kerjanya agar bisa titip absen dari HP lain.
  2. Pegawai membiarkan tab browser terbuka berbulan-bulan di HP tanpa pernah logout, atau login di komputer bersama kantor lalu lupa logout.
* **Dampak Sistem & Keamanan:**  
  1. **Fraud Presensi:** Terjadi manipulasi presensi di mana satu akun dapat aktif dan melakukan absensi dari beberapa lokasi berbeda secara bersamaan.
  2. **Penumpukan File Sesi di Server:** Jutaan berkas sesi teks (`sess_*`) menumpuk di direktori `/tmp` server dan menghabiskan jatah Inode disk hosting jika garbage collector tidak disetel.
* **Status Solusi:** **Telah Diterapkan (Opsi 1) ✅**
  * **Single Active Device Login (1 Akun = 1 Perangkat):** Menambahkan kolom `session_id` pada tabel `users`. Setiap kali login baru terjadi, ID sesi lama di perangkat lain otomatis di-invalidate. Perangkat lama akan langsung dikeluarkan secara aman dengan notifikasi flash: *"Akun Anda telah masuk di perangkat lain. Sesi pada perangkat ini telah diakhiri demi keamanan."*
  * **Diferensiasi Lifetime Sesi (Anti-Frustrasi):**
    - **Pegawai:** Diberikan batas sesi **12 jam** (pas untuk durasi 1 hari kerja penuh dari presensi pagi hingga sore tanpa terputus tiba-tiba).
    - **Admin:** Diberikan batas sesi **2 jam** idle timeout tanpa aktivitas untuk menjaga kerahasiaan data master & penggajian.
  * **Graceful AJAX Session Expiration:** Jika sesi habis saat pegawai menekan tombol presensi, server mengembalikan status JSON `session_expired` yang menampilkan pesan jelas dan mengarahkan ke halaman login tanpa memicu error parsing JSON di konsol browser.
  * **Active Session Garbage Collection:** Menyetel `session.gc_probability = 1`, `session.gc_divisor = 100`, dan `session.gc_maxlifetime = 43200` pada konfigurasi aplikasi agar PHP secara otomatis memusnahkan berkas sesi lama dan menjaga disk server tetap 100% bersih.

---

## 3. Human Error DevOps / Webmaster / SysAdmin (Tingkat Dampak: KRITIS 🔴)

### A. Tidak Pernah Menjalankan Retensi Berkas (*Inode & Disk Exhaustion*)
* **Kasus Nyata:**  
  Webmaster mengunggah website ke cPanel, namun **tidak pernah menyetel Cron Job** untuk menjalankan script `scripts/cleanup_photos.php`.
* **Dampak Hosting:**  
  Dengan 100 pegawai aktif yang presensi masuk dan pulang setiap hari:
  * 1 hari = 200 file foto.
  * 1 tahun = **73.000 file foto**.
  * 3 tahun = **> 200.000 file foto**.  
  Shared hosting cPanel memiliki batas kuota **Inode (jumlah berkas fisik)** yang ketat (biasanya 250.000 s/d 400.000 file). Begitu kuota Inode mencapai 100%:
  1. MySQL tidak dapat membuat *temporary table*.
  2. Sesi login PHP gagal dibuat (`session_start() failed: No space left on device`).
  3. **Seluruh website tumbang total (*Catastrophic System Outage*)**.
* **Rekomendasi Solusi Teknis:**  
  Wajib mengaktifkan Cron Job otomatis di cPanel setiap tanggal 1 awal bulan:
  ```bash
  /usr/local/bin/php /home/username/public_html/scripts/cleanup_photos.php --days=180 --force > /dev/null 2>&1
  ```

---

### B. Menghapus Folder Fisik Melalui cPanel File Manager
* **Kasus Nyata:**  
  Saat melakukan backup atau pembersihan hosting manual, pengelola hosting tidak sengaja menghapus folder `assets/images/uploads/presensi/` atau `assets/images/uploads/profil/`.
* **Dampak:**  
  Saat ada pegawai yang presensi, fungsi penyimpanan akan gagal jika direktori induknya hilang:
  * Berkas foto presensi gagal tersimpan ke disk.
  * Tabel rekap harian/bulanan akan menampilkan gambar rusak (*broken image / 404*).
* **Catatan Positif:**  
  Di [ImageHelper.php](file:///c:/laragon/www/absensi_sgm/app/Helpers/ImageHelper.php) kita sudah memasang `mkdir($fullTargetDir, 0755, true)`, sehingga folder tahunan/bulanan otomatis dibuat kembali jika tidak ada. Namun izin permission parent folder tetap harus dijaga.

---

### C. Kesalahan File Permission / Ownership di Linux (`chmod` & `chown`)
* **Kasus Nyata:**  
  Admin memindahkan file via SSH menggunakan user `root` (bukan user `cpanel` atau `www-data`), sehingga direktori `assets/images/uploads` menjadi milik `root:root` dengan permission `755`.
* **Dampak:**  
  Web server (Apache/Nginx) yang berjalan di bawah user non-root tidak memiliki izin tulis (*Permission Denied*). Seluruh proses upload foto presensi dan foto profil akan gagal total tanpa pesan error yang jelas bagi pegawai.
* **Rekomendasi Solusi Teknis:**  
  Pastikan seluruh folder `uploads/` memiliki kepemilikan user web server yang sesuai:
  ```bash
  chmod -R 755 assets/images/uploads/
  chown -R www-data:www-data assets/images/uploads/ # (atau username cpanel)
  ```

---

### D. Mode Debug / Tampilan Error Dibiarkan Aktif di Server Publik
* **Kasus Nyata:**  
  Pengelola lupa mematikan `display_errors` atau mengaktifkan `ini_set('display_errors', 1)` di file konfigurasi produksi.
* **Dampak Keamanan:**  
  Jika terjadi kendala koneksi database sementara, PHP akan mencetak struktur path server hosting (`/home/sgmco/public_html/...`) dan parameter query ke layar pengunjung. Informasi ini dapat dieksploitasi oleh pihak yang tidak bertanggung jawab (*Information Disclosure*).
* **Rekomendasi Solusi Teknis:**  
  Pada lingkungan produksi, pastikan konfigurasi PHP:
  ```ini
  display_errors = Off
  log_errors = On
  error_log = /path/to/private/php_errors.log
  ```

---

## 4. 📊 Matriks Prioritas Tindakan Mitigasi Human Error

| Prioritas | Potensi Human Error | Aktor | Tingkat Dampak | Solusi Pengamanan Teknis | Status |
| :---: | :--- | :---: | :---: | :--- | :---: |
| **P1** | **Hapus Pegawai Aktif** (Riwayat absensi hilang & foto jadi sampah) | Admin | **KRITIS 🔴** | Ubah menjadi **Soft Delete** (`status = 'nonaktif'`), blokir hapus fisik jika sudah ada presensi. | Rekomendasi |
| **P2** | **Duplicate Presensi Manual** (Fatal Error 502 / PDO 1062) | Admin | **TINGGI 🔴** | Tambahkan `try...catch` pada `createManual()` untuk menangkap error constraint 1062 dengan alert ramah. | Rekomendasi |
| **P3** | **Salah Ketik Koordinat GPS & Radius** (Kepanikan absensi masal) | Admin | **TINGGI 🔴** | Validasi rentang Latitude/Longitude dan clamping batas radius minimal (>= 50 meter). | Rekomendasi |
| **P4** | **Lupa Cleanup Foto** (Inode hosting 100% penuh & web mati) | DevOps | **KRITIS 🔴** | Pasang panduan Cron Job cPanel otomatis di dokumentasi produksi & beri warning kapasitas di dashboard. | Siap Diaktifkan |
| **P5** | **Ekspor Laporan Tanpa Batas** (PHP Memory Exhaustion) | Admin | **SEDANG 🟡** | Beri batas maksimal filter tanggal ekspor (maks 31 hari harian / 1 tahun bulanan). | Rekomendasi |
| **P6** | **Pegawai Salah Blokir GPS** (Gagal absen & komplain) | Pegawai | **SEDANG 🟡** | Deteksi kode error, auto-fallback low-accuracy, panduan reset izin per browser, & tombol retry instan. | **Telah Diterapkan ✅** |
| **P7** | **Upload Lampiran Dokumen Jumbo** (Gagal di batas upload hosting) | Pegawai | **SEDANG 🟡** | Auto-kompresi gambar di sisi klien (Canvas JS native) ke 1280px (~300 KB) dengan feedback penghematan data. | **Telah Diterapkan ✅** |
| **P8** | **Double-Click Form Submit** (Pengajuan izin dobel di database) | Pegawai | **SEDANG 🟡** | Frontend form lock debounce + Backend idempotency check (`checkDuplicate()`) sebelum insert. | **Telah Diterapkan ✅** |
| **P9** | **Blokir Kamera & Kamera Rusak** (Gagal foto presensi masuk/pulang) | Pegawai | **SEDANG 🟡** | Tangkap error WebRTC spesifik, panduan aktifkan izin, retry instan, & tombol switch kamera depan/belakang. | **Telah Diterapkan ✅** |

---

*Dokumen ini menjadi acuan panduan operasional (*SOP*) tim IT, HRD, dan DevOps PT Surya Gading Mandiri dalam memelihara keandalan sistem jangka panjang.*
