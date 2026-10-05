# Dokumen 3: Mitigasi Human Error & Integritas Relasi Data
**Proyek:** Sistem Presensi & Penggajian Karyawan - CV. Surya Gemilang Mojopahit (SGM)  
**Tujuan:** Panduan Implementasi Pencegahan Human Error & Proteksi Integritas Relasi Database pada Laravel + Inertia React  
**Versi Dokumen:** 1.0.0  
**Tanggal:** Oktober 2026  

---

## 📌 Ringkasan Masalah
Dalam operasional harian, sistem tidak hanya menghadapi beban server, melainkan juga kesalahan input dan tindakan keliru dari pengguna:
1. **Operator / HRD / Admin:** Kesalahan hapus master data, input anomali angka minus, salah koordinat GPS.
2. **Pegawai / Karyawan:** Menolak izin GPS/kamera, mengklik tombol presensi berkali-kali (*spam double-click*), mengunggah berkas izin ukuran raksasa.
3. **Sistem / Database:** Kehilangan relasi kunci asing (*orphan data*) dan berkas sampah (*orphaned storage files*).

Dokumen ini memetakan seluruh pengamanan human error yang **wajib diterapkan di Laravel FormRequest, Eloquent Events, dan Komponen React**.

---

## 🛑 Bagian 1: Mitigasi Human Error Operator / Admin / HRD

### 1. Proteksi Hapus Pegawai Bersejarah (*Accidental Cascade Deletion*)
* **Potensi Masalah:** Admin menghapus akun pegawai yang sudah *resign*. Karena ada relasi database `ON DELETE CASCADE`, seluruh riwayat absensi, lembur, dan cuti pegawai tersebut dari awal bekerja ikut terhapus permanen (*data loss fatal*).
* **Solusi di Laravel:**
  * Di Model `app/Models/User.php`:
    ```php
    public function hasRiwayat(): bool
    {
        return $this->presensi()->exists() || $this->ketidakhadiran()->exists();
    }
    ```
  * Di `PegawaiController@destroy`:
    ```php
    public function destroy(User $user)
    {
        if ($user->hasRiwayat()) {
            return redirect()->back()->withErrors([
                'error' => "⚠️ Perlindungan Integritas Data Historis: Pegawai {$user->nama} tidak dapat dihapus permanen karena memiliki catatan riwayat presensi/ketidakhadiran. Silakan nonaktifkan akun agar akses ditutup tanpa merusak histori."
            ]);
        }

        // Hapus file foto profil jika ada
        if ($user->foto_profil) {
            Storage::disk('public')->delete("profil/{$user->foto_profil}");
        }

        $user->delete();
        return redirect()->route('pegawai.index')->with('success', 'Akun pegawai baru berhasil dihapus.');
    }
    ```
  * **Fitur Soft-Toggle di React UI:** Sediakan tombol `Nonaktifkan / Aktifkan` di tabel pegawai (`Pegawai/Index.jsx`). Ketika dinonaktifkan, kolom `status` menjadi `nonaktif` dan sesi aktif dihapus tanpa menyentuh data presensi.

---

### 2. Proteksi Hapus Lokasi Presensi (*Referential Guard*)
* **Potensi Masalah:** Menghapus lokasi yang sedang aktif ditugaskan ke pegawai, menyebabkan pegawai kehilangan kantor (`lokasi_id` null/orphan) dan gagal absen GPS.
* **Solusi di Laravel:**
  * Di Model `app/Models/LokasiPresensi.php`:
    ```php
    public function isUsed(): array
    {
        $userCount = $this->users()->count();
        $presensiCount = $this->presensi()->count();
        return [
            'is_used' => ($userCount > 0 || $presensiCount > 0),
            'user_count' => $userCount,
            'presensi_count' => $presensiCount
        ];
    }
    ```
  * Di `LokasiController@destroy`:
    ```php
    public function destroy(LokasiPresensi $lokasi)
    {
        $usage = $lokasi->isUsed();
        if ($usage['is_used']) {
            return redirect()->back()->withErrors([
                'error' => "Lokasi {$lokasi->nama_lokasi} terkunci: masih terhubung dengan {$usage['user_count']} pegawai aktif dan {$usage['presensi_count']} presensi."
            ]);
        }

        $lokasi->delete();
        return redirect()->route('master.lokasi.index')->with('success', 'Lokasi berhasil dihapus.');
    }
    ```
  * **Di Komponen React (`Master/Lokasi/Index.jsx`):**
    * Tampilkan kolom **Keterikatan Data** (`👥 X Pegawai`, `📅 Y Presensi`).
    * Jika `$isUsed == true`: Render tombol **🔒 Terkunci** (disabled) + Tooltip penjelasan.
    * Jika `$isUsed == false`: Render tombol **🗑️ Hapus** aktif yang membuka komponen **`<ConfirmationModal />`** (bukan `window.confirm()` bawaan browser).

---

