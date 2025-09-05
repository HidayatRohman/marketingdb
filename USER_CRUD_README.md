# User CRUD Management System

Sistem CRUD (Create, Read, Update, Delete) untuk manajemen pengguna dengan tampilan dashboard modern menggunakan Laravel + Inertia.js + Vue.js.

## Fitur yang Tersedia

### 1. Dashboard
- **Lokasi**: `/dashboard`
- **Fitur**: 
  - Statistik total users berdasarkan role
  - Quick actions untuk manage users
  - Overview sistem

### 2. User Management
- **Lokasi**: `/users`
- **Fitur**:
  - Daftar semua users dengan pagination
  - Filter berdasarkan nama, email, dan role
  - Search functionality
  - Actions: View, Edit, Delete

### 3. Create User
- **Lokasi**: `/users/create`
- **Fitur**:
  - Form untuk membuat user baru
  - Validasi form real-time
  - Role selection (Super Admin, Admin, Marketing)
  - Password confirmation

### 4. Edit User
- **Lokasi**: `/users/{id}/edit`
- **Fitur**:
  - Edit informasi user
  - Optional password update
  - Email uniqueness validation
  - Role management

### 5. View User Detail
- **Lokasi**: `/users/{id}`
- **Fitur**:
  - Detail lengkap informasi user
  - Role badge dengan color coding
  - Quick actions (Edit, Delete)
  - System information (ID, created_at, updated_at)

## Struktur File yang Dibuat

### Backend (Laravel)
```
app/
├── Http/
│   ├── Controllers/
│   │   └── UserController.php          # CRUD operations
│   └── Middleware/
│       └── CheckRole.php               # Role-based access control
└── Models/
    └── User.php                        # Enhanced with role methods

routes/
└── web.php                             # Routes definition
```

### Frontend (Vue.js)
```
resources/js/
├── pages/
│   ├── Dashboard.vue                   # Dashboard with statistics
│   └── Users/
│       ├── Index.vue                   # Users listing with filters
│       ├── Create.vue                  # Create user form
│       ├── Edit.vue                    # Edit user form
│       └── Show.vue                    # User detail view
├── components/
│   ├── AppSidebar.vue                  # Updated with Users menu
│   └── FlashMessage.vue                # Flash message notifications
└── layouts/
    └── app/
        └── AppSidebarLayout.vue        # Updated with flash messages
```

## Role System

Aplikasi mendukung 3 jenis role:
1. **Super Admin** - Akses penuh ke semua fitur
2. **Admin** - Akses administratif
3. **Marketing** - Akses level marketing

## Features Highlights

### 1. Modern UI/UX
- Responsive design dengan Tailwind CSS
- Clean dan intuitive interface
- Loading states dan error handling
- Toast notifications untuk feedback

### 2. Search & Filter
- Real-time search pada nama dan email
- Filter berdasarkan role
- Debounced search untuk performance
- URL preservation untuk bookmarking

### 3. Data Management
- Pagination untuk large datasets
- Soft delete confirmation
- Form validation dengan error messages
- Success/error notifications

### 4. Security
- Role-based access control
- Password hashing
- CSRF protection
- Form validation

## Cara Penggunaan

### Mengakses User Management
1. Login ke aplikasi
2. Klik menu **Users** di sidebar
3. Anda akan melihat daftar semua users

### Membuat User Baru
1. Di halaman Users, klik tombol **Tambah User**
2. Isi form dengan informasi user
3. Pilih role yang sesuai
4. Klik **Simpan**

### Mengedit User
1. Di halaman Users, klik icon edit (pencil) pada user yang ingin diedit
2. Update informasi yang diperlukan
3. Klik **Simpan Perubahan**

### Melihat Detail User
1. Di halaman Users, klik icon view (eye) untuk melihat detail
2. Atau klik nama user untuk melihat detail lengkap

### Menghapus User
1. Klik icon delete (trash) pada user yang ingin dihapus
2. Konfirmasi penghapusan
3. User akan dihapus dari sistem

## Database Schema

Users table memiliki kolom:
- `id` - Primary key
- `name` - Nama lengkap user
- `email` - Email address (unique)
- `password` - Hashed password
- `role` - Enum: super_admin, admin, marketing
- `email_verified_at` - Timestamp verifikasi email
- `created_at` - Timestamp pembuatan
- `updated_at` - Timestamp update terakhir

## Installation & Setup

1. Install dependencies:
```bash
composer install
npm install
```

2. Run migrations:
```bash
php artisan migrate
```

3. Seed database dengan sample data:
```bash
php artisan db:seed --class=RoleBasedUserSeeder
```

4. Start development servers:
```bash
# Laravel server
php artisan serve

# Vite development server
npm run dev
```

## API Endpoints

- `GET /users` - List users
- `GET /users/create` - Show create form
- `POST /users` - Store new user
- `GET /users/{id}` - Show user detail
- `GET /users/{id}/edit` - Show edit form
- `PUT /users/{id}` - Update user
- `DELETE /users/{id}` - Delete user

## Teknologi yang Digunakan

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Vue.js 3, Inertia.js, TypeScript
- **Styling**: Tailwind CSS
- **Icons**: Lucide Vue
- **Database**: SQLite (development)

## Notes

- Flash messages ditampilkan untuk memberikan feedback kepada user
- Form validation dilakukan di sisi server dan client
- URL state preserved untuk better UX
- Responsive design untuk mobile dan desktop
- Role system siap untuk implementasi permission yang lebih kompleks
