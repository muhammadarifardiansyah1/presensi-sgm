/**
 * ==============================================================================
 * GOOGLE APPS SCRIPT: BACKEND SINKRONISASI SPREADSHEET & GOOGLE DRIVE (100% GRATIS)
 * SISTEM PRESENSI CV. SURYA GEMILANG MAJAPAHIT (SGM)
 * ==============================================================================
 * 
 * FITUR:
 * 1. Otomatis pisah sheet per bulan ("Presensi [Bulan] [Tahun]").
 * 2. Header elegan: "REKAP PRESENSI CV. SURYA GEMILANG MAJAPAHIT".
 * 3. Informasi Real-time: "Total Pegawai: X Orang | Waktu Sinkronisasi: [Waktu] WIB".
 * 4. Kolom lengkap: No, Tanggal, NIK, Nama Pegawai, Jabatan, Lokasi, Jam Masuk,
 *    Jam Pulang, Total Jam Kerja, Status, Keterangan Terlambat, Link Foto Masuk,
 *    Link Foto Pulang, Koordinat GPS.
 * 5. Foto selfie tersimpan di Google Drive dan URL langsung tercatat di Spreadsheet.
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

    // 2. HANDLER: BATCH SYNC REKAP PRESENSI BULANAN DARI ADMIN
    if (action === "sync_presensi" || (data.sheet_name && data.rows && Array.isArray(data.rows))) {
      const namaBulanList = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
      ];
      const bulanIndex = data.bulan ? (parseInt(data.bulan) - 1) : new Date().getMonth();
      const tahunText = data.tahun || new Date().getFullYear();
      const namaSheetBulan = "Presensi " + namaBulanList[bulanIndex] + " " + tahunText;
      const sheetName = data.sheet_name || namaSheetBulan;

      const listRows = data.rows || data.data || [];
      const uniqueNiks = {};
      listRows.forEach(function(r) { if (r.nik && r.nik !== "-") uniqueNiks[r.nik] = true; });
      const totalPegawaiCount = data.total_pegawai || Object.keys(uniqueNiks).length || listRows.length;

      let sheetBatch = ss.getSheetByName(sheetName);
      if (sheetBatch) {
        sheetBatch.clear();
      }
      sheetBatch = setupMonthlyPresensiSheet(ss, sheetName, totalPegawaiCount);

      listRows.forEach(function(r, idx) {
        sheetBatch.appendRow([
          (idx + 1),
          r.tanggal || "-",
          r.nik || "-",
          r.nama || "-",
          r.jabatan || "-",
          r.lokasi || "-",
          r.jam_masuk || "-",
          r.jam_pulang || "-",
          r.total_jam_kerja || "-",
          (r.status || r.status_masuk || "hadir").toUpperCase(),
          r.terlambat || "Tepat Waktu",
          r.foto_masuk || r.link_foto_masuk || "-",
          r.foto_keluar || r.link_foto_keluar || "-",
          r.koordinat || (r.latitude ? (r.latitude + ", " + r.longitude) : "-")
        ]);
      });

      return jsonResponse({
        status: "success",
        message: "Rekap presensi berhasil disinkronkan ke sheet '" + sheetName + "' (" + listRows.length + " data)"
      });
    }

    // 3. HANDLER: PRESENSI REAL-TIME (MASUK & PULANG)
    if (action === "presensi") {
      let photoUrl = "";
      if (data.image_base64 && GOOGLE_DRIVE_FOLDER_ID && GOOGLE_DRIVE_FOLDER_ID !== "MASUKKAN_FOLDER_ID_DRIVE_DISINI") {
        photoUrl = uploadBase64ToDrive(
          data.image_base64,
          data.filename || ("absen_" + (data.nik || "user") + "_" + Date.now()),
          "image/jpeg"
        );
      }

      // Tentukan Nama Sheet Berdasarkan Bulan Presensi
      const namaBulanList = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
      ];
      const tglString = data.tanggal || formatJakartaDate(new Date());
      const dateParts = tglString.split("-");
      const tahunPart = dateParts[0] || new Date().getFullYear();
      const bulanPart = dateParts[1] ? (parseInt(dateParts[1], 10) - 1) : new Date().getMonth();
      const sheetName = "Presensi " + namaBulanList[bulanPart] + " " + tahunPart;

      const sheetPresensi = setupMonthlyPresensiSheet(ss, sheetName, data.total_pegawai);

      const tgl = tglString;
      const nik = String(data.nik || "-");

      // Cek apakah sudah ada baris untuk NIK dan Tanggal ini (Data mulai dari baris ke-4)
      let existingRowIndex = -1;
      const values = sheetPresensi.getDataRange().getValues();
      for (let i = 3; i < values.length; i++) {
        const rowTgl = values[i][1] instanceof Date ? formatJakartaDate(values[i][1]) : String(values[i][1]);
        const rowNik = String(values[i][2]);
        if (rowTgl === tgl && rowNik === nik) {
          existingRowIndex = i + 1; // 1-indexed row number
          break;
        }
      }

      if (data.tipe === "pulang" && existingRowIndex > 0) {
        // Update jam pulang, total jam kerja, dan foto pulang
        if (data.jam_pulang) sheetPresensi.getRange(existingRowIndex, 8).setValue(data.jam_pulang);
        if (data.total_jam_kerja) sheetPresensi.getRange(existingRowIndex, 9).setValue(data.total_jam_kerja);
        if (data.status) sheetPresensi.getRange(existingRowIndex, 10).setValue((data.status).toUpperCase());
        if (photoUrl) sheetPresensi.getRange(existingRowIndex, 13).setValue(photoUrl);
      } else if (existingRowIndex > 0) {
        // Update jam masuk & foto masuk jika sudah ada baris
        if (data.jam_masuk) sheetPresensi.getRange(existingRowIndex, 7).setValue(data.jam_masuk);
        if (photoUrl) sheetPresensi.getRange(existingRowIndex, 12).setValue(photoUrl);
      } else {
        // Buat baris data baru
        const nomorUrut = Math.max(1, values.length - 2);
        sheetPresensi.appendRow([
          nomorUrut,
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
        message: "Data presensi tercatat di sheet '" + sheetName + "' dan foto tersimpan di Google Drive!",
        drive_url: photoUrl
      });
    }

    return jsonResponse({ status: "success", message: "Webhook diterima." });

  } catch (err) {
    return jsonResponse({ status: "error", message: err.toString() });
  }
}

/**
 * Setup atau Ambil Sheet Presensi Bulanan dengan Header Khusus CV. Surya Gemilang Majapahit
 */
