# Dokumen 1: Inventaris Fitur, Endpoint, & Pemetaan Halaman
**Proyek:** Sistem Presensi & Penggajian Karyawan - CV. Surya Gemilang Mojopahit (SGM)  
**Tujuan:** Cetak Biru (*Blueprint*) Migrasi dari Native PHP Pure MVC ke Laravel 11 + React + Inertia.js (Breeze Lightweight)  
**Versi Dokumen:** 1.0.0  
**Tanggal:** Oktober 2026  

---

## 📌 Ringkasan Arsitektur Target
* **Backend:** Laravel 11 (Routing via `routes/web.php`, Controllers, Services, FormRequests)
* **Frontend Bridge:** Inertia.js (Monolithic modern SPA, routing controller langsung merender komponen React tanpa REST API terpisah)
* **Frontend UI:** React (Functional Components + Hooks `useState`, `useEffect`, `useRef`, `useForm`) + Vite Bundler
* **Styling:** CSS Design Tokens (`resources/css/style.css` - hasil adaptasi dari arsitektur CSS Native SGM)
* **Database:** MySQL `absensi_sgm` (Tetap kompatibel 100% dengan struktur tabel yang sudah ada)

---

## 🗺️ Master Table: Pemetaan Rute Native ke Laravel + Inertia.js

