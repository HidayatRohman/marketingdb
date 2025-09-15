<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { useForm } from '@inertiajs/vue3';
import { Building2, Calendar, FileText, Loader2, MapPin, MessageSquare, Package, Phone, Tag, User } from 'lucide-vue-next';
import { watch } from 'vue';

interface Brand {
    id: number;
    nama: string;
    logo?: string;
    logo_url?: string;
}

interface Label {
    id: number;
    nama: string;
    warna: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Mitra {
    id?: number;
    nama: string;
    no_telp: string;
    tanggal_lead: string;
    brand_id: number;
    brand?: Brand;
    label_id: number | null;
    label?: Label | null;
    user_id: number | null;
    user?: User | null;
    chat: 'masuk' | 'followup' | 'followup_2' | 'followup_3';
    kota: string;
    provinsi: string;
    komentar: string | null;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    mitra?: Mitra;
    brands: Brand[];
    labels: Label[];
    marketingUsers: User[];
    currentUser: {
        id: number;
        name: string;
        role: string;
    };
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

// Indonesian provinces
const indonesianProvinces = [
    'Unknown',
    'Aceh',
    'Sumatera Utara',
    'Sumatera Barat',
    'Riau',
    'Kepulauan Riau',
    'Jambi',
    'Sumatera Selatan',
    'Kepulauan Bangka Belitung',
    'Bengkulu',
    'Lampung',
    'DKI Jakarta',
    'Jawa Barat',
    'Banten',
    'Jawa Tengah',
    'DI Yogyakarta',
    'Jawa Timur',
    'Bali',
    'Nusa Tenggara Barat',
    'Nusa Tenggara Timur',
    'Kalimantan Barat',
    'Kalimantan Tengah',
    'Kalimantan Selatan',
    'Kalimantan Timur',
    'Kalimantan Utara',
    'Sulawesi Utara',
    'Sulawesi Tengah',
    'Sulawesi Selatan',
    'Sulawesi Tenggara',
    'Gorontalo',
    'Sulawesi Barat',
    'Maluku',
    'Maluku Utara',
    'Papua',
    'Papua Barat',
    'Papua Selatan',
    'Papua Tengah',
    'Papua Pegunungan',
    'Papua Barat Daya',
];

const form = useForm({
    nama: '',
    no_telp: '',
    tanggal_lead: new Date().toISOString().split('T')[0], // Default to today
    brand_id: null as number | null,
    label_id: null as number | null,
    user_id: null as number | null,
    chat: 'masuk' as 'masuk' | 'followup' | 'followup_2' | 'followup_3',
    kota: 'Unknown',
    provinsi: 'Unknown',
    komentar: '',
});

// Watch for mitra prop changes
watch(
    () => props.mitra,
    (newMitra) => {
        if (newMitra) {
            form.nama = newMitra.nama || '';
            form.no_telp = newMitra.no_telp || '';
            // Handle date formatting more carefully
            if (newMitra.tanggal_lead) {
                // Convert any date format to YYYY-MM-DD format required by input[type="date"]
                const date = new Date(newMitra.tanggal_lead);
                if (!isNaN(date.getTime())) {
                    form.tanggal_lead = date.toISOString().split('T')[0];
                } else {
                    form.tanggal_lead = new Date().toISOString().split('T')[0];
                }
            } else {
                form.tanggal_lead = new Date().toISOString().split('T')[0];
            }
            form.brand_id = newMitra.brand_id || null;
            form.label_id = newMitra.label_id || null;
            // For marketing role, always use current user ID
            if (props.currentUser.role === 'marketing') {
                form.user_id = props.currentUser.id;
            } else {
                form.user_id = newMitra.user_id || null;
            }
            form.chat = newMitra.chat || 'masuk';
            form.kota = newMitra.kota || 'Unknown';
            form.provinsi = newMitra.provinsi || 'Unknown';
            form.komentar = newMitra.komentar || '';
        } else {
            form.reset();
            form.tanggal_lead = new Date().toISOString().split('T')[0]; // Reset to today's date
            form.kota = 'Unknown';
            form.provinsi = 'Unknown';
            // Auto-set user_id for marketing role when creating new mitra
            if (props.currentUser.role === 'marketing') {
                form.user_id = props.currentUser.id;
            }
        }
    },
    { immediate: true },
);

// Function to format phone number for WhatsApp
const formatWhatsAppNumber = (phoneNumber: string) => {
    // Remove all non-numeric characters
    let cleaned = phoneNumber.replace(/\D/g, '');

    // If starts with '0', replace with '62' (Indonesia country code)
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }

    // If doesn't start with '62', add it
    if (!cleaned.startsWith('62')) {
        cleaned = '62' + cleaned;
    }

    return cleaned;
};

// Function to create WhatsApp URL
const createWhatsAppUrl = (phoneNumber: string, message: string = '') => {
    const formattedNumber = formatWhatsAppNumber(phoneNumber);
    const encodedMessage = encodeURIComponent(message);
    return `https://wa.me/${formattedNumber}${message ? `?text=${encodedMessage}` : ''}`;
};

// Function to open WhatsApp
const openWhatsApp = (phoneNumber: string, mitraName: string) => {
    const message = `Halo ${mitraName}, saya ingin menindaklanjuti mengenai inquiry Anda.`;
    const url = createWhatsAppUrl(phoneNumber, message);
    window.open(url, '_blank');
};

// Function to format date for display
const formatDate = (dateString: string) => {
    if (!dateString) return '-';

    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Reset form when modal closes
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            form.tanggal_lead = new Date().toISOString().split('T')[0]; // Reset to today's date
            form.kota = 'Unknown';
            form.provinsi = 'Unknown';
            form.clearErrors();
        } else if (isOpen && props.mode === 'create') {
            // Auto-set user_id for marketing role when creating new mitra
            if (props.currentUser.role === 'marketing') {
                form.user_id = props.currentUser.id;
            }
        }
    },
);

