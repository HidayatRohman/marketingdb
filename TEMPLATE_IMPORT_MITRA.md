# Template Import Data Mitra

## Panduan Lengkap Import Data Mitra

### Format File yang Didukung
- **CSV** (.csv)
- **Excel** (.xlsx, .xls)

### Struktur Template

| Kolom | Nama Field | Wajib | Format | Contoh | Keterangan |
|-------|------------|-------|--------|---------|------------|
| A | ID | Tidak | Angka | - | Kosongkan untuk data baru, isi jika update data existing |
| B | Nama | **Ya** | Text | John Doe | Nama lengkap mitra |
| C | No. Telepon | **Ya** | Text | 081234567890 | Nomor telepon (dengan/tanpa kode negara) |
| D | Tanggal Lead | **Ya** | Date | 2024-01-15 | Format: YYYY-MM-DD |
| E | Brand | Tidak | Text | Brand A | Nama brand (akan dibuat otomatis jika belum ada) |
| F | Label | Tidak | Text | Hot Lead | Nama label yang sudah ada di sistem |
| G | Status Chat | Tidak | Text | Masuk | "Masuk" atau "Follow Up" |
| H | Kota | Tidak | Text | Jakarta | Nama kota |
| I | Provinsi | Tidak | Text | DKI Jakarta | Nama provinsi |
| J | Marketing | Tidak | Text | - | Akan diisi otomatis dengan user yang mengimport |
| K | Komentar | Tidak | Text | Tertarik dengan produk | Catatan tambahan |
| L | Dibuat Pada | Tidak | DateTime | - | Akan diisi otomatis sistem |
| M | Diupdate Pada | Tidak | DateTime | - | Akan diisi otomatis sistem |

### Aturan Import

#### 1. Field Wajib (Required)
- **Nama**: Harus diisi, tidak boleh kosong
- **No. Telepon**: Harus diisi, tidak boleh kosong  
- **Tanggal Lead**: Harus diisi dengan format YYYY-MM-DD

#### 2. Field Opsional
- **ID**: Kosongkan untuk data baru. Isi jika ingin update data yang sudah ada
- **Brand**: Jika kosong, data akan tersimpan tanpa brand. Jika diisi dengan brand yang belum ada, sistem akan membuat brand baru secara otomatis
- **Label**: Harus menggunakan nama label yang sudah ada di sistem. Jika tidak ada, field akan kosong
- **Status Chat**: Defaultnya "Masuk". Isi dengan "Follow Up" jika perlu
- **Kota & Provinsi**: Boleh kosong
- **Komentar**: Boleh kosong

#### 3. Format Data
- **Tanggal**: Gunakan format YYYY-MM-DD (contoh: 2024-01-15)
- **No. Telepon**: Bisa dengan atau tanpa kode negara. Sistem akan otomatis menambahkan kode negara Indonesia (62) jika diperlukan
- **Status Chat**: Hanya "Masuk" atau "Follow Up" (case insensitive)

#### 4. Validasi Sistem
- Sistem akan memvalidasi format tanggal
- Sistem akan memeriksa keberadaan label di database
- Sistem akan membuat brand baru jika belum ada
- Sistem akan menampilkan error jika ada data yang tidak valid

### Contoh Data Template

```csv
ID,Nama,No. Telepon,Tanggal Lead,Brand,Label,Status Chat,Kota,Provinsi,Marketing,Komentar,Dibuat Pada,Diupdate Pada
,John Doe,081234567890,2024-01-15,Brand A,Hot Lead,Masuk,Jakarta,DKI Jakarta,,Tertarik dengan produk,,
,Jane Smith,087654321098,2024-01-16,Brand B,Warm Lead,Follow Up,Surabaya,Jawa Timur,,Perlu follow up lebih lanjut,,
,Ahmad Rahman,082111222333,2024-01-17,Brand C,Cold Lead,Masuk,Bandung,Jawa Barat,,Inquiry produk via WhatsApp,,
```

### Tips Import yang Sukses

1. **Download Template**: Selalu gunakan template yang disediakan sistem
2. **Isi Data Bertahap**: Jangan import data terlalu banyak sekaligus (maksimal 100-200 baris)
3. **Cek Format Tanggal**: Pastikan format tanggal sesuai (YYYY-MM-DD)
4. **Backup Data**: Selalu backup data sebelum import
5. **Test dengan Data Sedikit**: Coba import 1-2 baris dulu untuk memastikan format benar

### Error yang Mungkin Terjadi

| Error | Penyebab | Solusi |
|-------|----------|---------|
| "Nama wajib diisi" | Kolom nama kosong | Isi kolom nama |
| "No. Telepon wajib diisi" | Kolom telepon kosong | Isi kolom telepon |
| "Format tanggal tidak valid" | Format tanggal salah | Gunakan format YYYY-MM-DD |
| "Import gagal" | File rusak/format salah | Download ulang template dan isi ulang |

### Langkah-langkah Import

1. **Download Template**
   - Klik tombol "Export" di halaman Mitra
   - Pilih "Download Template XLSX" atau "Download Template CSV"

2. **Isi Data**
   - Buka file template yang sudah didownload
   - Hapus baris contoh yang ada
   - Isi data sesuai panduan di atas

3. **Import Data**
   - Klik tombol "Import" di halaman Mitra
   - Pilih file yang sudah diisi
   - Tunggu proses import selesai

4. **Cek Hasil**
   - Sistem akan menampilkan jumlah data yang berhasil diimport
   - Jika ada error, sistem akan menampilkan detail error
   - Refresh halaman untuk melihat data baru

### Support
Jika mengalami kesulitan, silakan hubungi administrator sistem.
