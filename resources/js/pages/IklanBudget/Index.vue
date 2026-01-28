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
                            <!-- Import/Export Actions -->
                            <IklanBudgetImportExportActions
                                :filters="filters"
                                :can-import="true"
                                @import-success="handleImportSuccess"
                            />
                        </div>
                        <!-- Read-only message for admin users -->
                        <div v-else class="flex flex-col gap-2 sm:flex-row items-start sm:items-center">
                            <!-- Import/Export Actions (no import when read-only) -->
                            <IklanBudgetImportExportActions
                                :filters="filters"
                                :can-import="false"
                            />
                            <p class="text-white/80 text-sm">Mode tampilan saja - Anda tidak memiliki izin untuk menambah/edit data</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <Card class="border-0 shadow-md w-full">
                <CardHeader class="px-4 py-3 sm:px-6 bg-gradient-to-r from-indigo-50 via-sky-50 to-blue-50 dark:from-indigo-900 dark:via-sky-900 dark:to-blue-900 rounded-t-lg border-b border-indigo-100 dark:border-indigo-800">
                    <CardTitle class="flex items-center gap-2 text-lg font-semibold text-indigo-800 dark:text-indigo-100">
                        <Filter class="h-5 w-5" />
                        Filter Data
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 sm:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center w-full sm:w-auto">
                            <label class="text-sm font-medium">Periode:</label>
                            <DatePicker
                                v-model="filters.start_date"
                                placeholder="Tanggal Mulai"
                                class="w-full sm:w-48"
                            />
                            <span class="text-sm text-muted-foreground hidden sm:inline">s/d</span>
                            <DatePicker
                                v-model="filters.end_date"
                                placeholder="Tanggal Akhir"
                                class="w-full sm:w-48"
                            />
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center w-full sm:w-auto">
                            <label class="text-sm font-medium">Brand:</label>
                            <select
                                v-model="filters.brand_id"
                                class="w-full sm:w-48 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Semua Brand</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                    {{ brand.nama }}
                                </option>
                            </select>
                        </div>
                        <div class="flex gap-2 sm:flex-row flex-wrap w-full sm:w-auto">
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
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                <Card class="border-0 shadow-md bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900 dark:to-red-800">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Spent</div>
                        <div class="text-lg font-bold text-red-600">{{ formatCurrency(totals?.total_spent || 0) }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900 dark:to-red-800">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Spent+PPN ({{ ppnPercentage }}%)</div>
                        <div class="text-lg font-bold text-red-700">{{ formatCurrency(totals?.total_spent_plus_tax || 0) }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Leads</div>
                        <div class="text-lg font-bold text-green-600">{{ totals?.total_leads || 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900 dark:to-orange-800">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Avg CPL</div>
                        <div class="text-lg font-bold text-orange-600">{{ formatCurrency(totals?.avg_cost_per_lead || 0) }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Closing</div>
                        <div class="text-lg font-bold text-purple-600">{{ totals?.total_closing || 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900 dark:to-emerald-800">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Total Omset</div>
                        <div class="text-lg font-bold text-green-700">{{ formatCurrency(totals?.total_omset || 0) }}</div>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-md bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800">
                    <CardContent class="p-4 text-center">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">Avg ROAS</div>
                        <div class="text-lg font-bold text-blue-600">{{ Number(totals?.avg_roas || 0).toFixed(2) }}x</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Report Budget Vs Omset -->
            <Card class="mb-6 border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100 transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:from-gray-800/30 dark:to-gray-700/30 shadow-lg">
                <CardHeader class="px-4 py-3 bg-gradient-to-r from-indigo-50 via-sky-50 to-blue-50 dark:from-indigo-900 dark:via-sky-900 dark:to-blue-900 rounded-t-lg border-b border-indigo-100 dark:border-indigo-800">
                    <CardTitle class="flex items-center gap-2 text-indigo-800 dark:text-indigo-100">
                        <BarChart3 class="h-6 w-6" />
                        Report Budget Vs Omset
                    </CardTitle>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Ringkasan performa budget marketing vs omset per brand
                        <span v-if="reportPeriodLabel" class="ml-1">— {{ reportPeriodLabel }}</span>
                    </p>
                </CardHeader>
                <CardContent class="p-6">
                    <!-- Filter Section (mengikuti filter halaman aktif) -->
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-6">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Report mengikuti filter halaman aktif (Brand dan Periode).
                            <span v-if="reportPeriodLabel" class="ml-1">— {{ reportPeriodLabel }}</span>
                        </div>
                    </div>

                    <!-- Summary Statistics Cards -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 mb-6">
                        <Card class="relative bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-blue-500/60">
                                    <Target class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Spent</p>
                                        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                            {{ formatCurrency(summaryReport.reduce((sum, item) => sum + item.spent, 0)) }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-green-500/60">
                                    <TrendingUp class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-700 dark:text-green-300">Total Omset</p>
                                        <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                                            {{ formatCurrency(summaryReport.reduce((sum, item) => sum + item.omset, 0)) }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-purple-500/60">
                                    <Users class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Total Leads</p>
                                        <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                            {{ summaryReport.reduce((sum, item) => sum + item.real_lead, 0).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-orange-500/60">
                                    <Award class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-orange-700 dark:text-orange-300">Total Closing</p>
                                        <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                                            {{ summaryReport.reduce((sum, item) => sum + item.closing, 0).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950 dark:to-indigo-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-indigo-500/60">
                                    <DollarSign class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Cost Per Acquisition</p>
                                        <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">
                                            {{ 
                                                (() => {
                                                    const totalSpent = summaryReport.reduce((sum, item) => sum + item.spent, 0)
                                                    const totalClosing = summaryReport.reduce((sum, item) => sum + item.closing, 0)
                                                    return totalClosing > 0 ? formatCurrency(totalSpent / totalClosing) : 'Rp0'
                                                })()
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Summary Report Table -->
                    <div v-if="summaryReport.length > 0" class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2 sm:p-3 font-semibold sticky left-0 z-30 bg-background min-w-[140px] sm:min-w-[180px] border-r border-border">Brand</th>
                                    <th class="text-right p-3 font-semibold">Spent</th>
                                    <th class="text-right p-3 font-semibold">Spent+PPN ({{ ppnPercentage }}%)</th>
                                    <th class="text-right p-3 font-semibold">Real Lead</th>
                                    <th class="text-right p-3 font-semibold">Cost/Lead</th>
                                    <th class="text-right p-3 font-semibold">Closing</th>
                                    <th class="text-right p-3 font-semibold">Omset</th>
                                    <th class="text-right p-3 font-semibold">ROAS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in summaryReport" :key="item.brand" class="border-b hover:bg-muted/50">
                                    <td class="p-2 sm:p-3 text-sm sm:text-base font-medium text-blue-600 sticky left-0 z-20 bg-background min-w-[140px] sm:min-w-[180px] border-r border-border">{{ item.brand }}</td>
                                    <td class="p-3 text-right text-red-600">{{ formatCurrency(item.spent) }}</td>
                                    <td class="p-3 text-right text-red-600">{{ formatCurrency(item.spent_with_tax) }}</td>
                                    <td class="p-3 text-right">{{ item.real_lead }}</td>
                                    <td class="p-3 text-right text-orange-600">
                                        <span v-if="item.real_lead > 0">{{ formatCurrency(item.cost_per_lead) }}</span>
                                        <span v-else class="text-red-500">#DIV/0!</span>
                                    </td>
                                    <td class="p-3 text-right text-purple-600">{{ item.closing }}</td>
                                    <td class="p-3 text-right text-green-600">{{ formatCurrency(item.omset) }}</td>
                                    <td class="p-3 text-right" :class="item.roas >= 1 ? 'text-green-600 font-semibold' : 'text-red-500'">
                                        {{ item.roas.toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex h-32 flex-col items-center justify-center text-muted-foreground">
                        <BarChart3 class="mb-4 h-12 w-12 opacity-50" />
                        <p class="text-lg font-medium">Tidak ada data summary report</p>
                        <p class="text-sm">Data akan muncul setelah ada aktivitas marketing</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card class="border-0 shadow-md">
                <CardHeader class="px-4 py-3 pb-2 bg-gradient-to-r from-teal-50 via-cyan-50 to-blue-50 dark:from-teal-900 dark:via-cyan-900 dark:to-blue-900 rounded-t-lg border-b border-teal-100 dark:border-teal-800">
                    <CardTitle class="text-lg font-semibold text-teal-800 dark:text-teal-100">Data Budget Iklan</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="relative overflow-hidden">
                        <div v-if="permissions.canCrud" class="flex items-center justify-between px-4 py-2">
                            <div class="text-sm text-muted-foreground" v-if="selectedIds.length > 0">
                                {{ selectedIds.length }} dipilih
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    :disabled="selectedIds.length === 0 || processingBulk"
                                    @click="confirmBulkDelete"
                                    class="bg-red-600 hover:bg-red-700 text-white"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Hapus Terpilih
                                </Button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead v-if="permissions.canCrud" class="text-center w-[40px]">
                                            <input
                                                ref="headerCheckboxRef"
                                                type="checkbox"
                                                :checked="allSelected"
                                                @change="(e) => toggleSelectAll((e.target as HTMLInputElement).checked)"
                                                class="mx-auto h-4 w-4 cursor-pointer accent-blue-600"
                                            />
                                        </TableHead>
                                        <TableHead class="text-center">Tanggal</TableHead>
                                        <TableHead class="text-center">Brand</TableHead>
                                        <TableHead class="text-center">Spent</TableHead>
                                        <TableHead class="text-center">Spent+PPN ({{ ppnPercentage }}%)</TableHead>
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
                                        <TableCell v-if="permissions.canCrud" class="text-center">
                                              <input
                                                  type="checkbox"
                                                  :checked="selectedIds.includes(budget.id)"
                                                  @change="(e) => toggleRowSelection(budget.id, (e.target as HTMLInputElement).checked)"
                                                  class="mx-auto h-4 w-4 cursor-pointer accent-blue-600"
                                              />
                                          </TableCell>
                                        <TableCell class="text-center">
                                            {{ formatDate(budget.tanggal) }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-blue-600">{{ budget.brand?.nama || '-' }}</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-red-600">{{ formatCurrency(budget.spent_amount) }}</span>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-medium text-red-700">{{ formatCurrency(Number(budget.spent_plus_tax) || 0) }}</span>
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
                                            <span v-if="(Number(budget.spent_plus_tax) || 0) > 0 && budget.omset > 0" class="font-medium text-indigo-600">{{ (budget.omset / (Number(budget.spent_plus_tax) || 0)).toFixed(2) }}</span>
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
                                                    type="button"
                                                    @click="openDeleteModal(budget)"
                                                    aria-label="Hapus data budget"
                                                    title="Hapus data budget"
                                                    class="h-9 w-9 border border-red-300 bg-gradient-to-r from-red-100 to-red-200 p-0 text-red-700 transition-all duration-200 hover:from-red-200 hover:to-red-300"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    <span class="sr-only">Hapus data budget</span>
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

            <!-- Grafik Spent Bulanan -->
            <Card class="border-0 shadow-md">
                <CardHeader class="px-4 py-3 pb-2 bg-gradient-to-r from-indigo-50 via-sky-50 to-blue-50 dark:from-indigo-900 dark:via-sky-900 dark:to-blue-900 rounded-t-lg border-b border-indigo-100 dark:border-indigo-800">
                    <CardTitle class="flex items-center gap-2 text-indigo-800 dark:text-indigo-100">
                        <BarChart3 class="h-6 w-6" />
                        Grafik Spent Bulanan
                    </CardTitle>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                            <label for="spent-year" class="text-sm font-medium text-gray-700 dark:text-gray-300">Tahun:</label>
                            <select 
                                id="spent-year" 
                                v-model="spentChartYear" 
                                @change="fetchMonthlySpentData"
                                class="w-full sm:w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="yr in [2023, 2024, 2025, 2026]" :key="yr" :value="yr">{{ yr }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                            <label for="spent-brand" class="text-sm font-medium text-gray-700 dark:text-gray-300">Brand:</label>
                            <select 
                                id="spent-brand"
                                v-model="chartBrandId"
                                @change="fetchMonthlySpentData"
                                class="w-full sm:w-48 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Semua Brand</option>
                                <option v-for="b in props.brands" :key="b.id" :value="String(b.id)">{{ b.nama }}</option>
                            </select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-4">
                    <MonthlySpentChart 
                        :data="monthlySpentData" 
                        :loading="monthlySpentLoading"
                        :year="spentChartYear"
                        :brand-name="selectedBrandName"
                        @refresh="fetchMonthlySpentData"
                    />
                </CardContent>
            </Card>

            <!-- Grafik Total Leads Bulanan -->
            <Card class="border-0 shadow-md mt-6">
                <CardHeader class="px-4 py-3 pb-2 bg-gradient-to-r from-teal-50 via-emerald-50 to-green-50 dark:from-teal-900 dark:via-emerald-900 dark:to-green-900 rounded-t-lg border-b border-teal-100 dark:border-teal-800">
                    <CardTitle class="flex items-center gap-2 text-teal-800 dark:text-teal-100">
                        <BarChart3 class="h-6 w-6" />
                        Total Leads Per Bulan
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4">
                    <MonthlyLeadsChart 
                        :data="monthlyLeadsData" 
                        :loading="monthlyLeadsLoading"
                        :year="leadsChartYear"
                        :brand-name="selectedBrandName"
                        @refresh="fetchMonthlyLeadsData"
                    />
                </CardContent>
            </Card>

            <!-- Grafik Perbandingan Tahunan -->
            <YearlyComparisonChart 
                :brands="props.brands"
                :initial-brand-id="props.filters.brand_id"
            />
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { router, Head, usePage } from '@inertiajs/vue3'
import { index, destroy, bulkDestroy } from '@/routes/iklan-budgets'
import AppLayout from '@/layouts/AppLayout.vue'
import IklanBudgetModal from '@/components/IklanBudgetModal.vue'
import IklanBudgetDeleteModal from '@/components/IklanBudgetDeleteModal.vue'
import IklanBudgetImportExportActions from '@/components/IklanBudgetImportExportActions.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'
import Table from '@/components/ui/table/Table.vue'
import TableBody from '@/components/ui/table/TableBody.vue'
import TableCell from '@/components/ui/table/TableCell.vue'
import TableHead from '@/components/ui/table/TableHead.vue'
import TableHeader from '@/components/ui/table/TableHeader.vue'
import TableRow from '@/components/ui/table/TableRow.vue'
import MonthlySpentChart from '@/components/MonthlySpentChart.vue'
import MonthlyLeadsChart from '@/components/MonthlyLeadsChart.vue'
import YearlyComparisonChart from '@/components/YearlyComparisonChart.vue'

import { TrendingUp, Plus, Filter, Search, Edit, Trash2, RotateCcw, BarChart3, Target, Users, Award, DollarSign } from 'lucide-vue-next'

interface IklanBudget {
  id: number
  tanggal: string
  brand_id?: number
  brand?: { id: number; nama: string }
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
    total_leads: number
    avg_cost_per_lead: number
    total_closing: number
    total_omset: number
    avg_roas: number
  }
  filters: {
    start_date?: string
    end_date?: string
    brand_id?: number | string
  }
  permissions: {
    canCrud: boolean
    canOnlyView: boolean
    canOnlyViewOwn: boolean
    role: string
  }
  // Tambahkan prop reportSummary yang dikirim dari server
  reportSummary: Array<{
    brand: string
    spent: number
    spent_with_tax: number
    real_lead: number
    cost_per_lead: number
    closing: number
    omset: number
    roas: number
  }>
  brands: Array<{ id: number; nama: string }>
}

const props = defineProps<Props>()

const selectedIds = ref<number[]>([])
const allSelected = computed(() => props.iklanBudgets?.data?.length > 0 && props.iklanBudgets.data.every(b => selectedIds.value.includes(b.id)))
const someSelected = computed(() => selectedIds.value.length > 0 && !allSelected.value)
// represent header checkbox state using reka-ui pattern (boolean | 'indeterminate')
const headerCheckboxRef = ref<HTMLInputElement | null>(null)

// Sinkronkan tampilan indeterminate pada checkbox header
watch([selectedIds, allSelected, someSelected], () => {
  const el = headerCheckboxRef.value
  if (el) {
    el.indeterminate = someSelected.value && !allSelected.value
  }
})

const toggleSelectAll = (checked: boolean) => {
  if (checked) {
    selectedIds.value = props.iklanBudgets.data.map(b => b.id)
  } else {
    selectedIds.value = []
  }
}
const toggleRowSelection = (id: number, checked: boolean) => {
  if (checked) {
    if (!selectedIds.value.includes(id)) selectedIds.value.push(id)
  } else {
    selectedIds.value = selectedIds.value.filter(x => x !== id)
  }
}
watch(() => props.iklanBudgets.data, () => {
  selectedIds.value = []
})

const processingBulk = ref(false)
const confirmBulkDelete = () => {
  if (selectedIds.value.length === 0 || processingBulk.value) return
  const count = selectedIds.value.length
  const ok = window.confirm(`Hapus ${count} data terpilih? Tindakan ini tidak bisa dikembalikan.`)
  if (!ok) return
  processingBulk.value = true
  router.visit(bulkDestroy.url(), {
    method: 'delete',
    data: { ids: selectedIds.value },
    preserveScroll: false,
    onSuccess: () => {
      selectedIds.value = []
    },
    onError: (errors) => {
      console.error('Bulk delete error:', errors)
      alert('Gagal menghapus data terpilih.')
    },
    onFinish: () => {
      processingBulk.value = false
    }
  })
}

// Breadcrumbs
const breadcrumbs = computed(() => [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Budget Iklan', href: '/iklan-budgets' }
])

// Ambil multiplier PPN dinamis dari Inertia shared props (siteSettings)
const page = usePage()
const ppnMultiplier = computed(() => {
  const raw = (page.props as any)?.siteSettings?.ppn_rate
  const rate = Number(raw)
  if (isNaN(rate)) return 1.11 // default 11%
  return 1 + (rate / 100)
})

// Persentase PPN untuk label tampilan
const ppnPercentage = computed(() => {
  const raw = (page.props as any)?.siteSettings?.ppn_rate
  const rate = Number(raw)
  return isNaN(rate) ? 11 : rate
})

// Reactive data
const filters = reactive({
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
  brand_id: props.filters.brand_id || ''
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
  // Handle NaN, null, undefined values
  const validAmount = Number(amount) || 0
  if (isNaN(validAmount)) {
    return 'Rp0'
  }
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(validAmount)
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
  filters.start_date = ''
  filters.end_date = ''
  filters.brand_id = ''
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
  const ok = window.confirm('Yakin ingin menghapus data ini?')
  if (!ok) return
  router.delete(destroy.url(budget.id), {
    preserveScroll: false,
    onSuccess: handleDeleteSuccess,
    onError: (errors: any) => {
      console.error('Gagal menghapus:', errors)
      alert('Gagal menghapus data. Periksa izin dan koneksi, lalu coba lagi.')
    },
  })
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

const handleImportSuccess = () => {
  router.visit(index().url, {
    preserveState: false,
    preserveScroll: false
  })
}

// Report Budget Vs Omset variables
const selectedMonth = ref<string>('') // mengikuti filter halaman
const selectedYear = ref<string>('')  // mengikuti filter halaman

// Label periode dinamis untuk subtitle (mengikuti filter halaman)
const reportPeriodLabel = computed(() => {
  const brandId = filters.brand_id ? String(filters.brand_id) : ''
  const brandName = brandId
    ? (props.brands.find(br => String(br.id) === brandId)?.nama || `Brand #${brandId}`)
    : 'Semua Brand'

  const fmt = (d?: string) => {
    if (!d) return ''
    try {
      return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
    } catch {
      return d as string
    }
  }

  const start = filters.start_date || ''
  const end = filters.end_date || ''
  let period = 'Semua Periode'
  if (start && end) period = `${fmt(start)} — ${fmt(end)}`
  else if (start) period = `≥ ${fmt(start)}`
  else if (end) period = `≤ ${fmt(end)}`

  return `${brandName} • ${period}`
})

// Summary Report computed property
const summaryReport = computed(() => {
  return Array.isArray(props.reportSummary) ? props.reportSummary : []
})

// Filter function for month and year
const filterByMonthYear = () => {
  // This function is called when month or year filter changes
  // The computed property will automatically recalculate
}

// ============================
// Monthly Spent Chart (Analytics)
// ============================
const spentChartYear = ref<number>(new Date().getFullYear())
const monthlySpentLoading = ref(false)
const monthlySpentData = ref<Array<{ month: number; label: string; spent: number }>>([])
const chartBrandId = ref<string>(filters.brand_id ? String(filters.brand_id) : '')

const selectedBrandName = computed(() => {
  const id = chartBrandId.value
  if (!id) return ''
  const b = props.brands.find(br => String(br.id) === String(id))
  return b ? b.nama : ''
})

const fetchMonthlySpentData = async () => {
  monthlySpentLoading.value = true
  try {
    const params = new URLSearchParams({
      year: String(spentChartYear.value),
    })
    if (chartBrandId.value) {
      params.append('brand_id', String(chartBrandId.value))
    }
    const response = await fetch(`/iklan-budgets/analytics/monthly-spent?${params.toString()}`)
    if (response.ok) {
      const result = await response.json()
      monthlySpentData.value = result.data || []
    }
  } catch (err) {
    console.error('Gagal memuat data grafik spent bulanan:', err)
  } finally {
    monthlySpentLoading.value = false
  }
}

// ============================
// Monthly Leads Chart (Analytics)
// ============================
const leadsChartYear = ref<number>(new Date().getFullYear())
const monthlyLeadsLoading = ref(false)
const monthlyLeadsData = ref<Array<{ month: number; label: string; leads: number }>>([])

const fetchMonthlyLeadsData = async () => {
  monthlyLeadsLoading.value = true
  try {
    const params = new URLSearchParams({
      year: String(leadsChartYear.value),
    })
    if (chartBrandId.value) {
      params.append('brand_id', String(chartBrandId.value))
    }
    const response = await fetch(`/iklan-budgets/analytics/monthly-leads?${params.toString()}`)
    if (response.ok) {
      const result = await response.json()
      monthlyLeadsData.value = result.data || []
    }
  } catch (err) {
    console.error('Gagal memuat data grafik leads bulanan:', err)
  } finally {
    monthlyLeadsLoading.value = false
  }
}

onMounted(() => {
  fetchMonthlySpentData()
  fetchMonthlyLeadsData()
})

watch([spentChartYear, chartBrandId], () => {
  fetchMonthlySpentData()
})

watch(chartBrandId, () => {
  fetchMonthlyLeadsData()
})

</script>