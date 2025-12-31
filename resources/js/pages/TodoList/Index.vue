<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Calendar,
    CalendarIcon,
    CheckCircle,
    Clock,
    Columns3,
    Edit,
    Flag,
    List,
    MoreVertical,
    Plus,
    Trash2,
    User,
    UserCheck,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toLocalDateString } from '@/lib/utils';

interface Todo {
    id: number;
    title: string;
    description?: string;
    priority: 'low' | 'medium' | 'high';
    status: 'pending' | 'in_progress' | 'completed';
    start_date?: string;
    due_date: string;
    due_time?: string;
    user_id: number;
    assigned_to?: number;
    tags?: string[];
    user: {
        id: number;
        name: string;
        email: string;
    };
    assigned_user?: {
        id: number;
        name: string;
        email: string;
    };
    is_overdue: boolean;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Stats {
    total: number;
    completed: number;
    pending: number;
    overdue: number;
}

interface Props {
    todos: Todo[];
    users: User[];
    selectedDate: string;
    view: 'calendar' | 'board' | 'list';
    stats: Stats;
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    filters?: {
        status: string;
        priority: string;
        assigned: string;
        user: string;
        search: string;
    };
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'To Do List', href: '/todos' },
];

// State
const parseDate = (dateStr: string): Date => {
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const currentDate = ref(parseDate(props.selectedDate));
const selectedDate = ref(parseDate(props.selectedDate));
const currentView = ref(props.view);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingTodo = ref<Todo | null>(null);

// Filter state - initialize from props if available
const filters = ref({
    status: (props.filters?.status || 'all') as 'all' | 'pending' | 'in_progress' | 'completed',
    priority: (props.filters?.priority || 'all') as 'all' | 'low' | 'medium' | 'high',
    assigned: (props.filters?.assigned || 'all') as 'all' | 'me' | 'others',
    user: props.filters?.user || 'all',
    search: props.filters?.search || '',
});

// Form state
const form = useForm({
    title: '',
    description: '',
    priority: 'medium' as 'low' | 'medium' | 'high',
    status: 'pending' as 'pending' | 'in_progress' | 'completed',
    start_date: '',
    due_date: props.selectedDate,
    due_time: '',
    assigned_to: null as number | null,
    tags: [] as string[],
});

const onDateFocus = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input && typeof (input as any).showPicker === 'function') {
        try {
            (input as any).showPicker();
        } catch {}
    }
};

const onTimeFocus = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input && typeof (input as any).showPicker === 'function') {
        try {
            (input as any).showPicker();
        } catch {}
    }
};