function setupMonthlyPresensiSheet(ss, sheetName, totalPegawai) {
  let sheet = ss.getSheetByName(sheetName);
  const headers = [
    "No", "Tanggal", "NIK", "Nama Pegawai", "Jabatan", "Lokasi", 
    "Jam Masuk", "Jam Pulang", "Total Jam Kerja", "Status", 
    "Keterangan Terlambat", "Link Foto Masuk", "Link Foto Pulang", "Koordinat GPS"
  ];
  const totalCols = headers.length;
  const nowFormatted = formatJakartaTime(new Date());

  if (!sheet) {
    sheet = ss.insertSheet(sheetName);
    
    // Baris 1: Header Judul Utama Perusahaan
    sheet.getRange(1, 1, 1, totalCols).merge();
    sheet.getRange(1, 1)
         .setValue("REKAP PRESENSI CV. SURYA GEMILANG MAJAPAHIT")
         .setBackground("#047857")
         .setFontColor("#FFFFFF")
         .setFontWeight("bold")
         .setFontSize(13)
         .setHorizontalAlignment("center")
         .setVerticalAlignment("middle");
    sheet.setRowHeight(1, 36);

    // Baris 2: Sub-header Total Pegawai dan Waktu Sinkronisasi
    const totalPegText = totalPegawai ? String(totalPegawai) : "-";
    sheet.getRange(2, 1, 1, totalCols).merge();
    sheet.getRange(2, 1)
         .setValue("Total Pegawai: " + totalPegText + " Orang   |   Waktu Sinkronisasi: " + nowFormatted + " WIB")
         .setBackground("#d1fae5")
         .setFontColor("#065f46")
         .setFontWeight("bold")
         .setFontSize(9)
         .setHorizontalAlignment("center")
         .setVerticalAlignment("middle");
    sheet.setRowHeight(2, 22);

    // Baris 3: Header Kolom Tabel
    sheet.appendRow(headers);
    const headerRange = sheet.getRange(3, 1, 1, totalCols);
    headerRange.setFontWeight("bold")
               .setBackground("#059669")
               .setFontColor("#FFFFFF")
               .setFontSize(10)
               .setHorizontalAlignment("center")
               .setVerticalAlignment("middle");
    sheet.setRowHeight(3, 28);
    sheet.setFrozenRows(3);
  } else {
    // Perbarui waktu sinkronisasi di Baris 2
    try {
      const currentVal = String(sheet.getRange(2, 1).getValue() || "");
      let pegText = totalPegawai ? String(totalPegawai) : null;
      if (!pegText) {
        const match = currentVal.match(/Total Pegawai:\s*([0-9]+)/);
        pegText = match ? match[1] : "-";
      }
      sheet.getRange(2, 1).setValue("Total Pegawai: " + pegText + " Orang   |   Waktu Sinkronisasi: " + nowFormatted + " WIB");
    } catch (e) {
      // Ignore format update error
    }
  }

  return sheet;
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
 * Helper Membuat Sheet Standar (Ketidakhadiran, dll)
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
