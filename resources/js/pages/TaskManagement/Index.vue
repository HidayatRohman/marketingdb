<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar } from '@/components/ui/calendar/index';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover/index';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { CalendarIcon, Plus, Edit, Trash2, User, Clock, AlertCircle, CheckCircle, Target, UserCheck } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
// @ts-ignore
import { Container, Draggable } from 'vue3-smooth-dnd';

interface Task {
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
    user: { id: number; name: string; email: string };
    assigned_user?: { id: number; name: string; email: string };
    created_at: string;
    updated_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Summary {
    total: number;
    pending: number;
    in_progress: number;
    completed: number;
    overdue: number;
    today: number;
    this_week: number;
}

interface Props {
    tasks: {
        pending: Task[];
        in_progress: Task[];
        completed: Task[];
    };
    summary: Summary;
    users: User[];
}

const props = defineProps<Props>();

// Reactive data
const tasks = ref(props.tasks);
const isDialogOpen = ref(false);
const editingTask = ref<Task | null>(null);
const isDragging = ref(false);

// Form data
const form = ref({
    title: '',
    description: '',
    priority: 'medium' as 'low' | 'medium' | 'high',
    start_date: undefined as Date | undefined,
    due_date: undefined as Date | undefined,
    due_time: '',
    assigned_to: null as number | null,
    tags: [] as string[],
});

// Reset form
const resetForm = () => {
    form.value = {
        title: '',
        description: '',
        priority: 'medium',
        start_date: undefined,
        due_date: undefined,
        due_time: '',
        assigned_to: null,
        tags: [],
    };
    editingTask.value = null;
};

// Open create dialog
const openCreateDialog = () => {
    resetForm();
    isDialogOpen.value = true;
};

// Open edit dialog
const openEditDialog = (task: Task) => {
    editingTask.value = task;
    form.value = {
        title: task.title,
        description: task.description || '',
        priority: task.priority,
        start_date: task.start_date ? new Date(task.start_date) : undefined,
        due_date: new Date(task.due_date),
        due_time: task.due_time || '',
        assigned_to: task.assigned_to || null,
        tags: task.tags || [],
    };
    isDialogOpen.value = true;
};

// Submit form
const submitForm = () => {
    const data = {
        title: form.value.title,
        description: form.value.description,
        priority: form.value.priority,
        start_date: form.value.start_date ? format(form.value.start_date, 'yyyy-MM-dd') : null,
        due_date: form.value.due_date ? format(form.value.due_date, 'yyyy-MM-dd') : null,
        due_time: form.value.due_time,
        assigned_to: form.value.assigned_to,
        tags: form.value.tags,
    };

    if (editingTask.value) {
        router.put(`/task-management/${editingTask.value.id}`, data, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
            }
        });
    } else {
        router.post('/task-management', data, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
            }
        });
    }
};

// Delete task
const deleteTask = (task: Task) => {
    if (confirm('Apakah Anda yakin ingin menghapus task ini?')) {
        router.delete(`/task-management/${task.id}`);
    }
};

// Get priority color
const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'high': return 'bg-red-100 text-red-800 border-red-200';
        case 'medium': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'low': return 'bg-green-100 text-green-800 border-green-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

// Get priority icon
const getPriorityIcon = (priority: string) => {
    switch (priority) {
        case 'high': return AlertCircle;
        case 'medium': return Target;
        case 'low': return CheckCircle;
        default: return Target;
    }
};

// Check if task is overdue
const isOverdue = (task: Task) => {
    if (task.status === 'completed') return false;
    return new Date(task.due_date) < new Date();
};

