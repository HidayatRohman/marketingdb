<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Head, router, useForm } from '@inertiajs/vue3';
import { 
    Plus, Calendar as CalendarIcon, List, Clock, Flag, 
    User, CheckCircle, XCircle, AlertCircle, MoreVertical,
    Edit, Trash2, ArrowLeft, ArrowRight
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Todo {
    id: number;
    title: string;
    description?: string;
    priority: 'low' | 'medium' | 'high';
    status: 'pending' | 'in_progress' | 'completed';
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
    view: 'calendar' | 'list';
    stats: Stats;
}

const props = defineProps<Props>();

// State
const currentDate = ref(new Date(props.selectedDate));
const selectedDate = ref(new Date(props.selectedDate));
const currentView = ref(props.view);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingTodo = ref<Todo | null>(null);

// Form state
const form = useForm({
    title: '',
    description: '',
    priority: 'medium' as 'low' | 'medium' | 'high',
    status: 'pending' as 'pending' | 'in_progress' | 'completed',
    due_date: props.selectedDate,
    due_time: '',
    assigned_to: null as number | null,
    tags: [] as string[],
});

// Indonesian month names
const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Utility functions
const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    return `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
};

const formatTime = (timeStr?: string): string => {
    if (!timeStr) return '';
    return timeStr;
};

const isToday = (dateStr: string): boolean => {
    const today = new Date();
    const date = new Date(dateStr);
    return date.toDateString() === today.toDateString();
};

const isSameDate = (date1: Date, date2: Date): boolean => {
    return date1.toDateString() === date2.toDateString();
};

// Computed properties
const currentMonthYear = computed(() => {
    return `${monthNames[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`;
});

const todosGroupedByDate = computed(() => {
    const grouped: Record<string, Todo[]> = {};
    props.todos.forEach(todo => {
        const dateKey = todo.due_date;
        if (!grouped[dateKey]) {
            grouped[dateKey] = [];
        }
        grouped[dateKey].push(todo);
    });
    return grouped;
});

const todosForSelectedDate = computed(() => {
    const dateKey = selectedDate.value.toISOString().split('T')[0];
    return todosGroupedByDate.value[dateKey] || [];
});

const priorityColors = {
    low: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    high: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
};

const statusColors = {
    pending: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
};

const priorityLabels = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi'
};

const statusLabels = {
    pending: 'Pending',
    in_progress: 'Dikerjakan',
    completed: 'Selesai'
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
    if (view === 'calendar' || view === 'list') {
        currentView.value = view;
        router.get('/todos', { 
            view: view, 
            date: selectedDate.value.toISOString().split('T')[0]
        }, { preserveState: true });
    }
};

const navigateMonth = (direction: 'prev' | 'next') => {
    const newDate = new Date(currentDate.value);
    if (direction === 'prev') {
        newDate.setMonth(newDate.getMonth() - 1);
    } else {
        newDate.setMonth(newDate.getMonth() + 1);
    }
    currentDate.value = newDate;
    
    router.get('/todos', { 
        view: currentView.value, 
        date: newDate.toISOString().split('T')[0]
    }, { preserveState: true });
};

const selectDate = (date: Date) => {
    selectedDate.value = date;
    if (currentView.value === 'list') {
        router.get('/todos', { 
            view: 'list', 
            date: date.toISOString().split('T')[0]
        }, { preserveState: true });
    }
};

const openCreateModal = () => {
    form.reset();
    form.due_date = selectedDate.value.toISOString().split('T')[0];
    showCreateModal.value = true;
};

const openEditModal = (todo: Todo) => {
    editingTodo.value = todo;
    form.title = todo.title;
    form.description = todo.description || '';
    form.priority = todo.priority;
    form.status = todo.status;
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
            }
        });
    } else {
        form.post('/todos', {
            onSuccess: () => {
                showCreateModal.value = false;
            }
        });
    }
};

const deleteTodo = (todo: Todo) => {
    if (confirm('Apakah Anda yakin ingin menghapus todo ini?')) {
        router.delete(`/todos/${todo.id}`);
    }
};

const updateStatus = (todo: Todo, checked: boolean) => {
    const status = checked ? 'completed' : 'pending';
    router.patch(`/todos/${todo.id}/status`, { status }, {
        preserveScroll: true
    });
};

const getDayTodos = (date: Date) => {
    const dateKey = date.toISOString().split('T')[0];
    return todosGroupedByDate.value[dateKey] || [];
};

const isCurrentMonth = (date: Date) => {
    return date.getMonth() === currentDate.value.getMonth();
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
    <AppLayout>
        <Head title="To Do List" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">To Do List</h1>
                    <p class="text-gray-600 dark:text-gray-400">Kelola tugas dan jadwal marketing Anda</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <Tabs :default-value="currentView" @update:model-value="changeView">
                        <TabsList>
                            <TabsTrigger value="calendar" class="flex items-center gap-2">
                                <CalendarIcon class="h-4 w-4" />
                                Kalender
                            </TabsTrigger>
                            <TabsTrigger value="list" class="flex items-center gap-2">
                                <List class="h-4 w-4" />
                                Daftar
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                    
                    <Button @click="openCreateModal" class="ml-2">
                        <Plus class="h-4 w-4 mr-2" />
                        Tambah Todo
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <List class="h-4 w-4 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total</p>
                                <p class="text-xl font-bold">{{ stats.total }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <CheckCircle class="h-4 w-4 text-green-600" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Selesai</p>
                                <p class="text-xl font-bold">{{ stats.completed }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <Clock class="h-4 w-4 text-yellow-600" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Pending</p>
                                <p class="text-xl font-bold">{{ stats.pending }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center space-x-2">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <XCircle class="h-4 w-4 text-red-600" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Terlambat</p>
                                <p class="text-xl font-bold">{{ stats.overdue }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Calendar View -->
            <div v-if="currentView === 'calendar'" class="space-y-4">
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
                            <div v-for="day in dayNames" :key="day" 
                                 class="p-3 text-center text-sm font-medium text-gray-500">
                                {{ day }}
                            </div>
                            
                            <!-- Calendar days -->
                            <div v-for="date in calendarDays" :key="date.toString()"
                                 @click="selectDate(date)"
                                         :class="[
                                     'p-2 border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 min-h-[100px]',
                                     {
                                         'bg-blue-50 dark:bg-blue-900/20': isSameDate(date, selectedDate),
                                         'text-gray-400 dark:text-gray-600': !isCurrentMonth(date),
                                         'bg-yellow-50 dark:bg-yellow-900/20': isToday(date.toISOString().split('T')[0]),
                                         'ring-2 ring-blue-500': isSameDate(date, selectedDate)
                                     }
                                 ]">
                                <div class="flex flex-col h-full">
                                    <div class="text-sm font-medium mb-1">
                                        {{ date.getDate() }}
                                    </div>
                                    
                                    <!-- Todos for this date -->
                                    <div class="flex-1 space-y-1">
                                        <div v-for="todo in getDayTodos(date).slice(0, 3)" :key="todo.id"
                                             :class="[
                                                 'text-xs p-1 rounded truncate',
                                                 statusColors[todo.status]
                                             ]">
                                            {{ todo.title }}
                                        </div>
                                        
                                        <div v-if="getDayTodos(date).length > 3" 
                                             class="text-xs text-gray-500">
                                            +{{ getDayTodos(date).length - 3 }} lainnya
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
                        <CardTitle>
                            Tugas untuk {{ formatDate(selectedDate.toISOString().split('T')[0]) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="todo in todosForSelectedDate" :key="todo.id"
                                 class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <Checkbox 
                                        :checked="todo.status === 'completed'"
                                        @update:checked="(checked: boolean) => updateStatus(todo, checked)"
                                    />
                                    
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ todo.title }}
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Badge :class="priorityColors[todo.priority]">
                                                {{ todo.priority }}
                                            </Badge>
                                            <Badge :class="statusColors[todo.status]">
                                                {{ todo.status }}
                                            </Badge>
                                            <span v-if="todo.due_time" class="text-sm text-gray-500">
                                                {{ todo.due_time }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem @click="openEditModal(todo)">
                                            <Edit class="h-4 w-4 mr-2" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="deleteTodo(todo)" class="text-red-600">
                                            <Trash2 class="h-4 w-4 mr-2" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- List View -->
            <div v-if="currentView === 'list'" class="space-y-4">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>
                                Tugas untuk {{ formatDate(selectedDate.toISOString().split('T')[0]) }}
                            </CardTitle>
                            <Input 
                                type="date"
                                :value="selectedDate.toISOString().split('T')[0]"
                                @change="(e: Event) => selectDate(new Date((e.target as HTMLInputElement).value))"
                                class="w-auto"
                            />
                        </div>
                    </CardHeader>
                    
                    <CardContent>
                        <div v-if="todosForSelectedDate.length === 0" class="text-center py-8 text-gray-500">
                            Tidak ada tugas untuk tanggal ini
                        </div>
                        
                        <div v-else class="space-y-3">
                            <div v-for="todo in todosForSelectedDate" :key="todo.id"
                                 class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <Checkbox 
                                        :checked="todo.status === 'completed'"
                                        @update:checked="(checked: boolean) => updateStatus(todo, checked)"
                                    />
                                    
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ todo.title }}
                                        </h4>
                                        <p v-if="todo.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ todo.description }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <Badge :class="priorityColors[todo.priority]">
                                                <Flag class="h-3 w-3 mr-1" />
                                                {{ todo.priority }}
                                            </Badge>
                                            <Badge :class="statusColors[todo.status]">
                                                <component :is="getStatusIcon(todo.status)" class="h-3 w-3 mr-1" />
                                                {{ todo.status }}
                                            </Badge>
                                            <span v-if="todo.due_time" class="text-sm text-gray-500">
                                                <Clock class="h-3 w-3 inline mr-1" />
                                                {{ todo.due_time }}
                                            </span>
                                            <span v-if="todo.assigned_user" class="text-sm text-gray-500">
                                                <User class="h-3 w-3 inline mr-1" />
                                                {{ todo.assigned_user.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem @click="openEditModal(todo)">
                                            <Edit class="h-4 w-4 mr-2" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="deleteTodo(todo)" class="text-red-600">
                                            <Trash2 class="h-4 w-4 mr-2" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Create/Edit Todo Modal -->
            <Dialog :open="showCreateModal || showEditModal" @update:open="(open) => { showCreateModal = open; showEditModal = open; }">
                <DialogContent class="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editingTodo ? 'Edit Todo' : 'Tambah Todo Baru' }}
                        </DialogTitle>
                    </DialogHeader>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <Label for="title">Judul *</Label>
                                <Input 
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Masukkan judul todo"
                                    required
                                />
                                <span v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</span>
                            </div>
                            
                            <div class="md:col-span-2">
                                <Label for="description">Deskripsi</Label>
                                <Textarea 
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Masukkan deskripsi todo"
                                    :rows="3"
                                />
                                <span v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</span>
                            </div>
                            
                            <div>
                                <Label for="priority">Prioritas *</Label>
                                <select 
                                    id="priority"
                                    v-model="form.priority"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">Sedang Dikerjakan</option>
                                    <option value="completed">Selesai</option>
                                </select>
                                <span v-if="form.errors.status" class="text-sm text-red-600">{{ form.errors.status }}</span>
                            </div>
                            
                            <div>
                                <Label for="due_date">Tanggal Deadline *</Label>
                                <Input 
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                    required
                                />
                                <span v-if="form.errors.due_date" class="text-sm text-red-600">{{ form.errors.due_date }}</span>
                            </div>
                            
                            <div>
                                <Label for="due_time">Waktu Deadline</Label>
                                <Input 
                                    id="due_time"
                                    v-model="form.due_time"
                                    type="time"
                                />
                                <span v-if="form.errors.due_time" class="text-sm text-red-600">{{ form.errors.due_time }}</span>
                            </div>
                            
                            <div class="md:col-span-2">
                                <Label for="assigned_to">Assign ke User</Label>
                                <select 
                                    id="assigned_to"
                                    v-model="form.assigned_to"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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
                            <Button type="button" variant="outline" 
                                    @click="showCreateModal = false; showEditModal = false">
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
