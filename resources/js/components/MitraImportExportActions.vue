<template>
    <div class="button-group flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-x-2 sm:space-y-0 lg:space-x-3">
        <!-- Export Button -->
        <Button
            @click="handleExport"
            :disabled="isExporting"
            class="w-full border border-blue-600 bg-gradient-to-r from-blue-500 to-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:from-blue-600 hover:to-blue-700 sm:w-auto lg:px-4"
        >
            <Download class="mr-1 h-4 w-4 lg:mr-2" />
            <span class="hidden sm:inline">{{ isExporting ? 'Mengekspor...' : 'Export XLSX' }}</span>
            <span class="sm:hidden">{{ isExporting ? 'Export...' : 'Export' }}</span>
        </Button>

        <!-- Template Download Button -->
        <Button
            @click="handleTemplateDownload"
            :disabled="isDownloadingTemplate"
            class="w-full border border-green-600 bg-gradient-to-r from-green-500 to-green-600 px-3 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:from-green-600 hover:to-green-700 sm:w-auto lg:px-4"
        >
            <FileSpreadsheet class="mr-1 h-4 w-4 lg:mr-2" />
            <span class="hidden sm:inline">{{ isDownloadingTemplate ? 'Mengunduh...' : 'Template XLSX' }}</span>
            <span class="sm:hidden">{{ isDownloadingTemplate ? 'Template...' : 'Template' }}</span>
        </Button>

        <!-- Import Button with Tooltip -->
        <div class="group relative w-full sm:w-auto">
            <Button
                @click="triggerFileInput"
                :disabled="isImporting"
                class="w-full border border-orange-600 bg-gradient-to-r from-orange-500 to-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:from-orange-600 hover:to-orange-700 lg:px-4"
            >
                <Upload class="mr-1 h-4 w-4 lg:mr-2" />
                <span class="hidden sm:inline">{{ isImporting ? 'Mengimpor...' : 'Import XLSX' }}</span>
                <span class="sm:hidden">{{ isImporting ? 'Import...' : 'Import' }}</span>
            </Button>

            <!-- Tooltip - Only show on larger screens -->
            <div
                class="invisible absolute bottom-full left-1/2 z-50 mb-2 w-64 -translate-x-1/2 transform rounded-lg bg-gray-900 px-3 py-2 text-xs text-white opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100 hidden sm:block"
            >
                <div class="text-center">
                    <div class="mb-1 font-semibold">Import Data Mitra</div>
                    <div class="text-gray-300">
                        Format: XLSX saja (Max: 10MB)<br />
                        Download template terlebih dahulu
                    </div>
                </div>
                <!-- Arrow -->
                <div class="absolute top-full left-1/2 -translate-x-1/2 transform border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>

        <!-- Hidden File Input -->
        <input ref="fileInput" type="file" accept=".xlsx,.xls" @change="handleFileSelect" class="hidden" />

        <!-- Progress Modal -->
        <Dialog :open="showProgressModal" @update:open="showProgressModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ progressModal.title }}</DialogTitle>
                </DialogHeader>

                <div class="py-4">
                    <div class="mb-4 flex items-center justify-center">
                        <div class="h-12 w-12 animate-spin rounded-full border-b-2 border-primary"></div>
                    </div>
                    <p class="text-center text-muted-foreground">{{ progressModal.message }}</p>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Import Result Modal -->
        <Dialog :open="showResultModal" @update:open="showResultModal = $event">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        <div class="flex items-center gap-2">
                            <CheckCircle v-if="importResult?.success" class="h-5 w-5 text-green-600" />
                            <AlertCircle v-else class="h-5 w-5 text-red-600" />
                            {{ importResult?.success ? 'Import Berhasil' : 'Import Gagal' }}
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <!-- Summary -->
                    <div class="rounded-lg bg-muted/50 p-4">
                        <h4 class="mb-2 font-semibold">Ringkasan Import:</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-muted-foreground">Total Diproses:</span>
                                <span class="ml-2 font-medium">{{ importResult?.total_processed || 0 }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Berhasil:</span>
                                <span class="ml-2 font-medium text-green-600">{{ importResult?.imported || 0 }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Dilewati:</span>
                                <span class="ml-2 font-medium text-yellow-600">{{ importResult?.skipped || 0 }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Error:</span>
                                <span class="ml-2 font-medium text-red-600">{{ importResult?.errors?.length || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div
                        v-if="importResult?.message"
                        class="rounded-lg p-3"
                        :class="
                            importResult.success
                                ? 'border border-green-200 bg-green-50 text-green-800'
                                : 'border border-red-200 bg-red-50 text-red-800'
                        "
                    >
                        {{ importResult.message }}
                    </div>

                    <!-- Warnings -->
                    <div v-if="importResult?.warnings && importResult.warnings.length > 0" class="space-y-2">
                        <h4 class="flex items-center gap-2 font-semibold text-yellow-700">
                            <AlertTriangle class="h-4 w-4" />
                            Peringatan ({{ importResult.warnings.length }})
                        </h4>
                        <div class="max-h-32 overflow-y-auto rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                            <ul class="space-y-1 text-sm">
                                <li v-for="(warning, index) in importResult.warnings" :key="index" class="text-yellow-800">• {{ warning }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Errors -->
                    <div v-if="importResult?.errors && importResult.errors.length > 0" class="space-y-2">
                        <h4 class="flex items-center gap-2 font-semibold text-red-700">
                            <XCircle class="h-4 w-4" />
                            Error ({{ importResult.errors.length }})
                        </h4>
                        <div class="max-h-40 overflow-y-auto rounded-lg border border-red-200 bg-red-50 p-3">
                            <ul class="space-y-1 text-sm">
                                <li v-for="(error, index) in importResult.errors.slice(0, 10)" :key="index" class="text-red-800">• {{ error }}</li>
                                <li v-if="importResult.errors.length > 10" class="font-medium text-red-600">
                                    ... dan {{ importResult.errors.length - 10 }} error lainnya
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button @click="closeResultModal" class="w-full"> Tutup </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertCircle, AlertTriangle, CheckCircle, Download, FileSpreadsheet, Upload, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface ImportResult {
    success: boolean;
    message: string;
    imported: number;
    skipped: number;
    errors: string[];
    warnings?: string[];
    total_processed: number;
}

interface Props {
    filters?: Record<string, any>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    importSuccess: [];
}>();

// State
const isExporting = ref(false);
const isImporting = ref(false);
const isDownloadingTemplate = ref(false);
const fileInput = ref<HTMLInputElement>();

// Modals
const showProgressModal = ref(false);
const showResultModal = ref(false);
const importResult = ref<ImportResult>();

const progressModal = ref({
    title: '',
    message: '',
});

// Export functionality
const handleExport = async () => {
    try {
        isExporting.value = true;
        showProgressModal.value = true;
        progressModal.value = {
            title: 'Mengekspor Data',
            message: 'Sedang memproses dan mengunduh data mitra...',
        };

        // Get current filter parameters
        const params = new URLSearchParams();
        if (props.filters) {
            Object.entries(props.filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    params.append(key, String(value));
                }
            });
        }

        // Fetch export
        const response = await fetch(`/mitras/export?${params.toString()}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `Export failed with status: ${response.status}`);
        }

        // Handle download
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);

        // Extract filename from header or use default
        let filename = 'data-mitra.xlsx';
        const contentDisposition = response.headers.get('content-disposition');
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
            if (filenameMatch && filenameMatch[1]) {
                filename = filenameMatch[1].replace(/['"]/g, '');
            }
        }

        // Create download link
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Export failed:', error);
        alert(`Export gagal: ${error instanceof Error ? error.message : 'Unknown error'}`);
    } finally {
        isExporting.value = false;
        showProgressModal.value = false;
    }
};

// Template download functionality
const handleTemplateDownload = async () => {
    try {
        isDownloadingTemplate.value = true;

        const link = document.createElement('a');
        link.href = '/mitras/template';
        link.download = 'template-import-mitra.xlsx';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Template download failed:', error);
        alert('Download template gagal. Silakan coba lagi.');
    } finally {
        isDownloadingTemplate.value = false;
    }
};

// Import functionality
const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        // Validate file type
        const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];

        if (!allowedTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.xlsx')) {
            alert('Format file tidak didukung. Silakan pilih file XLSX.');
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10485760) {
            alert('File terlalu besar. Maksimal 10MB.');
            return;
        }

        handleImport(file);
    }
};

const handleImport = async (file: File) => {
    try {
        isImporting.value = true;
        showProgressModal.value = true;
        progressModal.value = {
            title: 'Mengimpor Data',
            message: 'Sedang memproses file dan mengimpor data mitra...',
        };

        const formData = new FormData();
        formData.append('file', file);

        // Try multiple ways to get CSRF token
        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Fallback to window Laravel object if meta tag fails
        if (!csrfToken && (window as any).Laravel && (window as any).Laravel.csrfToken) {
            csrfToken = (window as any).Laravel.csrfToken;
        }
        
        // Another fallback using _token from form
        if (!csrfToken) {
            const tokenInput = document.querySelector('input[name="_token"]') as HTMLInputElement;
            if (tokenInput) {
                csrfToken = tokenInput.value;
            }
        }
        
        if (!csrfToken) {
            throw new Error('CSRF token tidak ditemukan. Silakan refresh halaman dan coba lagi.');
        }

        console.log('Using CSRF token:', csrfToken?.substring(0, 10) + '...');

        const response = await fetch('/mitras/import', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        console.log('Response status:', response.status, response.statusText);

        if (!response.ok) {
            // Try to get JSON error response, fallback to text
            let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            try {
                const errorData = await response.json();
                console.log('Error response:', errorData);
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                // If JSON parsing fails, get text response
                const textResponse = await response.text();
                console.error('Non-JSON response received:', textResponse);
                
                // Check if it's a CSRF token mismatch specifically
                if (textResponse.includes('CSRF token mismatch') || textResponse.includes('419')) {
                    throw new Error('CSRF token tidak valid. Silakan refresh halaman dan coba lagi.');
                }
                
                throw new Error(`Server error: ${errorMessage}`);
            }
            throw new Error(errorMessage);
        }

        const result = await response.json();
        importResult.value = result;

        showProgressModal.value = false;
        showResultModal.value = true;

        if (result.success && result.imported > 0) {
            emit('importSuccess');
        }
    } catch (error) {
        console.error('Import failed:', error);
        importResult.value = {
            success: false,
            message: `Import gagal: ${error instanceof Error ? error.message : 'Unknown error'}`,
            imported: 0,
            skipped: 0,
            errors: [error instanceof Error ? error.message : 'Unknown error'],
            total_processed: 0,
        };
        showProgressModal.value = false;
        showResultModal.value = true;
    } finally {
        isImporting.value = false;

        // Reset file input
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
};

const closeResultModal = () => {
    showResultModal.value = false;
    importResult.value = undefined;
};
</script>

<style scoped>
/* Responsive button adjustments */
@media (max-width: 640px) {
    /* Mobile: Stack buttons vertically with full width */
    .flex-col button {
        min-height: 44px; /* Touch-friendly button height */
        justify-content: center;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    /* Tablet: Ensure buttons don't get too cramped */
    button {
        min-width: 100px;
    }
}

@media (min-width: 1025px) {
    /* Desktop: Full button text and proper spacing */
    button {
        min-width: 140px;
    }
}

/* Ensure buttons have consistent spacing and alignment */
.button-group {
    gap: 0.5rem;
}

@media (min-width: 1024px) {
    .button-group {
        gap: 0.75rem;
    }
}
</style>
