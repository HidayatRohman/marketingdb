# ROLE BASED ACCESS CONTROL DOCUMENTATION

## Overview
Sistem MarketingDB telah diimplementasikan dengan Role-Based Access Control (RBAC) yang membagi akses berdasarkan 3 level role:

1. **Super Admin** - Akses penuh ke seluruh sistem
2. **Admin** - Akses view-only (tanpa Create, Update, Delete)
3. **Marketing** - Hanya bisa melihat dan mengelola data milik sendiri

## Role Permissions

### Super Admin (`super_admin`)
- ✅ **Full Access** - Dapat melakukan semua operasi CRUD
- ✅ **View All Data** - Dapat melihat semua data di sistem
- ✅ **Manage Users** - Dapat mengelola user management
- ✅ **Manage Brands** - Dapat mengelola brand management
- ✅ **Manage Labels** - Dapat mengelola label management
- ✅ **Manage Mitras** - Dapat mengelola semua data mitra
- ✅ **Dashboard Analytics** - Dapat melihat semua analytics dan reports

### Admin (`admin`)
- ❌ **No CRUD Operations** - Tidak dapat Create, Update, Delete
- ✅ **View All Data** - Dapat melihat semua data di sistem (read-only)
- ✅ **View Users** - Dapat melihat daftar users (tanpa edit/delete)
- ✅ **View Brands** - Dapat melihat daftar brands (tanpa edit/delete)
- ✅ **View Labels** - Dapat melihat daftar labels (tanpa edit/delete)
- ✅ **View All Mitras** - Dapat melihat semua data mitra (tanpa edit/delete)
- ✅ **Dashboard Analytics** - Dapat melihat semua analytics dan reports

### Marketing (`marketing`)
- ✅ **Limited CRUD** - Hanya dapat mengelola data milik sendiri
- ❌ **No User Management** - Tidak dapat mengakses user management
- ❌ **No Brand Management** - Tidak dapat mengakses brand management
- ❌ **No Label Management** - Tidak dapat mengakses label management
- ✅ **Own Mitras Only** - Hanya dapat melihat/mengelola mitra yang dibuat sendiri
- ✅ **Personal Analytics** - Hanya dapat melihat analytics data pribadi

## Implementation Details

### Middleware
- `RoleBasedAccess` - Mengatur akses berdasarkan action (view, create, edit, destroy)
- `CheckRole` - Mengecek role user untuk akses ke route tertentu

### Traits
- `HasRoleAccess` - Trait untuk User model yang menyediakan method role checking

### Policies
- `UserPolicy` - Mengatur akses ke user management
- `MitraPolicy` - Mengatur akses ke mitra data dengan row-level security
- `BrandPolicy` - Mengatur akses ke brand management
- `LabelPolicy` - Mengatur akses ke label management

### Helper
- `RoleHelper` - Utility class untuk permission checking dan navigation items

## Data Filtering

### Super Admin
```php
// Dapat mengakses semua data tanpa filter
$mitras = Mitra::all();
```

### Admin
```php
// Dapat melihat semua data tapi read-only
$mitras = Mitra::all(); // No create/edit/delete buttons
```

### Marketing
```php
// Hanya data milik sendiri
$mitras = Mitra::where('user_id', auth()->id())->get();
```

## Route Protection

Semua route telah diproteksi dengan middleware yang sesuai:

```php
// View routes - Semua role dapat akses dengan data filtering
Route::middleware('role.access:view')->group(function () {
    Route::get('mitras', [MitraController::class, 'index']);
});

// CRUD routes - Hanya Super Admin
Route::middleware('role.access:create')->group(function () {
    Route::post('mitras', [MitraController::class, 'store']);
});
```

## Frontend Integration

Data permission disediakan melalui Inertia props:

```javascript
// Di komponen React/Vue
const { permissions, navigation, dataScope } = usePage().props.auth;

// Conditional rendering berdasarkan permission
{permissions.canCrud && (
    <CreateButton />
)}

{permissions.canOnlyView && (
    <ViewOnlyMessage />
)}
```

## Database Seeding

Gunakan `RoleBasedUserSeeder` untuk membuat users dengan role yang sesuai:

```bash
php artisan db:seed --class=RoleBasedUserSeeder
```

## Testing Role Access

1. **Login sebagai Super Admin**
   - Semua menu dan tombol aksi (Create, Edit, Delete) harus terlihat
   - Dapat melihat semua data di dashboard dan analytics

2. **Login sebagai Admin**
   - Semua menu terlihat tapi tanpa tombol Create, Edit, Delete
   - Dashboard menampilkan semua data tapi read-only

3. **Login sebagai Marketing**
   - Menu terbatas (tidak ada User, Brand, Label management)
   - Hanya dapat melihat dan mengelola data mitra sendiri
   - Dashboard hanya menampilkan analytics personal

## Security Notes

- Semua akses telah diproteksi di level middleware, controller, dan policy
- Data filtering diterapkan di query level untuk mencegah data leakage
- Row-level security untuk marketing users memastikan mereka hanya dapat mengakses data milik sendiri
- Authorization dilakukan sebelum action untuk mencegah unauthorized access

## Troubleshooting

### User tidak dapat akses halaman
- Pastikan user memiliki role yang sesuai
- Check middleware dan policy yang diterapkan pada route

### Data tidak terfilter dengan benar
- Pastikan `applyRoleFilter()` dipanggil di controller
- Check implementasi trait `HasRoleAccess`

### Permission tidak sesuai di frontend
- Check implementasi `RoleHelper::getPermissions()`
- Pastikan data permission dikirim melalui `HandleInertiaRequests`