const submit = () => {
    if (props.mode === 'create') {
        form.post('/mitras', {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    } else if (props.mode === 'edit' && props.mitra?.id) {
        form.put(`/mitras/${props.mitra.id}`, {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    }
};

const chatLabels = {
    masuk: 'Masuk',
    followup: 'Follow Up',
    followup_2: 'Follow Up 2',
    followup_3: 'Follow Up 3',
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <Building2 class="h-5 w-5" />
                    <span v-if="mode === 'create'">Tambah Mitra Baru</span>
                    <span v-else-if="mode === 'edit'">Edit Mitra</span>
                    <span v-else>Detail Mitra</span>
                </DialogTitle>
                <DialogDescription>
                    <span v-if="mode === 'create'">Tambahkan mitra bisnis baru ke dalam sistem.</span>
                    <span v-else-if="mode === 'edit'">Perbarui informasi mitra yang sudah ada.</span>
                    <span v-else>Informasi lengkap mitra bisnis.</span>
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Informasi Dasar -->
                <div class="space-y-4">
                    <h3 class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <Building2 class="h-4 w-4" />
                        Informasi Dasar
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="nama" class="flex items-center gap-2">
                                <Building2 class="h-3 w-3" />
                                Nama Mitra *
                            </Label>
                            <Input
                                id="nama"
                                v-model="form.nama"
                                :disabled="mode === 'view'"
                                placeholder="Masukkan nama mitra"
                                :class="{ 'border-destructive': form.errors.nama }"
                            />
                            <p v-if="form.errors.nama" class="text-sm text-destructive">
                                {{ form.errors.nama }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="no_telp" class="flex items-center gap-2">
                                <Phone class="h-3 w-3" />
                                No. Telepon *
                            </Label>
                            <div v-if="mode === 'view'" class="flex items-center gap-2 rounded-md border bg-muted/50 p-2">
                                <div class="rounded bg-green-100 p-1 dark:bg-green-800">
                                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"
                                        />
                                    </svg>
                                </div>
                                <button
                                    @click="openWhatsApp(form.no_telp, form.nama)"
                                    class="font-medium text-green-600 transition-colors duration-200 hover:text-green-800 hover:underline dark:text-green-400 dark:hover:text-green-300"
                                    :title="`Hubungi ${form.nama} via WhatsApp`"
                                >
                                    {{ form.no_telp }}
                                </button>
                            </div>
                            <Input
                                v-else
                                id="no_telp"
                                v-model="form.no_telp"
                                placeholder="Contoh: 08123456789"
                                :class="{ 'border-destructive': form.errors.no_telp }"
                            />
                            <p v-if="form.errors.no_telp" class="text-sm text-destructive">
                                {{ form.errors.no_telp }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="tanggal_lead" class="flex items-center gap-2">
                                <Calendar class="h-3 w-3" />
                                Tanggal Lead *
                            </Label>
                            <div v-if="mode === 'view'" class="flex items-center gap-2 rounded-md border bg-muted/50 p-2">
                                <div class="rounded bg-blue-100 p-1 dark:bg-blue-800">
                                    <Calendar class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ formatDate(form.tanggal_lead) }}
                                </span>
                            </div>
                            <DatePicker
                                v-else
                                v-model="form.tanggal_lead"
                                placeholder="Pilih tanggal lead"
                                :disabled="mode === 'view'"
                            />
                            
                            <p v-if="form.errors.tanggal_lead" class="text-sm text-destructive">
                                {{ form.errors.tanggal_lead }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="brand_id" class="flex items-center gap-2">
                            <Package class="h-3 w-3" />
                            Brand *
                        </Label>
                        <select
                            id="brand_id"
                            v-model="form.brand_id"
                            :disabled="mode === 'view'"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                            :class="{ 'border-destructive': form.errors.brand_id }"
                        >
                            <option value="" class="bg-background text-foreground">Pilih brand</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id" class="bg-background text-foreground">
                                {{ brand.nama }}
                            </option>
                        </select>
                        <p v-if="form.errors.brand_id" class="text-sm text-destructive">
                            {{ form.errors.brand_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="user_id" class="flex items-center gap-2">
                            <User class="h-3 w-3" />
                            Marketing
                        </Label>
                        <!-- Show current user for marketing role (read-only) -->
                        <div v-if="currentUser.role === 'marketing'" class="flex items-center gap-2 rounded-md border bg-muted/50 p-2">
                            <div class="rounded bg-blue-100 p-1 dark:bg-blue-800">
                                <User class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ currentUser.name }}
                            </span>
                            <span class="text-xs text-muted-foreground">(Auto)</span>
                        </div>
                        <!-- Show selected marketing user for readonly view -->
                        <div v-else-if="mode === 'view'" class="flex items-center gap-2 rounded-md border bg-muted/50 p-2">
                            <div class="rounded bg-blue-100 p-1 dark:bg-blue-800">
                                <User class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ form.user_id ? marketingUsers.find((u) => u.id === form.user_id)?.name || 'Tidak ada' : 'Tidak ada' }}
                            </span>
                        </div>
                        <!-- Show dropdown for super admin and admin -->
                        <select
                            v-else
                            id="user_id"
                            v-model="form.user_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                            :class="{ 'border-destructive': form.errors.user_id }"
                        >
                            <option value="" class="bg-background text-foreground">Pilih marketing</option>
                            <option v-for="user in marketingUsers" :key="user.id" :value="user.id" class="bg-background text-foreground">
                                {{ user.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.user_id" class="text-sm text-destructive">
                            {{ form.errors.user_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="chat" class="flex items-center gap-2">
                            <MessageSquare class="h-3 w-3" />
                            Status Chat *
                        </Label>
                        <select
                            id="chat"
                            v-model="form.chat"
                            :disabled="mode === 'view'"
                            :class="[
                                'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground',
                                { 'border-destructive': form.errors.chat },
                            ]"
                        >
                            <option value="" class="bg-background text-foreground">Pilih status chat</option>
                            <option value="masuk" class="bg-background text-foreground">Masuk</option>
                            <option value="followup" class="bg-background text-foreground">Follow Up</option>
                            <option value="followup_2" class="bg-background text-foreground">Follow Up 2</option>
                            <option value="followup_3" class="bg-background text-foreground">Follow Up 3</option>
                        </select>
                        <p v-if="form.errors.chat" class="text-sm text-destructive">
                            {{ form.errors.chat }}
                        </p>
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="space-y-4">
                    <h3 class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <MapPin class="h-4 w-4" />
                        Lokasi
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="kota" class="flex items-center gap-2">
                                <MapPin class="h-3 w-3" />
                                Kota
                            </Label>
                            <Input
                                id="kota"
                                v-model="form.kota"
                                :disabled="mode === 'view'"
                                placeholder="Masukkan nama kota (opsional)"
                                :class="{ 'border-destructive': form.errors.kota }"
                            />
                            <p v-if="form.errors.kota" class="text-sm text-destructive">
                                {{ form.errors.kota }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="provinsi" class="flex items-center gap-2">
                                <MapPin class="h-3 w-3" />
                                Provinsi
                            </Label>
                            <select
                                id="provinsi"
                                v-model="form.provinsi"
                                :disabled="mode === 'view'"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                                :class="{ 'border-destructive': form.errors.provinsi }"
                            >
                                <option
                                    v-for="province in indonesianProvinces"
                                    :key="province"
                                    :value="province"
                                    class="bg-background text-foreground"
                                >
                                    {{ province }}
                                </option>
                            </select>
                            <p v-if="form.errors.provinsi" class="text-sm text-destructive">
                                {{ form.errors.provinsi }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Label & Komentar -->
                <div class="space-y-4">
                    <h3 class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <Tag class="h-4 w-4" />
                        Informasi Tambahan
                    </h3>

                    <div class="space-y-2">
                        <Label for="label_id" class="flex items-center gap-2">
                            <Tag class="h-3 w-3" />
                            Label (Opsional)
                        </Label>
                        <select
                            id="label_id"
                            v-model="form.label_id"
                            :disabled="mode === 'view'"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                            :class="{ 'border-destructive': form.errors.label_id }"
                        >
                            <option value="" class="bg-background text-foreground">Pilih label</option>
                            <option v-for="label in labels" :key="label.id" :value="label.id" class="bg-background text-foreground">
                                {{ label.nama }}
                            </option>
                        </select>
                        <p v-if="form.errors.label_id" class="text-sm text-destructive">
                            {{ form.errors.label_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="komentar" class="flex items-center gap-2">
                            <FileText class="h-3 w-3" />
                            Komentar (Opsional)
                        </Label>
                        <Textarea
                            id="komentar"
                            v-model="form.komentar"
                            :disabled="mode === 'view'"
                            placeholder="Tambahkan catatan atau komentar..."
                            :rows="3"
                            :class="{ 'border-destructive': form.errors.komentar }"
                        />
                        <p v-if="form.errors.komentar" class="text-sm text-destructive">
                            {{ form.errors.komentar }}
                        </p>
                    </div>
                </div>
            </form>

            <DialogFooter v-if="mode !== 'view'" class="mt-6">
                <Button type="button" variant="outline" @click="$emit('close')" :disabled="form.processing"> Batal </Button>
                <Button @click="submit" :disabled="form.processing" class="min-w-[100px]">
                    <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    {{ mode === 'create' ? 'Simpan' : 'Perbarui' }}
                </Button>
            </DialogFooter>

            <DialogFooter v-else class="mt-6">
                <Button type="button" @click="$emit('close')"> Tutup </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
