<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Zap, Upload, X, ImageIcon } from 'lucide-vue-next';

interface Brand {
    id: number;
    nama: string;
    logo: string | null;
    logo_url: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    brand?: Brand;
}

interface Emits {
    (e: 'close'): void;
    (e: 'success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const form = useForm({
    nama: '',
    logo: null as File | null,
});

const isSubmitting = ref(false);
const logoPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Reset form when modal opens/closes or brand changes
watch([() => props.open, () => props.brand], () => {
    if (props.open) {
        if (props.mode === 'create') {
            form.reset();
            logoPreview.value = null;
        } else if (props.brand) {
            form.nama = props.brand.nama;
            form.logo = null; // Reset file input
            logoPreview.value = props.brand.logo_url || null;
        }
    }
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.logo = file;
        
        // Create preview URL
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleSubmit = async () => {
    if (props.mode === 'view') return;
    
    isSubmitting.value = true;
    
    try {
        if (props.mode === 'create') {
            await form.post('/brands', {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    emit('success');
                    emit('close');
                },
                onError: () => {
                    isSubmitting.value = false;
                },
                onFinish: () => {
                    isSubmitting.value = false;
                }
            });
        } else if (props.mode === 'edit' && props.brand) {
            // For edit, we need to add _method=PUT for Laravel
            form.transform((data) => ({
                ...data,
                _method: 'PUT'
            }));
            
            await form.post(`/brands/${props.brand.id}`, {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    emit('success');
                    emit('close');
                },
                onError: () => {
                    isSubmitting.value = false;
                },
                onFinish: () => {
                    isSubmitting.value = false;
                }
            });
        }
    } catch (error) {
        isSubmitting.value = false;
    }
};

const handleClose = () => {
    if (!isSubmitting.value) {
        emit('close');
    }
};

const getTitle = () => {
    switch (props.mode) {
        case 'create':
            return 'Tambah Brand Baru';
        case 'edit':
            return 'Edit Brand';
        case 'view':
            return 'Detail Brand';
        default:
            return 'Brand';
    }
};

const getDescription = () => {
    switch (props.mode) {
        case 'create':
            return 'Tambahkan brand baru ke dalam sistem.';
        case 'edit':
            return 'Perbarui informasi brand.';
        case 'view':
            return 'Lihat detail informasi brand.';
        default:
            return '';
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30 rounded-lg">
                        <Zap class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                    </div>
                    {{ getTitle() }}
                </DialogTitle>
                <DialogDescription>
                    {{ getDescription() }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="space-y-4">
                    <!-- Nama Brand -->
                    <div class="space-y-2">
                        <Label for="nama" class="text-sm font-medium">
                            Nama Brand
                            <span v-if="mode !== 'view'" class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="nama"
                            v-model="form.nama"
                            type="text"
                            placeholder="Masukkan nama brand"
                            :disabled="mode === 'view' || isSubmitting"
                            :class="form.errors.nama ? 'border-red-500' : ''"
                            class="h-11"
                        />
                        <div v-if="form.errors.nama" class="text-sm text-red-600">
                            {{ form.errors.nama }}
                        </div>
                    </div>

                    <!-- Logo -->
                    <div class="space-y-2">
                        <Label for="logo" class="text-sm font-medium">
                            Logo Brand
                        </Label>
                        <div class="space-y-3">
                            <!-- File Input -->
                            <div v-if="mode !== 'view'" class="space-y-2">
                                <input
                                    ref="fileInputRef"
                                    id="logo"
                                    type="file"
                                    accept="image/*"
                                    @change="handleFileChange"
                                    :disabled="isSubmitting"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-900/20 dark:file:text-violet-400"
                                />
                            </div>
                            
                            <!-- Logo Preview -->
                            <div v-if="logoPreview" class="flex items-center gap-3 p-3 bg-muted/30 rounded-lg border">
                                <div class="w-12 h-12 bg-white dark:bg-gray-800 rounded-lg flex items-center justify-center border shadow-sm">
                                    <img 
                                        :src="logoPreview" 
                                        :alt="form.nama || 'Logo preview'" 
                                        class="w-10 h-10 object-contain rounded"
                                        @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">Preview Logo</p>
                                    <p class="text-xs text-muted-foreground truncate">
                                        {{ form.logo ? form.logo.name : 'Logo saat ini' }}
                                    </p>
                                </div>
                                <Button
                                    v-if="mode !== 'view'"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeLogo"
                                    class="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            
                            <!-- Empty State -->
                            <div v-if="!logoPreview" class="flex items-center justify-center h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                                <div class="text-center">
                                    <ImageIcon class="h-8 w-8 mx-auto text-gray-400 mb-2" />
                                    <p class="text-sm text-gray-500">Tidak ada logo</p>
                                </div>
                            </div>
                            
                            <div v-if="form.errors.logo" class="text-sm text-red-600">
                                {{ form.errors.logo }}
                            </div>
                            
                            <div class="text-xs text-muted-foreground">
                                <Upload class="h-3 w-3 inline mr-1" />
                                Upload gambar logo brand (JPG, PNG, GIF, SVG - maks. 2MB)
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter v-if="mode !== 'view'">
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                        :disabled="isSubmitting"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        :disabled="isSubmitting || !form.nama.trim()"
                        class="bg-violet-600 hover:bg-violet-700"
                    >
                        <span v-if="isSubmitting" class="flex items-center gap-2">
                            <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            {{ mode === 'create' ? 'Menambah...' : 'Menyimpan...' }}
                        </span>
                        <span v-else>
                            {{ mode === 'create' ? 'Tambah Brand' : 'Simpan Perubahan' }}
                        </span>
                    </Button>
                </DialogFooter>
                
                <DialogFooter v-else>
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                        class="w-full"
                    >
                        Tutup
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
