# Panduan Lengkap Setup Cloud Database, Google Workspace & Deploy Vercel (100% GRATIS)
## Sistem Presensi & Penggajian Karyawan - CV. Surya Gemilang Mojopahit (SGM)

Panduan ini disusun untuk mewujudkan arsitektur modern:
1. **Tanpa phpMyAdmin/MySQL lokal** (menggunakan TiDB Serverless Cloud Database - 100% Gratis permanen tanpa kartu kredit).
2. **Otomatis Masuk ke Google Spreadsheet** (data presensi real-time tercatat rapi di Spreadsheet).
3. **Foto & Dokumen Masuk ke Google Drive** (tidak membebani penyimpanan hosting).
4. **Deploy Gratis di Vercel** (kinerja kilat, 0% Lag, latensi rendah).

---

## 🚀 LANGKAH 1: Buat Database Cloud Gratis (TiDB Serverless)
> *TiDB Cloud Serverless adalah database MySQL-compatible di cloud yang gratis selamanya dengan kuota 5 GB (mampu menampung jutaan transaksi absensi).*

1. Buka situs [https://tidbcloud.com](https://tidbcloud.com) dan klik **Sign Up Free** (Bisa login langsung pakai akun Google atau GitHub). **Tidak memerlukan kartu kredit**.
2. Klik **Create Cluster** -> Pilih **Serverless** (Free).
3. Pilih Region terdekat dengan Indonesia: **Singapore (ap-southeast-1)** agar website super cepat.
4. Beri nama cluster, lalu klik **Create**.
5. Setelah cluster siap (sekitar 10 detik), klik tombol **Connect** di pojok kanan atas.
6. Pilih **Endpoint Type: Public** dan lihat detail koneksi:
   - **Host:** (contoh: `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`)
   - **Port:** `4000`
   - **User:** (contoh: `xxxxxx.root`)
   - **Password:** Klik tombol *Generate Password* dan simpan password Anda.
   - **Database:** `absensi_sgm`
7. **Import Struktur Tabel:**
   - Di dashboard TiDB Cloud, buka tab **SQL Editor**.
   - Buka file [database/absensi_sgm.sql](file:///c:/laragon/www/absensi_sgm/database/absensi_sgm.sql) di komputer Anda, salin seluruh kodenya, tempel di SQL Editor TiDB Cloud, lalu klik **Run**.
   - *Atau via Terminal lokal:*
     ```powershell
     $env:DB_HOST="gateway01.ap-southeast-1.prod.aws.tidbcloud.com"
     $env:DB_PORT="4000"
     $env:DB_USER="xxxxxx.root"
     $env:DB_PASS="PasswordTiDBAnda"
     $env:DB_NAME="absensi_sgm"
     $env:DB_SSL="true"
     php migrate.php
     ```

---

## 📁 LANGKAH 2: Setup Google Spreadsheet & Google Drive
1. **Buat Folder di Google Drive:**
   - Buka [Google Drive](https://drive.google.com).
   - Buat folder baru, misalnya: `Foto Presensi SGM`.
   - Buka folder tersebut, lalu lihat URL di address bar browser:
     `https://drive.google.com/drive/folders/`**`1aBcDeFgHiJkLmNoPqRsTuVwXyZ`**
   - Salin kode acak di akhir URL tersebut (itulah **Folder ID** Anda).

2. **Buat Google Spreadsheet & Pasang Apps Script:**
   - Buat file Google Spreadsheet baru di Google Drive Anda, beri nama misalnya: `Database Rekap Presensi SGM`.
   - Klik menu atas: **Ekstensi (Extensions)** -> **Apps Script**.
   - Hapus semua tulisan di editor Apps Script.
   - Buka file [google_apps_script.js](file:///c:/laragon/www/absensi_sgm/google_apps_script.js) di proyek ini, salin seluruh isinya, dan tempel ke editor Apps Script.
   - Cari baris ke-26:
     ```javascript
     const GOOGLE_DRIVE_FOLDER_ID = "MASUKKAN_FOLDER_ID_DRIVE_DISINI";
     ```
     Ganti dengan Folder ID yang Anda salin pada langkah 1.
   - Klik ikon **Simpan** (ikon disket).

3. **Deploy Web App (PENTING):**
   - Klik tombol biru **Deploy** di kanan atas -> pilih **New deployment (Deployment baru)**.
   - Pada ikon gerigi di samping *Select type*, pilih **Web app**.
   - Masukkan konfigurasi:
     - **Description:** `SGM Webhook Presensi`
     - **Execute as:** `Me (email Anda)`
     - **Who has access:** `Anyone` *(Wajib pilih "Anyone" agar sistem web dapat mengirim data tanpa login akun Google)*.
   - Klik **Deploy**.
   - Klik **Authorize access** -> Pilih akun Google Anda -> Klik *Advanced* (Lanjutan) -> Klik *Go to Untitled project (unsafe)* -> Klik **Allow**.
   - Salin **Web app URL** yang muncul (format: `https://script.google.com/macros/s/AKfycb.../exec`).

---

## ⚡ LANGKAH 3: Deploy ke Vercel (100% GRATIS)
1. Unggah (*push*) proyek web ini ke akun **GitHub** Anda.
2. Buka [Vercel](https://vercel.com) dan login dengan akun GitHub Anda.
3. Klik tombol **Add New...** -> **Project** -> Pilih repository GitHub web ini.
4. Buka accordion **Environment Variables**, lalu tambahkan variabel berikut:

| Key / Nama Variabel | Value / Nilai |
|---|---|
| `DB_HOST` | Host TiDB Cloud (misal: `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`) |
| `DB_PORT` | `4000` |
| `DB_USER` | Username TiDB Cloud (misal: `xxxxxx.root`) |
| `DB_PASS` | Password TiDB Cloud Anda |
| `DB_NAME` | `absensi_sgm` |
| `DB_SSL` | `true` |
| `GOOGLE_WEBHOOK_URL` | Web app URL dari Google Apps Script Langkah 2 |

5. Klik tombol **Deploy**!
6. Dalam waktu ~30 detik, website Anda sudah live di URL `https://nama-project.vercel.app`!

---

## 🎯 Mengapa Arsitektur Ini Bebas Lag & 100% Gratis?
- **Kecepatan (< 100ms):** Database TiDB Cloud Serverless menggunakan storage SSD NVMe di region Singapura, memastikan akses data presensi, validasi geofencing, dan dashboard admin instan tanpa loading lambat.
- **Otomasi Spreadsheet:** Setiap karyawan absen masuk atau pulang, baris data langsung tercatat di Google Spreadsheet Anda secara real-time.
- **Penyimpanan Drive:** Foto selfie langsung terkirim ke Google Drive Anda dan link publiknya otomatis terhubung ke sistem.
- **Biaya Rp 0:** Seluruh komponen (TiDB Serverless tier gratis, Google Drive 15GB gratis, Google Spreadsheet gratis, Vercel Hobby gratis) 100% bebas biaya langganan sepeserpun.
