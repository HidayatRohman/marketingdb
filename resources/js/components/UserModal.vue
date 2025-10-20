<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2, Mail, Shield, User } from 'lucide-vue-next';
import { watch } from 'vue';

interface User {
    id?: number;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'marketing' | 'advertiser';
    password?: string;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    user?: User;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const form = useForm({
    name: '',
    email: '',
    role: 'marketing' as 'super_admin' | 'admin' | 'marketing' | 'advertiser',
    password: '',
    password_confirmation: '',
});

// Watch for user prop changes
watch(
    () => props.user,
    (newUser) => {
        if (newUser) {
            form.name = newUser.name || '';
            form.email = newUser.email || '';
            form.role = newUser.role || 'marketing';
            form.password = '';
            form.password_confirmation = '';
        } else {
            form.reset();
        }
    },
    { immediate: true },
);

// Reset form when modal closes
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
        }
    },
);

const submit = () => {
    if (props.mode === 'create') {
        form.post('/users', {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    } else if (props.mode === 'edit' && props.user?.id) {
        form.put(`/users/${props.user.id}`, {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    }
};

const roleLabels = {
    super_admin: 'Super Admin',
    admin: 'Admin',
    marketing: 'Marketing',
    advertiser: 'Advertiser',
};

const roleDescriptions = {
    super_admin: 'Akses penuh ke semua fitur sistem',
    admin: 'Dapat mengelola user dan operasional',
    marketing: 'Akses terbatas untuk tim pemasaran',
    advertiser: 'Akses penuh ke semua menu dan dapat edit data',
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <User class="h-5 w-5" />
                    <span v-if="mode === 'create'">Tambah User Baru</span>
                    <span v-else-if="mode === 'edit'">Edit User</span>
                    <span v-else>Detail User</span>
                </DialogTitle>
                <DialogDescription>
                    <span v-if="mode === 'create'">Buat pengguna baru untuk sistem Marketing Database</span>
                    <span v-else-if="mode === 'edit'">Update informasi pengguna</span>
                    <span v-else>Informasi detail pengguna</span>
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-4">
                    <!-- Name Field -->
                    <div class="space-y-2">
                        <Label for="name" class="flex items-center gap-2">
                            <User class="h-4 w-4" />
                            Nama Lengkap
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Masukkan nama lengkap"
                            :disabled="mode === 'view'"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <Label for="email" class="flex items-center gap-2">
                            <Mail class="h-4 w-4" />
                            Email
                        </Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="Masukkan alamat email"
                            :disabled="mode === 'view'"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Role Field -->
                    <div class="space-y-2">
                        <Label for="role" class="flex items-center gap-2">
                            <Shield class="h-4 w-4" />
                            Role
                        </Label>
                        <div v-if="mode === 'view'" class="rounded-lg bg-muted p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ roleLabels[form.role] }}</span>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-red-100 text-red-800': form.role === 'super_admin',
                                        'bg-blue-100 text-blue-800': form.role === 'admin',
                                        'bg-green-100 text-green-800': form.role === 'marketing',
                                        'bg-orange-100 text-orange-800': form.role === 'advertiser',
                                    }"
                                >
                                    {{ roleLabels[form.role] }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">{{ roleDescriptions[form.role] }}</p>
                        </div>
                        <select
                            v-else
                            id="role"
                            v-model="form.role"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-red-500': form.errors.role }"
                        >
                            <option value="marketing">Marketing</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="advertiser">Advertiser</option>
                        </select>
                        <p v-if="form.errors.role" class="text-sm text-red-500">{{ form.errors.role }}</p>
                        <p v-if="mode !== 'view'" class="text-sm text-muted-foreground">{{ roleDescriptions[form.role] }}</p>
                    </div>

                    <!-- Password Fields (only for create/edit) -->
                    <template v-if="mode !== 'view'">
                        <div class="space-y-2">
                            <Label for="password">
                                {{ mode === 'create' ? 'Password' : 'Password Baru (kosongkan jika tidak ingin mengubah)' }}
                            </Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="Masukkan password"
                                :class="{ 'border-red-500': form.errors.password }"
                            />
                            <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Konfirmasi Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Konfirmasi password"
                                :class="{ 'border-red-500': form.errors.password_confirmation }"
                            />
                            <p v-if="form.errors.password_confirmation" class="text-sm text-red-500">{{ form.errors.password_confirmation }}</p>
                        </div>
                    </template>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="$emit('close')">
                        {{ mode === 'view' ? 'Tutup' : 'Batal' }}
                    </Button>
                    <Button v-if="mode !== 'view'" type="submit" :disabled="form.processing" class="min-w-[100px]">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ mode === 'create' ? 'Buat User' : 'Update User' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
