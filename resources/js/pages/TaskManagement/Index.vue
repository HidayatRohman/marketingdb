<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    CalendarIcon,
    CheckCircle,
    Clock,
    Edit,
    MoveRight,
    Plus,
    Target,
    Trash2,
    User,
    UserCheck,
} from 'lucide-vue-next';
import { ref } from 'vue';
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
    currentUser: {
        id: number;
        name: string;
        role: string;
    };
    permissions: {
        canCrud: boolean;
        canOnlyView: boolean;
        canOnlyViewOwn: boolean;
        hasFullAccess: boolean;
        hasReadOnlyAccess: boolean;
        hasLimitedAccess: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Task Management', href: '/task-management' },
];
// Reactive data
const tasks = ref(props.tasks);
const summary = ref(props.summary);
const isDialogOpen = ref(false);
const editingTask = ref<Task | null>(null);
const isDragging = ref(false);

// Form data
const form = ref({
    title: '',
    description: '',
    priority: 'medium' as 'low' | 'medium' | 'high',
    start_date: '' as string,
    due_date: '' as string,
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
        start_date: '',
        due_date: '',
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
        start_date: task.start_date || '',
        due_date: task.due_date,
        due_time: task.due_time || '',
        assigned_to: task.assigned_to || null,
        tags: task.tags || [],
    };
    isDialogOpen.value = true;
};

// Submit form
const submitForm = () => {
    // Debug: log form values
    console.log('Form values before validation:', {
        title: form.value.title,
        priority: form.value.priority,
        due_date: form.value.due_date,
        start_date: form.value.start_date,
        description: form.value.description,
        due_time: form.value.due_time,
        assigned_to: form.value.assigned_to,
        tags: form.value.tags,
    });

    // Validasi form
    if (!form.value.title) {
        alert('Mohon isi Judul task');
        return;
    }

    if (!form.value.priority) {
        alert('Mohon pilih Prioritas task');
        return;
    }

    if (!form.value.due_date) {
        alert('Mohon pilih Tanggal Deadline');
        return;
    }

    const data = {
        title: form.value.title,
        description: form.value.description,
        priority: form.value.priority,
        start_date: form.value.start_date || null,
        due_date: form.value.due_date || null,
        due_time: form.value.due_time,
        assigned_to: form.value.assigned_to,
        tags: form.value.tags,
    };

    console.log('Submitting data:', data); // Debug log

    if (editingTask.value) {
        router.put(`/task-management/${editingTask.value.id}`, data, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
                router.reload({ only: ['tasks', 'summary'] });
            },
            onError: (errors) => {
                console.error('Update error:', errors);
                alert('Gagal update task: ' + JSON.stringify(errors));
            },
        });
    } else {
        router.post('/task-management', data, {
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
                router.reload({ only: ['tasks', 'summary'] });
            },
            onError: (errors) => {
                console.error('Create error:', errors);
                alert('Gagal membuat task: ' + JSON.stringify(errors));
            },
        });
    }
};

// Delete task
const deleteTask = (task: Task) => {
    if (confirm('Apakah Anda yakin ingin menghapus task ini?')) {
        router.delete(`/task-management/${task.id}`);
    }
};

// Move task to different status
const moveTask = async (task: Task, newStatus: string) => {
    if (task.status === newStatus) return;

    console.log('Moving task:', task.id, 'from', task.status, 'to', newStatus);

    try {
        // Try using Inertia router for better Laravel integration
        router.patch(
            `/task-management/${task.id}/status`,
            { status: newStatus },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    console.log('Status update success via Inertia');
                    // Update local state
                    const currentTasks = tasks.value[task.status as keyof typeof tasks.value];
                    const taskIndex = currentTasks.findIndex((t) => t.id === task.id);

                    if (taskIndex !== -1) {
                        // Remove from current status
                        currentTasks.splice(taskIndex, 1);

                        // Add to new status
                        const updatedTask = { ...task, status: newStatus as 'pending' | 'in_progress' | 'completed' };
                        tasks.value[newStatus as keyof typeof tasks.value].push(updatedTask);
                    }

                    // Refresh to get updated data
                    router.reload({ only: ['tasks', 'summary'] });
                },
                onError: (errors) => {
                    console.error('Status update error via Inertia:', errors);
                    alert('Gagal mengubah status task: ' + JSON.stringify(errors));
                },
            },
        );
    } catch (error) {
        console.error('Error updating task status:', error);
        const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
        alert('Gagal mengubah status task: ' + errorMessage);
    }
};

