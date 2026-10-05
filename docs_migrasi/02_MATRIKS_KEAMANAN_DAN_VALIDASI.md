# Dokumen 2: Matriks Keamanan & Anti-Bypass (*Security Guardrail Matrix*)
**Proyek:** Sistem Presensi & Penggajian Karyawan - CV. Surya Gemilang Mojopahit (SGM)  
**Tujuan:** Panduan Implementasi Lapisan Keamanan pada Arsitektur Laravel 11 + Inertia React  
**Versi Dokumen:** 1.0.0  
**Tanggal:** Oktober 2026  

---

## 📌 Prinsip Keamanan Sistem SGM
Aplikasi presensi berhadapan langsung dengan manipulasi presensi karyawan, risiko pembobolan akun, dan potensi kebocoran dokumen gaji/foto pegawai. Dalam migrasi ke Laravel, seluruh proteksi yang sudah kita uji di Native PHP **wajib dipertahankan 100% dan diperkuat menggunakan standar keamanan Laravel**.

---

## 🛡️ Matriks Lengkap Celah Keamanan & Solusi di Laravel

### 1. Proteksi Single Active Device Login (1 Akun = 1 Perangkat)
* **Ancaman:** Karyawan membagikan kredensial (username/password) kepada rekan kerja untuk titip absen dari HP lain saat dirinya belum sampai di kantor.
* **Mekanisme di Native PHP:** Setiap login berhasil, kolom `session_id` pada tabel `users` diperbarui. Di setiap request, `AuthController::checkAuth()` membandingkan `session_id()` peramban dengan database. Jika berbeda, sesi langsung dimatikan paksa (*force logout*).
* **Implementasi di Laravel:**
  * Buat Middleware: `app/Http/Middleware/EnsureSingleSession.php`
  * Di dalam middleware:
    ```php
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            $currentSessionId = session()->getId();
            if ($user->session_id && $user->session_id !== $currentSessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'username' => 'Akun Anda telah masuk di perangkat lain. Sesi pada perangkat ini telah diakhiri demi keamanan.'
                ]);
            }
        }
        return $next($request);
    }
    ```
  * Daftarkan middleware pada grup `web` di `bootstrap/app.php`.

---

### 2. Validasi Anti-Bypass Lokasi GPS (*Anti Fake GPS & Server-Side Haversine*)
* **Ancaman:** Karyawan memanipulasi koordinat via ekstensi peramban (*Mock Location/Fake GPS*) atau mengubah kode JavaScript React di DevTools untuk membypass pengecekan radius.
* **Aturan Kritis:** **Jangan pernah mempercayai boolean `is_inside_radius` dari Frontend React!** Pengecekan radius **wajib** dihitung ulang di server backend Laravel.
* **Implementasi di Laravel:**
  * Buat Service: `app/Services/GeoLocationService.php`
  ```php
  namespace App\Services;

  class GeoLocationService
  {
      public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
      {
          $earthRadius = 6371000; // meter
          $latFrom = deg2rad($lat1);
          $lonFrom = deg2rad($lon1);
          $latTo   = deg2rad($lat2);
          $lonTo   = deg2rad($lon2);

          $latDelta = $latTo - $latFrom;
          $lonDelta = $lonTo - $lonFrom;

          $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
              cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

          return round($angle * $earthRadius, 2);
      }
  }
  ```
  * Di `PresensiController@masukStore`:
    * Ambil data master lokasi penugasan pegawai (`$user->lokasi`).
    * Hitung jarak server-side: `$jarak = GeoLocationService::calculateDistance($userLat, $userLon, $lokasi->latitude, $lokasi->longitude);`
    * Jika `$jarak > $lokasi->radius`, tolak presensi dengan status `422 Unprocessable Entity` dan pesan jarak spesifik.

---

### 3. Keamanan File Upload & Foto Selfie Presensi WebRTC
* **Ancaman:** Pengunggahan malware, file web shell PHP tersembunyi berkedok gambar, atau eksploitasi MIME type.
* **Implementasi di Laravel:**
  * Foto presensi WebRTC dikirim dalam format Base64 dataURL (`data:image/jpeg;base64,...`).
  * Buat Form Request atau Helper Sanitizer:
    * Periksa awalan header: `preg_match('/^data:image\/(jpeg|png|webp);base64,/', $dataUrl)`.
    * Decode binary stream dan periksa header signature menggunakan `getimagesizefromstring()`.
    * Simpan file ke storage privat: `Storage::disk('local')->put("presensi/{$fileName}", $binaryData)`.
    * **Penting:** Foto presensi **tidak boleh disimpan di folder `public/`** agar tidak bisa diakses langsung via URL tanpa autentikasi.
  * Buat controller pembaca file privat: `Route::get('/storage/presensi/{filename}', [StorageController::class, 'showPresensi'])->middleware('auth');`.

---

### 4. Perlindungan CSRF (*Cross-Site Request Forgery*)
* **Mekanisme di Laravel + Inertia:**
  * Inertia.js secara otomatis membaca cookie `XSRF-TOKEN` yang di-generate Laravel dan mengirimkannya di setiap header `X-XSRF-TOKEN` pada semua request `POST`, `PUT`, `DELETE`.
  * Tidak perlu membuat input hidden `@csrf` manual di dalam form React!
  * Menggunakan helper `useForm` dari `@inertiajs/react`:
    ```jsx
    const { data, setData, post, processing, errors } = useForm({ ... });
    ```

---

### 5. Perlindungan Brute-Force & Rate Limiting
* **Ancaman:** Serangan penebakan password otomatis pada halaman login.
* **Implementasi di Laravel:**
  * Di `App\Http\Requests\Auth\LoginRequest`:
    * Pasang throttle bawaan Laravel:
      ```php
      RateLimiter::ensureIsNotRateLimited($this->throttleKey());
      ...
      RateLimiter::hit($this->throttleKey(), 60); // 5 percobaan per menit
      ```

---

### 6. Role-Based Access Control (RBAC) & Route Guard
* **Role:** `admin` (Akses penuh) vs `pegawai` (Hanya presensi mandiri, ajukan izin, dan dashboard personal).
* **Implementasi di Laravel:**
  * Buat Middleware: `app/Http/Middleware/EnsureRole.php`
  ```php
  public function handle(Request $request, Closure $next, string $role)
  {
      if (!$request->user() || $request->user()->role !== $role) {
          abort(403, 'Akses tidak diizinkan untuk tingkatan pengguna ini.');
      }
      return $next($request);
  }
  ```
  * Pasang alias middleware di `bootstrap/app.php`: `'role' => EnsureRole::class`.
  * Rute admin: `Route::middleware(['auth', 'role:admin'])->group(...)`.
  * Rute pegawai: `Route::middleware(['auth', 'role:pegawai'])->group(...)`.

---

### 7. Batasan Jam Kerja & Inactivity Session Expiration
* **Mekanisme:**
  * Di `config/session.php`:
    * `'lifetime' => 720,` (12 jam maksimal per sesi shift).
    * `'expire_on_close' => true,`
    * `'http_only' => true,`
    * `'same_site' => 'lax',`
    * `'secure' => env('SESSION_SECURE_COOKIE', false),` (Otomatis true di production HTTPS).
