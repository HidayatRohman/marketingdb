# Update: Logo Brand dari URL ke Upload File

## Perubahan yang Dilakukan

### 1. Backend Changes (Laravel)

#### Model Brand (`app/Models/Brand.php`)
- Menambahkan accessor `logo_url` untuk mendapatkan URL lengkap logo
- Menambahkan `$appends = ['logo_url']` untuk selalu menyertakan URL logo dalam response

#### Controller Brand (`app/Http/Controllers/BrandController.php`)
- **Store Method**: 
  - Mengubah validasi logo dari `string` menjadi `image` dengan format: jpeg, png, jpg, gif, svg (max 2MB)
  - Menggunakan `$request->file('logo')->store('brands', 'public')` untuk menyimpan file
- **Update Method**:
  - Menambahkan logika untuk menghapus logo lama saat upload logo baru
  - Menggunakan storage untuk mengelola file upload
- **Destroy Method**:
  - Menambahkan logika untuk menghapus file logo saat brand dihapus

### 2. Frontend Changes (Vue.js)

#### BrandModal Component (`resources/js/components/BrandModal.vue`)
- Mengubah form dari text input (URL) menjadi file input
- Menambahkan preview gambar yang dipilih sebelum upload
- Menambahkan fungsi `handleFileChange()` untuk preview file
- Menambahkan fungsi `removeLogo()` untuk menghapus preview
- Menggunakan `forceFormData: true` untuk mendukung file upload
- Menambahkan tombol remove logo dan empty state

#### Brand Index Page (`resources/js/pages/Brand/Index.vue`)
- Mengupdate interface `Brand` untuk menambahkan field `logo_url`
- Mengubah referensi dari `brand.logo` menjadi `brand.logo_url` untuk menampilkan gambar

#### BrandDeleteModal Component (`resources/js/components/BrandDeleteModal.vue`)
- Mengupdate interface `Brand` untuk menambahkan field `logo_url`
- Mengubah referensi dari `brand.logo` menjadi `brand.logo_url` untuk menampilkan gambar

### 3. Storage Configuration

#### Storage Link
- Menjalankan `php artisan storage:link` untuk membuat symbolic link
- Membuat direktori `storage/app/public/brands` untuk menyimpan logo brand

## Fitur Baru

### 1. File Upload
- Support format: JPEG, PNG, JPG, GIF, SVG
- Maksimal ukuran file: 2MB
- File disimpan di `storage/app/public/brands/`

### 2. Preview Gambar
- Preview real-time saat memilih file
- Tombol untuk menghapus gambar yang dipilih
- Empty state ketika tidak ada logo

### 3. Manajemen File
- Otomatis menghapus file lama saat update logo
- Otomatis menghapus file saat brand dihapus
- URL logo otomatis ter-generate melalui accessor

## Cara Penggunaan

1. **Menambah Brand Baru**:
   - Klik "Tambah Brand"
   - Isi nama brand
   - Klik "Choose File" untuk memilih logo (opsional)
   - Preview akan muncul setelah memilih file
   - Klik "Tambah Brand" untuk menyimpan

2. **Edit Brand**:
   - Klik tombol edit pada brand
   - Upload logo baru akan mengganti logo lama
   - Logo lama akan otomatis terhapus dari storage

3. **Hapus Brand**:
   - Saat brand dihapus, logo juga akan terhapus dari storage

## Technical Details

### URL Logo
- Logo disimpan sebagai path relatif: `brands/filename.jpg`
- URL lengkap di-generate otomatis: `http://domain.com/storage/brands/filename.jpg`
- Menggunakan Laravel's `asset('storage/' . $path)` helper

### File Validation
```php
'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
```

### Storage Location
```
storage/app/public/brands/
├── [timestamp]_logo1.jpg
├── [timestamp]_logo2.png
└── ...
```

## Keamanan

1. **Validasi File Type**: Hanya menerima file gambar
2. **Ukuran File**: Dibatasi maksimal 2MB
3. **Storage Location**: File disimpan di dalam storage yang aman
4. **File Cleanup**: Otomatis menghapus file yang tidak terpakai

## Testing

Setelah implementasi, test fitur berikut:
1. ✅ Upload logo saat create brand baru
2. ✅ Preview logo sebelum upload
3. ✅ Update logo brand existing
4. ✅ Hapus logo dengan tombol remove
5. ✅ Tampilan logo di list brand
6. ✅ Validasi file type dan size
7. ✅ Cleanup file saat delete brand

## Migration Note

Untuk data existing yang masih menggunakan URL logo, tidak perlu migration khusus karena:
- Field `logo` tetap ada untuk backward compatibility
- Accessor `logo_url` akan handle both file path dan URL
- Frontend sudah menggunakan `logo_url` untuk display
