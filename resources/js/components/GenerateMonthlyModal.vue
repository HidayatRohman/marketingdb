<template>
  <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>
      
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="submit">
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                  Generate Budget Bulanan
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Buat data budget iklan untuk seluruh bulan dengan template yang sama.
                  </p>
                </div>
                
                <div class="mt-4 space-y-4">
                  <!-- Bulan dan Tahun -->
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Bulan <span class="text-red-500">*</span>
                      </label>
                      <select
                        id="month"
                        v-model="form.month"
                        :class="{
                          'border-red-500': form.errors.month,
                          'border-gray-300 dark:border-gray-600': !form.errors.month
                        }"
                        class="mt-1 block w-full rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        required
                      >
                        <option value="">Pilih Bulan</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                      </select>
                      <div v-if="form.errors.month" class="text-sm text-red-600 mt-1">
                        {{ form.errors.month }}
                      </div>
                    </div>
                    
                    <div>
                      <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tahun <span class="text-red-500">*</span>
                      </label>
                      <input
                        id="year"
                        type="number"
                        min="2020"
                        max="2030"
                        v-model="form.year"
                        :class="{
                          'border-red-500': form.errors.year,
                          'border-gray-300 dark:border-gray-600': !form.errors.year
                        }"
                        class="mt-1 block w-full rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :placeholder="currentYear.toString()"
                        required
                      />
                      <div v-if="form.errors.year" class="text-sm text-red-600 mt-1">
                        {{ form.errors.year }}
                      </div>
                    </div>
                  </div>

                  <!-- Template Budget -->
                  <div>
                    <label for="budget_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Budget Harian (Template) <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="budget_amount"
                      type="number"
                      step="0.01"
                      min="0"
                      v-model="form.budget_amount"
                      :class="{
                        'border-red-500': form.errors.budget_amount,
                        'border-gray-300 dark:border-gray-600': !form.errors.budget_amount
                      }"
                      class="mt-1 block w-full rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                      placeholder="0"
                      required
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Budget ini akan diterapkan untuk setiap hari dalam bulan yang dipilih
                    </p>
                    <div v-if="form.errors.budget_amount" class="text-sm text-red-600 mt-1">
                      {{ form.errors.budget_amount }}
                    </div>
                  </div>

                  <!-- Skip Existing -->
                  <div class="flex items-center">
                    <input
                      id="skip_existing"
                      type="checkbox"
                      v-model="form.skip_existing"
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                    />
                    <label for="skip_existing" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                      Lewati tanggal yang sudah ada data
                    </label>
                  </div>

                  <!-- Preview -->
                  <div v-if="form.month && form.year" class="bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview:</h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                      <p>Bulan: {{ getMonthName(form.month) }} {{ form.year }}</p>
                      <p>Jumlah hari: {{ getDaysInMonth(form.month, form.year) }} hari</p>
                      <p>Budget per hari: {{ formatCurrency(form.budget_amount) }}</p>
                      <p class="font-medium">Total budget bulan: {{ formatCurrency(form.budget_amount * getDaysInMonth(form.month, form.year)) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="form.processing || !form.month || !form.year || !form.budget_amount"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              <span v-if="form.processing">Generating...</span>
              <span v-else>Generate Budget</span>
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-700"
            >
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

interface Props {
  open: boolean
}

interface Emits {
  close: []
  success: []
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const currentYear = new Date().getFullYear()
const currentMonth = new Date().getMonth() + 1

const form = useForm({
  month: currentMonth.toString(),
  year: currentYear,
  budget_amount: 0,
  skip_existing: true
})

// Watch for modal open/close to reset form
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    form.month = currentMonth.toString()
    form.year = currentYear
    form.budget_amount = 0
    form.skip_existing = true
    form.clearErrors()
  }
})

const getMonthName = (month: string | number): string => {
  const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ]
  return monthNames[parseInt(month.toString()) - 1] || ''
}

const getDaysInMonth = (month: string | number, year: string | number): number => {
  return new Date(parseInt(year.toString()), parseInt(month.toString()), 0).getDate()
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const submit = () => {
  form.post(route('iklan-budgets.generate-monthly'), {
    onSuccess: () => {
      emit('success')
    },
    onError: (errors) => {
      console.error('Form errors:', errors)
    }
  })
}
</script>