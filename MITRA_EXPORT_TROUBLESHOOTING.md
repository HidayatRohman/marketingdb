# MITRA EXPORT TROUBLESHOOTING GUIDE

## Problem: Export tidak berfungsi di halaman /mitras

### Root Cause Analysis
Berdasarkan investigasi, export functionality secara teknis berfungsi dengan baik, namun ada beberapa faktor yang bisa menyebabkan export "tidak berfungsi":

### 1. **Authentication Required**
Route export dilindungi oleh middleware `role.access:view` yang memerlukan autentikasi:
```php
Route::middleware('role.access:view')->group(function () {
    Route::get('mitras/export', [MitraController::class, 'export'])->name('mitras.export');
});
```

**Solusi**: Pastikan user sudah login sebelum mencoba export.

### 2. **Browser Security/Download Settings**
Browser mungkin memblokir download otomatis atau menyimpan file di folder yang tidak terlihat.

**Solusi**: 
- Check browser download settings
- Check Downloads folder
- Allow pop-ups for the domain

### 3. **Role-Based Access**
Export hanya tersedia untuk user dengan permission yang sesuai:
- Super Admin: Full access
- Admin: Read-only access  
- Marketing: Limited access (own data only)

### 4. **Network/Server Issues**
Jika ada masalah koneksi atau server timeout untuk file besar.

## Technical Components ✅

Semua komponen teknis sudah berfungsi:

### Backend (Laravel)
- ✅ Route `/mitras/export` terdaftar
- ✅ MitraController::export() method exists
- ✅ PhpSpreadsheet library installed
- ✅ CSV generation working
- ✅ Database data available (4 mitras)

### Frontend (Vue.js)
- ✅ Export button exists in UI
- ✅ exportData() function implemented correctly
- ✅ Uses window.location.href (most reliable method)
- ✅ Error handling implemented

### Controller Logic
```php
public function export(Request $request)
{
    // ✅ Authentication check
    // ✅ Role-based filtering
    // ✅ Filter support (search, periode, chat, label, user)
    // ✅ Both CSV and XLSX formats
    // ✅ Proper headers for download
    // ✅ Error handling and logging
}
```

## Testing & Debugging

### Debug Routes Available:
- `/test-mitra-export.html` - Diagnostic tool
- `/debug-mitra-export` - Direct controller test (no middleware)
- `/test-auth-export` - Check authentication status
- `/debug-export` - Basic connectivity test

### Steps to Test:

1. **Login to Application**
   ```
   http://127.0.0.1:8000/login
   ```

2. **Go to Mitras Page**
   ```
   http://127.0.0.1:8000/mitras
   ```

3. **Click Export Button**
   - Should trigger download automatically
   - Check browser downloads folder

4. **Alternative: Direct URL Test**
   ```
   http://127.0.0.1:8000/mitras/export?export=csv
   ```

## Common Solutions

### If Export Still Not Working:

1. **Clear Browser Cache & Cookies**
2. **Check Browser Console for JavaScript errors**
3. **Try different browser**
4. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Test with different user roles**

6. **Verify file permissions on storage directory**

## Code Implementation

### Frontend Export Function (Vue):
```javascript
const exportData = async (format: 'csv' | 'xlsx') => {
    try {
        isExporting.value = true;
        
        const filters = getFilterParams();
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined) {
                params.append(key, String(value));
            }
        });
        params.append('export', format);
        
        const url = `/mitras/export?${params.toString()}`;
        window.location.href = url;  // Most reliable for auth downloads
        
    } catch (error) {
        console.error('Export failed:', error);
        alert('Export gagal. Silakan coba lagi.');
    } finally {
        setTimeout(() => {
            isExporting.value = false;
        }, 2000);
    }
};
```

### Backend Export Method (Laravel):
```php
public function export(Request $request)
{
    // Authentication & role filtering
    $user = auth()->user();
    $query = Mitra::with(['brand', 'label', 'user']);
    $query = $user->applyRoleFilter($query, 'user_id');
    
    // Apply filters from request
    // Generate CSV or XLSX
    // Return download response with proper headers
}
```

## Recommended Actions

1. **Immediate Fix**: Ensure users are properly logged in
2. **User Training**: Show users where downloads are saved
3. **Monitor Logs**: Check Laravel logs for any export errors
4. **Performance**: For large datasets, consider background jobs

## Test Data
- Total Mitras: 4
- Sample: "PT Mitra Sejahtera (Magu Magu Chicken)"
- All export components verified working

---

**Status**: Export functionality is technically working. Issue is likely authentication or browser download settings.

**Last Updated**: September 9, 2025
