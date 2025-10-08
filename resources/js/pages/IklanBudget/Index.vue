<template>
    <Head title="Budget Iklan" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-6 mt-6 space-y-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-4 text-white sm:p-6">
                <div class="relative z-10">
                    <!-- Header Content - Responsive Layout -->
                    <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
                        <!-- Title Section -->
                        <div class="flex-1">
                            <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                                <TrendingUp class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                                Manajemen Budget Iklan
                            </h1>
                            <p class="text-sm text-blue-100 sm:text-base lg:text-lg">
                                Kelola dan pantau budget iklan dengan perhitungan ROAS otomatis
                            </p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col gap-2 sm:flex-row" v-if="permissions.canCrud">
                            <Button
                                @click="openCreateModal"
                                class="bg-white text-blue-600 hover:bg-blue-50 shadow-lg transition-all duration-200"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Data
                            </Button>
                        </div>
                        <!-- Read-only message for admin users -->
                        <div v-else class="text-white/80 text-sm">
                            <p>Mode tampilan saja - Anda tidak memiliki izin untuk menambah/edit data</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <Card class="border-0 shadow-md">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-lg font-semibold">
                        <Filter class="h-5 w-5" />
                        Filter Data
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium">Periode:</label>
                            <DatePicker
                                v-model="filters.start_date"
                                placeholder="Tanggal Mulai"
                                class="w-40"
                            />
                            <span class="text-sm text-muted-foreground">s/d</span>
                            <DatePicker
                                v-model="filters.end_date"
                                placeholder="Tanggal Akhir"
                                class="w-40"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button
                                @click="applyFilter"
                                class="bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                <Search class="mr-2 h-4 w-4" />
                                Filter
                            </Button>
                            <Button
                                @click="resetFilter"
                                variant="outline"
                                class="border-gray-300 hover:bg-gray-50"
                            >
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Reset
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                <Card class="border-0 shadow-md">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Spent</div>
                        <div class="text-lg font-bold text-red-600">{{ formatCurrency(totals?.total_spent || 0) }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Spent+Tax</div>
                        <div class="text-lg font-bold text-red-700">{{ formatCurrency(totals?.total_spent_tax || 0) }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Leads</div>
                        <div class="text-lg font-bold text-green-600">{{ totals?.total_leads || 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Avg CPL</div>
                        <div class="text-lg font-bold text-orange-600">{{ formatCurrency(totals?.avg_cost_per_lead || 0) }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Closing</div>
                        <div class="text-lg font-bold text-purple-600">{{ totals?.total_closing || 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Omset</div>
                        <div class="text-lg font-bold text-green-700">{{ formatCurrency(totals?.total_omset || 0) }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Table Card -->
            <Card class="border-0 shadow-md">
                <CardHeader class="pb-2">
                    <CardTitle class="text-lg font-semibold">Data Budget Iklan</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="relative overflow-hidden">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="text-center">Tanggal</TableHead>
                                        <TableHead class="text-center">Spent</TableHead>
                                        <TableHead class="text-center">Spent+Tax</TableHead>
                                        <TableHead class="text-center">Real Lead</TableHead>
                                        <TableHead class="text-center">Cost/Lead</TableHead>
                                        <TableHead class="text-center">Closing</TableHead>
                                        <TableHead class="text-center">Omset</TableHead>
                                        <TableHead class="text-center">ROAS</TableHead>
                                        <TableHead class="text-center" v-if="permissions.canCrud">Aksi</TableHead>
                                        <TableHead class="text-center" v-else>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="budget in iklanBudgets.data" :key="budget.id">
                                        <TableCell class="text-center">
                                            {{ formatDate(budget.tanggal) }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-red-600">{{ formatCurrency(budget.spent_amount) }}</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-red-700">{{ formatCurrency(budget.spent_plus_tax) }}</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-green-600">{{ budget.real_lead }}</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span v-if="budget.real_lead > 0" class="font-medium text-orange-600">{{ formatCurrency(budget.cost_per_lead) }}</span>
                                            <span v-else class="text-red-500 font-medium">#DIV/0!</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-purple-600">{{ budget.closing }}</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-green-700">{{ formatCurrency(budget.omset) }}</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span v-if="budget.spent_amount > 0 && budget.roas !== null && budget.roas !== undefined" class="font-medium text-indigo-600">{{ Number(budget.roas).toFixed(2) }}</span>
                                            <span v-else class="text-red-500 font-medium">0.00</span>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex justify-center gap-2" v-if="permissions.canCrud">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="openEditModal(budget)"
                                                    class="h-9 w-9 border border-blue-300 bg-gradient-to-r from-blue-100 to-blue-200 p-0 text-blue-700 transition-all duration-200 hover:from-blue-200 hover:to-blue-300"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="openDeleteModal(budget)"
                                                    class="h-9 w-9 border border-red-300 bg-gradient-to-r from-red-100 to-red-200 p-0 text-red-700 transition-all duration-200 hover:from-red-200 hover:to-red-300"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                            <div v-else class="text-center text-gray-500 text-sm">
                                                -
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Enhanced Pagination -->
                        <div class="mt-4 flex flex-col items-center justify-between gap-3 rounded-lg bg-muted/20 p-3 sm:flex-row" v-if="iklanBudgets.total > 0">
                            <div class="text-sm text-foreground/80 dark:text-foreground/90">
                                Menampilkan <span class="font-medium text-foreground">{{ iklanBudgets.data.length }}</span> dari
                                <span class="font-medium text-foreground">{{ iklanBudgets.total }}</span> data
                                <span v-if="iklanBudgets.total > 0" class="text-foreground/70 dark:text-foreground/80">
                                    ({{ iklanBudgets.from }} - {{ iklanBudgets.to }})
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Previous Page -->
                                <Button
                                    v-if="iklanBudgets.prev_page_url"
                                    variant="outline"
                                    size="sm"
                                    @click="router.get(iklanBudgets.prev_page_url)"
                                    class="h-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 px-3 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300"
                                >
                                    ← Prev
                                </Button>

                                <!-- Current Page -->
                                <Button
                                    variant="default"
                                    size="sm"
                                    class="h-9 w-9 border-blue-500 bg-gradient-to-r from-blue-500 to-blue-600 p-0 text-white shadow-md"
                                    disabled
                                >
                                    {{ iklanBudgets.current_page }}
                                </Button>

                                <!-- Next Page -->
                                <Button
                                    v-if="iklanBudgets.next_page_url"
                                    variant="outline"
                                    size="sm"
                                    @click="router.get(iklanBudgets.next_page_url)"
                                    class="h-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 px-3 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300"
                                >
                                    Next →
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Modal Components -->
        <IklanBudgetModal
            :open="budgetModal.open"
            :mode="budgetModal.mode"
            :budget="budgetModal.budget"
            :brands="brands"
            @close="closeBudgetModal"
            @success="handleBudgetSuccess"
        />

        <IklanBudgetDeleteModal
            :open="deleteModal.open"
            :budget="deleteModal.budget"
            @close="closeDeleteModal"
            @success="handleDeleteSuccess"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import { index } from '@/routes/iklan-budgets'
import AppLayout from '@/layouts/AppLayout.vue'
import IklanBudgetModal from '@/components/IklanBudgetModal.vue'
import IklanBudgetDeleteModal from '@/components/IklanBudgetDeleteModal.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'
import Table from '@/components/ui/table/Table.vue'
import TableBody from '@/components/ui/table/TableBody.vue'
import TableCell from '@/components/ui/table/TableCell.vue'
import TableHead from '@/components/ui/table/TableHead.vue'
import TableHeader from '@/components/ui/table/TableHeader.vue'
import TableRow from '@/components/ui/table/TableRow.vue'
import { TrendingUp, Plus, Filter, Search, Edit, Trash2, RotateCcw } from 'lucide-vue-next'

interface IklanBudget {
  id: number
  tanggal: string
  brand_id?: number
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
  iklanBudgets: {
    data: IklanBudget[]
    links: any[]
    from: number
    to: number
    total: number
    prev_page_url?: string
    next_page_url?: string
  }
  totals: {
    total_budget: number
    total_spent: number
    total_spent_plus_tax: number
    total_real_lead: number
    avg_cost_per_lead: number
    total_closing: number
    total_omset: number
    avg_roas: number
  }
  filters: {
    start_date?: string
    end_date?: string
  }
  permissions: {
    canCrud: boolean
    canOnlyView: boolean
    canOnlyViewOwn: boolean
    role: string
  }
  brands: Array<{ id: number; nama: string }>
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = computed(() => [
  { label: 'Dashboard', href: '/' },
  { label: 'Budget Iklan', href: '/iklan-budgets' }
])

// Reactive data
const filters = reactive({
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || ''
})

const budgetModal = reactive({
  open: false,
  mode: 'create' as 'create' | 'edit',
  budget: null as IklanBudget | null
})

const deleteModal = reactive({
  open: false,
  budget: null as IklanBudget | null
})

// Methods
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const applyFilter = () => {
  router.get(index().url, filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const resetFilter = () => {
  filters.start_date = null
  filters.end_date = null
  router.visit(index().url, {
    preserveState: false,
    preserveScroll: false
  })
}

const openCreateModal = () => {
  budgetModal.mode = 'create'
  budgetModal.budget = null
  budgetModal.open = true
}

const openEditModal = (budget: IklanBudget) => {
  budgetModal.mode = 'edit'
  budgetModal.budget = budget
  budgetModal.open = true
}

const openDeleteModal = (budget: IklanBudget) => {
  deleteModal.budget = budget
  deleteModal.open = true
}

const closeBudgetModal = () => {
  budgetModal.open = false
  budgetModal.budget = null
}

const closeDeleteModal = () => {
  deleteModal.open = false
  deleteModal.budget = null
}

const handleBudgetSuccess = () => {
  closeBudgetModal()
  router.visit(index().url, {
    preserveState: false,
    preserveScroll: false
  })
}

const handleDeleteSuccess = () => {
  closeDeleteModal()
  router.visit(index().url, {
    preserveState: false,
    preserveScroll: false
  })
}

</script>