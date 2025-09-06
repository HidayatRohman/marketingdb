<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Upload, X, ImageIcon, Settings, Trash2, Eye } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';

interface Settings {
    site_title: string;
    site_description: string;
    site_logo: string | null;
    site_favicon: string | null;
    site_logo_url: string | null;
    site_favicon_url: string | null;
}

interface Props {
    settings: Settings;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Site Settings',
        href: '/settings/site',
    },
];

const form = useForm({
    site_title: props.settings.site_title || '',
    site_description: props.settings.site_description || '',
    site_logo: null as File | null,
    site_favicon: null as File | null,
});

const logoPreview = ref<string | null>(props.settings.site_logo_url);
const faviconPreview = ref<string | null>(props.settings.site_favicon_url);
const deleteModal = ref(false);
const deleteType = ref<'logo' | 'favicon'>('logo');

// Handle logo file selection
const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.site_logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

// Handle favicon file selection
const handleFaviconChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.site_favicon = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            faviconPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

// Remove logo preview
const removeLogo = () => {
    form.site_logo = null;
    logoPreview.value = props.settings.site_logo_url;
    const input = document.getElementById('logo-input') as HTMLInputElement;
    if (input) input.value = '';
};

// Remove favicon preview
const removeFavicon = () => {
    form.site_favicon = null;
    faviconPreview.value = props.settings.site_favicon_url;
    const input = document.getElementById('favicon-input') as HTMLInputElement;
    if (input) input.value = '';
};

// Delete existing file
const deleteFile = (type: 'logo' | 'favicon') => {
    deleteType.value = type;
    deleteModal.value = true;
};

// Confirm delete file
const confirmDeleteFile = () => {
    const deleteForm = useForm({
        key: deleteType.value === 'logo' ? 'site_logo' : 'site_favicon',
    });

    deleteForm.delete('/settings/site/file', {
        onSuccess: () => {
            if (deleteType.value === 'logo') {
                logoPreview.value = null;
                form.site_logo = null;
            } else {
                faviconPreview.value = null;
                form.site_favicon = null;
            }
            deleteModal.value = false;
        }
    });
};

// Submit form
const submit = () => {
    form.patch('/settings/site', {
        forceFormData: true,
        onSuccess: () => {
            form.site_logo = null;
            form.site_favicon = null;
        }
    });
};

// Helper function to trigger file input
const triggerFileInput = (inputId: string) => {
    const input = document.getElementById(inputId) as HTMLInputElement;
    if (input) {
        input.click();
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Site Settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall 
                    title="Site Settings" 
                    description="Kelola pengaturan umum aplikasi seperti logo, favicon, dan informasi dasar" 
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Settings class="h-5 w-5" />
                                Informasi Dasar
                            </CardTitle>
                            <CardDescription>
                                Pengaturan informasi dasar aplikasi
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="site_title">Judul Aplikasi</Label>
                                <Input
                                    id="site_title"
                                    v-model="form.site_title"
                                    type="text"
                                    placeholder="Masukkan judul aplikasi"
                                    :class="{ 'border-red-500': form.errors.site_title }"
                                />
                                <p v-if="form.errors.site_title" class="text-sm text-red-500">
                                    {{ form.errors.site_title }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="site_description">Deskripsi Aplikasi</Label>
                                <Textarea
                                    id="site_description"
                                    v-model="form.site_description"
                                    placeholder="Masukkan deskripsi singkat aplikasi"
                                    :rows="3"
                                    :class="{ 'border-red-500': form.errors.site_description }"
                                />
                                <p v-if="form.errors.site_description" class="text-sm text-red-500">
                                    {{ form.errors.site_description }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Logo Settings -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ImageIcon class="h-5 w-5" />
                                Logo Aplikasi
                            </CardTitle>
                            <CardDescription>
                                Upload logo utama aplikasi (Format: JPEG, PNG, JPG, GIF, SVG - Max: 2MB)
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Logo Preview -->
                            <div v-if="logoPreview" class="relative">
                                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center gap-3">
                                        <div class="w-16 h-16 bg-white dark:bg-gray-700 rounded-lg p-2 border border-gray-200 dark:border-gray-600">
                                            <img :src="logoPreview" alt="Logo Preview" class="w-full h-full object-contain" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Logo Preview</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ form.site_logo ? form.site_logo.name : 'Logo saat ini' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button
                                            v-if="props.settings.site_logo_url && !form.site_logo"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="deleteFile('logo')"
                                            class="text-red-500 hover:text-red-700"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeLogo"
                                            class="text-gray-500 hover:text-gray-700"
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Area -->
                            <div v-if="!logoPreview" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
                                <ImageIcon class="h-8 w-8 mx-auto text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 mb-2">Belum ada logo</p>
                            </div>

                            <!-- Upload Button -->
                            <div class="flex items-center gap-3">
                                <input
                                    id="logo-input"
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml"
                                    @change="handleLogoChange"
                                    class="hidden"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="() => triggerFileInput('logo-input')"
                                >
                                    <Upload class="h-4 w-4 mr-2" />
                                    {{ logoPreview ? 'Ganti Logo' : 'Upload Logo' }}
                                </Button>
                            </div>

                            <p v-if="form.errors.site_logo" class="text-sm text-red-500">
                                {{ form.errors.site_logo }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Favicon Settings -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ImageIcon class="h-5 w-5" />
                                Favicon
                            </CardTitle>
                            <CardDescription>
                                Upload favicon aplikasi (Format: ICO, PNG - Max: 1MB)
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Favicon Preview -->
                            <div v-if="faviconPreview" class="relative">
                                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-white dark:bg-gray-700 rounded p-1 border border-gray-200 dark:border-gray-600">
                                            <img :src="faviconPreview" alt="Favicon Preview" class="w-full h-full object-contain" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Favicon Preview</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ form.site_favicon ? form.site_favicon.name : 'Favicon saat ini' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button
                                            v-if="props.settings.site_favicon_url && !form.site_favicon"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="deleteFile('favicon')"
                                            class="text-red-500 hover:text-red-700"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeFavicon"
                                            class="text-gray-500 hover:text-gray-700"
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Area -->
                            <div v-if="!faviconPreview" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
                                <ImageIcon class="h-6 w-6 mx-auto text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 mb-2">Belum ada favicon</p>
                            </div>

                            <!-- Upload Button -->
                            <div class="flex items-center gap-3">
                                <input
                                    id="favicon-input"
                                    type="file"
                                    accept="image/x-icon,image/png"
                                    @change="handleFaviconChange"
                                    class="hidden"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="() => triggerFileInput('favicon-input')"
                                >
                                    <Upload class="h-4 w-4 mr-2" />
                                    {{ faviconPreview ? 'Ganti Favicon' : 'Upload Favicon' }}
                                </Button>
                            </div>

                            <p v-if="form.errors.site_favicon" class="text-sm text-red-500">
                                {{ form.errors.site_favicon }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <Button 
                            type="submit" 
                            :disabled="form.processing"
                            class="min-w-[120px]"
                        >
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>

        <!-- Delete Confirmation Modal -->
        <Dialog :open="deleteModal" @update:open="deleteModal = false">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Trash2 class="h-5 w-5 text-red-500" />
                        Hapus {{ deleteType === 'logo' ? 'Logo' : 'Favicon' }}
                    </DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus {{ deleteType === 'logo' ? 'logo' : 'favicon' }} ini? 
                        Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="deleteModal = false">
                        Batal
                    </Button>
                    <Button variant="destructive" @click="confirmDeleteFile">
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
