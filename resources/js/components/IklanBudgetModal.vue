<template>
  <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="$emit('close')"></div>
      
      <div class="relative bg-white dark:bg-gray-900 rounded-lg sm:rounded-xl shadow-2xl transform transition-all w-full max-w-xs sm:max-w-lg lg:max-w-2xl max-h-[90vh] overflow-hidden border border-gray-200 dark:border-gray-700 mx-2 sm:mx-4">
        <form @submit.prevent="submit" class="flex flex-col h-full">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <TrendingUp class="h-5 w-5 text-blue-600 dark:text-blue-400" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100" id="modal-title">
                {{ isEditMode ? 'Edit Budget Iklan' : 'Tambah Budget Iklan' }}
              </h3>
            </div>
            <button
              type="button"
              @click="$emit('close')"
              class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              <X class="h-5 w-5 text-gray-500 dark:text-gray-400" />
            </button>
          </div>
          
          <!-- Content -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
              <div class="space-y-4 sm:space-y-6">
                

              <!-- Tanggal -->
              <div class="space-y-2">
                <label for="tanggal" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  <Calendar class="inline h-4 w-4 mr-1" />
                  Tanggal <span class="text-red-500">*</span>
                </label>
                <input
                  id="tanggal"
                  type="date"
                  v-model="form.tanggal"
                  :class="{
                    'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.tanggal,
                    'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500': !form.errors.tanggal
                  }"
                  class="block w-full rounded-lg border px-3 py-2.5 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-opacity-50 dark:bg-gray-800 dark:text-gray-100"
                  required
                />
                <div v-if="form.errors.tanggal" class="text-sm text-red-600 flex items-center gap-1">
                  <AlertCircle class="h-4 w-4" />
                  {{ form.errors.tanggal }}
                </div>
              </div>

              <!-- Brand -->
              <div class="space-y-2">
                <label for="brand_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  <TrendingUp class="inline h-4 w-4 mr-1" />
                  Brand
                </label>
                <select
                  id="brand_id"
                  v-model="form.brand_id"
                  :class="{
                    'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.brand_id,
                    'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500': !form.errors.brand_id
                  }"
                  class="block w-full rounded-lg border px-3 py-2.5 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-opacity-50 dark:bg-gray-800 dark:text-gray-100"
                >
                  <option value="">Pilih Brand</option>
                  <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                    {{ brand.nama }}
                  </option>
                </select>
                <div v-if="form.errors.brand_id" class="text-sm text-red-600 flex items-center gap-1">
                  <AlertCircle class="h-4 w-4" />
                  {{ form.errors.brand_id }}
                </div>
              </div>

              <!-- Spent Amount -->
              <div class="space-y-2">
                <label for="spent_amount" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  <CreditCard class="inline h-4 w-4 mr-1" />
                  Spent <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">Rp</span>
                  <input
                    id="spent_amount"
                    type="text"
                    v-model="spentFormatted"
                    @input="handleSpentInput"
                    :class="{
                      'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.spent_amount,
                      'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500': !form.errors.spent_amount
                    }"
                    class="block w-full pl-10 pr-3 py-2.5 rounded-lg border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-opacity-50 dark:bg-gray-800 dark:text-gray-100"
                    placeholder="0"
                    required
                  />
                </div>
                <div v-if="form.errors.spent_amount" class="text-sm text-red-600 flex items-center gap-1">
                  <AlertCircle class="h-4 w-4" />
                  {{ form.errors.spent_amount }}
                </div>
              </div>

              <!-- Real Lead -->
              <!-- Field ini dihapus karena real_lead sekarang diambil otomatis dari tabel Mitra -->


            </div>
          </div>
          
          <!-- Footer -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-3 p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <button
              type="button"
              @click="$emit('close')"
              class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-colors flex items-center gap-2 min-w-[100px] justify-center"
            >
              <div v-if="form.processing" class="flex items-center gap-2">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <span class="hidden sm:inline">Processing...</span>
                <span class="sm:hidden">Proses...</span>
              </div>
              <div v-else class="flex items-center gap-2">
                <Save class="h-4 w-4" />
                <span>{{ isEditMode ? 'Update' : 'Simpan' }}</span>
              </div>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { store, update } from '@/routes/iklan-budgets'