// Get priority color
const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'high':
            return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700';
        case 'medium':
            return 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700';
        case 'low':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700';
        default:
            return 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600';
    }
};

// Get priority icon
const getPriorityIcon = (priority: string) => {
    switch (priority) {
        case 'high':
            return AlertCircle;
        case 'medium':
            return Target;
        case 'low':
            return CheckCircle;
        default:
            return Target;
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

    console.log('Drag and drop:', { removedIndex, addedIndex, payload, status });

    // If the task was moved to a different status, update the server
    if (payload && payload.status !== status) {
        console.log('Status change detected:', payload.status, '->', status);

        // Update local state immediately for better UX
        if (removedIndex !== null) {
            tasks.value[payload.status as keyof typeof tasks.value].splice(removedIndex, 1);
        }

        if (addedIndex !== null) {
            tasks.value[status as keyof typeof tasks.value].splice(addedIndex, 0, { ...payload, status });
        }

        // Use Inertia router for server update
        router.patch(
            `/task-management/${payload.id}/status`,
            { status },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Drag drop status update success');
                    // Refresh to ensure data consistency
                    router.reload({ only: ['tasks', 'summary'] });
                },
                onError: (errors) => {
                    console.error('Drag drop status update error:', errors);
                    alert('Gagal mengubah status task: ' + JSON.stringify(errors));
                    // Reload page to revert changes
                    router.reload();
                },
            },
        );
    } else {
        // Just reordering within the same status
        if (removedIndex !== null) {
            tasks.value[status as keyof typeof tasks.value].splice(removedIndex, 1);
        }

        if (addedIndex !== null) {
            tasks.value[status as keyof typeof tasks.value].splice(addedIndex, 0, payload);
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

// Check if user can edit/delete task
const canEditTask = (task: Task) => {
    return (
        props.permissions.canCrud ||
        props.permissions.hasFullAccess ||
        task.user_id === props.currentUser.id ||
        task.assigned_to === props.currentUser.id
    );
};

// Check if user can delete task
const canDeleteTask = (task: Task) => {
    return props.permissions.canCrud || props.permissions.hasFullAccess || task.user_id === props.currentUser.id;
};
</script>

<template>
    <Head title="Task Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 p-4 sm:p-6 my-2 sm:my-3 shadow-sm mb-8 flex flex-col gap-3 md:flex-row md:items-center md:justify-between text-white">
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            Task Management
                        </h1>
                        <p class="mt-2 text-lg text-white/90">Kelola dan pantau progress task Anda dengan mudah</p>
                    </div>
                    <Button
                        v-if="props.permissions.canCrud || props.permissions.hasFullAccess"
                        @click="openCreateDialog"
                        class="w-full md:w-auto flex transform items-center gap-2 bg-white text-blue-600 shadow-lg transition-all duration-200 hover:scale-105 hover:bg-white/90"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Task
                    </Button>
                </div>

                <!-- Summary Cards -->
                <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-7">
                    <Card
                        class="border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 transition-all duration-200 hover:shadow-lg dark:border-blue-800 dark:from-blue-900/20 dark:to-blue-800/20"
                    >
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total</p>
                                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ summary.total }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-200 shadow-md dark:bg-blue-700">
                                    <Target class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-orange-200 bg-gradient-to-br from-orange-50 to-orange-100 transition-all duration-200 hover:shadow-lg dark:border-orange-800 dark:from-orange-900/20 dark:to-orange-800/20"
                    >
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Rencana</p>
                                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ summary.pending }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-200 shadow-md dark:bg-orange-700">
                                    <Clock class="h-5 w-5 text-orange-600 dark:text-orange-300" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 transition-all duration-200 hover:shadow-lg dark:border-blue-800 dark:from-blue-900/20 dark:to-blue-800/20"
                    >
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Dikerjakan</p>
                                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ summary.in_progress }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-200 shadow-md dark:bg-blue-700">
                                    <Target class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 transition-all duration-200 hover:shadow-lg dark:border-emerald-800 dark:from-emerald-900/20 dark:to-emerald-800/20"
                    >
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Selesai</p>
                                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ summary.completed }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-200 shadow-md dark:bg-emerald-700">
                                    <CheckCircle class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-red-200 bg-gradient-to-br from-red-50 to-red-100 transition-all duration-200 hover:shadow-lg dark:border-red-800 dark:from-red-900/20 dark:to-red-800/20"
                    >
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Terlambat</p>
                                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ summary.overdue }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-200 shadow-md dark:bg-red-700">
                                    <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-300" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 transition-all duration-200 hover:shadow-lg dark:border-purple-800 dark:from-purple-900/20 dark:to-purple-800/20"
                    >
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Hari Ini</p>
                                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ summary.today }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-200 shadow-md dark:bg-purple-700">
                                    <CalendarIcon class="h-5 w-5 text-purple-600 dark:text-purple-300" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-indigo-200 bg-gradient-to-br from-indigo-50 to-indigo-100 transition-all duration-200 hover:shadow-lg dark:border-indigo-800 dark:from-indigo-900/20 dark:to-indigo-800/20"
                    >
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Minggu Ini</p>
                                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ summary.this_week }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-200 shadow-md dark:bg-indigo-700">
                                    <CalendarIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-300" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Kanban Board -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Rencana Column -->
                    <div class="space-y-4">
                        <Card class="overflow-hidden border-orange-500 shadow-lg dark:border-orange-600">
                            <CardHeader class="m-0 bg-orange-500 p-0 dark:bg-orange-600">
                                <CardTitle class="m-0 flex items-center gap-2 px-6 py-4 font-bold text-white">
                                    <Clock class="h-5 w-5" />
                                    Rencana ({{ tasks.pending.length }})
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="bg-slate-50 p-4 dark:bg-slate-900">
                                <Container
                                    group-name="tasks"
                                    @drop="(dropResult: any) => onDrop(dropResult, 'pending')"
                                    :get-child-payload="(index: any) => getChildPayload(index, 'pending')"
                                    class="min-h-[200px] space-y-3"
                                >
                                    <Draggable v-for="task in tasks.pending" :key="task.id">
                                        <Card
                                            class="mb-3 transform cursor-move border-l-4 border-l-orange-400 bg-white transition-all duration-200 hover:scale-[1.02] hover:shadow-xl dark:border-l-orange-500 dark:bg-slate-800"
                                        >
                                            <CardContent class="p-4">
                                                <div class="mb-3 flex items-start justify-between">
                                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ task.title }}</h4>
                                                    <div class="flex gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="moveTask(task, 'in_progress')"
                                                            class="h-7 w-7 p-0 hover:bg-blue-100 dark:hover:bg-blue-900/30"
                                                            title="Pindah ke Dikerjakan"
                                                        >
                                                            <ArrowRight class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="moveTask(task, 'completed')"
                                                            class="h-7 w-7 p-0 hover:bg-emerald-100 dark:hover:bg-emerald-900/30"
                                                            title="Tandai Selesai"
                                                        >
                                                            <CheckCircle class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                                        </Button>
                                                        <Button
                                                            v-if="canEditTask(task)"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="openEditDialog(task)"
                                                            class="h-7 w-7 p-0 hover:bg-blue-100 dark:hover:bg-blue-900/30"
                                                        >
                                                            <Edit class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                                        </Button>
                                                        <Button
                                                            v-if="canDeleteTask(task)"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="deleteTask(task)"
                                                            class="h-7 w-7 p-0 hover:bg-red-100 dark:hover:bg-red-900/30"
                                                        >
                                                            <Trash2 class="h-3 w-3 text-red-600 dark:text-red-400" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                <p v-if="task.description" class="mb-3 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                                    {{ task.description.substring(0, 100) }}{{ task.description.length > 100 ? '...' : '' }}
                                                </p>

                                                <div class="mb-3 flex flex-wrap gap-2">
                                                    <Badge :class="getPriorityColor(task.priority)" variant="outline" class="text-xs font-medium">
                                                        <component :is="getPriorityIcon(task.priority)" class="mr-1 h-3 w-3" />
                                                        {{ task.priority }}
                                                    </Badge>
                                                    <Badge
                                                        v-if="isOverdue(task)"
                                                        variant="destructive"
                                                        class="bg-red-500 text-xs text-white dark:bg-red-600"
                                                    >
                                                        Terlambat
                                                    </Badge>
                                                </div>

                                                <div class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                                                    <div class="flex items-center gap-2">
                                                        <CalendarIcon class="h-3 w-3" />
                                                        <span class="font-medium">{{ formatDate(task.due_date) }}</span>
                                                        <span v-if="task.due_time" class="rounded bg-slate-100 px-2 py-1 text-xs dark:bg-slate-700">{{
                                                            formatTime(task.due_time)
                                                        }}</span>
                                                    </div>
                                                    <div v-if="task.assigned_user" class="flex items-center gap-2">
                                                        <UserCheck class="h-3 w-3" />
                                                        <span
                                                            >Assigned:
                                                            <span class="font-medium text-blue-600 dark:text-blue-400">{{
                                                                task.assigned_user.name
                                                            }}</span></span
                                                        >
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <User class="h-3 w-3" />
                                                        <span
                                                            >Created by: <span class="font-medium">{{ task.user.name }}</span></span
                                                        >
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
                        <Card class="overflow-hidden border-blue-500 shadow-lg dark:border-blue-600">
                            <CardHeader class="m-0 bg-blue-500 p-0 dark:bg-blue-600">
                                <CardTitle class="m-0 flex items-center gap-2 px-6 py-4 font-bold text-white">
                                    <Target class="h-5 w-5" />
                                    Dikerjakan ({{ tasks.in_progress.length }})
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="bg-slate-50 p-4 dark:bg-slate-900">
                                <Container
                                    group-name="tasks"
                                    @drop="(dropResult: any) => onDrop(dropResult, 'in_progress')"
                                    :get-child-payload="(index: any) => getChildPayload(index, 'in_progress')"
                                    class="min-h-[200px] space-y-3"
                                >
                                    <Draggable v-for="task in tasks.in_progress" :key="task.id">
                                        <Card
                                            class="mb-3 transform cursor-move border-l-4 border-l-blue-400 bg-white transition-all duration-200 hover:scale-[1.02] hover:shadow-xl dark:border-l-blue-500 dark:bg-slate-800"
                                        >
                                            <CardContent class="p-4">
                                                <div class="mb-3 flex items-start justify-between">
                                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ task.title }}</h4>
                                                    <div class="flex gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="moveTask(task, 'pending')"
                                                            class="h-7 w-7 p-0 hover:bg-orange-100 dark:hover:bg-orange-900/30"
                                                            title="Kembali ke Rencana"
                                                        >
                                                            <ArrowLeft class="h-3 w-3 text-orange-600 dark:text-orange-400" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="moveTask(task, 'completed')"
                                                            class="h-7 w-7 p-0 hover:bg-emerald-100 dark:hover:bg-emerald-900/30"
                                                            title="Tandai Selesai"
                                                        >
                                                            <CheckCircle class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                                        </Button>
                                                        <Button
                                                            v-if="canEditTask(task)"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="openEditDialog(task)"
                                                            class="h-7 w-7 p-0 hover:bg-blue-100 dark:hover:bg-blue-900/30"
                                                        >
                                                            <Edit class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                                        </Button>
                                                        <Button
                                                            v-if="canDeleteTask(task)"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="deleteTask(task)"
                                                            class="h-7 w-7 p-0 hover:bg-red-100 dark:hover:bg-red-900/30"
                                                        >
                                                            <Trash2 class="h-3 w-3 text-red-600 dark:text-red-400" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                <p v-if="task.description" class="mb-3 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                                    {{ task.description.substring(0, 100) }}{{ task.description.length > 100 ? '...' : '' }}
                                                </p>

                                                <div class="mb-3 flex flex-wrap gap-2">
                                                    <Badge :class="getPriorityColor(task.priority)" variant="outline" class="text-xs font-medium">
                                                        <component :is="getPriorityIcon(task.priority)" class="mr-1 h-3 w-3" />
                                                        {{ task.priority }}
                                                    </Badge>
                                                    <Badge
                                                        v-if="isOverdue(task)"
                                                        variant="destructive"
                                                        class="bg-red-500 text-xs text-white dark:bg-red-600"
                                                    >
                                                        Terlambat
                                                    </Badge>
                                                    <Badge class="bg-blue-100 text-xs text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                        In Progress
                                                    </Badge>
                                                </div>

                                                <div class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                                                    <div class="flex items-center gap-2">
                                                        <CalendarIcon class="h-3 w-3" />
                                                        <span class="font-medium">{{ formatDate(task.due_date) }}</span>
                                                        <span v-if="task.due_time" class="rounded bg-slate-100 px-2 py-1 text-xs dark:bg-slate-700">{{
                                                            formatTime(task.due_time)
                                                        }}</span>
                                                    </div>
                                                    <div v-if="task.assigned_user" class="flex items-center gap-2">
                                                        <UserCheck class="h-3 w-3" />
                                                        <span
                                                            >Assigned:
                                                            <span class="font-medium text-blue-600 dark:text-blue-400">{{
                                                                task.assigned_user.name
                                                            }}</span></span
                                                        >
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <User class="h-3 w-3" />
                                                        <span
                                                            >Created by: <span class="font-medium">{{ task.user.name }}</span></span
                                                        >
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
                        <Card class="overflow-hidden border-emerald-500 shadow-lg dark:border-emerald-600">
                            <CardHeader class="m-0 bg-emerald-500 p-0 dark:bg-emerald-600">
                                <CardTitle class="m-0 flex items-center gap-2 px-6 py-4 font-bold text-white">
                                    <CheckCircle class="h-5 w-5" />
                                    Selesai ({{ tasks.completed.length }})
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="bg-slate-50 p-4 dark:bg-slate-900">
                                <Container
                                    group-name="tasks"
                                    @drop="(dropResult: any) => onDrop(dropResult, 'completed')"
                                    :get-child-payload="(index: any) => getChildPayload(index, 'completed')"
                                    class="min-h-[200px] space-y-3"
                                >
                                    <Draggable v-for="task in tasks.completed" :key="task.id">
                                        <Card
                                            class="mb-3 transform cursor-move border-l-4 border-l-emerald-400 bg-white opacity-90 transition-all duration-200 hover:scale-[1.02] hover:shadow-xl dark:border-l-emerald-500 dark:bg-slate-800"
                                        >
                                            <CardContent class="p-4">
                                                <div class="mb-3 flex items-start justify-between">
                                                    <h4 class="text-sm font-semibold text-slate-700 line-through dark:text-slate-300">
                                                        {{ task.title }}
                                                    </h4>
                                                    <div class="flex gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="moveTask(task, 'pending')"
                                                            class="h-7 w-7 p-0 hover:bg-orange-100 dark:hover:bg-orange-900/30"
                                                            title="Kembali ke Rencana"
                                                        >
                                                            <ArrowLeft class="h-3 w-3 text-orange-600 dark:text-orange-400" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="moveTask(task, 'in_progress')"
                                                            class="h-7 w-7 p-0 hover:bg-blue-100 dark:hover:bg-blue-900/30"
                                                            title="Kembali ke Dikerjakan"
                                                        >
                                                            <MoveRight class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                                        </Button>
                                                        <Button
                                                            v-if="canEditTask(task)"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="openEditDialog(task)"
                                                            class="h-7 w-7 p-0 hover:bg-blue-100 dark:hover:bg-blue-900/30"
                                                        >
                                                            <Edit class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                                        </Button>
                                                        <Button
                                                            v-if="canDeleteTask(task)"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="deleteTask(task)"
                                                            class="h-7 w-7 p-0 hover:bg-red-100 dark:hover:bg-red-900/30"
                                                        >
                                                            <Trash2 class="h-3 w-3 text-red-600 dark:text-red-400" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                <p
                                                    v-if="task.description"
                                                    class="mb-3 text-xs leading-relaxed text-slate-500 line-through dark:text-slate-400"
                                                >
                                                    {{ task.description.substring(0, 100) }}{{ task.description.length > 100 ? '...' : '' }}
                                                </p>

                                                <div class="mb-3 flex flex-wrap gap-2">
                                                    <Badge
                                                        :class="getPriorityColor(task.priority)"
                                                        variant="outline"
                                                        class="text-xs font-medium opacity-75"
                                                    >
                                                        <component :is="getPriorityIcon(task.priority)" class="mr-1 h-3 w-3" />
                                                        {{ task.priority }}
                                                    </Badge>
                                                    <Badge
                                                        class="bg-emerald-100 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300"
                                                    >
                                                        <CheckCircle class="mr-1 h-3 w-3" />
                                                        Completed
                                                    </Badge>
                                                </div>

                                                <div class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                                                    <div class="flex items-center gap-2">
                                                        <CalendarIcon class="h-3 w-3" />
                                                        <span class="font-medium">{{ formatDate(task.due_date) }}</span>
                                                        <span v-if="task.due_time" class="rounded bg-slate-100 px-2 py-1 text-xs dark:bg-slate-700">{{
                                                            formatTime(task.due_time)
                                                        }}</span>
                                                    </div>
                                                    <div v-if="task.assigned_user" class="flex items-center gap-2">
                                                        <UserCheck class="h-3 w-3" />
                                                        <span
                                                            >Assigned:
                                                            <span class="font-medium text-emerald-600 dark:text-emerald-400">{{
                                                                task.assigned_user.name
                                                            }}</span></span
                                                        >
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <User class="h-3 w-3" />
                                                        <span
                                                            >Created by: <span class="font-medium">{{ task.user.name }}</span></span
                                                        >
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
                    <DialogContent
                        class="max-h-[90vh] max-w-2xl overflow-y-auto border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900"
                    >
                        <DialogHeader class="border-b border-slate-200 pb-4 dark:border-slate-700">
                            <DialogTitle class="text-xl font-bold text-slate-900 dark:text-slate-100">
                                {{ editingTask ? 'Edit Task' : 'Tambah Task Baru' }}
                            </DialogTitle>
                        </DialogHeader>

                        <form @submit.prevent="submitForm" class="space-y-6 pt-4">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <Label for="title" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Judul Task *</Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        placeholder="Masukkan judul task"
                                        required
                                        class="mt-2 border-slate-300 bg-white text-slate-900 focus:border-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <Label for="description" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Deskripsi</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Masukkan deskripsi task"
                                        :rows="3"
                                        class="mt-2 border-slate-300 bg-white text-slate-900 focus:border-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400"
                                    />
                                </div>

                                <div>
                                    <Label for="priority" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Prioritas *</Label>
                                    <select
                                        v-model="form.priority"
                                        required
                                        class="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    >
                                        <option value="">Pilih prioritas</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>

                                <div>
                                    <Label for="assigned_to" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Assign ke User</Label>
                                    <select
                                        v-model="form.assigned_to"
                                        class="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    >
                                        <option :value="null">Tidak di-assign</option>
                                        <option v-for="user in users" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label for="start_date" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tanggal Mulai</Label>
                                    <div class="relative">
                                        <Input
                                            id="start_date"
                                            type="date"
                                            v-model="form.start_date"
                                            class="mt-2 w-full border-slate-300 bg-white pr-10 text-slate-900 focus:border-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400"
                                        />
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <CalendarIcon class="h-5 w-5 text-slate-400" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <Label for="due_date" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tanggal Deadline *</Label>
                                    <div class="relative">
                                        <Input
                                            id="due_date"
                                            type="date"
                                            v-model="form.due_date"
                                            required
                                            class="mt-2 w-full border-slate-300 bg-white pr-10 text-slate-900 focus:border-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400"
                                        />
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <CalendarIcon class="h-5 w-5 text-slate-400" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <Label for="due_time" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Waktu Deadline</Label>
                                    <Input
                                        id="due_time"
                                        type="time"
                                        v-model="form.due_time"
                                        class="mt-2 border-slate-300 bg-white text-slate-900 focus:border-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400"
                                    />
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-700">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="isDialogOpen = false"
                                    class="border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    class="bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg hover:from-blue-700 hover:to-purple-700"
                                >
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
    transition: transform 0.2s ease-out;
    transform: rotateZ(3deg) scale(1.05);
    opacity: 0.8;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.smooth-dnd-ghost * {
    pointer-events: none;
}