| No | Modul | Rute Native URL | HTTP Method | Laravel Route Name | Target Laravel Controller | Komponen React Inertia (`resources/js/Pages/`) | Middleware Role |
|:--:|---|---|:---:|---|---|---|:---:|
| 1 | Auth | `/login` | GET | `login` | `AuthenticatedSessionController@create` | `Auth/Login.jsx` | `guest` |
| 2 | Auth | `/login` | POST | `login.store` | `AuthenticatedSessionController@store` | - (Redirect ke dashboard) | `guest` |
| 3 | Auth | `/logout` | POST / GET | `logout` | `AuthenticatedSessionController@destroy` | - (Redirect ke login) | `auth` |
| 4 | Dashboard | `/dashboard` | GET | `dashboard` | `DashboardController@index` | `Dashboard/Admin.jsx` atau `Dashboard/Pegawai.jsx` | `auth` |
| 5 | Profil | `/profil` | GET | `profile.edit` | `ProfileController@edit` | `Profile/Edit.jsx` | `auth` |
| 6 | Profil | `/profil/update` | POST / PUT | `profile.update` | `ProfileController@update` | - (Redirect back + flash) | `auth` |
| 7 | Profil | `/profil/ganti-password`| POST / PUT | `profile.password` | `ProfileController@updatePassword` | - (Redirect back + flash) | `auth` |
| 8 | Presensi | `/presensi/masuk` | GET | `presensi.masuk` | `PresensiController@masukForm` | `Presensi/Masuk.jsx` | `auth, role:pegawai` |
| 9 | Presensi | `/presensi/masuk` | POST | `presensi.masuk.store`| `PresensiController@masukStore` | - (Redirect back + flash) | `auth, role:pegawai` |
| 10| Presensi | `/presensi/keluar` | GET | `presensi.keluar` | `PresensiController@keluarForm` | `Presensi/Keluar.jsx` | `auth, role:pegawai` |
| 11| Presensi | `/presensi/keluar` | POST | `presensi.keluar.store`| `PresensiController@keluarStore`| - (Redirect back + flash) | `auth, role:pegawai` |
| 12| Master Lokasi| `/master/lokasi` | GET | `master.lokasi.index` | `LokasiController@index` | `Master/Lokasi/Index.jsx` | `auth, role:admin` |
| 13| Master Lokasi| `/master/lokasi/tambah`| GET | `master.lokasi.create`| `LokasiController@create` | `Master/Lokasi/Form.jsx` | `auth, role:admin` |
| 14| Master Lokasi| `/master/lokasi/tambah`| POST | `master.lokasi.store` | `LokasiController@store` | - (Redirect ke index) | `auth, role:admin` |
| 15| Master Lokasi| `/master/lokasi/detail`| GET | `master.lokasi.show` | `LokasiController@show` | `Master/Lokasi/Detail.jsx` | `auth, role:admin` |
| 16| Master Lokasi| `/master/lokasi/edit` | GET | `master.lokasi.edit` | `LokasiController@edit` | `Master/Lokasi/Form.jsx` | `auth, role:admin` |
| 17| Master Lokasi| `/master/lokasi/edit` | POST / PUT | `master.lokasi.update` | `LokasiController@update` | - (Redirect ke index) | `auth, role:admin` |
| 18| Master Lokasi| `/master/lokasi/hapus`| POST / DELETE | `master.lokasi.destroy` | `LokasiController@destroy` | - (Redirect ke index) | `auth, role:admin` |
| 19| Master Jabatan| `/master/jabatan` | GET | `master.jabatan.index`| `JabatanController@index` | `Master/Jabatan/Index.jsx` | `auth, role:admin` |
| 20| Master Jabatan| `/master/jabatan/tambah`| GET/POST | `master.jabatan.store` | `JabatanController@store` | `Master/Jabatan/Form.jsx` | `auth, role:admin` |
| 21| Master Jabatan| `/master/jabatan/edit` | GET/PUT | `master.jabatan.update`| `JabatanController@update` | `Master/Jabatan/Form.jsx` | `auth, role:admin` |
| 22| Master Jabatan| `/master/jabatan/hapus`| POST / DELETE | `master.jabatan.destroy`| `JabatanController@destroy` | - (Redirect ke index) | `auth, role:admin` |
| 23| Pegawai | `/pegawai` | GET | `pegawai.index` | `PegawaiController@index` | `Pegawai/Index.jsx` | `auth, role:admin` |
| 24| Pegawai | `/pegawai/tambah` | GET/POST | `pegawai.store` | `PegawaiController@store` | `Pegawai/Form.jsx` | `auth, role:admin` |
| 25| Pegawai | `/pegawai/edit` | GET/PUT | `pegawai.update` | `PegawaiController@update` | `Pegawai/Form.jsx` | `auth, role:admin` |
| 26| Pegawai | `/pegawai/hapus` | POST / DELETE | `pegawai.destroy` | `PegawaiController@destroy` | - (Redirect ke index) | `auth, role:admin` |
| 27| Pegawai | `/pegawai/toggle-status`| POST | `pegawai.toggleStatus`| `PegawaiController@toggleStatus`| - (Redirect ke index) | `auth, role:admin` |
| 28| Pegawai | `/pegawai/import` | POST | `pegawai.import` | `PegawaiController@import` | - (Redirect ke index) | `auth, role:admin` |
| 29| Pegawai | `/pegawai/template` | GET | `pegawai.template` | `PegawaiController@template` | Binary Download (.csv) | `auth, role:admin` |
| 30| Izin / Cuti | `/ketidakhadiran` | GET | `ketidakhadiran.index`| `KetidakhadiranController@index`| `Ketidakhadiran/Index.jsx` | `auth` |
| 31| Izin / Cuti | `/ketidakhadiran/ajukan`| GET/POST | `ketidakhadiran.store`| `KetidakhadiranController@store`| `Ketidakhadiran/Ajukan.jsx`| `auth, role:pegawai` |
| 32| Izin / Cuti | `/ketidakhadiran/approve`| POST | `ketidakhadiran.approve`| `KetidakhadiranController@approve`| - (Redirect back) | `auth, role:admin` |
| 33| Izin / Cuti | `/ketidakhadiran/reject` | POST | `ketidakhadiran.reject` | `KetidakhadiranController@reject` | - (Redirect back) | `auth, role:admin` |
| 34| Penggajian | `/gaji` | GET | `gaji.index` | `GajiController@index` | `Gaji/Index.jsx` | `auth, role:admin` |
| 35| Penggajian | `/gaji/export-csv` | GET | `gaji.exportCsv` | `GajiController@exportCsv` | Stream Download (.csv) | `auth, role:admin` |
| 36| Penggajian | `/gaji/sync-spreadsheet`| POST | `gaji.syncSheets` | `GajiController@syncSpreadsheet` | JSON Response / Flash | `auth, role:admin` |
| 37| Penggajian | `/gaji/simpan-pengaturan`| POST | `gaji.simpanPengaturan`| `GajiController@simpanPengaturan`| - (Redirect back) | `auth, role:admin` |
| 38| Penggajian | `/gaji/slip` | GET | `gaji.slipSingle` | `GajiController@slipSingle` | `Gaji/SlipSingle.jsx` (Printable)| `auth, role:admin` |
| 39| Penggajian | `/gaji/slip-bulk` | GET | `gaji.slipBulk` | `GajiController@slipBulk` | `Gaji/SlipBulk.jsx` (Printable)| `auth, role:admin` |
| 40| Laporan | `/laporan/harian` | GET | `laporan.harian` | `LaporanController@harian` | `Laporan/Harian.jsx` | `auth, role:admin` |
| 41| Laporan | `/laporan/bulanan` | GET | `laporan.bulanan` | `LaporanController@bulanan` | `Laporan/Bulanan.jsx` | `auth, role:admin` |
| 42| Laporan | `/laporan/export-excel` | GET | `laporan.exportExcel` | `LaporanController@exportExcel` | Stream Download (.xls) | `auth, role:admin` |
| 43| Laporan | `/laporan/export-excel-harian`| GET | `laporan.exportExcelHarian`| `LaporanController@exportExcelHarian`| Stream Download (.xls) | `auth, role:admin` |
| 44| Laporan | `/laporan/tambah-presensi`| POST | `laporan.tambahPresensi`| `LaporanController@tambahPresensi`| - (Redirect back) | `auth, role:admin` |
| 45| Laporan | `/laporan/edit-presensi` | POST / PUT | `laporan.editPresensi` | `LaporanController@editPresensi` | - (Redirect back) | `auth, role:admin` |
| 46| Laporan | `/laporan/hapus-presensi`| POST / DELETE | `laporan.hapusPresensi`| `LaporanController@hapusPresensi`| - (Redirect back) | `auth, role:admin` |
| 47| Storage | `/master/storage` | GET | `master.storage.index`| `StorageController@index` | `Master/Storage/Index.jsx` | `auth, role:admin` |
| 48| Storage | `/master/storage/cleanup`| POST | `master.storage.cleanup`| `StorageController@cleanup`| - (Redirect back) | `auth, role:admin` |

