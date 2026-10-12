/**
 * ==============================================================================
 * GOOGLE APPS SCRIPT: BACKEND SINKRONISASI SPREADSHEET & GOOGLE DRIVE (100% GRATIS)
 * SISTEM PRESENSI CV. SURYA GEMILANG MOJOPAHIT (SGM)
 * ==============================================================================
 * 
 * PANDUAN CARA PASANG:
 * 1. Buka Google Drive Anda (drive.google.com).
 * 2. Buat Folder Baru, beri nama misalnya: "Foto Presensi SGM".
 * 3. Buka folder tersebut, salin FOLDER ID dari URL browser:
 *    Contoh URL: https://drive.google.com/drive/folders/1aBcDeFgHiJkLmNoPqRsTuVwXyZ
 *    -> FOLDER ID-nya adalah: 1aBcDeFgHiJkLmNoPqRsTuVwXyZ
 * 4. Buat Google Spreadsheet baru di Google Drive Anda.
 * 5. Di Spreadsheet, klik menu: Ekstensi (Extensions) -> Apps Script.
 * 6. Hapus semua kode default, lalu COPAS (TEMPEL) seluruh isi file ini.
 * 7. Ganti "MASUKKAN_FOLDER_ID_DRIVE_DISINI" pada baris 26 dengan ID Folder Anda.
 * 8. Klik tombol "Simpan" (ikon disket).
 * 9. Klik tombol "Deploy" (di kanan atas) -> "New deployment" (Deployment baru).
 * 10. Pilih type: "Web app".
 * 11. Konfigurasi:
 *     - Description: "SGM Webhook v1"
 *     - Execute as: "Me (email Anda)"
 *     - Who has access: "Anyone" (Siapa saja - PENTING agar web bisa kirim data)
 * 12. Klik "Deploy", lalu berikan izin akses Google ("Authorize access").
 * 13. Salin "Web app URL" (format: https://script.google.com/macros/s/.../exec).
 * 14. Tempel Web app URL tersebut ke:
 *     - Pengaturan di Web SGM (/gaji -> Pengaturan Webhook), atau
 *     - Environment Variable Vercel: GOOGLE_WEBHOOK_URL
 * ==============================================================================
 */

// GANTI DENGAN ID FOLDER GOOGLE DRIVE ANDA:
const GOOGLE_DRIVE_FOLDER_ID = "MASUKKAN_FOLDER_ID_DRIVE_DISINI";

/**
 * Endpoint Utama Menangani POST Request dari Web Absensi
 */
