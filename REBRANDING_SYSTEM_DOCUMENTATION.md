# 🎨 Sistem Rebranding Dinamis - Laravel Starter Kit

## 📋 Overview

Sistem rebranding dinamis telah diimplementasikan untuk memungkinkan penggantian logo, favicon, dan informasi aplikasi secara dinamis melalui halaman pengaturan. Sistem ini memungkinkan Super Admin untuk melakukan rebranding aplikasi tanpa perlu mengubah kode.

## 🏗️ Arsitektur Sistem

### 1. Model & Database

#### SiteSetting Model (`app/Models/SiteSetting.php`)
- Mengelola pengaturan site secara key-value
- Support untuk tipe data: `text`, `textarea`, `file`, `url`
- Method `get()` dan `set()` untuk mengakses dan mengubah pengaturan
- Auto-generate URL untuk file (logo/favicon)

#### Migration (`database/migrations/2025_09_06_000001_create_site_settings_table.php`)
```php
Schema::create('site_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->enum('type', ['text', 'textarea', 'file', 'url'])->default('text');
    $table->string('description')->nullable();
    $table->timestamps();
});
```

### 2. Backend Controller

#### SiteSettingController (`app/Http/Controllers/Settings/SiteSettingController.php`)
- **`edit()`**: Menampilkan halaman pengaturan site
- **`update()`**: Mengupdate pengaturan dengan validasi file
- **`deleteFile()`**: Menghapus file logo/favicon

**File Upload Features:**
- Logo: JPEG, PNG, JPG, GIF, SVG (Max: 2MB)
- Favicon: ICO, PNG (Max: 1MB)
- Auto-delete file lama saat upload baru
- Storage di `storage/app/public/site-assets/`

### 3. Frontend Components

#### Composable (`resources/js/composables/useSiteSettings.ts`)
```typescript
export function useSiteSettings() {
    return {
        siteTitle,      // Computed site title
        siteDescription, // Computed site description
        siteLogo,       // Computed logo URL
        siteFavicon,    // Computed favicon URL
        updateSettings, // Function to update settings
    };
}
```

#### AppLogo Component (`resources/js/components/AppLogo.vue`)
- Menampilkan logo dinamis atau fallback icon
- Menggunakan `useSiteSettings()` composable
- Auto-responsive untuk ukuran logo

#### AppHeader Component (`resources/js/components/AppHeader.vue`)
- Menggunakan site title dinamis
- Terintegrasi dengan AppLogo component

### 4. Provider Integration

#### SiteSettingsServiceProvider (`app/Providers/SiteSettingsServiceProvider.php`)
- Share settings globally via Inertia.js
- Share settings ke semua Blade views
- Auto-register di `bootstrap/providers.php`

## 🎛️ Pengaturan yang Tersedia

| Setting | Type | Description |
|---------|------|-------------|
| `site_title` | text | Judul aplikasi yang ditampilkan di browser |
| `site_description` | textarea | Deskripsi singkat tentang aplikasi |
| `site_logo` | file | Logo utama aplikasi |
| `site_favicon` | file | Favicon aplikasi |

## 🔧 Cara Penggunaan

### 1. Mengakses Pengaturan Site
- Login sebagai Super Admin
- Navigasi ke **Settings > Site Settings**
- Upload logo dan favicon baru
- Update judul dan deskripsi aplikasi

### 2. Menggunakan Settings di Komponen Vue
```vue
<script setup>
import { useSiteSettings } from '@/composables/useSiteSettings';

const { siteTitle, siteLogo } = useSiteSettings();
</script>

<template>
    <div>
        <img v-if="siteLogo" :src="siteLogo" :alt="siteTitle" />
        <h1>{{ siteTitle }}</h1>
    </div>
</template>
```

### 3. Menggunakan Settings di Blade Views
```php
<!-- File sudah tersedia globally -->
<title>{{ $siteTitle }}</title>
<link rel="icon" href="{{ $siteFavicon }}" type="image/png">
```

## 📁 File Structure

```
app/
├── Http/Controllers/Settings/
│   └── SiteSettingController.php
├── Models/
│   └── SiteSetting.php
└── Providers/
    └── SiteSettingsServiceProvider.php

resources/
├── js/
│   ├── components/
│   │   ├── AppLogo.vue
│   │   └── AppHeader.vue
│   ├── composables/
│   │   └── useSiteSettings.ts
│   └── pages/settings/
│       └── SiteSettings.vue
└── views/
    └── app.blade.php

database/migrations/
└── 2025_09_06_000001_create_site_settings_table.php

routes/
└── settings.php
```

## 🔒 Permission & Security

### Role-based Access
- Hanya **Super Admin** yang dapat mengakses Site Settings
- Middleware `role.access:edit` melindungi routes
- Validasi file upload yang ketat

### File Security
- Upload hanya ke `storage/app/public/site-assets/`
- Validasi MIME type dan ukuran file
- Auto-delete file lama saat upload baru

## 🚀 Features

### ✅ Implemented
- [x] Upload logo aplikasi
- [x] Upload favicon
- [x] Update site title dan description
- [x] Preview logo/favicon sebelum upload
- [x] Delete logo/favicon yang ada
- [x] Integrasi dengan AppHeader dan AppLogo
- [x] Global sharing via Inertia.js
- [x] Blade views integration
- [x] Role-based access control
- [x] File validation dan security
- [x] Responsive design
- [x] Dark mode support

### 🔄 Automatic Features
- Auto-generate URL untuk file assets
- Auto-delete file lama saat upload baru
- Auto-fallback ke default values
- Auto-refresh UI setelah update

## 🧪 Testing

### Manual Testing Checklist
- [ ] Upload logo baru (.png, .jpg, .svg)
- [ ] Upload favicon baru (.ico, .png)
- [ ] Update site title dan description
- [ ] Delete logo/favicon yang ada
- [ ] Verify responsive design
- [ ] Test role-based access
- [ ] Check file validation limits

### Test Commands
```bash
# Test file upload
php test-logo-upload.php

# Test role access
php test-role-access.php
```

## 📝 Notes

### Default Values
- Site Title: "Laravel Starter Kit"
- Site Description: "Marketing Database Management System"
- Logo: Fallback ke AppLogoIcon
- Favicon: Fallback ke public/favicon.ico

### Browser Support
- Modern browsers dengan ES6+ support
- File API untuk preview uploads
- CSS Grid dan Flexbox

### Performance
- Lazy loading untuk logo/favicon
- Optimized asset URLs
- Efficient database queries

## 🔧 Configuration

### Storage Configuration
Pastikan storage link sudah dibuat:
```bash
php artisan storage:link
```

### Provider Registration
Provider sudah auto-registered di `bootstrap/providers.php`:
```php
App\Providers\SiteSettingsServiceProvider::class,
```

## 🤝 Contributing

Saat menambah setting baru:
1. Update migration dengan setting default
2. Update SiteSettingController validation
3. Update frontend form di SiteSettings.vue
4. Update composable jika diperlukan
5. Update dokumentasi ini

---
**Created:** September 7, 2025  
**Last Updated:** September 7, 2025  
**Version:** 1.0.0