import { X, Calendar, AlertCircle, CreditCard, Save, TrendingUp } from 'lucide-vue-next'

interface IklanBudget {
  id: number
  tanggal: string
  brand_id: number
  spent_amount: number
}

interface Props {
  open: boolean
  mode: 'create' | 'edit'
  budget: IklanBudget | null
  brands: Array<{ id: number; nama: string }>
}

interface Emits {
  close: []
  success: []
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isEditMode = computed(() => props.mode === 'edit')

const form = useForm({
  tanggal: new Date().toISOString().split('T')[0], // Default to today
  brand_id: null,
  spent_amount: 0
})

// Reactive variables for formatted amount displays
const spentFormatted = ref('0')

// Function to format number as Indonesian Rupiah
const formatRupiah = (amount: number): string => {
  return new Intl.NumberFormat('id-ID').format(amount)
}

// Function to parse formatted rupiah back to number
const parseRupiah = (formatted: string): number => {
  return parseInt(formatted.replace(/[^0-9]/g, '')) || 0
}

// Handle spent input formatting
const handleSpentInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = target.value
  const numericValue = parseRupiah(value)
  form.spent_amount = numericValue
  spentFormatted.value = formatRupiah(numericValue)
}

// Watch for form amount changes to update formatted displays
watch(() => form.spent_amount, (newValue) => {
  if (newValue !== parseRupiah(spentFormatted.value)) {
    spentFormatted.value = formatRupiah(newValue)
  }
})

// Watch for budget changes to populate form
watch(() => props.budget, (newBudget) => {
  if (newBudget && props.mode === 'edit') {
    // Format tanggal untuk input date HTML (YYYY-MM-DD)
    const date = new Date(newBudget.tanggal)
    form.tanggal = date.toISOString().split('T')[0]
    form.brand_id = newBudget.brand_id
    form.spent_amount = newBudget.spent_amount
    // Update formatted displays
    spentFormatted.value = formatRupiah(newBudget.spent_amount)
  }
}, { immediate: true })

// Watch for modal open/close to reset form
watch(() => props.open, (isOpen) => {
  if (isOpen && props.mode === 'create') {
    form.reset()
    form.clearErrors()
    // Set default date to today
    const today = new Date().toISOString().split('T')[0]
    form.tanggal = today
    form.brand_id = null
    // Reset formatted displays
    spentFormatted.value = '0'
  }
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

// Client-side validation
const validateForm = (): boolean => {
  form.clearErrors()
  let isValid = true
  
  // Validate required fields
  if (!form.tanggal) {
    form.setError('tanggal', 'Tanggal harus diisi')
    isValid = false
  }
  
  if (!form.spent_amount || form.spent_amount < 0) {
    form.setError('spent_amount', 'Spent amount tidak boleh negatif')
    isValid = false
  }
  
  return isValid
}

const submit = () => {
  // Validate form before submission
  if (!validateForm()) {
    return
  }
  
  if (isEditMode.value && props.budget) {
    form.put(update(props.budget.id).url, {
      onSuccess: () => {
        emit('success')
      },
      onError: (errors) => {
        // Handle server validation errors
        Object.keys(errors).forEach(key => {
          form.setError(key, errors[key])
        })
      }
    })
  } else {
    form.post(store().url, {
      onSuccess: () => {
        emit('success')
      },
      onError: (errors) => {
        // Handle server validation errors
        Object.keys(errors).forEach(key => {
          form.setError(key, errors[key])
        })
      }
    })
  }
}
</script>