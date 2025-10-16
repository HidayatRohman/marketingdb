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
            <Card class="border-0 shadow-md mx-auto max-w-7xl">
                <CardHeader class="pb-2 px-4 sm:px-6">
                    <CardTitle class="flex items-center gap-2 text-lg font-semibold">
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
                    <p class="text-sm text-muted-foreground">Ringkasan performa budget marketing vs omset per brand</p>
                </CardHeader>
                <CardContent class="p-6">
                    <!-- Filter Section -->
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <label for="month-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Bulan:</label>
                            <select 
                                id="month-filter" 
                                v-model="selectedMonth" 
                                @change="filterByMonthYear"
                                class="w-full sm:w-auto px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Semua Bulan</option>
                                <option value="01">Januari</option>
                                <option value="02">Februari</option>
                                <option value="03">Maret</option>
                                <option value="04">April</option>
                                <option value="05">Mei</option>
                                <option value="06">Juni</option>
                                <option value="07">Juli</option>
                                <option value="08">Agustus</option>
                                <option value="09">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <label for="year-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Tahun:</label>
                            <select 
                                id="year-filter" 
                                v-model="selectedYear" 
                                @change="filterByMonthYear"
                                class="w-full sm:w-auto px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Semua Tahun</option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                            </select>
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
                                    <th class="text-left p-3 font-semibold">Brand</th>
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
                                    <td class="p-3 font-medium text-blue-600">{{ item.brand }}</td>
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
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
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
                                            <span class="font-medium text-red-700">{{ formatCurrency((Number(budget.spent_amount) || 0) * ppnMultiplier) }}</span>
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
                                            <span v-if="budget.spent_amount > 0 && budget.omset > 0" class="font-medium text-indigo-600">{{ (budget.omset / budget.spent_amount).toFixed(2) }}</span>
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

            <!-- Grafik Spent Bulanan -->
            <Card class="border-0 shadow-md">
                <CardHeader class="px-4 py-3 pb-2 bg-gradient-to-r from-indigo-50 via-sky-50 to-blue-50 dark:from-indigo-900 dark:via-sky-900 dark:to-blue-900 rounded-t-lg border-b border-indigo-100 dark:border-indigo-800">
                    <CardTitle class="flex items-center gap-2 text-indigo-800 dark:text-indigo-100">
                        <BarChart3 class="h-6 w-6" />
                        Grafik Spent Bulanan
                    </CardTitle>
                    <div class="mt-2 flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label for="spent-year" class="text-sm font-medium text-gray-700 dark:text-gray-300">Tahun:</label>
                            <select 
                                id="spent-year" 
                                v-model="spentChartYear" 
                                @change="fetchMonthlySpentData"
                                class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="yr in [2023, 2024, 2025, 2026]" :key="yr" :value="yr">{{ yr }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="spent-brand" class="text-sm font-medium text-gray-700 dark:text-gray-300">Brand:</label>
                            <select 
                                id="spent-brand"
                                v-model="chartBrandId"
                                @change="fetchMonthlySpentData"
                                class="w-48 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
import MonthlySpentChart from '@/components/MonthlySpentChart.vue'
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
  brands: Array<{ id: number; nama: string }>
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = computed(() => [
  { label: 'Dashboard', href: '/' },
  { label: 'Budget Iklan', href: '/iklan-budgets' }
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

// Report Budget Vs Omset variables
const selectedMonth = ref('')
const selectedYear = ref('')

// Summary Report computed property
const summaryReport = computed(() => {
  let filteredData = props.iklanBudgets.data

  // Filter by month and year if selected
  if (selectedMonth.value || selectedYear.value) {
    filteredData = filteredData.filter(budget => {
      const budgetDate = new Date(budget.tanggal)
      const budgetMonth = String(budgetDate.getMonth() + 1).padStart(2, '0')
      const budgetYear = String(budgetDate.getFullYear())

      const monthMatch = !selectedMonth.value || budgetMonth === selectedMonth.value
      const yearMatch = !selectedYear.value || budgetYear === selectedYear.value

      return monthMatch && yearMatch
    })
  }

  // Group by brand
  const brandGroups = filteredData.reduce((acc, budget) => {
    const brandName = budget.brand?.nama || 'Unknown'
    if (!acc[brandName]) {
      acc[brandName] = {
        brand: brandName,
        spent: 0,
        spent_with_tax: 0,
        real_lead: 0,
        closing: 0,
        omset: 0,
        cost_per_lead: 0,
        roas: 0
      }
    }

    acc[brandName].spent += Number(budget.spent_amount) || 0
    acc[brandName].spent_with_tax += (Number(budget.spent_amount) || 0) * ppnMultiplier.value
    acc[brandName].real_lead += Number(budget.real_lead) || 0
    acc[brandName].closing += Number(budget.closing) || 0
    acc[brandName].omset += Number(budget.omset) || 0

    return acc
  }, {} as Record<string, any>)

  // Calculate derived metrics for each brand
  return Object.values(brandGroups).map((group: any) => {
    group.cost_per_lead = group.real_lead > 0 ? group.spent / group.real_lead : 0
    group.roas = group.spent > 0 ? group.omset / group.spent : 0
    return group
  })
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

onMounted(() => {
  fetchMonthlySpentData()
})

watch([spentChartYear, chartBrandId], () => {
  fetchMonthlySpentData()
})

</script>