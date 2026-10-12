<?php
/**
 * Google Workspace Sync Helper (Spreadsheet & Google Drive)
 * Absensi Surya Gemilang Mojopahit (SGM)
 *
 * Mengintegrasikan database presensi dengan Google Spreadsheet secara real-time
 * dan mengunggah foto selfie presensi / dokumen bukti ke Google Drive tanpa lag.
 */

require_once __DIR__ . '/../Config/Config.php';

class GoogleSyncHelper {

    /**
     * Dapatkan URL Webhook Google Apps Script
     */
    public static function getWebhookUrl(): string {
        $envUrl = getenv('GOOGLE_WEBHOOK_URL');
        if (!empty($envUrl)) {
            return trim($envUrl);
        }
        return Config::getSpreadsheetWebhookUrl();
    }

    /**
     * Cek apakah webhook Google Apps Script telah dikonfigurasi
     */
    public static function isConfigured(): bool {
        $url = self::getWebhookUrl();
        return !empty($url) && filter_var($url, FILTER_VALIDATE_URL) && strpos($url, 'script.google.com/macros/s/') !== false;
    }

    /**
     * Kirim data presensi (Masuk / Pulang) + Foto Selfie ke Google Spreadsheet & Google Drive
     *
     * @param array $data Payload presensi
     * @return array ['success' => bool, 'drive_url' => ?string, 'message' => string]
     */
    public static function syncAttendance(array $data): array {
        if (!self::isConfigured()) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => 'Google Apps Script Webhook belum dikonfigurasi.'
            ];
        }

        $url = self::getWebhookUrl();
        $payload = array_merge([
            'action' => 'presensi',
            'timestamp' => date('Y-m-d H:i:s')
        ], $data);

        return self::sendPostRequest($url, $payload, 5);
    }

    /**
     * Unggah berkas dokumen (bukti sakit/izin/cuti) ke Google Drive & rekap ke Spreadsheet
     *
     * @param string $localFilePath Path fisik file di server
     * @param string $originalName Nama file asli
     * @param array $extraData Data izin (nama, kategori, tanggal, dll)
     * @return array ['success' => bool, 'drive_url' => ?string, 'message' => string]
     */
    public static function syncDocument(string $localFilePath, string $originalName, array $extraData = []): array {
        if (!self::isConfigured()) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => 'Google Apps Script Webhook belum dikonfigurasi.'
            ];
        }

        if (!file_exists($localFilePath) || !is_readable($localFilePath)) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => 'File dokumen tidak ditemukan.'
            ];
        }

        $fileContent = @file_get_contents($localFilePath);
        if ($fileContent === false) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => 'Gagal membaca konten file dokumen.'
            ];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $localFilePath) ?: 'application/octet-stream';
        finfo_close($finfo);

        $base64 = base64_encode($fileContent);

        $payload = array_merge([
            'action' => 'dokumen',
            'filename' => $originalName,
            'mime_type' => $mime,
            'file_base64' => $base64,
            'timestamp' => date('Y-m-d H:i:s')
        ], $extraData);

        return self::sendPostRequest(self::getWebhookUrl(), $payload, 7);
    }

    /**
     * Helper cURL POST dengan proteksi FollowLocation 302 (Khas Google Apps Script)
     * dan batas timeout terukur agar tidak memperlambat respon web pengguna.
     */
    private static function sendPostRequest(string $url, array $payload, int $timeoutSeconds = 5): array {
        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => 'Ekstensi PHP cURL tidak aktif.'
            ];
        }

        $jsonData = json_encode($payload);
        if ($jsonData === false) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => 'Gagal serialize payload JSON.'
            ];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ],
            // Google Apps Script merespon dengan HTTP 302 Redirect ke script.googleusercontent.com
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'SGM-Attendance-App/1.0'
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => 'Koneksi ke Google Webhook gagal/timeout: ' . $curlError
            ];
        }

        if ($httpCode !== 200 && $httpCode !== 302) {
            return [
                'success' => false,
                'drive_url' => null,
                'message' => "Google Apps Script merespon dengan kode HTTP {$httpCode}"
            ];
        }

        $json = @json_decode($response, true);
        if (!is_array($json)) {
            // Bisa jadi Google Apps Script merespon text murni
            return [
                'success' => true,
                'drive_url' => null,
                'message' => 'Terkirim ke Google Apps Script (non-json response).'
            ];
        }

        $isSuccess = ($json['status'] ?? '') === 'success' || ($json['success'] ?? false) === true;
        return [
            'success' => $isSuccess,
            'drive_url' => $json['drive_url'] ?? $json['file_url'] ?? null,
            'message' => $json['message'] ?? ($isSuccess ? 'Sinkronisasi berhasil' : 'Gagal memproses data di Google Apps Script')
        ];
    }

    /**
     * Fire-and-forget: Kirim data ke Google Apps Script TANPA menunggu response
     * Presensi langsung tersimpan di database, sync ke Google menyusul di background
     * Timeout hanya 1 detik — jika server Google tidak merespon, tidak menghambat user
     */
    public static function syncAttendanceAsync(array $data): void {
        if (!self::isConfigured()) {
            return;
        }

        $url = self::getWebhookUrl();
        $payload = array_merge([
            'action' => 'presensi',
            'timestamp' => date('Y-m-d H:i:s')
        ], $data);

        $jsonData = json_encode($payload);
        if ($jsonData === false) {
            return;
        }

        // Fire-and-forget: kirim tanpa menunggu response
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData)
                ],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 2,        // Maks 2 detik total
                CURLOPT_CONNECTTIMEOUT => 1,  // Maks 1 detik koneksi
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'SGM-Attendance-App/1.0-async',
                CURLOPT_NOSIGNAL => 1         // Prevent signal interference
            ]);
            @curl_exec($ch);
            curl_close($ch);
        }
    }
}