function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return jsonResponse({ status: "error", message: "Payload request kosong." });
    }

    const data = JSON.parse(e.postData.contents);
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    const action = data.action || "presensi";

    // 1. HANDLER: DOKUMEN BUKTI IZIN / SAKIT / CUTI
    if (action === "dokumen") {
      let fileUrl = "";
      if (data.file_base64 && GOOGLE_DRIVE_FOLDER_ID && GOOGLE_DRIVE_FOLDER_ID !== "MASUKKAN_FOLDER_ID_DRIVE_DISINI") {
        fileUrl = uploadBase64ToDrive(
          data.file_base64,
          data.filename || ("dokumen_" + Date.now()),
          data.mime_type || "application/pdf"
        );
      }

      // Catat ke Sheet "Ketidakhadiran"
      let sheetIzin = getOrCreateSheet(ss, "Ketidakhadiran", [
        "Waktu Submit", "NIK", "Nama Pegawai", "Kategori", "Tgl Mulai", "Tgl Selesai", "Keterangan", "Link Dokumen Drive"
      ]);

      sheetIzin.appendRow([
        formatJakartaTime(new Date()),
        data.nik || "-",
        data.nama || "-",
        (data.kategori || "izin").toUpperCase(),
        data.tanggal_mulai || "-",
        data.tanggal_selesai || "-",
        data.keterangan || "-",
        fileUrl || "-"
      ]);

      return jsonResponse({
        status: "success",
        message: "Dokumen berhasil disimpan ke Google Drive & dicatat ke Spreadsheet!",
        drive_url: fileUrl
      });
    }

    // 2. HANDLER: PRESENSI MASUK & PULANG
    if (action === "presensi") {
      let photoUrl = "";
      if (data.image_base64 && GOOGLE_DRIVE_FOLDER_ID && GOOGLE_DRIVE_FOLDER_ID !== "MASUKKAN_FOLDER_ID_DRIVE_DISINI") {
        photoUrl = uploadBase64ToDrive(
          data.image_base64,
          data.filename || ("absen_" + (data.nik || "user") + "_" + Date.now()),
          "image/jpeg"
        );
      }

      const sheetPresensi = getOrCreateSheet(ss, "Presensi", [
        "Tanggal", "NIK", "Nama Pegawai", "Jabatan", "Lokasi", 
        "Jam Masuk", "Jam Pulang", "Total Jam Kerja", "Status", "Keterangan Terlambat", 
        "Link Foto Masuk", "Link Foto Pulang", "Koordinat GPS"
      ]);

      const tgl = data.tanggal || formatJakartaDate(new Date());
      const nik = data.nik || "-";

      // Cek apakah sudah ada baris untuk pegawai ini di tanggal ini (untuk update jam pulang)
      let existingRowIndex = -1;
      const values = sheetPresensi.getDataRange().getValues();
      for (let i = 1; i < values.length; i++) {
        const rowTgl = values[i][0] instanceof Date ? formatJakartaDate(values[i][0]) : String(values[i][0]);
        const rowNik = String(values[i][1]);
        if (rowTgl === tgl && rowNik === nik) {
          existingRowIndex = i + 1; // 1-indexed baris spreadsheet
          break;
        }
      }

      if (data.tipe === "pulang" && existingRowIndex > 0) {
        // Update data pulang di baris yang sudah ada
        if (data.jam_pulang) sheetPresensi.getRange(existingRowIndex, 7).setValue(data.jam_pulang);
        if (data.total_jam_kerja) sheetPresensi.getRange(existingRowIndex, 8).setValue(data.total_jam_kerja);
        if (photoUrl) sheetPresensi.getRange(existingRowIndex, 12).setValue(photoUrl);
      } else if (existingRowIndex > 0) {
        // Update baris yang sudah ada
        if (data.jam_masuk) sheetPresensi.getRange(existingRowIndex, 6).setValue(data.jam_masuk);
        if (photoUrl) sheetPresensi.getRange(existingRowIndex, 11).setValue(photoUrl);
      } else {
        // Buat baris baru
        sheetPresensi.appendRow([
          tgl,
          nik,
          data.nama || "-",
          data.jabatan || "-",
          data.lokasi || "-",
          data.jam_masuk || "-",
          data.jam_pulang || "-",
          data.total_jam_kerja || "-",
          (data.status || "hadir").toUpperCase(),
          data.terlambat || "Tepat Waktu",
          (data.tipe === "masuk" ? photoUrl : "-"),
          (data.tipe === "pulang" ? photoUrl : "-"),
          data.latitude ? (data.latitude + ", " + data.longitude) : "-"
        ]);
      }

      return jsonResponse({
        status: "success",
        message: "Data presensi tercatat di Spreadsheet dan foto tersimpan di Google Drive!",
        drive_url: photoUrl
      });
    }

    // 3. HANDLER: REKAP BULANAN BATCH (Dukungan Fitur Sinkronisasi Laporan)
    if (data.sheet_name && data.rows && Array.isArray(data.rows)) {
      const sheetBatch = getOrCreateSheet(ss, data.sheet_name, [
        "No", "NIK", "Nama Pegawai", "Jabatan", "Lokasi", "Tanggal", "Jam Masuk", "Status Masuk", "Keterangan", "Jam Pulang", "Total Jam Kerja"
      ]);

      data.rows.forEach(function(r) {
        sheetBatch.appendRow([
          r.no || "-",
          r.nik || "-",
          r.nama || "-",
          r.jabatan || "-",
          r.lokasi || "-",
          r.tanggal || "-",
          r.jam_masuk || "-",
          r.status_masuk || "-",
          r.terlambat || "-",
          r.jam_pulang || "-",
          r.total_jam_kerja || "-"
        ]);
      });

      return jsonResponse({
        status: "success",
        message: "Rekap bulanan berhasil disinkronkan ke sheet " + data.sheet_name
      });
    }

    return jsonResponse({ status: "success", message: "Webhook diterima." });

  } catch (err) {
    return jsonResponse({ status: "error", message: err.toString() });
  }
}

/**
 * Helper Upload File Base64 ke Google Drive & Return Direct Link
 */
function uploadBase64ToDrive(base64Data, filename, mimeType) {
  try {
    const folder = DriveApp.getFolderById(GOOGLE_DRIVE_FOLDER_ID);
    const cleanBase64 = base64Data.replace(/^data:[a-zA-Z0-9\/\+]+;base64,/, "");
    const decodedBytes = Utilities.base64Decode(cleanBase64);
    
    let ext = ".jpg";
    if (mimeType.indexOf("pdf") !== -1) ext = ".pdf";
    else if (mimeType.indexOf("png") !== -1) ext = ".png";

    const cleanFilename = filename.indexOf(ext) !== -1 ? filename : (filename + ext);
    const blob = Utilities.newBlob(decodedBytes, mimeType, cleanFilename);
    const file = folder.createFile(blob);

    // Set permission agar foto bisa dilihat oleh admin/web via link
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);

    // Kembalikan direct thumbnail / view URL
    const fileId = file.getId();
    if (mimeType.indexOf("image") !== -1) {
      return "https://lh3.googleusercontent.com/d/" + fileId;
    }
    return file.getUrl();
  } catch (e) {
    return "";
  }
}

/**
 * Helper Membuat atau Memilih Sheet dengan Header Elegan
 */
function getOrCreateSheet(ss, name, headers) {
  let sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
    sheet.appendRow(headers);
    const headerRange = sheet.getRange(1, 1, 1, headers.length);
    headerRange.setFontWeight("bold")
               .setBackground("#059669")
               .setFontColor("#FFFFFF")
               .setHorizontalAlignment("center");
    sheet.setFrozenRows(1);
  }
  return sheet;
}

function formatJakartaDate(date) {
  return Utilities.formatDate(date, "Asia/Jakarta", "yyyy-MM-dd");
}

function formatJakartaTime(date) {
  return Utilities.formatDate(date, "Asia/Jakarta", "yyyy-MM-dd HH:mm:ss");
}

function jsonResponse(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
                       .setMimeType(ContentService.MimeType.JSON);
}