// Handle drag and drop
const onDrop = async (dropResult: any, status: string) => {
    const { removedIndex, addedIndex, payload } = dropResult;
    
    if (removedIndex === null && addedIndex === null) return;
    
    // Update local state immediately for better UX
    if (removedIndex !== null) {
        tasks.value[status as keyof typeof tasks.value].splice(removedIndex, 1);
    }
    
    if (addedIndex !== null) {
        tasks.value[status as keyof typeof tasks.value].splice(addedIndex, 0, { ...payload, status });
    }

    // Update on server
    if (payload && payload.status !== status) {
        try {
            await fetch(`/task-management/${payload.id}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ status }),
            });
        } catch (error) {
            console.error('Error updating task status:', error);
            // Optionally revert local changes on error
        }
    }
};

const getChildPayload = (index: number, status: string) => {
    return tasks.value[status as keyof typeof tasks.value][index];
};

// Format date for display
const formatDate = (date: string) => {
    return format(new Date(date), 'dd/MM/yyyy');
};

// Format time for display
const formatTime = (time: string) => {
    return time ? format(new Date(`2000-01-01 ${time}`), 'HH:mm') : '';
};
</script>

<template>
    <Head title="Task Management" />

    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Task Management</h1>
                        <p class="text-gray-600 mt-1">Kelola dan pantau progress task Anda</p>
                    </div>
                    <Button @click="openCreateDialog" class="flex items-center gap-2">
                        <Plus class="h-4 w-4" />
                        Tambah Task
                    </Button>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ summary.total }}</p>
                                </div>
                                <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <Target class="h-4 w-4 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Rencana</p>
                                    <p class="text-2xl font-bold text-orange-600">{{ summary.pending }}</p>
                                </div>
                                <div class="h-8 w-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <Clock class="h-4 w-4 text-orange-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Dikerjakan</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ summary.in_progress }}</p>
                                </div>
                                <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <Target class="h-4 w-4 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Selesai</p>
                                    <p class="text-2xl font-bold text-green-600">{{ summary.completed }}</p>
                                </div>
                                <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <CheckCircle class="h-4 w-4 text-green-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Terlambat</p>
                                    <p class="text-2xl font-bold text-red-600">{{ summary.overdue }}</p>
                                </div>
                                <div class="h-8 w-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <AlertCircle class="h-4 w-4 text-red-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Hari Ini</p>
                                    <p class="text-2xl font-bold text-purple-600">{{ summary.today }}</p>
                                </div>
                                <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <CalendarIcon class="h-4 w-4 text-purple-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Minggu Ini</p>
                                    <p class="text-2xl font-bold text-indigo-600">{{ summary.this_week }}</p>
                                </div>
                                <div class="h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <CalendarIcon class="h-4 w-4 text-indigo-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Kanban Board -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Rencana Column -->
                    <div class="space-y-4">
                        <Card class="border-orange-200">
                            <CardHeader class="bg-orange-50 border-b border-orange-200">
                                <CardTitle class="flex items-center gap-2 text-orange-800">
                                    <Clock class="h-5 w-5" />
                                    Rencana ({{ tasks.pending.length }})
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-4">
                                <Container
                                    group-name="tasks"
                                    @drop="(dropResult: any) => onDrop(dropResult, 'pending')"
                                    :get-child-payload="(index: any) => getChildPayload(index, 'pending')"
                                    class="min-h-[200px]"
                                >
                                    <Draggable v-for="task in tasks.pending" :key="task.id">
                                        <Card class="mb-3 cursor-move hover:shadow-md transition-shadow">
                                            <CardContent class="p-4">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="font-semibold text-sm">{{ task.title }}</h4>
                                                    <div class="flex gap-1">
                                                        <Button variant="ghost" size="sm" @click="openEditDialog(task)">
                                                            <Edit class="h-3 w-3" />
                                                        </Button>
                                                        <Button variant="ghost" size="sm" @click="deleteTask(task)">
                                                            <Trash2 class="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </div>
                                                
                                                <p v-if="task.description" class="text-xs text-gray-600 mb-2">
                                                    {{ task.description.substring(0, 100) }}{{ task.description.length > 100 ? '...' : '' }}
                                                </p>
                                                
                                                <div class="flex flex-wrap gap-1 mb-2">
                                                    <Badge :class="getPriorityColor(task.priority)" variant="outline" class="text-xs">
                                                        <component :is="getPriorityIcon(task.priority)" class="h-3 w-3 mr-1" />
                                                        {{ task.priority }}
                                                    </Badge>
                                                    <Badge v-if="isOverdue(task)" variant="destructive" class="text-xs">
                                                        Terlambat
                                                    </Badge>
                                                </div>
                                                
                                                <div class="space-y-1 text-xs text-gray-500">
                                                    <div class="flex items-center gap-1">
                                                        <CalendarIcon class="h-3 w-3" />
                                                        {{ formatDate(task.due_date) }}
                                                        <span v-if="task.due_time">{{ formatTime(task.due_time) }}</span>
                                                    </div>
                                                    <div v-if="task.assigned_user" class="flex items-center gap-1">
                                                        <UserCheck class="h-3 w-3" />
                                                        Assigned: {{ task.assigned_user.name }}
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        <User class="h-3 w-3" />
                                                        Created by: {{ task.user.name }}
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Draggable>
                                </Container>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Dikerjakan Column -->
                    <div class="space-y-4">
                        <Card class="border-blue-200">
                            <CardHeader class="bg-blue-50 border-b border-blue-200">
                                <CardTitle class="flex items-center gap-2 text-blue-800">
                                    <Target class="h-5 w-5" />
                                    Dikerjakan ({{ tasks.in_progress.length }})
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-4">
                                <Container
                                    group-name="tasks"
                                    @drop="(dropResult: any) => onDrop(dropResult, 'in_progress')"
                                    :get-child-payload="(index: any) => getChildPayload(index, 'in_progress')"
                                    class="min-h-[200px]"
                                >
                                    <Draggable v-for="task in tasks.in_progress" :key="task.id">
                                        <Card class="mb-3 cursor-move hover:shadow-md transition-shadow">
                                            <CardContent class="p-4">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="font-semibold text-sm">{{ task.title }}</h4>
                                                    <div class="flex gap-1">
                                                        <Button variant="ghost" size="sm" @click="openEditDialog(task)">
                                                            <Edit class="h-3 w-3" />
                                                        </Button>
                                                        <Button variant="ghost" size="sm" @click="deleteTask(task)">
                                                            <Trash2 class="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </div>
                                                
                                                <p v-if="task.description" class="text-xs text-gray-600 mb-2">
                                                    {{ task.description.substring(0, 100) }}{{ task.description.length > 100 ? '...' : '' }}
                                                </p>
                                                
                                                <div class="flex flex-wrap gap-1 mb-2">
                                                    <Badge :class="getPriorityColor(task.priority)" variant="outline" class="text-xs">
                                                        <component :is="getPriorityIcon(task.priority)" class="h-3 w-3 mr-1" />
                                                        {{ task.priority }}
                                                    </Badge>
                                                    <Badge v-if="isOverdue(task)" variant="destructive" class="text-xs">
                                                        Terlambat
                                                    </Badge>
                                                </div>
                                                
                                                <div class="space-y-1 text-xs text-gray-500">
                                                    <div class="flex items-center gap-1">
                                                        <CalendarIcon class="h-3 w-3" />
                                                        {{ formatDate(task.due_date) }}
                                                        <span v-if="task.due_time">{{ formatTime(task.due_time) }}</span>
                                                    </div>
                                                    <div v-if="task.assigned_user" class="flex items-center gap-1">
                                                        <UserCheck class="h-3 w-3" />
                                                        Assigned: {{ task.assigned_user.name }}
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        <User class="h-3 w-3" />
                                                        Created by: {{ task.user.name }}
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Draggable>
                                </Container>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Selesai Column -->
                    <div class="space-y-4">
                        <Card class="border-green-200">
                            <CardHeader class="bg-green-50 border-b border-green-200">
                                <CardTitle class="flex items-center gap-2 text-green-800">
                                    <CheckCircle class="h-5 w-5" />
                                    Selesai ({{ tasks.completed.length }})
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-4">
                                <Container
                                    group-name="tasks"
                                    @drop="(dropResult: any) => onDrop(dropResult, 'completed')"
                                    :get-child-payload="(index: any) => getChildPayload(index, 'completed')"
                                    class="min-h-[200px]"
                                >
                                    <Draggable v-for="task in tasks.completed" :key="task.id">
                                        <Card class="mb-3 cursor-move hover:shadow-md transition-shadow opacity-75">
                                            <CardContent class="p-4">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="font-semibold text-sm line-through">{{ task.title }}</h4>
                                                    <div class="flex gap-1">
                                                        <Button variant="ghost" size="sm" @click="openEditDialog(task)">
                                                            <Edit class="h-3 w-3" />
                                                        </Button>
                                                        <Button variant="ghost" size="sm" @click="deleteTask(task)">
                                                            <Trash2 class="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </div>
                                                
                                                <p v-if="task.description" class="text-xs text-gray-600 mb-2 line-through">
                                                    {{ task.description.substring(0, 100) }}{{ task.description.length > 100 ? '...' : '' }}
                                                </p>
                                                
                                                <div class="flex flex-wrap gap-1 mb-2">
                                                    <Badge :class="getPriorityColor(task.priority)" variant="outline" class="text-xs">
                                                        <component :is="getPriorityIcon(task.priority)" class="h-3 w-3 mr-1" />
                                                        {{ task.priority }}
                                                    </Badge>
                                                    <Badge variant="secondary" class="text-xs bg-green-100 text-green-800">
                                                        Completed
                                                    </Badge>
                                                </div>
                                                
                                                <div class="space-y-1 text-xs text-gray-500">
                                                    <div class="flex items-center gap-1">
                                                        <CalendarIcon class="h-3 w-3" />
                                                        {{ formatDate(task.due_date) }}
                                                        <span v-if="task.due_time">{{ formatTime(task.due_time) }}</span>
                                                    </div>
                                                    <div v-if="task.assigned_user" class="flex items-center gap-1">
                                                        <UserCheck class="h-3 w-3" />
                                                        Assigned: {{ task.assigned_user.name }}
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        <User class="h-3 w-3" />
                                                        Created by: {{ task.user.name }}
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Draggable>
                                </Container>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Create/Edit Task Dialog -->
                <Dialog v-model:open="isDialogOpen">
                    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>
                                {{ editingTask ? 'Edit Task' : 'Tambah Task Baru' }}
                            </DialogTitle>
                        </DialogHeader>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <Label for="title">Judul Task *</Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        placeholder="Masukkan judul task"
                                        required
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <Label for="description">Deskripsi</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Masukkan deskripsi task"
                                        :rows="3"
                                    />
                                </div>

                                <div>
                                    <Label for="priority">Prioritas *</Label>
                                    <Select v-model="form.priority" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih prioritas" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="low">Low</SelectItem>
                                            <SelectItem value="medium">Medium</SelectItem>
                                            <SelectItem value="high">High</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label for="assigned_to">Assign ke User</Label>
                                    <Select v-model="form.assigned_to">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih user" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null">Tidak di-assign</SelectItem>
                                            <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                                                {{ user.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label for="start_date">Tanggal Mulai</Label>
                                    <Popover>
                                        <PopoverTrigger as-child>
                                            <Button
                                                variant="outline"
                                                :class="cn('w-full justify-start text-left font-normal', !form.start_date && 'text-muted-foreground')"
                                            >
                                                <CalendarIcon class="mr-2 h-4 w-4" />
                                                {{ form.start_date ? format(form.start_date, 'dd/MM/yyyy') : 'Pilih tanggal' }}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-auto p-0">
                                            <Calendar v-model="form.start_date" />
                                        </PopoverContent>
                                    </Popover>
                                </div>

                                <div>
                                    <Label for="due_date">Tanggal Deadline *</Label>
                                    <Popover>
                                        <PopoverTrigger as-child>
                                            <Button
                                                variant="outline"
                                                :class="cn('w-full justify-start text-left font-normal', !form.due_date && 'text-muted-foreground')"
                                            >
                                                <CalendarIcon class="mr-2 h-4 w-4" />
                                                {{ form.due_date ? format(form.due_date, 'dd/MM/yyyy') : 'Pilih tanggal' }}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-auto p-0">
                                            <Calendar v-model="form.due_date" />
                                        </PopoverContent>
                                    </Popover>
                                </div>

                                <div>
                                    <Label for="due_time">Waktu Deadline</Label>
                                    <Input
                                        id="due_time"
                                        type="time"
                                        v-model="form.due_time"
                                    />
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4">
                                <Button type="button" variant="outline" @click="isDialogOpen = false">
                                    Batal
                                </Button>
                                <Button type="submit">
                                    {{ editingTask ? 'Update' : 'Simpan' }}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.smooth-dnd-container.vertical > .smooth-dnd-draggable-wrapper {
    overflow: visible;
}

.smooth-dnd-ghost {
    transition: transform 0.18s ease-out;
    transform: rotateZ(5deg);
}

.smooth-dnd-ghost * {
    pointer-events: none;
}

.smooth-dnd-drop-preview {
    background-color: rgba(150, 150, 200, 0.1);
    border: 1px dashed #abc;
    margin: 5px;
}
</style>
