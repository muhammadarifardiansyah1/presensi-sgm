<?php
/**
 * Lightweight Spreadsheet Helper (CSV & XLSX)
 * Absensi Surya Gemilang Mojopahit (SGM)
 *
 * Implements native PHP parsing without bloated 3rd-party dependencies.
 * Handles auto-detection of delimiters, UTF-8 BOM, and basic .xlsx XML streams.
 */

class SpreadsheetHelper {

    /**
     * Parse CSV or XLSX file and return array of rows with normalized associative keys.
     */
    public static function parse(string $filePath, string $originalName = ''): array {
        $ext = strtolower(pathinfo($originalName ?: $filePath, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            return self::parseXlsx($filePath);
        }

        return self::parseCsv($filePath);
    }

    /**
     * Parse CSV with auto-detected delimiter and UTF-8 BOM removal
     */
    public static function parseCsv(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("File CSV tidak dapat dibaca.");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Gagal membuka file CSV.");
        }

        // 1. Detect BOM & strip it from first line
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        // 2. Detect Delimiter: comma, semicolon, tab, or pipe
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $d) {
            $count = count(str_getcsv($firstLine, $d, '"', "\\"));
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $d;
            }
        }

        // Parse header
        $rawHeaders = str_getcsv($firstLine, $bestDelimiter, '"', "\\");
        $headers = self::normalizeHeaders($rawHeaders);

        $rows = [];
        $lineNum = 2;

        while (($data = fgetcsv($handle, 0, $bestDelimiter, '"', "\\")) !== false) {
            if (empty($data) || (count($data) === 1 && trim($data[0] ?? '') === '')) {
                $lineNum++;
                continue; // Skip empty rows
            }

            $row = ['_line_num' => $lineNum++];
            foreach ($headers as $index => $key) {
                if ($key !== null) {
                    $row[$key] = trim($data[$index] ?? '');
                }
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Parse XLSX file using native ZipArchive + SimpleXML
     */
    public static function parseXlsx(string $filePath): array {
        if (!class_exists('ZipArchive')) {
            throw new Exception("Ekstensi PHP ZipArchive tidak aktif.");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Gagal membuka file Excel (.xlsx). Pastikan format file valid.");
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $item) {
                    if (isset($item->t)) {
                        $sharedStrings[] = (string)$item->t;
                    } elseif (isset($item->r)) {
                        $text = '';
                        foreach ($item->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first worksheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Fallback: check if there's any worksheet
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $name)) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            throw new Exception("Tidak menemukan sheet data pada file Excel.");
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return [];
        }

        $matrix = [];
        foreach ($xml->sheetData->row as $rowNode) {
            $rowNum = (int)$rowNode['r'];
            $rowData = [];

            foreach ($rowNode->c as $cell) {
                $cellRef = (string)$cell['r']; // e.g. A1, B2
                $colIndex = self::columnLetterToIndex(preg_replace('/\d+/', '', $cellRef));
                $cellType = (string)$cell['t']; // 's' for shared string
                $val = (string)$cell->v;

                if ($cellType === 's' && isset($sharedStrings[(int)$val])) {
                    $cellValue = $sharedStrings[(int)$val];
                } elseif ($cellType === 'inlineStr' && isset($cell->is->t)) {
                    $cellValue = (string)$cell->is->t;
                } else {
                    $cellValue = $val;
                }

                $rowData[$colIndex] = trim($cellValue);
            }

            $matrix[$rowNum] = $rowData;
        }

        if (empty($matrix)) {
            return [];
        }

        // Sort by row numbers
        ksort($matrix);

        // Header row
        $firstRowKey = array_key_first($matrix);
        $headerRow = $matrix[$firstRowKey];
        unset($matrix[$firstRowKey]);

        // Find max col index in header
        $maxCol = !empty($headerRow) ? max(array_keys($headerRow)) : 0;
        $rawHeaders = [];
        for ($c = 0; $c <= $maxCol; $c++) {
            $rawHeaders[$c] = $headerRow[$c] ?? '';
        }
        $headers = self::normalizeHeaders($rawHeaders);

        $rows = [];
        foreach ($matrix as $rNum => $rowData) {
            // Check if row is empty
            $hasContent = false;
            foreach ($rowData as $val) {
                if ($val !== '') {
                    $hasContent = true;
                    break;
                }
            }
            if (!$hasContent) {
                continue;
            }

            $row = ['_line_num' => $rNum];
            foreach ($headers as $index => $key) {
                if ($key !== null) {
                    $row[$key] = $rowData[$index] ?? '';
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Convert Excel column letter (A, B, ..., Z, AA, AB) to 0-based index.
     */
    private static function columnLetterToIndex(string $col): int {
        $col = strtoupper($col);
        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    /**
     * Map arbitrary header strings to standard field keys
     */
    private static function normalizeHeaders(array $rawHeaders): array {
        $headers = [];
        foreach ($rawHeaders as $index => $raw) {
            $clean = strtolower(trim($raw));
            // remove non-alphanumeric except underscore and space
            $clean = preg_replace('/[^a-z0-9_ ]/', '', $clean);
            $clean = preg_replace('/\s+/', '_', $clean);

            if (in_array($clean, ['nama', 'nama_pegawai', 'nama_karyawan', 'name', 'full_name'])) {
                $headers[$index] = 'nama';
            } elseif (in_array($clean, ['username', 'user_name', 'user', 'akun'])) {
                $headers[$index] = 'username';
            } elseif (in_array($clean, ['password', 'kata_sandi', 'pass', 'pwd'])) {
                $headers[$index] = 'password';
            } elseif (in_array($clean, ['jenis_kelamin', 'gender', 'jk', 'sex'])) {
                $headers[$index] = 'jenis_kelamin';
            } elseif (in_array($clean, ['jabatan', 'posisi', 'divisi', 'role_jabatan'])) {
                $headers[$index] = 'jabatan';
            } elseif (in_array($clean, ['lokasi_presensi', 'lokasi', 'kantor', 'penempatan', 'cabang'])) {
                $headers[$index] = 'lokasi_presensi';
            } elseif (in_array($clean, ['no_handphone', 'nohp', 'no_hp', 'telepon', 'phone', 'hp', 'wa'])) {
                $headers[$index] = 'no_handphone';
            } elseif (in_array($clean, ['alamat', 'alamat_rumah', 'domisili', 'address'])) {
                $headers[$index] = 'alamat';
            } elseif (in_array($clean, ['role', 'hak_akses', 'level'])) {
                $headers[$index] = 'role';
            } elseif (in_array($clean, ['status', 'status_pegawai', 'keaktifan'])) {
                $headers[$index] = 'status';
            } else {
                // Ignore unrecognized columns or keep as null
                $headers[$index] = null;
            }
        }
        return $headers;
    }

    /**
     * Generate authentic .xlsx Excel Template using native ZipArchive + OpenXML
     */
    public static function generateTemplateXlsx(): string {
        $tempFile = tempnam(sys_get_temp_dir(), 'sgm_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Gagal membuat file template Excel.");
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Data Pegawai" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml (Header styling with emerald green fill and bold white font)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><name val="Calibri"/><sz val="11"/></font>
        <font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/><sz val="11"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF059669"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/></border>
        <border>
            <left style="thin"><color rgb="FFD1D5DB"/></left>
            <right style="thin"><color rgb="FFD1D5DB"/></right>
            <top style="thin"><color rgb="FFD1D5DB"/></top>
            <bottom style="thin"><color rgb="FFD1D5DB"/></bottom>
        </border>
    </borders>
    <cellXfs count="3">
        <xf fontId="0" fillId="0" borderId="0"/>
        <xf fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf fontId="0" fillId="0" borderId="1" applyFont="1" applyBorder="1"/>
    </cellXfs>
</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $headers = [
            'Nama Pegawai',
            'Username',
            'Password',
            'Jenis Kelamin',
            'Jabatan',
            'Lokasi Presensi',
            'No Handphone',
            'Alamat',
            'Role',
            'Status'
        ];

        $sampleRows = [
            [
                'Budi Santoso',
                'budi.santoso',
                '12345678',
                'Laki-laki',
                'Staff Operasional',
                'Asrama Banua',
                '081234567890',
                'Jl. Ahmad Yani No. 12, Banjarbaru',
                'pegawai',
                'aktif'
            ],
            [
                'Siti Aminah',
                'siti.aminah',
                '',
                'Perempuan',
                'Finance & Accounting',
                'Asrama Banua',
                '085678901234',
                'Jl. Gatot Subroto No. 45, Banjarmasin',
                'pegawai',
                'aktif'
            ]
        ];

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <cols>
        <col min="1" max="1" width="26" customWidth="1"/>
        <col min="2" max="2" width="20" customWidth="1"/>
        <col min="3" max="3" width="16" customWidth="1"/>
        <col min="4" max="4" width="16" customWidth="1"/>
        <col min="5" max="5" width="24" customWidth="1"/>
        <col min="6" max="6" width="22" customWidth="1"/>
        <col min="7" max="7" width="18" customWidth="1"/>
        <col min="8" max="8" width="38" customWidth="1"/>
        <col min="9" max="9" width="14" customWidth="1"/>
        <col min="10" max="10" width="14" customWidth="1"/>
    </cols>
    <sheetData>';

        // Header row
        $sheet .= '<row r="1" ht="28" customHeight="1">';
        foreach ($headers as $idx => $h) {
            $colLetter = chr(65 + $idx);
            $sheet .= '<c r="' . $colLetter . '1" t="inlineStr" s="1"><is><t>' . htmlspecialchars($h, ENT_XML1) . '</t></is></c>';
        }
        $sheet .= '</row>';

        // Data rows
        $rowIdx = 2;
        foreach ($sampleRows as $rData) {
            $sheet .= '<row r="' . $rowIdx . '" ht="22" customHeight="1">';
            foreach ($rData as $idx => $val) {
                $colLetter = chr(65 + $idx);
                $sheet .= '<c r="' . $colLetter . $rowIdx . '" t="inlineStr" s="2"><is><t>' . htmlspecialchars($val, ENT_XML1) . '</t></is></c>';
            }
            $sheet .= '</row>';
            $rowIdx++;
        }

        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);
        return $content;
    }

    /**
     * Generate CSV Template with UTF-8 BOM for Microsoft Excel compatibility
     */
    public static function generateTemplateCsv(): string {
        $headers = [
            'Nama Pegawai',
            'Username',
            'Password',
            'Jenis Kelamin',
            'Jabatan',
            'Lokasi Presensi',
            'No Handphone',
            'Alamat',
            'Role',
            'Status'
        ];

        $sampleRows = [
            [
                'Budi Santoso',
                'budi.santoso',
                '12345678',
                'Laki-laki',
                'Staff Operasional',
                'Asrama Banua',
                '081234567890',
                'Jl. Ahmad Yani No. 12, Banjarbaru',
                'pegawai',
                'aktif'
            ],
            [
                'Siti Aminah',
                'siti.aminah',
                '',
                'Perempuan',
                'Finance & Accounting',
                'Asrama Banua',
                '085678901234',
                'Jl. Gatot Subroto No. 45, Banjarmasin',
                'pegawai',
                'aktif'
            ]
        ];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $fp = fopen('php://temp', 'r+');

        // Pass all 5 arguments to fputcsv to prevent PHP 8.4 deprecation notices
        fputcsv($fp, $headers, ',', '"', "\\");
        foreach ($sampleRows as $row) {
            fputcsv($fp, $row, ',', '"', "\\");
        }

        rewind($fp);
        $output .= stream_get_contents($fp);
        fclose($fp);

        return $output;
    }

    /**
     * Generate Payroll Summary CSV with UTF-8 BOM for Microsoft Excel & Google Sheets
     */
    public static function generatePayrollCsv(array $payrollData, string $periode = ''): string {
        $headers = [
            'No',
            'NIK',
            'Nama Pegawai',
            'Jabatan',
            'Status (L/P)',
            'Lokasi Presensi',
            'Hari Hadir',
            'Absen (Hari)',
            'Pengganti Libur (Hari)',
            'Lembur (Jam)',
            'Gaji Pokok (Rp)',
            'Uang Pengganti Libur (Rp)',
            'Uang Lembur (Rp)',
            'Potongan Absen (Rp)',
            'Lain-Lain (Rp)',
            'Gaji Bersih / Total Diterima (Rp)',
            'Keterangan'
        ];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $fp = fopen('php://temp', 'r+');

        fputcsv($fp, ['REKAP PENGGAJIAN & LAPORAN GAJI KARYAWAN'], ',', '"', "\\");
        if ($periode) {
            fputcsv($fp, ['Periode: ' . $periode], ',', '"', "\\");
        }
        fputcsv($fp, [], ',', '"', "\\"); // Empty line

        fputcsv($fp, $headers, ',', '"', "\\");

        $no = 1;
        foreach ($payrollData as $r) {
            $row = [
                $no++,
                $r['nik'],
                $r['nama'],
                $r['jabatan'],
                $r['status_gender'] ?? 'L',
                $r['nama_lokasi'] ?? '',
                $r['hadir'] ?? 0,
                $r['alpa'] ?? 0,
                $r['pengganti_libur_hari'] ?? 0,
                number_format((float)($r['jam_lembur'] ?? 0), 2, '.', ''),
                (float)($r['gaji_pokok'] ?? 0),
                (float)($r['uang_pengganti_libur'] ?? 0),
                (float)($r['uang_lembur'] ?? 0),
                (float)($r['potongan_alpa'] ?? 0),
                (float)($r['lain_lain'] ?? 0),
                (float)($r['take_home_pay'] ?? 0),
                $r['keterangan_penyesuaian'] ?? ''
            ];
            fputcsv($fp, $row, ',', '"', "\\");
        }

        rewind($fp);
        $output .= stream_get_contents($fp);
        fclose($fp);

        return $output;
    }
}
