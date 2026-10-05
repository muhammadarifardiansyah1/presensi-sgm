<?php
/**
 * Image Processing & Optimization Helper
 * Absensi Surya Gemilang Mojopahit (SGM)
 *
 * Mengoptimalkan kompresi dan dimensi foto agar ringan, hemat bandwidth,
 * dan menghemat kuota disk penyimpanan hosting.
 */

class ImageHelper {

    /**
     * Simpan gambar dari string Base64 (Webcam) dengan validasi dan kompresi
     *
     * @param string $base64Data Data Base64 (data:image/jpeg;base64,...)
     * @param string $targetDir Direktori penyimpanan
     * @param string $prefix Awalan nama file (contoh: masuk_1)
     * @param int $maxWidth Lebar maksimal gambar (default: 640px)
     * @param int $quality Kualitas kompresi JPEG (1-100, default: 75)
     * @return string|null Nama file yang tersimpan atau null jika gagal
     */
    public static function saveBase64Optimized(
        string $base64Data, 
        string $targetDir, 
        string $prefix, 
        int $maxWidth = 640, 
        int $quality = 75
    ): ?string {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Data)) {
            return null;
        }

        $data = substr($base64Data, strpos($base64Data, ',') + 1);
        $decoded = base64_decode($data);
        if ($decoded === false) {
            return null;
        }

        // Validasi header gambar biner
        $imageInfo = @getimagesizefromstring($decoded);
        if ($imageInfo === false) {
            return null;
        }

        // 1. Buat partisi direktori berdasarkan Tahun/Bulan (YYYY/MM) untuk mencegah kelebihan inode/file pada satu folder
        $subDir = date('Y') . '/' . date('m');
        $fullTargetDir = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subDir);
        if (!is_dir($fullTargetDir)) {
            mkdir($fullTargetDir, 0755, true);
        }

        $baseFilename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $destPath = $fullTargetDir . DIRECTORY_SEPARATOR . $baseFilename;
        $relativeFilename = $subDir . '/' . $baseFilename;

        // 2. FAST PATH CPU: Jika gambar sudah berupa JPEG valid dengan dimensi <= maxWidth dan ukuran <= 150KB (sudah dikompresi di browser canvas)
        // Lewati pemrosesan GD CPU-heavy untuk menghemat hingga 90% CPU server saat jam sibuk
        $mime   = $imageInfo['mime'] ?? '';
        $width  = $imageInfo[0] ?? 0;
        $height = $imageInfo[1] ?? 0;

        if ($mime === 'image/jpeg' && $width <= $maxWidth && $height <= $maxWidth && strlen($decoded) <= 153600) {
            file_put_contents($destPath, $decoded);
            return $relativeFilename;
        }

        // 3. Fallback / Processing GD jika gambar melebihi batas dimensi atau format non-JPEG
        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            $srcImg = @imagecreatefromstring($decoded);
            if ($srcImg !== false) {
                $origWidth  = imagesx($srcImg);
                $origHeight = imagesy($srcImg);

                // Resize proporsional jika lebih besar dari maxWidth
                if ($origWidth > $maxWidth || $origHeight > $maxWidth) {
                    if ($origWidth >= $origHeight) {
                        $newWidth  = $maxWidth;
                        $newHeight = (int) round(($origHeight / $origWidth) * $maxWidth);
                    } else {
                        $newHeight = $maxWidth;
                        $newWidth  = (int) round(($origWidth / $origHeight) * $maxWidth);
                    }

                    $dstImg = imagecreatetruecolor($newWidth, $newHeight);
                    // Pertahankan kualitas warna
                    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                    imagejpeg($dstImg, $destPath, $quality);
                    imagedestroy($dstImg);
                } else {
                    // Kompresi ulang gambar dengan kualitas target
                    imagejpeg($srcImg, $destPath, $quality);
                }

                imagedestroy($srcImg);
                return $relativeFilename;
            }
        }

        // Fallback jika pemrosesan GD gagal
        file_put_contents($destPath, $decoded);
        return $relativeFilename;
    }

    /**
     * Kompres dan simpan file gambar yang diunggah via form ($_FILES)
     *
     * @param array $fileData Array elemen dari $_FILES['nama_field']
     * @param string $targetDir Direktori penyimpanan
     * @param string $prefix Awalan nama file (contoh: profil_1)
     * @param int $maxDim Dimensi maksimal (lebar/tinggi, default: 500px)
     * @param int $quality Kualitas kompresi JPEG (default: 80)
     * @return string|null Nama file yang tersimpan atau null jika gagal
     */
    public static function compressAndSaveUpload(
        array $fileData,
        string $targetDir,
        string $prefix,
        int $maxDim = 500,
        int $quality = 80
    ): ?string {
        if (empty($fileData['tmp_name']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $imageInfo = @getimagesize($fileData['tmp_name']);
        if ($imageInfo === false) {
            return null;
        }

        $mime = $imageInfo['mime'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowedMimes)) {
            return null;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $destPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        // Jika GD terpasang, resize & kompres
        if (extension_loaded('gd')) {
            $srcImg = null;
            switch ($mime) {
                case 'image/jpeg':
                    $srcImg = @imagecreatefromjpeg($fileData['tmp_name']);
                    break;
                case 'image/png':
                    $srcImg = @imagecreatefrompng($fileData['tmp_name']);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $srcImg = @imagecreatefromwebp($fileData['tmp_name']);
                    }
                    break;
            }

            if ($srcImg !== null && $srcImg !== false) {
                $origWidth  = imagesx($srcImg);
                $origHeight = imagesy($srcImg);

                // Hitung skala baru jika dimensi melebihi batas
                if ($origWidth > $maxDim || $origHeight > $maxDim) {
                    if ($origWidth >= $origHeight) {
                        $newWidth  = $maxDim;
                        $newHeight = (int) round(($origHeight / $origWidth) * $maxDim);
                    } else {
                        $newHeight = $maxDim;
                        $newWidth  = (int) round(($origWidth / $origHeight) * $maxDim);
                    }
                } else {
                    $newWidth  = $origWidth;
                    $newHeight = $origHeight;
                }

                $dstImg = imagecreatetruecolor($newWidth, $newHeight);

                // Buat latar belakang putih untuk PNG transparan jika di-convert ke JPG
                $white = imagecolorallocate($dstImg, 255, 255, 255);
                imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $white);

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagejpeg($dstImg, $destPath, $quality);

                imagedestroy($dstImg);
                imagedestroy($srcImg);
                return $filename;
            }
        }

        // Fallback jika GD tidak memproses
        if (move_uploaded_file($fileData['tmp_name'], $destPath)) {
            return $filename;
        }

        return null;
    }
}
