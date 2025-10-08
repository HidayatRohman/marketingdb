<template>
  <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>
      
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                Hapus Budget Iklan
              </h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Apakah Anda yakin ingin menghapus data budget iklan ini? Tindakan ini tidak dapat dibatalkan.
                </p>
                
                <div v-if="budget" class="mt-4 bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                  <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Detail Budget yang akan dihapus:</h4>
                  <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <div class="flex justify-between">
                      <span>Tanggal:</span>
                      <span class="font-medium">{{ formatDate(budget.tanggal) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Budget:</span>
                      <span class="font-medium">{{ formatCurrency(budget.budget_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Spent:</span>
                      <span class="font-medium">{{ formatCurrency(budget.spent_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Real Lead:</span>
                      <span class="font-medium">{{ budget.real_lead }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Closing:</span>
                      <span class="font-medium">{{ budget.closing }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Omset:</span>
                      <span class="font-medium">{{ formatCurrency(budget.omset) }}</span>
                    </div>
                    <div v-if="budget.keterangan" class="flex justify-between">
                      <span>Keterangan:</span>
                      <span class="font-medium">{{ budget.keterangan }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            type="button"
            @click="confirmDelete"
            :disabled="processing"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
          >
            <span v-if="processing">Menghapus...</span>
            <span v-else>Ya, Hapus</span>
          </button>
          <button
            type="button"
            @click="$emit('close')"
            :disabled="processing"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-700 disabled:opacity-50"
          >
            Batal
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

interface IklanBudget {
  id: number
  tanggal: string
  budget_amount: number
  spent_amount: number
  spent_plus_tax: number
  real_lead: number
  cost_per_lead: number
  closing: number
  omset: number
  roas: number
  keterangan?: string
}

interface Props {
  open: boolean
  budget: IklanBudget | null
}

interface Emits {
  close: []
  success: []
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const processing = ref(false)

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const confirmDelete = () => {
  if (!props.budget) return
  
  processing.value = true
  
  router.delete(route('iklan-budgets.destroy', props.budget.id), {
    onSuccess: () => {
      processing.value = false
      emit('success')
    },
    onError: (errors) => {
      processing.value = false
      console.error('Delete error:', errors)
    },
    onFinish: () => {
      processing.value = false
    }
  })
}
</script>