---

## 🧩 Komponen React Inti yang Wajib Dibuat (*Component Architecture*)

Untuk menjaga arsitektur React tetap bersih dan modular, berikut daftar komponen yang harus dibangun:

### 1. Layouts (`resources/js/Layouts/`)
* **`AuthenticatedLayout.jsx`**: Navbar utama, dark-mode switch, notification bell dropdown, mobile navigation menu, user profile pill, dan footer hak cipta.
* **`GuestLayout.jsx`**: Container halaman login dengan backdrop kartu elegan dan logo perusahaan.

### 2. Fitur Khusus Hardware & Peta (`resources/js/Components/`)
* **`WebcamCapture.jsx`**:
  * Menggunakan `navigator.mediaDevices.getUserMedia`.
  * Tombol switch kamera depan/belakang (`facingMode: user` vs `environment`).
  * Mirroring pintar: kamera depan di-mirror, kamera belakang tidak di-mirror.
  * Canvas snapshot outputting Base64 JPEG yang dikirim via form Inertia.
* **`LeafletMap.jsx`**:
  * Peta interaktif Leaflet untuk radius kantor dan titik pin GPS pegawai.
  * **Penting untuk SPA/Inertia:** Wajib menyertakan cleanup `map.remove()` di dalam hook `useEffect` return function untuk mencegah *memory leak* dan error canvas saat berpindah rute.
* **`LeafletPicker.jsx`**:
  * Digunakan pada form Master Lokasi untuk geser pin koordinat latitude/longitude dan slider radius lingkaran biru dinamis (`L.circle`).

### 3. Komponen UI Pendukung (*Design Tokens*)
* **`ConfirmationModal.jsx`**: Modal konfirmasi in-app pengganti native `confirm()` untuk aksi hapus lokasi, pegawai, atau jabatan.
* **`DataTable.jsx`**: Tabel pintar dengan sorting header (A-Z, Z-A, angka, tanggal) dan quick filter.
* **`DigitalClock.jsx`**: Jam digital realtime dengan overlay tanggal Indonesia untuk dashboard.
* **`Badge.jsx`**: Badge status (`Hadir`, `Terlambat`, `Izin`, `Sakit`, `Pusat`, `Cabang`, `Terkunci`).