.smooth-dnd-drop-preview {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
    border: 2px dashed rgba(59, 130, 246, 0.3);
    margin: 8px 0;
    border-radius: 8px;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.smooth-dnd-drop-preview::before {
    content: 'Drop here';
    color: rgba(59, 130, 246, 0.6);
    font-weight: 500;
    font-size: 14px;
}

/* Custom scrollbar for dark mode */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background-color: rgb(241 245 249);
}

.dark ::-webkit-scrollbar-track {
    background-color: rgb(30 41 59);
}

::-webkit-scrollbar-thumb {
    background-color: rgb(148 163 184);
    border-radius: 9999px;
}

.dark ::-webkit-scrollbar-thumb {
    background-color: rgb(71 85 105);
}

::-webkit-scrollbar-thumb:hover {
    background-color: rgb(100 116 139);
}

/* Enhance card hover effects */
.card-hover-effect {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover-effect:hover {
    transform: translateY(-2px);
    box-shadow:
        0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Button hover animations */
.btn-gradient {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    transition: all 0.2s ease;
}

.btn-gradient:hover {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
}

/* Loading animation for priority badges */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.priority-badge {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Dark mode specific enhancements */
@media (prefers-color-scheme: dark) {
    .glass-effect {
        backdrop-filter: blur(10px);
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(71, 85, 105, 0.3);
    }
}
</style>
