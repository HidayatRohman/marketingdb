# Dashboard Task Statistics Fix

## Problem
Report Task per Marketing di dashboard tidak sesuai dengan data yang ada di `/task-management`. Dashboard menampilkan total yang tidak akurat karena ada masalah dalam kalkulasi.

## Root Cause Analysis

### Original Problem
Dashboard menggunakan logika kalkulasi yang berbeda dibandingkan dengan halaman Task Management:

1. **Dashboard (Before Fix)**: Menghitung total tasks dengan cara `created_total + assigned_total`, yang bisa menyebabkan duplikasi jika ada task yang dibuat dan di-assign ke user yang sama.

2. **Task Management**: Menggunakan query `WHERE (user_id = X OR assigned_to = X)` yang menghindari duplikasi.

### Code Differences

**DashboardController (Original)**:
```php
$totalTasks = $user->created_total + $user->assigned_total;
```

**TaskManagementController (Correct)**:
```php
$baseQuery->where(function($q) {
    $q->where('user_id', auth()->id())
      ->orWhere('assigned_to', auth()->id());
});
```

## Solution

### 1. Updated DashboardController Logic
Mengubah `getTaskStatistics()` method untuk menggunakan logika yang sama dengan TaskManagementController:

```php
private function getTaskStatistics($currentUser)
{
    // ... existing code ...

    $marketingStats = $marketingQuery->get()->map(function ($user) use ($currentUser) {
        // Build base query for this specific user with role-based access
        $userTasksQuery = TodoList::query();
        
        if ($currentUser->hasLimitedAccess()) {
            $userTasksQuery->where(function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id)
                      ->orWhere('assigned_to', $currentUser->id);
            });
        }
        
        // Apply user filter - tasks where user is creator OR assigned to
        $userTasksQuery->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
        });

        // Get counts using the same logic as TaskManagementController
        $totalTasks = (clone $userTasksQuery)->count();
        $pendingTasks = (clone $userTasksQuery)->where('status', 'pending')->count();
        // ... etc
    });
}
```

### 2. Added Missing Field
Menambahkan field `overdue_tasks` yang sebelumnya tidak ada untuk konsistensi dengan overall stats.

### 3. Updated Frontend Interface
Memperbarui TypeScript interface di Dashboard.vue untuk include field `overdue_tasks`.

### 4. Fixed Display Logic
Mengubah display "Terlambat" column dari `marketing.assigned_overdue` ke `marketing.overdue_tasks`.

## Files Modified

### 1. `app/Http/Controllers/DashboardController.php`
- Method `getTaskStatistics()` - Fixed calculation logic
- Added `overdue_tasks` field to response

### 2. `resources/js/pages/Dashboard.vue`
- Updated `TaskMarketingStats` interface
- Fixed "Terlambat" column display

## Test Results

### Before Fix (Potential Issues)
- Total bisa double-count jika task created by dan assigned to user yang sama
- Inconsistency antara dashboard dan task management page

### After Fix (Verified)
```
Overall Task Counts:
   Total: 4
   Pending: 3
   In Progress: 0
   Completed: 1
   Overdue: 1

Per Marketing User (using corrected logic):
   Marketing User: Total Tasks: 0, Pending: 0, In Progress: 0, Completed: 0, Overdue: 0
   endang: Total Tasks: 1, Pending: 0, In Progress: 0, Completed: 1, Overdue: 0
   Hestiana: Total Tasks: 1, Pending: 1, In Progress: 0, Completed: 0, Overdue: 0

Checking for potential duplication: None found
```

## Benefits

1. **Data Consistency**: Dashboard sekarang menampilkan data yang sama dengan Task Management page
2. **No Duplication**: Eliminasi potensi double-counting tasks
3. **Accurate Metrics**: Total tasks, completion rates, dan metrics lainnya sekarang akurat
4. **Role-based Access**: Maintains proper access control for different user roles

## Implementation Notes

- Fix mempertahankan backward compatibility
- Role-based access control tetap berjalan dengan benar
- Performance impact minimal karena menggunakan efficient database queries
- No database schema changes required

## Verification

Untuk memverifikasi fix bekerja dengan benar:

1. Buka Dashboard dan catat total task per marketing
2. Buka Task Management (`/task-management`) dan bandingkan angka-angkanya
3. Angka-angka seharusnya sekarang konsisten antara kedua halaman

## Status

✅ **COMPLETED** - Dashboard task statistics sekarang menampilkan data yang akurat dan konsisten dengan Task Management page.
