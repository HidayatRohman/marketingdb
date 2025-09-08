# Todo List - Week Ahead View Update

## Deskripsi Perubahan

Sistem todo list sekarang menampilkan **1 minggu ke depan** secara default ketika menggunakan view=list tanpa filter aktif, bukan lagi hanya menampilkan tugas untuk tanggal yang dipilih.

## Perubahan Detail

### 1. **Backend (TodoListController.php)**
- **Default View List**: Ketika tidak ada filter aktif, sistem akan menampilkan todos untuk 1 minggu ke depan (dari hari ini sampai 7 hari ke depan)
- **Dengan Filter**: Tetap menggunakan tanggal yang dipilih ketika ada filter aktif

**Kode yang diubah**:
```php
// Default: show todos for 1 week ahead from today
if (!$hasFilters) {
    $startDate = now()->format('Y-m-d');
    $endDate = now()->addDays(7)->format('Y-m-d');
    $todos = $query->whereBetween('due_date', [$startDate, $endDate])
                  ->orderBy('due_date')
                  ->orderBy('due_time')
                  ->orderBy('priority')
                  ->get();
} else {
    // With filters: filter by selected date
    $todos = $query->whereDate('due_date', $selectedDate)
                  ->orderBy('due_time')
                  ->orderBy('priority')
                  ->get();
}
```

### 2. **Frontend (Index.vue)**
- **Header Dinamis**: Menampilkan "Tugas 1 Minggu ke Depan" dengan rentang tanggal ketika tidak ada filter
- **Computed Property**: Updated `todosForSelectedDate` untuk menangani logika 1 minggu ke depan

**Header yang diubah**:
```vue
<span v-if="filters.status === 'all' && filters.priority === 'all' && filters.assigned === 'all' && filters.user === 'all' && !filters.search">
    Tugas 1 Minggu ke Depan ({{ formatDate(new Date().toISOString().split('T')[0]) }} - {{ formatDate(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]) }})
</span>
```

**Logic Frontend**:
```vue
// If no filters are active, show todos for 1 week ahead (default behavior)
if (!hasFilters) {
    // Get current date and 7 days ahead
    const today = new Date();
    const weekAhead = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
    const todayKey = today.toISOString().split('T')[0];
    const weekAheadKey = weekAhead.toISOString().split('T')[0];
    
    // Collect todos from today to 1 week ahead
    const weekTodos: Todo[] = [];
    props.todos.forEach(todo => {
        if (todo.due_date >= todayKey && todo.due_date <= weekAheadKey) {
            weekTodos.push(todo);
        }
    });
    return weekTodos;
}
```

## Behavior Sistem

### ✅ Default (Tanpa Filter)
- **View**: List
- **Tampilan**: Todos untuk 1 minggu ke depan dari hari ini
- **Header**: "Tugas 1 Minggu ke Depan (2025-09-08 - 2025-09-15)"
- **Sorting**: Berdasarkan due_date, due_time, priority

### ✅ Dengan Filter Aktif
- **View**: List
- **Tampilan**: Todos untuk tanggal yang dipilih (sesuai behavior lama)
- **Header**: "Hasil Filter Tugas"
- **Filter yang mempengaruhi**: status, priority, assigned, user, search

### ✅ Calendar View
- **Behavior**: Tidak berubah, tetap menampilkan todos untuk tanggal yang dipilih
- **Tampilan**: Calendar dengan todos di tanggal masing-masing

### ✅ Board View
- **Behavior**: Tidak berubah, tetap menampilkan weekly board view

## Testing

Tested dengan file `test-week-ahead-view.php`:
```bash
$ php test-week-ahead-view.php
Testing Week Ahead View for Todo List
====================================
Today: 2025-09-08
Week Ahead: 2025-09-15

Query range: 2025-09-08 to 2025-09-15
Todos found for 1 week ahead: 4

Week ahead todos:
- [2025-09-08] Blasting (Priority: High, Status: In progress)
- [2025-09-09] Minta DP (Priority: High, Status: Pending)
- [2025-09-09] Minta tambahan DP ke Bu Sri Mulyani (Priority: High, Status: Pending)
- [2025-09-09] siapkan map (Priority: High, Status: Completed)
```

## Files Diubah

1. **app/Http/Controllers/TodoListController.php** - Logic backend untuk week ahead query
2. **resources/js/pages/TodoList/Index.vue** - Frontend logic dan header
3. **test-week-ahead-view.php** - Test file untuk verifikasi

## User Experience Improvement

- **Lebih Produktif**: User bisa langsung melihat tugas yang akan datang dalam seminggu
- **Default yang Berguna**: Tidak perlu manual memilih tanggal untuk melihat tugas upcoming
- **Filter Tetap Berfungsi**: User masih bisa filter berdasarkan tanggal spesifik jika diperlukan
- **Intuitive**: Header yang jelas menunjukkan rentang tanggal yang ditampilkan

---

**Status**: ✅ Implemented & Tested
**Version**: Updated September 8, 2025