// Indonesian month names
const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Utility functions
const formatDate = (dateStr: string): string => {
    // Parse date as local time to avoid timezone issues
    const [year, month, day] = dateStr.split('-').map(Number);
    const date = new Date(year, month - 1, day); // month is 0-indexed
    return `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
};

const formatTime = (timeStr?: string): string => {
    if (!timeStr) return '';
    return timeStr;
};

const isToday = (dateStr: string): boolean => {
    const today = new Date();
    const [year, month, day] = dateStr.split('-').map(Number);
    const date = new Date(year, month - 1, day); // month is 0-indexed
    return date.toDateString() === today.toDateString();
};

const isSameDate = (date1: Date, date2: Date): boolean => {
    return date1.toDateString() === date2.toDateString();
};

// Computed properties
const currentMonthYear = computed(() => {
    return `${monthNames[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`;
});

const isSuperAdmin = computed(() => {
    return props.auth.user.role === 'super_admin';
});

const todosGroupedByDate = computed(() => {
    const grouped: Record<string, Todo[]> = {};
    props.todos.forEach((todo) => {
        const dateKey = todo.due_date;
        if (!grouped[dateKey]) {
            grouped[dateKey] = [];
        }
        grouped[dateKey].push(todo);
    });
    return grouped;
});

const todosForSelectedDate = computed(() => {
    // For calendar view, always show todos for selected date without filters
    if (currentView.value === 'calendar') {
        const dateKey = toLocalDateString(selectedDate.value);
        return todosGroupedByDate.value[dateKey] || [];
    }

    // For list view
    const hasFilters =
        filters.value.status !== 'all' ||
        filters.value.priority !== 'all' ||
        filters.value.assigned !== 'all' ||
        filters.value.user !== 'all' ||
        !!filters.value.search;

    // If Super Admin has active filters, show all filtered todos
    if (isSuperAdmin.value && hasFilters) {
        return allFilteredTodos.value;
    }

    // If no filters are active, show todos for 1 week ahead (default behavior)
    if (!hasFilters) {
        // Get current date and 7 days ahead
        const today = new Date();
        const weekAhead = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
        const todayKey = toLocalDateString(today);
        const weekAheadKey = toLocalDateString(weekAhead);

        // Collect todos from today to 1 week ahead
        const weekTodos: Todo[] = [];
        props.todos.forEach((todo) => {
            if (todo.due_date >= todayKey && todo.due_date <= weekAheadKey) {
                weekTodos.push(todo);
            }
        });
        return weekTodos;
    }

    // For filtered cases, show todos for selected date
    const dateKey = toLocalDateString(selectedDate.value);
    const todosForDate = todosGroupedByDate.value[dateKey] || [];

    // Apply filters to todos for the selected date
    return todosForDate.filter((todo) => {
        if (filters.value.status !== 'all' && todo.status !== filters.value.status) return false;
        if (filters.value.priority !== 'all' && todo.priority !== filters.value.priority) return false;
        if (filters.value.assigned !== 'all') {
            if (filters.value.assigned === 'me' && todo.assigned_to !== null && todo.user_id !== todo.assigned_to) return false;
            if (filters.value.assigned === 'others' && (todo.assigned_to === null || todo.user_id === todo.assigned_to)) return false;
        }
        if (filters.value.user !== 'all' && todo.user_id.toString() !== filters.value.user) return false;
        if (filters.value.search) {
            const searchLower = filters.value.search.toLowerCase();
            if (!todo.title.toLowerCase().includes(searchLower) && !(todo.description && todo.description.toLowerCase().includes(searchLower))) {
                return false;
            }
        }
        return true;
    });
});

// Get all todos for current view with filters applied
const allFilteredTodos = computed(() => {
    let todos = props.todos;

    // Apply filters
    if (filters.value.status !== 'all') {
        todos = todos.filter((todo) => todo.status === filters.value.status);
    }

    if (filters.value.priority !== 'all') {
        todos = todos.filter((todo) => todo.priority === filters.value.priority);
    }

    if (filters.value.assigned !== 'all') {
        if (filters.value.assigned === 'me') {
            todos = todos.filter((todo) => todo.assigned_to === null || todo.user_id === todo.assigned_to);
        } else {
            todos = todos.filter((todo) => todo.assigned_to !== null && todo.user_id !== todo.assigned_to);
        }
    }

    if (filters.value.user !== 'all') {
        todos = todos.filter((todo) => todo.user_id.toString() === filters.value.user);
    }

    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase();
        todos = todos.filter(
            (todo) => todo.title.toLowerCase().includes(searchLower) || (todo.description && todo.description.toLowerCase().includes(searchLower)),
        );
    }

    return todos;
});

const priorityColors = {
    low: 'bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-800 dark:from-emerald-900/30 dark:to-teal-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700',
    medium: 'bg-gradient-to-r from-amber-100 to-orange-100 text-amber-800 dark:from-amber-900/30 dark:to-orange-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-700',
    high: 'bg-gradient-to-r from-rose-100 to-red-100 text-rose-800 dark:from-rose-900/30 dark:to-red-900/30 dark:text-rose-300 border border-rose-200 dark:border-rose-700',
};

const statusColors = {
    pending:
        'bg-gradient-to-r from-slate-100 to-gray-100 text-slate-700 dark:from-slate-800/50 dark:to-gray-800/50 dark:text-slate-300 border border-slate-200 dark:border-slate-600',
    in_progress:
        'bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 dark:from-blue-900/30 dark:to-indigo-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
    completed:
        'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 dark:from-green-900/30 dark:to-emerald-900/30 dark:text-green-300 border border-green-200 dark:border-green-700',
};

const priorityLabels = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
};

const statusLabels = {
    pending: 'Pending',
    in_progress: 'Dikerjakan',
    completed: 'Selesai',
};

// Calendar generation
const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    // First day of the month
    const firstDay = new Date(year, month, 1);
    // Last day of the month
    const lastDay = new Date(year, month + 1, 0);

    // Start from the previous month to fill the first week
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    // End on the next month to fill the last week
    const endDate = new Date(lastDay);
    endDate.setDate(endDate.getDate() + (6 - lastDay.getDay()));

    const days: Date[] = [];
    const currentDay = new Date(startDate);

    while (currentDay <= endDate) {
        days.push(new Date(currentDay));
        currentDay.setDate(currentDay.getDate() + 1);
    }

    return days;
});

// Methods
const changeView = (view: string) => {
    if (view === 'calendar' || view === 'board' || view === 'list') {
        currentView.value = view;
        router.get(
            '/todos',
            {
                view: view,
                date: toLocalDateString(selectedDate.value),
            },
            { preserveState: true, preserveScroll: true, replace: true, only: ['todos', 'stats', 'selectedDate', 'view'] },
        );
    }
};

watch(currentView, (view) => {
    if (view === 'calendar' || view === 'board' || view === 'list') {
        router.get(
            '/todos',
            {
                view: view,
                date: toLocalDateString(selectedDate.value),
            },
            { preserveState: true, preserveScroll: true, replace: true, only: ['todos', 'stats', 'selectedDate', 'view'] },
        );
    }
});

const navigateMonth = (direction: 'prev' | 'next') => {
    const newDate = new Date(currentDate.value);
    if (direction === 'prev') {
        newDate.setMonth(newDate.getMonth() - 1);
    } else {
        newDate.setMonth(newDate.getMonth() + 1);
    }
    currentDate.value = newDate;

    router.get(
        '/todos',
        {
            view: currentView.value,
            date: toLocalDateString(newDate),
        },
        { preserveState: true },
    );
};

const navigateWeek = (direction: 'prev' | 'next') => {
    const newDate = new Date(selectedDate.value);
    if (direction === 'prev') {
        newDate.setDate(newDate.getDate() - 7);
    } else {
        newDate.setDate(newDate.getDate() + 7);
    }
    selectedDate.value = newDate;
    router.get(
        '/todos',
        {
            view: 'board',
            date: toLocalDateString(newDate),
        },
        { preserveState: true, preserveScroll: true, replace: true, only: ['todos', 'stats', 'selectedDate', 'view'] },
    );
};

const selectDate = (date: Date) => {
    selectedDate.value = date;
    if (currentView.value === 'list') {
        router.get(
            '/todos',
            {
                view: 'list',
                date: toLocalDateString(date),
            },
            { preserveState: true },
        );
    }
};

const openCreateModal = () => {
    form.reset();
    form.start_date = toLocalDateString(new Date());
    form.due_date = toLocalDateString(selectedDate.value);
    showCreateModal.value = true;
};

const openCreateModalForDay = (date: Date) => {
    form.reset();
    const dateString = toLocalDateString(date);
    form.start_date = dateString;
    form.due_date = dateString;
    showCreateModal.value = true;
};

const openEditModal = (todo: Todo) => {
    editingTodo.value = todo;
    form.title = todo.title;
    form.description = todo.description || '';
    form.priority = todo.priority;
    form.status = todo.status;
    form.start_date = todo.start_date || '';
    form.due_date = todo.due_date;
    form.due_time = todo.due_time || '';
    form.assigned_to = todo.assigned_to || null;
    form.tags = todo.tags || [];
    showEditModal.value = true;
};

const submitForm = () => {
    if (editingTodo.value) {
        form.put(`/todos/${editingTodo.value.id}`, {
            onSuccess: () => {
                showEditModal.value = false;
                editingTodo.value = null;
                form.reset();
                // Use router.reload to get fresh data
                router.reload({
                    only: ['todos', 'stats'],
                    preserveUrl: true,
                });
            },
        });
    } else {
        form.post('/todos', {
            onSuccess: () => {
                showCreateModal.value = false;
                form.reset();
                // Use router.reload to get fresh data including the new todo
                router.reload({
                    only: ['todos', 'stats'],
                    preserveUrl: true,
                });
            },
        });
    }
};

const clearFilters = () => {
    filters.value = {
        status: 'all',
        priority: 'all',
        assigned: 'all',
        user: 'all',
        search: '',
    };
    applyFilters();
};

const applyFilters = () => {
    const params = new URLSearchParams();
    params.set('view', currentView.value);
    params.set('date', toLocalDateString(selectedDate.value));

    if (filters.value.status !== 'all') params.set('status', filters.value.status);
    if (filters.value.priority !== 'all') params.set('priority', filters.value.priority);
    if (filters.value.assigned !== 'all') params.set('assigned', filters.value.assigned);
    if (filters.value.user !== 'all') params.set('user', filters.value.user);
    if (filters.value.search) params.set('search', filters.value.search);

    router.get(
        '/todos?' + params.toString(),
        {},
        {
            preserveUrl: true,
        },
    );
};

// Watch for filter changes and apply them
watch(
    filters,
    () => {
        applyFilters();
    },
    { deep: true },
);

const deleteTodo = (todo: Todo) => {
    if (confirm('Apakah Anda yakin ingin menghapus todo ini?')) {
        router.delete(`/todos/${todo.id}`, {
            onSuccess: () => {
                // Reload page to update data
                router.reload({
                    only: ['todos', 'stats'],
                    preserveUrl: true,
                });
            },
        });
    }
};

const updateStatus = (todo: Todo, checked: boolean) => {
    const status = checked ? 'completed' : 'pending';
    router.patch(
        `/todos/${todo.id}/status`,
        { status },
        {
            preserveUrl: true,
            onSuccess: () => {
                // Reload to update stats and data
                router.reload({
                    only: ['todos', 'stats'],
                    preserveUrl: true,
                });
            },
        },
    );
};

const getDayTodos = (date: Date) => {
    const dateKey = toLocalDateString(date);
    const todos = todosGroupedByDate.value[dateKey] || [];

    // Sort todos by priority and time for better display
    return todos.sort((a, b) => {
        // Prioritize high priority todos
        const priorityOrder = { high: 3, medium: 2, low: 1 };
        const priorityDiff = priorityOrder[b.priority] - priorityOrder[a.priority];
        if (priorityDiff !== 0) return priorityDiff;

        // Then sort by time if available
        if (a.due_time && b.due_time) {
            return a.due_time.localeCompare(b.due_time);
        }

        // Finally by title
        return a.title.localeCompare(b.title);
    });
};

// Get todos that span across date ranges (for bar visualization)
const getTodosForDateRange = (date: Date) => {
    const dateKey = toLocalDateString(date);

    return props.todos
        .filter((todo) => {
            // If todo has start_date, check if current date is within the range
            if (todo.start_date) {
                return dateKey >= todo.start_date && dateKey <= todo.due_date;
            }
            // If no start_date, only show on due_date (original behavior)
            return todo.due_date === dateKey;
        })
        .sort((a, b) => {
            const priorityOrder = { high: 3, medium: 2, low: 1 };
            const priorityDiff = priorityOrder[b.priority] - priorityOrder[a.priority];
            if (priorityDiff !== 0) return priorityDiff;

            if (a.due_time && b.due_time) {
                return a.due_time.localeCompare(b.due_time);
            }

            return a.title.localeCompare(b.title);
        });
};

// Get position of date within todo's date range (for bar visualization)
const getTodoBarPosition = (todo: Todo, date: Date) => {
    if (!todo.start_date) return 'full';

    const dateKey = toLocalDateString(date);
    const startDate = todo.start_date;
    const endDate = todo.due_date;

    if (dateKey === startDate && dateKey === endDate) return 'full';
    if (dateKey === startDate) return 'start';
    if (dateKey === endDate) return 'end';
    if (dateKey > startDate && dateKey < endDate) return 'middle';

    return 'none';
};

const isCurrentMonth = (date: Date) => {
    return date.getMonth() === currentDate.value.getMonth();
};

// Board view functions
const getWeekDays = () => {
    const startOfWeek = new Date(selectedDate.value);
    const day = startOfWeek.getDay();

    // If selected date is Sunday, show the upcoming week (next Monday to Sunday)
    // This matches the backend logic for better user experience
    if (day === 0) {
        // Sunday
        // Move to next Monday
        startOfWeek.setDate(startOfWeek.getDate() + 1);
        const newDay = startOfWeek.getDay();
        const diff = startOfWeek.getDate() - newDay + 1; // Start from Monday
        startOfWeek.setDate(diff);
    } else {
        const diff = startOfWeek.getDate() - day + 1; // Start from Monday
        startOfWeek.setDate(diff);
    }

    const weekDays = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date(startOfWeek);
        date.setDate(startOfWeek.getDate() + i);
        weekDays.push(date);
    }
    return weekDays;
};

const getTodosForWeekDay = (date: Date) => {
    const dateKey = toLocalDateString(date);

    return props.todos
        .filter((todo) => {
            // Include todos that span across this date or have due_date on this date
            if (todo.start_date) {
                return dateKey >= todo.start_date && dateKey <= todo.due_date;
            }
            return todo.due_date === dateKey;
        })
        .sort((a, b) => {
            const priorityOrder = { high: 3, medium: 2, low: 1 };
            const priorityDiff = priorityOrder[b.priority] - priorityOrder[a.priority];
            if (priorityDiff !== 0) return priorityDiff;

            if (a.due_time && b.due_time) {
                return a.due_time.localeCompare(b.due_time);
            }

            return a.title.localeCompare(b.title);
        });
};

const getDayName = (date: Date) => {
    return dayNames[date.getDay()];
};

const getShortDayName = (date: Date) => {
    const shortDayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    return shortDayNames[date.getDay()];
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed':
            return CheckCircle;
        case 'in_progress':
            return Clock;
        default:
            return AlertCircle;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="To Do List" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div
                class="relative overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 p-6 dark:border-blue-800 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20"
            >
                <div class="bg-grid-pattern absolute inset-0 opacity-5"></div>
                <div class="relative flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div class="space-y-2">
                        <h1 class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 bg-clip-text text-4xl font-bold text-transparent">
                            To Do List
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-300">
                            {{ isSuperAdmin ? '✨ Kelola semua tugas dan jadwal marketing tim' : '📋 Kelola tugas dan jadwal marketing Anda' }}
                        </p>
                        <div v-if="isSuperAdmin" class="mt-2">
                            <span
                                class="inline-flex items-center rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 px-3 py-1.5 text-sm font-semibold text-white shadow-lg"
                            >
                                👑 Super Admin View - Akses Penuh
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Tabs v-model="currentView">
                            <TabsList
                                class="border border-white/20 bg-white/70 shadow-lg backdrop-blur-sm dark:border-gray-700/50 dark:bg-gray-800/70"
                            >
                                <TabsTrigger
                                    value="calendar"
                                    class="flex items-center gap-2 transition-all duration-200 data-[state=active]:bg-gradient-to-r data-[state=active]:from-blue-500 data-[state=active]:to-indigo-500 data-[state=active]:text-white"
                                >
                                    <CalendarIcon class="h-4 w-4" />
                                    Kalender
                                </TabsTrigger>
                                <TabsTrigger
                                    value="board"
                                    class="flex items-center gap-2 transition-all duration-200 data-[state=active]:bg-gradient-to-r data-[state=active]:from-green-500 data-[state=active]:to-emerald-500 data-[state=active]:text-white"
                                >
                                    <Columns3 class="h-4 w-4" />
                                    Board
                                </TabsTrigger>
                                <TabsTrigger
                                    value="list"
                                    class="flex items-center gap-2 transition-all duration-200 data-[state=active]:bg-gradient-to-r data-[state=active]:from-purple-500 data-[state=active]:to-pink-500 data-[state=active]:text-white"
                                >
                                    <List class="h-4 w-4" />
                                    Daftar
                                </TabsTrigger>
                            </TabsList>
                        </Tabs>

                        <Button
                            @click="openCreateModal"
                            class="transform rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-2.5 font-semibold text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-blue-600 hover:to-indigo-700 hover:shadow-xl"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Tambah Todo
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <Card class="relative overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20"
                    ></div>
                    <CardContent class="relative p-4">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-3 shadow-lg">
                                <List class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Tugas</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-emerald-500/10 dark:from-green-500/20 dark:to-emerald-500/20"
                    ></div>
                    <CardContent class="relative p-4">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 p-3 shadow-lg">
                                <CheckCircle class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Selesai</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.completed }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-orange-500/10 dark:from-amber-500/20 dark:to-orange-500/20"
                    ></div>
                    <CardContent class="relative p-4">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 p-3 shadow-lg">
                                <Clock class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.pending }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-red-500/10 dark:from-rose-500/20 dark:to-red-500/20"></div>
                    <CardContent class="relative p-4">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-xl bg-gradient-to-br from-rose-500 to-red-600 p-3 shadow-lg">
                                <XCircle class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Terlambat</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.overdue }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Calendar View -->
            <div v-if="currentView === 'calendar'" class="space-y-4">
                <!-- Calendar Legend -->
                <Card class="border-blue-200 bg-gradient-to-r from-gray-50 to-blue-50 dark:border-blue-700 dark:from-gray-800 dark:to-blue-900/20">
                    <CardContent class="p-5">
                        <div class="flex flex-wrap items-center gap-6 text-sm">
                            <span class="flex items-center gap-2 font-semibold text-gray-800 dark:text-gray-200">
                                <CalendarIcon class="h-4 w-4" />
                                Legend:
                            </span>

                            <div class="flex items-center gap-2.5">
                                <div
                                    class="h-4 w-4 rounded-lg border border-slate-300 bg-gradient-to-br from-slate-200 to-gray-200 shadow-sm dark:border-slate-500 dark:from-slate-600 dark:to-gray-600"
                                ></div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Pending</span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <div class="h-4 w-4 rounded-lg border border-blue-300 bg-gradient-to-br from-blue-400 to-indigo-500 shadow-sm"></div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Dikerjakan</span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <div
                                    class="h-4 w-4 rounded-lg border border-green-300 bg-gradient-to-br from-green-400 to-emerald-500 shadow-sm"
                                ></div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Selesai</span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <div
                                    class="rounded-lg border border-rose-200 bg-gradient-to-br from-rose-100 to-red-100 p-1 dark:border-rose-700 dark:from-rose-900/30 dark:to-red-900/30"
                                >
                                    <Flag class="h-3 w-3 text-rose-600 dark:text-rose-400" />
                                </div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Prioritas Tinggi</span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <div class="flex items-center">
                                    <span class="font-mono text-sm text-purple-600 dark:text-purple-400">●━━●</span>
                                </div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Range Tanggal</span>
                            </div>

                            <span class="text-xs text-gray-500">💡 Todo dengan tanggal mulai ditampilkan sebagai bar dari mulai sampai deadline</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Calendar Header -->
                <Card>
                    <CardHeader class="pb-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold">{{ currentMonthYear }}</h2>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" @click="navigateMonth('prev')">
                                    <ArrowLeft class="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="sm" @click="navigateMonth('next')">
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <!-- Day headers -->
                            <div v-for="day in dayNames" :key="day" class="p-3 text-center text-sm font-medium text-gray-500">
                                {{ day }}
                            </div>

                            <!-- Calendar days -->
                            <div
                                v-for="date in calendarDays"
                                :key="date.toString()"
                                @click="selectDate(date)"
                                :class="[
                                    'min-h-[100px] cursor-pointer border border-gray-200 p-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800',
                                    {
                                        'bg-blue-50 dark:bg-blue-900/20': isSameDate(date, selectedDate),
                                        'text-gray-400 dark:text-gray-600': !isCurrentMonth(date),
                                        'bg-yellow-50 dark:bg-yellow-900/20': isToday(toLocalDateString(date)),
                                        'ring-2 ring-blue-500': isSameDate(date, selectedDate),
                                    },
                                ]"
                            >
                                <div class="flex h-full flex-col">
                                    <div class="mb-1 text-sm font-medium">
                                        {{ date.getDate() }}
                                    </div>

                                    <!-- Todos for this date with bar visualization -->
                                    <div class="flex-1 space-y-1">
                                        <div
                                            v-for="todo in getTodosForDateRange(date).slice(0, 3)"
                                            :key="todo.id"
                                            :class="[
                                                'relative cursor-pointer p-1 text-xs transition-colors',
                                                statusColors[todo.status],
                                                'hover:opacity-80',
                                                {
                                                    // Bar styling based on position in date range
                                                    'rounded-l rounded-r': getTodoBarPosition(todo, date) === 'full',
                                                    'rounded-l': getTodoBarPosition(todo, date) === 'start',
                                                    'rounded-r': getTodoBarPosition(todo, date) === 'end',
                                                    'rounded-none': getTodoBarPosition(todo, date) === 'middle',
                                                },
                                            ]"
                                            :title="`${todo.title} - ${statusLabels[todo.status]} - ${priorityLabels[todo.priority]}${todo.due_time ? ' (' + todo.due_time + ')' : ''}${todo.start_date ? '\nPeriode: ' + formatDate(todo.start_date) + ' - ' + formatDate(todo.due_date) : ''}`"
                                            @click.stop="openEditModal(todo)"
                                        >
                                            <div class="flex items-center gap-1">
                                                <component :is="getStatusIcon(todo.status)" class="h-3 w-3 flex-shrink-0" />
                                                <span class="truncate">{{ todo.title }}</span>
                                                <!-- Show date range indicator -->
                                                <span v-if="todo.start_date && getTodoBarPosition(todo, date) === 'start'" class="text-xs opacity-60"
                                                    >●</span
                                                >
                                                <span
                                                    v-else-if="todo.start_date && getTodoBarPosition(todo, date) === 'middle'"
                                                    class="text-xs opacity-60"
                                                    >━</span
                                                >
                                                <span
                                                    v-else-if="todo.start_date && getTodoBarPosition(todo, date) === 'end'"
                                                    class="text-xs opacity-60"
                                                    >●</span
                                                >
                                            </div>
                                            <div v-if="todo.due_time && getTodoBarPosition(todo, date) === 'end'" class="text-xs opacity-75">
                                                {{ todo.due_time }}
                                            </div>
                                        </div>

                                        <div
                                            v-if="getTodosForDateRange(date).length > 3"
                                            class="cursor-pointer rounded p-1 text-xs text-gray-500 hover:bg-gray-100"
                                            @click.stop="selectDate(date)"
                                            :title="`Klik untuk melihat semua ${getTodosForDateRange(date).length} tugas`"
                                        >
                                            +{{ getTodosForDateRange(date).length - 3 }} lainnya
                                        </div>

                                        <!-- Show priority indicator if any high priority todos -->
                                        <div
                                            v-if="getTodosForDateRange(date).some((todo) => todo.priority === 'high' && todo.status !== 'completed')"
                                            class="flex items-center gap-1 text-xs font-medium text-red-600"
                                        >
                                            <Flag class="h-3 w-3" />
                                            Prioritas Tinggi
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Selected Date Todos -->
                <Card v-if="todosForSelectedDate.length > 0">
                    <CardHeader>
                        <CardTitle> Tugas untuk {{ formatDate(toLocalDateString(selectedDate)) }} </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="todo in todosForSelectedDate"
                                :key="todo.id"
                                class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-4 transition-all duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-600"
                            >
                                <!-- Priority indicator line -->
                                <div
                                    class="absolute top-0 bottom-0 left-0 w-1 rounded-l-xl"
                                    :class="{
                                        'bg-gradient-to-b from-emerald-500 to-teal-500': todo.priority === 'low',
                                        'bg-gradient-to-b from-amber-500 to-orange-500': todo.priority === 'medium',
                                        'bg-gradient-to-b from-rose-500 to-red-500': todo.priority === 'high',
                                    }"
                                ></div>

                                <div class="flex items-center justify-between">
                                    <div class="flex flex-1 items-center space-x-4">
                                        <Checkbox
                                            :checked="todo.status === 'completed'"
                                            @update:checked="(checked: boolean) => updateStatus(todo, checked)"
                                            class="data-[state=checked]:border-green-500 data-[state=checked]:bg-green-500"
                                        />

                                        <div class="flex-1">
                                            <h4
                                                class="font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-gray-100 dark:group-hover:text-blue-400"
                                            >
                                                {{ todo.title }}
                                            </h4>
                                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                                <Badge :class="priorityColors[todo.priority]" class="text-xs font-medium shadow-sm">
                                                    <Flag class="mr-1 h-3 w-3" />
                                                    {{ priorityLabels[todo.priority] }}
                                                </Badge>
                                                <Badge :class="statusColors[todo.status]" class="text-xs font-medium shadow-sm">
                                                    <component :is="getStatusIcon(todo.status)" class="mr-1 h-3 w-3" />
                                                    {{ statusLabels[todo.status] }}
                                                </Badge>
                                                <div
                                                    v-if="todo.due_time"
                                                    class="flex items-center gap-1 rounded-lg border border-cyan-200 bg-gradient-to-r from-cyan-50 to-blue-50 px-2 py-1 text-xs font-medium text-cyan-700 dark:border-cyan-700 dark:from-cyan-900/20 dark:to-blue-900/20 dark:text-cyan-300"
                                                >
                                                    <Clock class="h-3 w-3" />
                                                    {{ todo.due_time }}
                                                </div>
                                                <div
                                                    class="flex items-center gap-1 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-2 py-1 text-xs font-medium text-blue-700 dark:border-blue-700 dark:from-blue-900/20 dark:to-indigo-900/20 dark:text-blue-300"
                                                >
                                                    <User class="h-3 w-3" />
                                                    {{ todo.user.name }}
                                                </div>
                                                <div
                                                    v-if="todo.assigned_user"
                                                    class="flex items-center gap-1 rounded-lg border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 px-2 py-1 text-xs font-medium text-green-700 dark:border-green-700 dark:from-green-900/20 dark:to-emerald-900/20 dark:text-green-300"
                                                >
                                                    <UserCheck class="h-3 w-3" />
                                                    {{ todo.assigned_user.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="opacity-0 transition-opacity duration-200 group-hover:opacity-100 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            >
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent>
                                            <DropdownMenuItem @click="openEditModal(todo)">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="deleteTodo(todo)" class="text-red-600">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Hapus
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Board View -->
            <div v-if="currentView === 'board'" class="space-y-4">
                <!-- Board Header -->
                <Card>
                    <CardHeader class="pb-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between md:flex-nowrap">
                            <div class="flex items-start gap-2 min-w-0">
                                <Columns3 class="h-5 w-5" />
                                <div class="min-w-0">
                                    <CardTitle class="text-base sm:text-lg break-words">Board View - Weekly Planning</CardTitle>
                                    <p class="mt-1 text-xs text-gray-600 sm:text-sm break-words">Kelola tugas berdasarkan hari dalam minggu</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-2 sm:mt-0">
                                <Button variant="outline" size="sm" @click="navigateWeek('prev')">
                                    <ArrowLeft class="h-4 w-4" />
                                </Button>
                                <span class="px-3 text-xs font-medium sm:text-sm">
                                    {{ formatDate(toLocalDateString(getWeekDays()[0])) }} -
                                    {{ formatDate(toLocalDateString(getWeekDays()[6])) }}
                                </span>
                                <Button variant="outline" size="sm" @click="navigateWeek('next')">
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <!-- Kanban Board (Weekly, same data rule as Calendar) -->
                <div class="grid min-h-[600px] grid-cols-1 gap-4 md:grid-cols-7">
                    <div v-for="(date, index) in getWeekDays()" :key="index" class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <!-- Day Header -->
                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ getDayName(date) }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ formatDate(toLocalDateString(date)) }}
                            </p>
                            <div class="mt-1 text-xs text-gray-400">{{ getTodosForDateRange(date).length }} tugas</div>
                        </div>

                        <!-- Todo Cards -->
                        <div class="space-y-3">
                            <div
                                v-for="todo in getTodosForDateRange(date)"
                                :key="todo.id"
                                class="group relative cursor-pointer overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:hover:border-blue-600"
                                @click="openEditModal(todo)"
                            >
                                <!-- Priority indicator line -->
                                <div
                                    class="absolute top-0 bottom-0 left-0 w-1 rounded-l-xl"
                                    :class="{
                                        'bg-gradient-to-b from-emerald-500 to-teal-500': todo.priority === 'low',
                                        'bg-gradient-to-b from-amber-500 to-orange-500': todo.priority === 'medium',
                                        'bg-gradient-to-b from-rose-500 to-red-500': todo.priority === 'high',
                                    }"
                                ></div>

                                <!-- Card Header -->
                                <div class="mb-3 flex items-start justify-between">
                                    <h4
                                        class="line-clamp-2 text-sm leading-tight font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-gray-100 dark:group-hover:text-blue-400"
                                    >
                                        {{ todo.title }}
                                    </h4>
                                    <Checkbox
                                        :checked="todo.status === 'completed'"
                                        @update:checked="(checked: boolean) => updateStatus(todo, checked)"
                                        @click.stop
                                        class="ml-2 flex-shrink-0 data-[state=checked]:border-green-500 data-[state=checked]:bg-green-500"
                                    />
                                </div>

                                <!-- Card Content -->
                                <div class="space-y-2.5">
                                    <!-- Priority & Status -->
                                    <div class="flex items-center gap-2">
                                        <Badge :class="priorityColors[todo.priority]" class="text-xs font-medium shadow-sm">
                                            <Flag class="mr-1 h-2.5 w-2.5" />
                                            {{ priorityLabels[todo.priority] }}
                                        </Badge>
                                        <Badge :class="statusColors[todo.status]" class="text-xs font-medium shadow-sm">
                                            <component :is="getStatusIcon(todo.status)" class="mr-1 h-2.5 w-2.5" />
                                            {{ statusLabels[todo.status] }}
                                        </Badge>
                                    </div>

                                    <!-- Time & Duration -->
                                    <div
                                        v-if="todo.due_time"
                                        class="flex items-center gap-1 rounded-md border border-cyan-200 bg-gradient-to-r from-cyan-50 to-blue-50 px-2 py-1 text-xs font-medium text-cyan-700 dark:border-cyan-700 dark:from-cyan-900/20 dark:to-blue-900/20 dark:text-cyan-300"
                                    >
                                        <Clock class="h-3 w-3" />
                                        {{ todo.due_time }}
                                    </div>

                                    <!-- Date Range (if applicable) -->
                                    <div
                                        v-if="todo.start_date && todo.start_date !== todo.due_date"
                                        class="flex items-center gap-1 rounded-md border border-purple-200 bg-gradient-to-r from-purple-50 to-indigo-50 px-2 py-1 text-xs font-medium text-purple-700 dark:border-purple-700 dark:from-purple-900/20 dark:to-indigo-900/20 dark:text-purple-300"
                                    >
                                        <Calendar class="h-3 w-3" />
                                        {{ formatDate(todo.start_date) }} - {{ formatDate(todo.due_date) }}
                                    </div>

                                    <!-- Assignee -->
                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <div
                                                class="flex items-center gap-1 rounded-md border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-2 py-1 text-xs font-medium text-blue-700 dark:border-blue-700 dark:from-blue-900/20 dark:to-indigo-900/20 dark:text-blue-300"
                                            >
                                                <User class="h-3 w-3" />
                                                {{ todo.user.name.split(' ')[0] }}
                                            </div>
                                            <div
                                                v-if="todo.assigned_user"
                                                class="flex items-center gap-1 rounded-md border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 px-2 py-1 text-xs font-medium text-green-700 dark:border-green-700 dark:from-green-900/20 dark:to-emerald-900/20 dark:text-green-300"
                                            >
                                                <UserCheck class="h-3 w-3" />
                                                {{ todo.assigned_user.name.split(' ')[0] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Todo Button for this day -->
                            <Button
                                variant="ghost"
                                class="w-full justify-start rounded-xl border-2 border-dashed border-gray-300 text-gray-500 transition-all duration-200 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-600 dark:text-gray-400 dark:hover:border-blue-500 dark:hover:bg-blue-900/20 dark:hover:text-blue-400"
                                @click="openCreateModalForDay(date)"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Tugas
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-if="currentView === 'list'" class="space-y-4">
                <!-- Filters Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            <span>Filter & Pencarian</span>
                            <Button variant="outline" size="sm" @click="clearFilters"> Reset Filter </Button>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
                            <!-- Date Filter -->
                            <div>
                                <Label>Tanggal</Label>
                                <DatePicker
                                    :model-value="toLocalDateString(selectedDate)"
                                    @update:model-value="(value: string) => selectDate(new Date(value))"
                                    placeholder="Pilih tanggal"
                                />
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <Label for="statusFilter">Status</Label>
                                <select
                                    id="statusFilter"
                                    v-model="filters.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">Dikerjakan</option>
                                    <option value="completed">Selesai</option>
                                </select>
                            </div>

                            <!-- Priority Filter -->
                            <div>
                                <Label for="priorityFilter">Prioritas</Label>
                                <select
                                    id="priorityFilter"
                                    v-model="filters.priority"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="all">Semua Prioritas</option>
                                    <option value="high">Tinggi</option>
                                    <option value="medium">Sedang</option>
                                    <option value="low">Rendah</option>
                                </select>
                            </div>

                            <!-- User Filter -->
                            <div>
                                <Label for="userFilter">Dibuat Oleh</Label>
                                <select
                                    id="userFilter"
                                    v-model="filters.user"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="all">Semua User</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id.toString()">
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Assigned Filter -->
                            <div>
                                <Label for="assignedFilter">Assignment</Label>
                                <select
                                    id="assignedFilter"
                                    v-model="filters.assigned"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="all">Semua</option>
                                    <option v-if="!isSuperAdmin" value="me">Tugas Saya</option>
                                    <option value="others">Ada Assignment</option>
                                    <option v-if="isSuperAdmin" value="unassigned">Belum Di-assign</option>
                                </select>
                            </div>

                            <!-- Search -->
                            <div>
                                <Label for="searchFilter">Pencarian</Label>
                                <Input id="searchFilter" v-model="filters.search" placeholder="Cari judul atau deskripsi..." />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Todo List -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>
                                <span
                                    v-if="
                                        filters.status === 'all' &&
                                        filters.priority === 'all' &&
                                        filters.assigned === 'all' &&
                                        filters.user === 'all' &&
                                        !filters.search
                                    "
                                >
                                    Tugas 1 Minggu ke Depan ({{ formatDate(toLocalDateString(new Date())) }} -
                                    {{ formatDate(toLocalDateString(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000))) }})
                                </span>
                                <span v-else> Hasil Filter Tugas </span>
                                <span v-if="todosForSelectedDate.length !== props.todos.length" class="ml-2 text-sm font-normal text-gray-500">
                                    ({{ todosForSelectedDate.length }} hasil)
                                </span>
                            </CardTitle>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div v-if="todosForSelectedDate.length === 0" class="py-8 text-center text-gray-500">Tidak ada tugas untuk tanggal ini</div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="todo in todosForSelectedDate"
                                :key="todo.id"
                                class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-600"
                            >
                                <!-- Priority indicator line -->
                                <div
                                    class="absolute top-0 bottom-0 left-0 w-1 rounded-l-xl"
                                    :class="{
                                        'bg-gradient-to-b from-emerald-500 to-teal-500': todo.priority === 'low',
                                        'bg-gradient-to-b from-amber-500 to-orange-500': todo.priority === 'medium',
                                        'bg-gradient-to-b from-rose-500 to-red-500': todo.priority === 'high',
                                    }"
                                ></div>

                                <div class="flex items-center justify-between">
                                    <div class="flex flex-1 items-start space-x-4">
                                        <div class="mt-1">
                                            <Checkbox
                                                :checked="todo.status === 'completed'"
                                                @update:checked="(checked: boolean) => updateStatus(todo, checked)"
                                                class="data-[state=checked]:border-green-500 data-[state=checked]:bg-green-500"
                                            />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="mb-2 flex items-start justify-between">
                                                <h4
                                                    class="text-lg font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-gray-100 dark:group-hover:text-blue-400"
                                                >
                                                    {{ todo.title }}
                                                </h4>
                                                <div class="ml-4 flex items-center gap-2">
                                                    <Badge :class="priorityColors[todo.priority]" class="text-xs font-medium shadow-sm">
                                                        <Flag class="mr-1 h-3 w-3" />
                                                        {{ priorityLabels[todo.priority] }}
                                                    </Badge>
                                                    <Badge :class="statusColors[todo.status]" class="text-xs font-medium shadow-sm">
                                                        <component :is="getStatusIcon(todo.status)" class="mr-1 h-3 w-3" />
                                                        {{ statusLabels[todo.status] }}
                                                    </Badge>
                                                </div>
                                            </div>

                                            <p v-if="todo.description" class="mb-3 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                                {{ todo.description }}
                                            </p>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <!-- Date info -->
                                                <div
                                                    class="flex items-center gap-1 rounded-lg border border-purple-200 bg-gradient-to-r from-purple-50 to-indigo-50 px-3 py-1.5 text-sm font-medium text-purple-700 dark:border-purple-700 dark:from-purple-900/20 dark:to-indigo-900/20 dark:text-purple-300"
                                                >
                                                    <Calendar class="h-3.5 w-3.5" />
                                                    <span v-if="todo.start_date">
                                                        {{ formatDate(todo.start_date) }} - {{ formatDate(todo.due_date) }}
                                                    </span>
                                                    <span v-else>
                                                        {{ formatDate(todo.due_date) }}
                                                    </span>
                                                </div>

                                                <!-- Time info -->
                                                <div
                                                    v-if="todo.due_time"
                                                    class="flex items-center gap-1 rounded-lg border border-cyan-200 bg-gradient-to-r from-cyan-50 to-blue-50 px-3 py-1.5 text-sm font-medium text-cyan-700 dark:border-cyan-700 dark:from-cyan-900/20 dark:to-blue-900/20 dark:text-cyan-300"
                                                >
                                                    <Clock class="h-3.5 w-3.5" />
                                                    {{ todo.due_time }}
                                                </div>

                                                <!-- Creator info -->
                                                <div
                                                    class="flex items-center gap-1 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-3 py-1.5 text-sm font-medium text-blue-700 dark:border-blue-700 dark:from-blue-900/20 dark:to-indigo-900/20 dark:text-blue-300"
                                                >
                                                    <User class="h-3.5 w-3.5" />
                                                    {{ todo.user.name }}
                                                </div>

                                                <!-- Assignee info -->
                                                <div
                                                    v-if="todo.assigned_user"
                                                    class="flex items-center gap-1 rounded-lg border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 px-3 py-1.5 text-sm font-medium text-green-700 dark:border-green-700 dark:from-green-900/20 dark:to-emerald-900/20 dark:text-green-300"
                                                >
                                                    <UserCheck class="h-3.5 w-3.5" />
                                                    {{ todo.assigned_user.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="opacity-0 transition-opacity duration-200 group-hover:opacity-100 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            >
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent>
                                            <DropdownMenuItem @click="openEditModal(todo)">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="deleteTodo(todo)" class="text-red-600">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Hapus
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Create/Edit Todo Modal -->
            <Dialog
                :open="showCreateModal || showEditModal"
                @update:open="
                    (open) => {
                        showCreateModal = open;
                        showEditModal = open;
                    }
                "
            >
                <DialogContent class="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editingTodo ? 'Edit Todo' : 'Tambah Todo Baru' }}
                        </DialogTitle>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <Label for="title">Judul *</Label>
                                <Input id="title" v-model="form.title" placeholder="Masukkan judul todo" required />
                                <span v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</span>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="description">Deskripsi</Label>
                                <Textarea id="description" v-model="form.description" placeholder="Masukkan deskripsi todo" :rows="3" />
                                <span v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</span>
                            </div>

                            <div>
                                <Label for="priority">Prioritas *</Label>
                                <select
                                    id="priority"
                                    v-model="form.priority"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="low">Rendah</option>
                                    <option value="medium">Sedang</option>
                                    <option value="high">Tinggi</option>
                                </select>
                                <span v-if="form.errors.priority" class="text-sm text-red-600">{{ form.errors.priority }}</span>
                            </div>

                            <div>
                                <Label for="status">Status *</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">Sedang Dikerjakan</option>
                                    <option value="completed">Selesai</option>
                                </select>
                                <span v-if="form.errors.status" class="text-sm text-red-600">{{ form.errors.status }}</span>
                            </div>

                            <div>
                                <Label for="start_date">Tanggal Mulai</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    v-model="form.start_date"
                                    @focus="onDateFocus"
                                    @click="onDateFocus"
                                    class="mt-2"
                                />
                                <span v-if="form.errors.start_date" class="text-sm text-red-600">{{ form.errors.start_date }}</span>
                            </div>

                            <div>
                                <Label for="due_date">Tanggal Deadline *</Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    v-model="form.due_date"
                                    required
                                    @focus="onDateFocus"
                                    @click="onDateFocus"
                                    class="mt-2"
                                />
                                <span v-if="form.errors.due_date" class="text-sm text-red-600">{{ form.errors.due_date }}</span>
                            </div>

                            <div>
                                <Label for="due_time">Waktu Deadline</Label>
                                <Input
                                    id="due_time"
                                    type="time"
                                    v-model="form.due_time"
                                    @focus="onTimeFocus"
                                    @click="onTimeFocus"
                                    class="mt-2"
                                />
                                <span v-if="form.errors.due_time" class="text-sm text-red-600">{{ form.errors.due_time }}</span>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="assigned_to">Assign ke User</Label>
                                <select
                                    id="assigned_to"
                                    v-model="form.assigned_to"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option :value="null">Tidak ada</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                                <span v-if="form.errors.assigned_to" class="text-sm text-red-600">{{ form.errors.assigned_to }}</span>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                @click="
                                    showCreateModal = false;
                                    showEditModal = false;
                                "
                            >
                                Batal
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ editingTodo ? 'Update' : 'Simpan' }}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