### 3. Validasi Form Request Anti-Anomali (*Input Sanitization*)

Buat Form Request khusus di `app/Http/Requests/`:

#### A. `MasterLokasiRequest.php`:
* Latitude wajib berada di rentang `-90.0 s/d 90.0`.
* Longitude wajib berada di rentang `-180.0 s/d 180.0`.
* Radius minimal `20` meter, maksimal `50.000` meter.
* Jam masuk wajib sebelum jam pulang (`jam_masuk < jam_pulang`).

#### B. `MasterJabatanRequest.php` & `PengaturanGajiRequest.php`:
* Gaji Pokok, Uang Makan, Uang Transport, dan Tarif Lembur per jam: wajib `numeric` dan `min:0` (mencegah input minus/negatif yang mengacaukan neraca payroll).

#### C. `ImportPegawaiRequest.php`:
* Ekstensi wajib `csv,txt`, ukuran file maksimal `5MB`.
* Menggunakan `DB::transaction` saat memproses baris CSV: Jika ada 1 baris yang gagal/duplikat NIK, seluruh transaksi di-rollback agar database tidak terisi setengah-setengah.

---

## 📱 Bagian 2: Mitigasi Human Error Pegawai / End-User

### 1. Penanganan Izin Sensor Kamera & GPS Ditolak (*Dual-Sensor Recovery*)
* **Masalah:** Karyawan menekan tombol "Block" saat browser meminta izin kamera atau lokasi, lalu mengira aplikasi rusak.
* **Solusi di React (`Presensi/Masuk.jsx` & `Presensi/Keluar.jsx`):**
  * Sediakan state error terpisah: `gpsError` dan `cameraError`.
  * Jika `error.code === 1` (Permission Denied): Render banner interaktif:
    * **Chrome Android:** Panduan klik ikon gembok 🔒 di address bar &rarr; *Permissions* &rarr; Izinkan Lokasi & Kamera &rarr; *Reload*.
    * **Safari iOS:** Panduan buka *Settings &rarr; Safari &rarr; Camera / Location* &rarr; Ubah ke *Allow*.

### 2. Pencegahan Double-Submit Presensi (*Button Debouncing*)
* **Masalah:** Jaringan lambat menyebabkan karyawan menekan tombol "Presensi Masuk" berkali-kali secara cepat, memicu duplikasi data di database.
* **Solusi di React:**
  * Manfaatkan state `processing` bawaan helper `useForm` dari `@inertiajs/react`:
    ```jsx
    <button 
        type="submit" 
        disabled={processing || isOutsideRadius}
        className="btn btn-primary"
    >
        {processing ? (
            <>
                <span className="spinner"></span>
                <span>Mengirim Presensi...</span>
            </>
        ) : (
            <span>Presensi Masuk Sekarang</span>
        )}
    </button>
    ```
* **Solusi di Laravel Backend:**
  * Di `PresensiController@masukStore`:
    ```php
    $alreadyExists = Presensi::where('user_id', $user->id)
        ->whereDate('tanggal', today())
        ->exists();

    if ($alreadyExists) {
        return redirect()->back()->withErrors([
            'error' => 'Anda sudah melakukan presensi masuk untuk hari ini.'
        ]);
    }
    ```

### 3. Pembatasan Ukuran & Jenis Berkas Izin/Sakit
* **Masalah:** Pegawai mengunggah foto surat dokter langsung dari kamera beresolusi 48MP (ukuran 15–20 MB), menyebabkan server kehabisan bandwidth dan timeout.
* **Solusi di React:**
  * Validasi ukuran di sisi client sebelum dikirim:
    ```javascript
    if (file.size > 2 * 1024 * 1024) {
        alert("Ukuran file maksimal 2 MB!");
        return;
    }
    ```
* **Solusi di Laravel (`KetidakhadiranRequest.php`):**
  * Rule: `'dokumen' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048']`.

---

## 🗄️ Bagian 3: Mitigasi Beban Server & Transaksi Database

### 1. Pembungkusan Transaksi Finansial (*Database Transaction*)
* Saat Admin melakukan kalkulasi gaji bulanan massal (`GajiController@generatePayroll`):
  ```php
  DB::transaction(function () use ($bulan, $tahun) {
      // Loop seluruh pegawai
      // Hitung kehadiran, lembur, potongan
      // Insert/Update ke tabel gaji
  });
  ```
  Jika ada 1 pegawai dengan formula anomali, seluruh proses dibatalkan (*atomic roll-back*).

### 2. Otomasi Pembersihan Foto Presensi Lama (*Storage Lifecycle*)
* Foto presensi yang sudah berusia lebih dari 6 bulan dapat dibersihkan secara otomatis.
* Di Laravel, buat Command: `app/Console/Commands/CleanupOldPresensiPhotos.php`.
* Daftarkan di scheduler `routes/console.php`:
  ```php
  Schedule::command('presensi:cleanup-photos --days=180')->monthly();
  ```
