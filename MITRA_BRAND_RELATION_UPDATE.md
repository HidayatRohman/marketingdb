# Update: Field Produk di Mitra menggunakan Brand

## Perubahan yang dilakukan:

### 1. Database Schema
- **Migrasi baru**: `2025_09_05_163553_modify_produk_field_in_mitras_table.php`
- **Perubahan**: 
  - Menghapus kolom `produk` (string) dari tabel `mitras`
  - Menambah kolom `brand_id` (foreign key) yang mereferensi tabel `brands`

### 2. Model Updates

#### Model Mitra (`app/Models/Mitra.php`)
- **Fillable field**: Mengganti `'produk'` dengan `'brand_id'`
- **Relasi baru**: 
  ```php
  public function brand()
  {
      return $this->belongsTo(Brand::class);
  }
  ```

#### Model Brand (`app/Models/Brand.php`)
- **Relasi baru**: 
  ```php
  public function mitras()
  {
      return $this->hasMany(Mitra::class);
  }
  ```

### 3. Factory & Seeder

#### Factories
- **BrandFactory**: Untuk membuat data brand dummy
- **MitraFactory**: Menggunakan `brand_id` dengan referensi ke Brand

#### Seeders
- **BrandSeeder**: Menambahkan beberapa brand sample
- **MitraSeeder**: Membuat data mitra dengan brand_id yang valid

### 4. Cara Penggunaan

#### Membuat Mitra baru:
```php
use App\Models\Mitra;

$mitra = Mitra::create([
    'nama' => 'Nama Mitra',
    'no_telp' => '081234567890',
    'brand_id' => 1, // ID brand yang valid
    'chat' => 'masuk',
    'kota' => 'Jakarta',
    'provinsi' => 'DKI Jakarta',
    'transaksi' => 1000000.00,
    'komentar' => 'Komentar mitra'
]);
```

#### Mengakses Brand dari Mitra:
```php
$mitra = Mitra::find(1);
echo $mitra->brand->nama; // Nama brand
```

#### Mengakses Mitras dari Brand:
```php
$brand = Brand::find(1);
$mitras = $brand->mitras; // Collection of mitras
```

#### Query dengan Eager Loading:
```php
// Ambil semua mitra dengan brand-nya
$mitras = Mitra::with('brand')->get();

// Ambil brand dengan semua mitras-nya  
$brands = Brand::with('mitras')->get();
```

### 5. Commands yang dijalankan:
```bash
# Membuat migrasi
php artisan make:migration modify_produk_field_in_mitras_table

# Menjalankan migrasi
php artisan migrate

# Membuat seeders
php artisan make:seeder BrandSeeder
php artisan make:seeder MitraSeeder

# Menjalankan seeders
php artisan db:seed --class=BrandSeeder
php artisan db:seed --class=MitraSeeder

# Membuat factories
php artisan make:factory BrandFactory
php artisan make:factory MitraFactory
```

## Keuntungan perubahan ini:
1. **Konsistensi data**: Field produk sekarang menggunakan referensi ke tabel brands
2. **Normalisasi database**: Menghindari duplikasi nama produk/brand
3. **Relational integrity**: Foreign key constraint memastikan data consistency
4. **Kemudahan maintenance**: Brand bisa dikelola secara terpusat
5. **Extensibility**: Mudah menambah field baru ke tabel brands (seperti logo, deskripsi, dll)

## Status:
✅ **COMPLETED** - Semua perubahan telah diimplementasi dan ditest
