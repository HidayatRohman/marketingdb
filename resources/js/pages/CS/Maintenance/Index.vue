<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Wrench as WrenchIcon } from 'lucide-vue-next'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { ref, watch, onMounted, computed } from 'vue'
import { indonesianProvinces } from '@/lib/indonesianProvinces'
import { Dialog, DialogHeader, DialogTitle, DialogScrollContent, DialogDescription } from '@/components/ui/dialog'
import DropdownMenu from '@/components/ui/dropdown-menu/DropdownMenu.vue'
import DropdownMenuTrigger from '@/components/ui/dropdown-menu/DropdownMenuTrigger.vue'
import DropdownMenuContent from '@/components/ui/dropdown-menu/DropdownMenuContent.vue'
import DropdownMenuItem from '@/components/ui/dropdown-menu/DropdownMenuItem.vue'
import CsMaintenanceDailyChart from '@/components/CsMaintenanceDailyChart.vue'
import CsMaintenanceCategoryPieChart from '@/components/CsMaintenanceCategoryPieChart.vue'

interface Item {
  id: number
  nama_pelanggan: string
  no_tlp: string
  tanggal?: string
  product?: { id: number; nama: string } | null
  chat?: string
  kota?: string
  provinsi?: string
  kendala?: string
  solusi?: string
}

const props = defineProps<{
  items: {
    data: Item[]
    total: number
    per_page: number
    current_page: number
    last_page?: number
    prev_page_url?: string | null
    next_page_url?: string | null
    links?: Array<{ url: string | null; label: string; active: boolean }>
  }
  filters: { q?: string | null; product_id?: number | string | null }
  products: Array<{ id: number; nama: string }>
}>()

const inertiaPage = usePage() as any
const inertiaVersion = inertiaPage?.version || ''

const q = ref(props.filters.q || '')
const productId = ref(props.filters.product_id || '')

const pageLinks = computed(() => {
  const links = (props.items as any)?.links || []
  return Array.isArray(links) ? links : []
})
const numericLinks = computed(() => {
  const links = pageLinks.value || []
  return links.filter((ln: any) => ln && ln.label && /^\d+$/.test(String(ln.label)))
})
const totalPages = computed(() => Number((props.items as any)?.last_page || 1))
const prevUrl = computed(() => (props.items as any)?.prev_page_url || null)
const nextUrl = computed(() => (props.items as any)?.next_page_url || null)
const goTo = (url: string | null) => {
  if (!url) return
  router.visit(url, { preserveState: true, preserveScroll: true })
}
const goToPage = (page: number) => {
  const params: Record<string, any> = {}
  if (q.value) params.q = q.value
  if (productId.value) params.product_id = productId.value
  params.page = page
  router.get('/cs/maintenances', params, { preserveState: true, preserveScroll: true })
}

// Grafik: tanggal awal/akhir default bulan berjalan
const today = new Date()
const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0)
const startDate = ref(startOfMonth.toISOString().split('T')[0])
const endDate = ref(endOfMonth.toISOString().split('T')[0])
const chartLoading = ref(false)
const dailyData = ref<{ date: string; count: number }[]>([])

const fetchDaily = async () => {
  chartLoading.value = true
  try {
    const params = new URLSearchParams({
      start_date: startDate.value,
      end_date: endDate.value,
      product_id: productId.value ? String(productId.value) : ''
    })
    const res = await fetch(`/cs/maintenances/analytics/daily-count?${params}`)
    if (res.ok) {
      const json = await res.json()
      dailyData.value = Array.isArray(json.data) ? json.data : []
    }
  } catch (e) {
    // noop
  } finally {
    chartLoading.value = false
  }
}

// Grafik Kendala & Solusi
const kendalaLoading = ref(false)
const solusiLoading = ref(false)
const kendalaData = ref<Array<{ label: string; count: number; warna?: string }>>([])
const solusiData = ref<Array<{ label: string; count: number; warna?: string }>>([])
const kendalaOptions = ref<string[]>([])
const solusiOptions = ref<string[]>([])

// Pencarian untuk dropdown di modal Edit Kendala & Solusi (Mitra)
const mitraKendalaSearch = ref('')
const mitraSolusiSearch = ref('')
const filteredKendalasModal = computed(() => {
  const q = (mitraKendalaSearch.value || '').toLowerCase()
  return kendalaOptions.value.filter((k) => k.toLowerCase().includes(q))
})
const filteredSolusisModal = computed(() => {
  const q = (mitraSolusiSearch.value || '').toLowerCase()
  return solusiOptions.value.filter((s) => s.toLowerCase().includes(q))
})
const kendalaOpen = ref(false)
const solusiOpen = ref(false)
const editKendalaOpen = ref(false)
const editSolusiOpen = ref(false)
const editKendalaSearch = ref('')
const editSolusiSearch = ref('')
const filteredKendalasEdit = computed(() => {
  const q = (editKendalaSearch.value || '').toLowerCase()
  return (kendalaOptions.value || []).filter(k => k.toLowerCase().includes(q))
})
const filteredSolusisEdit = computed(() => {
  const q = (editSolusiSearch.value || '').toLowerCase()
  return (solusiOptions.value || []).filter(s => s.toLowerCase().includes(q))
})

type RepeatRow = {
  id?: number
  nama_pelanggan: string
  no_tlp: string
  bio_pelanggan?: string | null
  tanggal?: string
  product?: { id: number; nama: string } | null
  kota?: string
  provinsi?: string
  transaksi?: number
  keterangan?: string | null
  kendala?: string | null
  solusi?: string | null
  maintenance_tanggal?: string | null
  join_tanggal?: string | null
}
const repeatTableLoading = ref(false)
const repeatTableRows = ref<RepeatRow[]>([])
const repeatItems = ref<RepeatRow[]>([])
const repeatStartDate = ref('')
const repeatEndDate = ref('')
const repeatMinTransaksi = ref<string>('')
const repeatMaxTransaksi = ref<string>('')

const parseRupiahToNumber = (s: string): number => {
  const digits = String(s || '').replace(/\D/g, '')
  return digits ? Number(digits) : NaN
}

const formatRupiah = (n: number): string => {
  try {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0)
  } catch {
    return `Rp ${Math.round(n || 0).toLocaleString('id-ID')}`
  }
}

watch(repeatMinTransaksi, (val) => {
  const digits = String(val || '').replace(/\D/g, '')
  if (!digits) {
    if (val === '') return
    repeatMinTransaksi.value = ''
    return
  }
  const formatted = formatRupiah(Number(digits))
  if (val !== formatted) repeatMinTransaksi.value = formatted
})

watch(repeatMaxTransaksi, (val) => {
  const digits = String(val || '').replace(/\D/g, '')
  if (!digits) {
    if (val === '') return
    repeatMaxTransaksi.value = ''
    return
  }
  const formatted = formatRupiah(Number(digits))
  if (val !== formatted) repeatMaxTransaksi.value = formatted
})

const repeatFilteredItems = computed(() => {
  let list = Array.isArray(repeatItems.value) ? [...repeatItems.value] : []
  const start = repeatStartDate.value ? new Date(repeatStartDate.value) : null
  const end = repeatEndDate.value ? new Date(repeatEndDate.value) : null
  const min = parseRupiahToNumber(repeatMinTransaksi.value)
  const max = parseRupiahToNumber(repeatMaxTransaksi.value)

  if (start && !isNaN(start.getTime())) {
    list = list.filter(r => r.tanggal && new Date(r.tanggal).getTime() >= start.getTime())
  }
  if (end && !isNaN(end.getTime())) {
    list = list.filter(r => r.tanggal && new Date(r.tanggal).getTime() <= end.getTime())
  }
  if (!isNaN(min)) {
    list = list.filter(r => (r.transaksi || 0) >= min)
  }
  if (!isNaN(max)) {
    list = list.filter(r => (r.transaksi || 0) <= max)
  }

  const grouped = new Map<string, RepeatRow>()
  for (const r of list) {
    const phoneKey = (r.no_tlp || '').trim()
    const nameKey = (r.nama_pelanggan || '').trim().toLowerCase()
    const key = phoneKey || nameKey
    if (!key) continue
    const prev = grouped.get(key)
    const currTime = r.tanggal ? new Date(r.tanggal).getTime() : -1
    const prevTime = prev?.tanggal ? new Date(prev.tanggal).getTime() : -1
    if (!prev || currTime >= prevTime) grouped.set(key, r)
  }
  const result = Array.from(grouped.values())
  result.sort((a, b) => (a.transaksi || 0) - (b.transaksi || 0))
  return result
})
const mitraKey = (r: RepeatRow) => ((r.no_tlp || '').trim()) || ((r.nama_pelanggan || '').trim().toLowerCase())
const inactiveSet = ref<Set<string>>(new Set())
const loadInactive = () => {
  try {
    const raw = localStorage.getItem('mitraInactive')
    if (raw) inactiveSet.value = new Set(JSON.parse(raw))
  } catch {}
}
const saveInactive = () => {
  try {
    localStorage.setItem('mitraInactive', JSON.stringify(Array.from(inactiveSet.value)))
  } catch {}
}
const isInactive = (r: RepeatRow) => inactiveSet.value.has(mitraKey(r))
const toggleMitraActive = (r: RepeatRow) => {
  const key = mitraKey(r)
  if (!key) return
  if (inactiveSet.value.has(key)) inactiveSet.value.delete(key)
  else inactiveSet.value.add(key)
  saveInactive()
}
const inactiveRows = computed(() => repeatTableRows.value.filter((r) => isInactive(r)))
const activeRows = computed(() => repeatTableRows.value.filter((r) => !isInactive(r)))
const fetchRepeatTable = async () => {
  repeatTableLoading.value = true
  try {
    const params = new URLSearchParams()
    if (productId.value) params.append('product_id', String(productId.value))
    if (q.value) params.append('search', q.value)

    const fetchProps = async (url: string) => {
      const res = await fetch(`${url}?${params.toString()}`, {
        headers: {
          'X-Inertia': 'true',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-Inertia-Partial-Component': 'CS/Repeat/Index',
          'X-Inertia-Partial-Data': 'items',
          ...(inertiaVersion ? { 'X-Inertia-Version': inertiaVersion } : {}),
        }
      })
      if (!res.ok) return null
      try {
        const page = await res.json()
        // Support full Inertia response and partial-only (items) response
        const items = page?.props?.items?.data ?? page?.items?.data ?? page?.items ?? null
        return items
      } catch {
        return null
      }
    }

    let rows = await fetchProps('/cs/repeats')
    if (!rows) rows = await fetchProps('/repeats')
    repeatItems.value = Array.isArray(rows)
      ? rows.map((n: any) => ({
          id: Number(n?.id || 0),
          nama_pelanggan: String(n?.nama_pelanggan ?? n?.nama ?? ''),
          no_tlp: String(n?.no_tlp ?? ''),
          bio_pelanggan: n?.bio_pelanggan ?? null,
          tanggal: String(n?.tanggal ?? n?.created_at ?? ''),
          product: n?.product ? { id: Number(n.product.id), nama: String(n.product.nama) } : (n?.product_id ? { id: Number(n.product_id), nama: String(n?.product_nama ?? '-') } : null),
          kota: String(n?.kota ?? ''),
          provinsi: String(n?.provinsi ?? ''),
          transaksi: Number(n?.transaksi ?? 0),
          keterangan: n?.keterangan ?? null,
        }))
      : []

    // Build latest kendala/solusi map from maintenance items
    const latestMap: Record<string, { kendala?: string; solusi?: string; tanggal?: string }> = {}
    props.items.data.forEach((it) => {
      const key = ((it.no_tlp || '').trim()) || ((it.nama_pelanggan || '').trim().toLowerCase())
      const prev = latestMap[key]
      const curTime = it.tanggal ? new Date(it.tanggal).getTime() : 0
      const prevTime = prev?.tanggal ? new Date(prev.tanggal).getTime() : -1
      if (!prev || curTime >= prevTime) {
        latestMap[key] = { kendala: it.kendala, solusi: it.solusi, tanggal: it.tanggal }
      }
    })

    // Build earliest maintenance date map for join date (fix: first ever maintenance)
    const earliestMap: Record<string, { tanggal?: string }> = {}
    props.items.data.forEach((it) => {
      const key = ((it.no_tlp || '').trim()) || ((it.nama_pelanggan || '').trim().toLowerCase())
      const prev = earliestMap[key]
      const curTime = it.tanggal ? new Date(it.tanggal).getTime() : Number.POSITIVE_INFINITY
      const prevTime = prev?.tanggal ? new Date(prev.tanggal).getTime() : Number.POSITIVE_INFINITY
      if (!prev || curTime < prevTime) {
        if (it.tanggal) earliestMap[key] = { tanggal: it.tanggal }
      }
    })

    const mapped = Array.isArray(rows)
      ? rows.map((n: any, idx: number) => {
          const key = (String(n?.no_tlp || '').trim()) || (String(n?.nama_pelanggan ?? n?.nama ?? '').trim().toLowerCase())
          const latest = latestMap[key] || {}
          const earliest = earliestMap[key] || {}
          return ({
            id: Number(n?.id ?? idx + 1),
            nama_pelanggan: String(n?.nama_pelanggan ?? n?.nama ?? ''),
            no_tlp: String(n?.no_tlp ?? ''),
            bio_pelanggan: n?.bio_pelanggan ?? null,
            tanggal: String(n?.tanggal ?? n?.created_at ?? ''),
            product: n?.product ? { id: Number(n.product.id), nama: String(n.product.nama) } : (n?.product_id ? { id: Number(n.product_id), nama: String(n?.product_nama ?? '-') } : null),
            kota: String(n?.kota ?? ''),
            provinsi: String(n?.provinsi ?? ''),
            transaksi: Number(n?.transaksi ?? 0),
            keterangan: n?.keterangan ?? null,
            kendala: latest.kendala ?? null,
            solusi: latest.solusi ?? null,
            maintenance_tanggal: latest.tanggal ?? null,
            join_tanggal: earliest.tanggal ?? null,
          })
        })
      : []

    const grouped = new Map<string, RepeatRow>()
    for (const r of mapped) {
      const k = (r.nama_pelanggan || '').trim().toLowerCase()
      if (!k) continue
      const prev = grouped.get(k)
      const currTime = r.tanggal ? new Date(r.tanggal).getTime() : -1
      const prevTime = prev?.tanggal ? new Date(prev.tanggal).getTime() : -1
      if (!prev || currTime >= prevTime) {
        grouped.set(k, r)
      }
    }
    repeatTableRows.value = Array.from(grouped.values())
  } catch (e) {
    repeatTableRows.value = []
  } finally {
    repeatTableLoading.value = false
  }
}

// Earliest Repeat Order date per mitra across full history
const repeatJoinCache = new Map<string, string>()
const fetchEarliestRepeatDate = async (searchKey: string, targetPhone?: string, targetName?: string): Promise<string | null> => {
  if (!searchKey) return null
  if (repeatJoinCache.has(searchKey)) return repeatJoinCache.get(searchKey) || null
  const params = new URLSearchParams({
    search: searchKey,
    periode_start: '2000-01-01',
    periode_end: '2099-12-31',
    page: '1'
  })
  try {
    const fetchPage = async (pageNum: number) => {
      params.set('page', String(pageNum))
      const res = await fetch(`/cs/repeats?${params.toString()}`, {
        headers: {
          'X-Inertia': 'true',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-Inertia-Partial-Component': 'CS/Repeat/Index',
          'X-Inertia-Partial-Data': 'items',
          ...(inertiaVersion ? { 'X-Inertia-Version': inertiaVersion } : {}),
        }
      })
      if (!res.ok) return null
      const page = await res.json()
      const items = page?.props?.items ?? page?.items ?? null
      return items
    }
    const first = await fetchPage(1)
    if (!first || !Array.isArray(first.data)) {
      repeatJoinCache.set(searchKey, '')
      return null
    }
    let earliest: string | null = null
    const consider = (arr: any[]) => {
      for (const t of arr) {
        const phone = String(t?.no_tlp || '').trim()
        const name = String(t?.nama_pelanggan ?? t?.nama ?? '').trim().toLowerCase()
        const phoneMatch = targetPhone ? (phone === String(targetPhone).trim()) : false
        const nameMatch = !phoneMatch && targetName ? (name === String(targetName).trim().toLowerCase()) : false
        if (!phoneMatch && !nameMatch) continue
        const d = String(t?.tanggal ?? '')
        if (!d) continue
        const time = new Date(d).getTime()
        const prev = earliest ? new Date(earliest).getTime() : Number.POSITIVE_INFINITY
        if (!earliest || time < prev) earliest = d
      }
    }
    consider(first.data)
    const lastPage = Number(first.current_page && first.last_page ? first.last_page : first?.last_page ?? 1)
    for (let p = 2; p <= lastPage; p++) {
      const page = await fetchPage(p)
      if (page && Array.isArray(page.data)) consider(page.data)
    }
    repeatJoinCache.set(searchKey, earliest || '')
    return earliest || null
  } catch {
    return null
  }
}

const fillRepeatJoinDates = async () => {
  const tasks: Promise<void>[] = []
  for (const r of activeRows.value) {
    const key = (r.no_tlp || '').trim() || (r.nama_pelanggan || '').trim().toLowerCase()
    if (!key) continue
    tasks.push((async () => {
      const d = await fetchEarliestRepeatDate(key, r.no_tlp || '', (r.nama_pelanggan || '').trim().toLowerCase())
      if (d) r.join_tanggal = d
    })())
  }
  await Promise.allSettled(tasks)
}

const fetchKendala = async () => {
  kendalaLoading.value = true
  try {
    const params = new URLSearchParams({
      start_date: startDate.value,
      end_date: endDate.value,
      product_id: productId.value ? String(productId.value) : ''
    })
    const res = await fetch(`/cs/maintenances/analytics/kendala?${params}`)
    if (res.ok) {
      const json = await res.json()
      kendalaData.value = Array.isArray(json.data) ? json.data : []
    }
  } catch (e) {
    // noop
  } finally {
    kendalaLoading.value = false
  }
}

const fetchSolusi = async () => {
  solusiLoading.value = true
  try {
    const params = new URLSearchParams({
      start_date: startDate.value,
      end_date: endDate.value,
      product_id: productId.value ? String(productId.value) : ''
    })
    const res = await fetch(`/cs/maintenances/analytics/solusi?${params}`)
    if (res.ok) {
      const json = await res.json()
      solusiData.value = Array.isArray(json.data) ? json.data : []
    }
  } catch (e) {
    // noop
  } finally {
    solusiLoading.value = false
  }
}

const fetchKendalaOptions = async () => {
  try {
    const res = await fetch(`/kendalas`, {
      headers: {
        'X-Inertia': 'true',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-Inertia-Partial-Component': 'Kendalas/Index',
        'X-Inertia-Partial-Data': 'kendalas',
        ...(inertiaVersion ? { 'X-Inertia-Version': inertiaVersion } : {}),
      }
    })
    if (!res.ok) return
    const page = await res.json()
    const list = page?.props?.kendalas ?? page?.kendalas ?? []
    kendalaOptions.value = Array.isArray(list) ? list.map((k: any) => String(k?.nama || '')) : []
  } catch {}
}

const fetchSolusiOptions = async () => {
  try {
    const res = await fetch(`/solusis`, {
      headers: {
        'X-Inertia': 'true',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-Inertia-Partial-Component': 'Solusis/Index',
        'X-Inertia-Partial-Data': 'solusis',
        ...(inertiaVersion ? { 'X-Inertia-Version': inertiaVersion } : {}),
      }
    })
    if (!res.ok) return
    const page = await res.json()
    const list = page?.props?.solusis ?? page?.solusis ?? []
    solusiOptions.value = Array.isArray(list) ? list.map((s: any) => String(s?.nama || '')) : []
  } catch {}
}

watch([q, productId], () => {
  const params: Record<string, any> = {}
  if (q.value) params.q = q.value
  if (productId.value) params.product_id = productId.value
  router.get('/cs/maintenances', params, { preserveState: true, preserveScroll: true })
  fetchRepeatTable()
  setTimeout(() => fillRepeatJoinDates(), 0)
})

watch([startDate, endDate, productId], () => {
  fetchDaily()
  fetchKendala()
  fetchSolusi()
  fetchRepeatTable()
  setTimeout(() => fillRepeatJoinDates(), 0)
})

onMounted(() => {
  loadInactive()
  fetchDaily()
  fetchKendala()
  fetchSolusi()
  fetchKendalaOptions()
  fetchSolusiOptions()
  fetchRepeatTable()
  setTimeout(() => fillRepeatJoinDates(), 0)
})

const destroyItem = (id: number) => {
  if (!confirm('Hapus data ini?')) return
  router.delete(`/cs/maintenances/${id}`)
}

const formatDate = (d?: string) => {
  if (!d) return '-'
  const raw = String(d)
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    const [y, m, da] = raw.split('-').map((x) => parseInt(x, 10))
    const dd = String(da).padStart(2, '0')
    const mm = String(m).padStart(2, '0')
    const yyyy = String(y)
    return `${dd}/${mm}/${yyyy}`
  }
  const dt = new Date(raw)
  return isNaN(dt.getTime()) ? '-' : dt.toLocaleDateString('id-ID')
}

const formatWhatsAppNumber = (phoneNumber: string) => {
  let cleaned = String(phoneNumber).replace(/\D/g, '')
  if (cleaned.startsWith('0')) cleaned = '62' + cleaned.substring(1)
  if (!cleaned.startsWith('62')) cleaned = '62' + cleaned
  return cleaned
}

const formatCurrency = (value: number) => {
  try {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0)
  } catch {
    return `Rp ${Math.round(value || 0).toLocaleString('id-ID')}`
  }
}
const createWhatsAppUrl = (phoneNumber: string, message: string = '') => {
  const formattedNumber = formatWhatsAppNumber(phoneNumber)
  const encodedMessage = encodeURIComponent(message)
  return `https://wa.me/${formattedNumber}${message ? `?text=${encodedMessage}` : ''}`
}
const openWhatsApp = (phoneNumber: string, customerName: string) => {
  const message = `Halo ${customerName}, saya ingin menindaklanjuti mengenai ..... Anda.`
  const url = createWhatsAppUrl(phoneNumber, message)
  window.open(url, '_blank')
}

// View modal state & handlers (meniru Daftar Repeat Order)
const showView = ref(false)
const viewItem = ref<Item | null>(null)
const openView = (item: Item) => {
  viewItem.value = item
  showView.value = true
}
const closeView = () => {
  showView.value = false
  viewItem.value = null
}

const asTime = (d?: string) => {
  if (!d) return 0
  const x = new Date(d)
  return isNaN(x.getTime()) ? 0 : x.getTime()
}
const timelineEvents = computed<Item[]>(() => {
  const v = viewItem.value
  if (!v) return []
  const keyPhone = (v.no_tlp || '').trim()
  const keyName = (v.nama_pelanggan || '').trim().toLowerCase()
  return [...props.items.data]
    .filter((i) => {
      const samePhone = keyPhone && (i.no_tlp || '').trim() === keyPhone
      const sameName = keyName && (i.nama_pelanggan || '').trim().toLowerCase() === keyName
      return samePhone || sameName
    })
    .sort((a, b) => asTime(a.tanggal) - asTime(b.tanggal))
})

const toYMD = (input?: string | null): string => {
  if (!input) return ''
  const raw = String(input)
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw
  const d = new Date(raw)
  if (isNaN(d.getTime())) return ''
  const tzOffsetMs = d.getTimezoneOffset() * 60000
  const local = new Date(d.getTime() - tzOffsetMs)
  return local.toISOString().slice(0, 10)
}
const getTodayYMD = () => {
  const now = new Date()
  const tzOffsetMs = now.getTimezoneOffset() * 60000
  return new Date(now.getTime() - tzOffsetMs).toISOString().slice(0, 10)
}

const showEdit = ref(false)
const editForm = useForm({
  id: 0,
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  tanggal: '',
  chat: '',
  kota: '',
  provinsi: 'Unknown',
  kendala: '',
  solusi: '',
})
const openEdit = (item: Item) => {
  editForm.id = item.id
  editForm.nama_pelanggan = item.nama_pelanggan || ''
  editForm.no_tlp = item.no_tlp || ''
  editForm.product_id = item.product?.id ? String(item.product.id) : ''
  editForm.tanggal = toYMD(item.tanggal) || ''
  editForm.chat = item.chat || ''
  editForm.kota = item.kota || ''
  editForm.provinsi = item.provinsi || 'Unknown'
  editForm.kendala = item.kendala || ''
  editForm.solusi = item.solusi || ''
  showEdit.value = true
}
const submitEdit = () => {
  editForm.put(`/cs/maintenances/${editForm.id}` as any, {
    preserveScroll: true,
    onSuccess: () => {
      showEdit.value = false
    },
  })
}

const showMaintenance = ref(false)
const maintenanceForm = useForm({
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  tanggal: '',
  chat: '',
  kota: '',
  provinsi: 'Unknown',
  kendala: '',
  solusi: '',
})
const openMaintenanceFromView = (item: Item) => {
  maintenanceForm.nama_pelanggan = item.nama_pelanggan || ''
  maintenanceForm.no_tlp = item.no_tlp || ''
  maintenanceForm.product_id = item.product?.id ? String(item.product.id) : ''
  maintenanceForm.tanggal = getTodayYMD()
  maintenanceForm.chat = item.chat || ''
  maintenanceForm.kota = item.kota || ''
  maintenanceForm.provinsi = item.provinsi || 'Unknown'
  maintenanceForm.kendala = ''
  maintenanceForm.solusi = ''
  showMaintenance.value = true
}

// Mitra view/edit state & handlers
const showMitraView = ref(false)
const showMitraEdit = ref(false)
const mitraItem = ref<RepeatRow | null>(null)
const mitraEditForm = useForm({
  nama_pelanggan: '',
  no_tlp: '',
  bio_pelanggan: '',
  product_id: '',
  kota: '',
  provinsi: 'Unknown',
  kendala: '',
  solusi: '',
  tanggal: '',
  chat: '',
})
const mitraEditProductName = computed(() => {
  return mitraItem.value?.product?.nama || '-'
})
const openMitraView = (row: RepeatRow) => {
  mitraItem.value = row
  showMitraView.value = true
}
const openMitraEdit = (row: RepeatRow) => {
  mitraItem.value = row
  mitraEditForm.nama_pelanggan = row.nama_pelanggan || ''
  mitraEditForm.no_tlp = row.no_tlp || ''
  mitraEditForm.bio_pelanggan = String(row.bio_pelanggan || '')
  mitraEditForm.product_id = row.product?.id ? String(row.product.id) : ''
  mitraEditForm.kota = row.kota || ''
  mitraEditForm.provinsi = row.provinsi || 'Unknown'
  mitraEditForm.kendala = String(row.kendala || '')
  mitraEditForm.solusi = String(row.solusi || '')
  mitraEditForm.tanggal = toYMD(row.maintenance_tanggal || row.tanggal) || getTodayYMD()
  mitraEditForm.chat = ''
  showMitraEdit.value = true
}
const mitraTimelineEvents = computed<Item[]>(() => {
  const v = mitraItem.value
  if (!v) return []
  const keyPhone = (v.no_tlp || '').trim()
  const keyName = (v.nama_pelanggan || '').trim().toLowerCase()
  return [...props.items.data]
    .filter((i) => {
      const samePhone = keyPhone && (i.no_tlp || '').trim() === keyPhone
      const sameName = keyName && (i.nama_pelanggan || '').trim().toLowerCase() === keyName
      return samePhone || sameName
    })
    .sort((a, b) => asTime(a.tanggal) - asTime(b.tanggal))
})
const submitMitraEdit = () => {
  // Simpan sebagai entry CS Maintenance baru; setelah sukses, sinkronkan tampilan tabel mitra
  mitraEditForm.post('/cs/maintenances', {
    preserveScroll: true,
    onSuccess: () => {
      showMitraEdit.value = false
      const keyPhone = (mitraEditForm.no_tlp || '').trim()
      const keyName = (mitraEditForm.nama_pelanggan || '').trim().toLowerCase()
      repeatTableRows.value = repeatTableRows.value.map((r) => {
        const samePhone = keyPhone && (r.no_tlp || '').trim() === keyPhone
        const sameName = keyName && (r.nama_pelanggan || '').trim().toLowerCase() === keyName
        if (samePhone || sameName) {
          return {
            ...r,
            kendala: mitraEditForm.kendala || null,
            solusi: mitraEditForm.solusi || null,
            maintenance_tanggal: mitraEditForm.tanggal || r.maintenance_tanggal || null,
          }
        }
        return r
      })
      router.reload({ only: ['items'] })
    },
  })
}
const submitMaintenance = () => {
  maintenanceForm.post('/cs/maintenances', {
    preserveScroll: true,
    onSuccess: () => {
      showMaintenance.value = false
      maintenanceForm.reset()
    },
  })
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'CS Maintenance', href: '/cs/maintenances' },
]
</script>

<template>
  <Head title="CS Maintenance" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <div class="relative overflow-hidden rounded-xl border border-indigo-100 dark:border-indigo-900 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-950 dark:via-sky-950 dark:to-cyan-950 p-4 text-indigo-700 dark:text-indigo-300 sm:p-6">
        <div class="relative z-10">
          <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex-1">
              <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                <WrenchIcon class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                Manajemen CS Maintenance
              </h1>
              <p class="text-sm text-indigo-700/80 dark:text-indigo-300/80">Kelola data CS Maintenance secara konsisten dan rapi.</p>
            </div>
            <div class="flex items-center gap-2">
              <Button as-child variant="secondary" class="bg-white/60 hover:bg-white/70 dark:bg-gray-800/60 dark:hover:bg-gray-800/80 text-indigo-700 dark:text-indigo-300">
                <a href="/cs/maintenances/create">Tambah</a>
              </Button>
            </div>
          </div>
        </div>
      </div>
  <Card>
    <CardHeader class="border-b border-indigo-100/50 dark:border-indigo-900/50 bg-gradient-to-br from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-950 dark:via-sky-950 dark:to-cyan-950">
      <CardTitle class="dark:text-gray-100">Daftar Maintenance Mitra</CardTitle>
    </CardHeader>
    <CardContent>
        <div class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between mb-4">
          <div class="flex gap-2 items-center">
            <Input v-model="q" placeholder="Cari nama/kota/provinsi/kendala/solusi" class="w-64 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <select v-model="productId" class="h-9 rounded border px-2 bg-background border-input dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">Semua Produk</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
          </div>
        </div>
        <div v-if="repeatTableLoading" class="py-6 text-sm text-muted-foreground dark:text-gray-400">Memuat data dari Repeat Order…</div>
        <div v-else class="overflow-x-auto responsive-table">
        <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 px-2 sticky left-0 z-30 bg-gray-50 dark:bg-gray-800 min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-semibold">
                  <span class="sm:hidden">Nama</span>
                  <span class="hidden sm:inline">Nama Pelanggan</span>
                </th>
                <th class="py-2 px-2 min-w-[160px] sm:min-w-[240px] bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">No Tlp</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Bio Pelanggan</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Tanggal Join</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Produk</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Kota</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Provinsi</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Tanggal Maintenance</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Kendala</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Solusi</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in activeRows" :key="it.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="sticky left-0 z-20 bg-background dark:bg-gray-900 p-2 sm:p-3 font-medium text-xs sm:text-base min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">{{ it.nama_pelanggan }}</td>
                <td class="py-2 px-2 min-w-[160px] sm:min-w-[240px] text-gray-600 dark:text-gray-300">
                  <div class="flex items-center gap-2">
                    <div class="rounded bg-green-100 p-1 dark:bg-green-800">
                      <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
                      </svg>
                    </div>
                    <button
                      @click="openWhatsApp(it.no_tlp, it.nama_pelanggan)"
                      class="font-medium text-green-600 transition-colors duration-200 hover:text-green-800 hover:underline dark:text-green-400 dark:hover:text-green-300"
                      :title="`Hubungi ${it.nama_pelanggan} via WhatsApp`"
                    >
                      {{ it.no_tlp }}
                    </button>
                  </div>
                </td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.bio_pelanggan || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ formatDate(it.join_tanggal || '') }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.product?.nama || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.kota || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.provinsi || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ formatDate(it.maintenance_tanggal || '') }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.kendala || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.solusi || '-' }}</td>
                <td class="py-2 px-2">
                  <div class="flex gap-2">
                    <Button variant="secondary" @click="openMitraView(it)">View</Button>
                    <Button variant="outline" @click="openMitraEdit(it)">Edit</Button>
                    <button
                      type="button"
                      @click="toggleMitraActive(it)"
                      :class="[
                        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                        isInactive(it) ? 'bg-gray-300 dark:bg-gray-600' : 'bg-blue-500 dark:bg-blue-600'
                      ]"
                      aria-checked="false"
                      role="switch"
                    >
                      <span class="sr-only">Toggle aktif</span>
                      <span
                        aria-hidden="true"
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                        :class="isInactive(it) ? 'translate-x-0.5' : 'translate-x-5'"
                      />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="activeRows.length === 0">
                <td colspan="11" class="text-center py-6 text-muted-foreground dark:text-gray-400">Tidak ada data Repeat untuk ditampilkan</td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
    <Card>
      <CardHeader class="border-b border-red-100/50 dark:border-red-900/50 bg-red-50 dark:bg-red-950/30">
        <CardTitle class="dark:text-red-200">Daftar Mitra Tidak Aktif</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto responsive-table">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 px-2 sticky left-0 z-30 bg-background dark:bg-gray-900 min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-semibold">Nama Pelanggan</th>
                <th class="py-2 px-2 min-w-[160px] sm:min-w-[240px] text-gray-700 dark:text-gray-200 font-semibold">No Tlp</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Tanggal Join</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Produk</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Kota</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Provinsi</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Tanggal Maintenance</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Kendala</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Solusi</th>
                <th class="py-2 px-2 text-gray-700 dark:text-gray-200 font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in inactiveRows" :key="mitraKey(it)" class="border-b border-gray-200 dark:border-gray-700 bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                <td class="sticky left-0 z-20 bg-background dark:bg-gray-900 p-2 sm:p-3 font-medium text-xs sm:text-base min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">{{ it.nama_pelanggan }}</td>
                <td class="py-2 px-2 min-w-[160px] sm:min-w-[240px] text-gray-600 dark:text-gray-300">
                  <div class="flex items-center gap-2">
                    <div class="rounded bg-green-100 p-1 dark:bg-green-800">
                      <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
                      </svg>
                    </div>
                    <button
                      @click="openWhatsApp(it.no_tlp, it.nama_pelanggan)"
                      class="font-medium text-green-600 transition-colors duration-200 hover:text-green-800 hover:underline dark:text-green-400 dark:hover:text-green-300"
                      :title="`Hubungi ${it.nama_pelanggan} via WhatsApp`"
                    >
                      {{ it.no_tlp }}
                    </button>
                  </div>
                </td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ formatDate(it.join_tanggal || '') }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.product?.nama || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.kota || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.provinsi || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ formatDate(it.maintenance_tanggal || '') }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.kendala || '-' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.solusi || '-' }}</td>
                <td class="py-2 px-2">
                  <div class="flex gap-2">
                    <button
                      type="button"
                      @click="toggleMitraActive(it)"
                      :class="[
                        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                        isInactive(it) ? 'bg-gray-300 dark:bg-gray-600' : 'bg-blue-500 dark:bg-blue-600'
                      ]"
                      aria-checked="false"
                      role="switch"
                    >
                      <span class="sr-only">Toggle aktif</span>
                      <span
                        aria-hidden="true"
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                        :class="isInactive(it) ? 'translate-x-0.5' : 'translate-x-5'"
                      />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="inactiveRows.length === 0">
                <td colspan="10" class="text-center py-6 text-muted-foreground dark:text-gray-400">Tidak ada mitra tidak aktif</td>
              </tr>
            </tbody>
      </table>
    </div>
  </CardContent>
  </Card>

    <Card>
      <CardHeader>
        <CardTitle class="dark:text-gray-100">CS Maintenance</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between mb-4">
          <div class="flex gap-2 items-center">
            <Input v-model="q" placeholder="Cari nama/chat/kota/provinsi/kendala/solusi" class="w-64 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <select v-model="productId" class="h-9 rounded border px-2 bg-background border-input dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">Semua Produk</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
          </div>
          <Button as-child>
            <a href="/cs/maintenances/create">Tambah</a>
          </Button>
        </div>
        <div class="overflow-x-auto responsive-table">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                <th class="py-2 px-2 sticky left-0 z-30 bg-gray-50 dark:bg-gray-800 min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-semibold">
                  <span class="sm:hidden">Nama</span>
                  <span class="hidden sm:inline">Nama Pelanggan</span>
                </th>
                <th class="py-2 px-2 min-w-[160px] sm:min-w-[240px] bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">No Tlp</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Tanggal</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Produk</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Chat</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Kota</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Provinsi</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Kendala</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Solusi</th>
                <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in props.items.data" :key="it.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="sticky left-0 z-20 bg-background dark:bg-gray-900 p-2 sm:p-3 font-medium text-xs sm:text-base min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">{{ it.nama_pelanggan }}</td>
                <td class="py-2 px-2 min-w-[160px] sm:min-w-[240px] text-gray-600 dark:text-gray-300">
                  <div class="flex items-center gap-2">
                    <div class="rounded bg-green-100 p-1 dark:bg-green-800">
                      <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
                      </svg>
                    </div>
                    <button
                      @click="openWhatsApp(it.no_tlp, it.nama_pelanggan)"
                      class="font-medium text-green-600 transition-colors duration-200 hover:text-green-800 hover:underline dark:text-green-400 dark:hover:text-green-300"
                      :title="`Hubungi ${it.nama_pelanggan} via WhatsApp`"
                    >
                      {{ it.no_tlp }}
                    </button>
                  </div>
                </td>
                <td class="py-2 px-2">{{ formatDate(it.tanggal) }}</td>
                <td class="py-2 px-2">{{ it.product?.nama || '-' }}</td>
                <td class="py-2 px-2">{{ it.chat || '-' }}</td>
                <td class="py-2 px-2">{{ it.kota || '-' }}</td>
                <td class="py-2 px-2">{{ it.provinsi || '-' }}</td>
                <td class="py-2 px-2">{{ it.kendala || '-' }}</td>
                <td class="py-2 px-2">{{ it.solusi || '-' }}</td>
                <td class="py-2 px-2">
                  <div class="flex gap-2">
                    <Button variant="secondary" @click="openView(it)">View</Button>
                    <Button variant="outline" @click="openEdit(it)">Edit</Button>
                    <Button variant="destructive" @click="destroyItem(it.id)">Hapus</Button>
                  </div>
                </td>
              </tr>
              <tr v-if="props.items.data.length === 0">
                <td colspan="9" class="text-center py-6 text-muted-foreground dark:text-gray-400">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <div class="text-sm text-muted-foreground dark:text-gray-400">
            Total {{ props.items.total }} data • Hal {{ props.items.current_page }}
          </div>
          <nav aria-label="Pagination" class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!prevUrl"
              @click="goTo(prevUrl)"
            >Prev</button>

            <template v-if="pageLinks && pageLinks.length">
              <button
                v-for="(ln, idx) in numericLinks"
                :key="`${ln.label}-${idx}`"
                type="button"
                @click="goTo(ln.url)"
                :class="[
                  'inline-flex items-center rounded-md border px-3 py-1.5 text-sm',
                  ln.active
                    ? 'border-blue-200 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-800 text-blue-700 dark:text-blue-300 font-medium'
                    : 'border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
              >{{ ln.label }}</button>
            </template>
            <template v-else>
              <button
                v-for="p in totalPages"
                :key="p"
                type="button"
                @click="goToPage(p as number)"
                :class="[
                  'inline-flex items-center rounded-md border px-3 py-1.5 text-sm',
                  (props.items.current_page === p)
                    ? 'border-blue-200 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-800 text-blue-700 dark:text-blue-300 font-medium'
                    : 'border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
              >{{ p }}</button>
            </template>

            <button
              type="button"
              class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!nextUrl"
              @click="goTo(nextUrl)"
            >Next</button>
          </nav>
        </div>
      </CardContent>
  </Card>

    <!-- Report Grafik: ditempatkan di bawah CS Maintenance -->
    <Card>
      <CardHeader>
        <CardTitle class="dark:text-gray-100">Report Grafik (CS Maintenance)</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-4">
          <div class="flex items-center gap-2">
            <Input type="date" v-model="startDate" class="w-40 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <span class="text-sm text-muted-foreground dark:text-gray-400">s/d</span>
            <Input type="date" v-model="endDate" class="w-40 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <Button variant="outline" @click="fetchDaily" class="dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Terapkan</Button>
          </div>
          <div class="text-sm text-muted-foreground dark:text-gray-400" v-if="chartLoading">Memuat grafik…</div>
        </div>
        <CsMaintenanceDailyChart :data="dailyData" :startDate="startDate" :endDate="endDate" @refresh="fetchDaily" />
  </CardContent>
  </Card>

  <!-- Mitra Perhatian Maintenance dipindah tepat di bawah CS Maintenance dan di atas Report Grafik -->
  <Card class="mt-6">
    <CardHeader class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-indigo-100/50 dark:border-indigo-900/50 bg-gradient-to-br from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-950/40 dark:via-sky-950/40 dark:to-cyan-950/40">
      <CardTitle class="dark:text-gray-100">Mitra Perhatian Maintenance</CardTitle>
      <div class="flex w-full items-center gap-2 mt-1 sm:mt-0 sm:w-auto sm:justify-end">
        <div class="relative flex-1 sm:flex-none">
          <Input v-model="q" placeholder="Cari nama/no tlp" class="w-full sm:w-64 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
        </div>
        <input type="date" v-model="repeatStartDate" class="h-9 rounded border px-2 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" title="Tanggal mulai" />
        <input type="date" v-model="repeatEndDate" class="h-9 rounded border px-2 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" title="Tanggal selesai" />
        <Input v-model="repeatMinTransaksi" placeholder="Min Rp" class="w-28 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
        <Input v-model="repeatMaxTransaksi" placeholder="Max Rp" class="w-28 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
      </div>
    </CardHeader>
    <CardContent class="pt-4">
      <div class="overflow-x-auto responsive-table">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b border-gray-200 dark:border-gray-700">
              <th class="py-2 px-2 sticky left-0 z-30 bg-gray-50 dark:bg-gray-800 min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-semibold">
                <span class="sm:hidden">Nama</span>
                <span class="hidden sm:inline">Nama Pelanggan</span>
              </th>
              <th class="py-2 px-2 min-w-[160px] sm:min-w-[240px] bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">No Tlp</th>
              <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Bio Pelanggan</th>
              <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Tanggal</th>
              <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Produk</th>
              <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Kota</th>
              <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Provinsi</th>
              <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Transaksi</th>
              <th class="py-2 px-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="it in repeatFilteredItems" :key="it.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="sticky left-0 z-20 bg-background dark:bg-gray-900 p-2 sm:p-3 font-medium text-xs sm:text-base min-w-[120px] sm:min-w-[200px] border-r border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100">{{ it.nama_pelanggan }}</td>
              <td class="py-2 px-2 min-w-[160px] sm:min-w-[240px] text-gray-600 dark:text-gray-300">
                <div class="flex items-center gap-2">
                  <div class="rounded bg-green-100 p-1 dark:bg-green-800">
                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
                    </svg>
                  </div>
                  <button
                    @click="openWhatsApp(it.no_tlp, it.nama_pelanggan)"
                    class="font-medium text-green-600 transition-colors duration-200 hover:text-green-800 hover:underline dark:text-green-400 dark:hover:text-green-300"
                    :title="`Hubungi ${it.nama_pelanggan} via WhatsApp`"
                  >
                    {{ it.no_tlp }}
                  </button>
                </div>
              </td>
              <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.bio_pelanggan || '-' }}</td>
              <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ formatDate(it.tanggal) }}</td>
              <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.product?.nama || '-' }}</td>
              <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.kota || '-' }}</td>
              <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.provinsi || '-' }}</td>
              <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ formatCurrency(it.transaksi || 0) }}</td>
              <td class="py-2 px-2 text-gray-600 dark:text-gray-300">{{ it.keterangan || '-' }}</td>
            </tr>
            <tr v-if="repeatItems.length === 0">
              <td colspan="9" class="text-center py-6 text-muted-foreground dark:text-gray-400">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardContent>
  </Card>
    <!-- Grafik Kendala & Solusi dalam dua kolom pada desktop -->
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
      <CsMaintenanceCategoryPieChart
        :title="'Grafik Kendala'"
        :legendTitle="'Kendala'"
        :data="kendalaData"
        :loading="kendalaLoading"
        :startDate="startDate"
        :endDate="endDate"
        emptyMessage="Tidak ada data kendala untuk periode ini."
        @refresh="fetchKendala"
      />

      <CsMaintenanceCategoryPieChart
        :title="'Grafik Solusi'"
        :legendTitle="'Solusi'"
        :data="solusiData"
        :loading="solusiLoading"
        :startDate="startDate"
        :endDate="endDate"
        emptyMessage="Tidak ada data solusi untuk periode ini."
        @refresh="fetchSolusi"
      />
    </div>
    </div>

    <!-- Dialog View Detail CS Maintenance -->
    <Dialog :open="showView" @update:open="(v:boolean)=> showView = v">
      <DialogScrollContent class="sm:max-w-md dark:bg-gray-900 dark:border-gray-800">
        <DialogHeader class="bg-gradient-to-r from-indigo-600 via-sky-600 to-cyan-600 text-white rounded-t-md -mx-6 -mt-6 px-6 py-3">
          <DialogTitle>Detail CS Maintenance</DialogTitle>
        </DialogHeader>
        <div v-if="viewItem" class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-gray-900 dark:text-gray-100">Nama</div>
            <div class="col-span-2 font-medium text-gray-800 dark:text-gray-200">{{ viewItem.nama_pelanggan }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-gray-900 dark:text-gray-100">No Tlp</div>
            <div class="col-span-2">{{ viewItem.no_tlp }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-gray-900 dark:text-gray-100">Kota</div>
            <div class="col-span-2">{{ viewItem.kota || '-' }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-gray-900 dark:text-gray-100">Provinsi</div>
            <div class="col-span-2">{{ viewItem.provinsi || '-' }}</div>
          </div>

          <div v-if="timelineEvents.length >= 1" class="mt-6">
            <div class="text-sm font-semibold text-indigo-700 dark:text-indigo-300 border border-indigo-100/50 dark:border-indigo-900/50 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-900/40 dark:via-sky-900/30 dark:to-cyan-900/30 rounded-md px-3 py-2">Histori Maintenance</div>
            <div class="relative mt-3 pl-8 pr-2 max-h-64 overflow-y-auto">
              <div class="absolute left-3 top-0 h-full w-0.5 bg-indigo-200 dark:bg-indigo-800"></div>
              <div v-for="e in timelineEvents" :key="e.id" class="relative mb-4">
                <div class="absolute -left-4 top-1 h-3 w-3 rounded-full border-2 border-indigo-500 bg-white dark:bg-gray-900"></div>
                <div class="grid grid-cols-[120px_1fr] gap-3">
                  <div class="text-indigo-700 dark:text-indigo-300 font-semibold">{{ formatDate(e.tanggal) }}</div>
                  <div class="col-span-2">
                    <div class="text-xs"><span class="font-semibold text-gray-900 dark:text-gray-100">Kendala:</span> <span class="text-gray-600 dark:text-gray-400">{{ e.kendala || '-' }}</span></div>
                    <div class="text-xs"><span class="font-semibold text-gray-900 dark:text-gray-100">Solusi:</span> <span class="text-gray-600 dark:text-gray-400">{{ e.solusi || '-' }}</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <Button variant="outline" @click="closeView" class="dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700">Tutup</Button>
          <Button v-if="viewItem" @click="openEdit(viewItem as any)" class="dark:bg-indigo-600 dark:text-white dark:hover:bg-indigo-700">Edit</Button>
          <Button v-if="viewItem" variant="secondary" @click="openMaintenanceFromView(viewItem as any)" class="dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">Maintenance</Button>
        </div>
      </DialogScrollContent>
    </Dialog>

    <!-- Dialog Edit CS Maintenance -->
    <Dialog :open="showEdit" @update:open="(v:boolean)=> showEdit = v">
      <DialogScrollContent class="sm:max-w-xl dark:bg-gray-900 dark:border-gray-800">
        <DialogHeader>
          <DialogTitle class="dark:text-gray-100">Edit CS Maintenance</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Tanggal</label>
            <Input v-model="editForm.tanggal" type="date" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <div v-if="editForm.errors.tanggal" class="text-sm text-red-600 mt-1">{{ editForm.errors.tanggal }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Nama Pelanggan</label>
            <Input v-model="editForm.nama_pelanggan" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <div v-if="editForm.errors.nama_pelanggan" class="text-sm text-red-600 mt-1">{{ editForm.errors.nama_pelanggan }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">No Tlp</label>
            <Input v-model="editForm.no_tlp" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <div v-if="editForm.errors.no_tlp" class="text-sm text-red-600 mt-1">{{ editForm.errors.no_tlp }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Produk</label>
            <select v-model="editForm.product_id" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">-- Pilih Produk --</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
            <div v-if="editForm.errors.product_id" class="text-sm text-red-600 mt-1">{{ editForm.errors.product_id }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Chat</label>
            <select v-model="editForm.chat" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">-- Pilih Status Chat --</option>
              <option value="Baru">Baru</option>
              <option value="Follow Up">Follow Up</option>
              <option value="Follow Up 2">Follow Up 2</option>
              <option value="Followup 3">Followup 3</option>
            </select>
            <div v-if="editForm.errors.chat" class="text-sm text-red-600 mt-1">{{ editForm.errors.chat }}</div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kota</label>
              <Input v-model="editForm.kota" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
              <div v-if="editForm.errors.kota" class="text-sm text-red-600 mt-1">{{ editForm.errors.kota }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Provinsi</label>
              <select v-model="editForm.provinsi" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                <option v-for="province in indonesianProvinces" :key="province" :value="province">{{ province }}</option>
              </select>
              <div v-if="editForm.errors.provinsi" class="text-sm text-red-600 mt-1">{{ editForm.errors.provinsi }}</div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kendala</label>
            <DropdownMenu :open="editKendalaOpen" @update:open="(v:boolean)=> editKendalaOpen = v">
              <DropdownMenuTrigger :as-child="true">
                <button type="button" class="h-9 rounded border px-2 w-full flex items-center justify-between dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                  <span class="truncate">{{ editForm.kendala || '-- Pilih Kendala --' }}</span>
                  <svg class="h-4 w-4 opacity-60" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z"/></svg>
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent class="w-80 dark:bg-gray-800 dark:border-gray-700">
                <DropdownMenuItem @click="editForm.kendala = ''; editKendalaOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">-- Pilih Kendala --</DropdownMenuItem>
                <div class="px-2 py-1">
                  <Input
                    v-model="editKendalaSearch"
                    placeholder="Cari kendala"
                    @keydown.stop
                    @keyup.stop
                    @keypress.stop
                    @input.stop
                    class="dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
                  />
                </div>
                <div class="max-h-48 overflow-y-auto">
                  <DropdownMenuItem v-for="k in filteredKendalasEdit" :key="k" @click="editForm.kendala = k; editKendalaOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ k }}
                  </DropdownMenuItem>
                  <div v-if="filteredKendalasEdit.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada hasil</div>
                </div>
              </DropdownMenuContent>
            </DropdownMenu>
            <div v-if="editForm.errors.kendala" class="text-sm text-red-600 mt-1">{{ editForm.errors.kendala }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Solusi</label>
            <DropdownMenu :open="editSolusiOpen" @update:open="(v:boolean)=> editSolusiOpen = v">
              <DropdownMenuTrigger :as-child="true">
                <button type="button" class="h-9 rounded border px-2 w-full flex items-center justify-between dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                  <span class="truncate">{{ editForm.solusi || '-- Pilih Solusi --' }}</span>
                  <svg class="h-4 w-4 opacity-60" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z"/></svg>
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent class="w-80 dark:bg-gray-800 dark:border-gray-700">
                <DropdownMenuItem @click="editForm.solusi = ''; editSolusiOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">-- Pilih Solusi --</DropdownMenuItem>
                <div class="px-2 py-1">
                  <Input
                    v-model="editSolusiSearch"
                    placeholder="Cari solusi"
                    @keydown.stop
                    @keyup.stop
                    @keypress.stop
                    @input.stop
                    class="dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
                  />
                </div>
                <div class="max-h-48 overflow-y-auto">
                  <DropdownMenuItem v-for="s in filteredSolusisEdit" :key="s" @click="editForm.solusi = s; editSolusiOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ s }}
                  </DropdownMenuItem>
                  <div v-if="filteredSolusisEdit.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada hasil</div>
                </div>
              </DropdownMenuContent>
            </DropdownMenu>
            <div v-if="editForm.errors.solusi" class="text-sm text-red-600 mt-1">{{ editForm.errors.solusi }}</div>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showEdit = false" class="dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700">Batal</Button>
            <Button :disabled="editForm.processing" @click="submitEdit" class="dark:bg-indigo-600 dark:text-white dark:hover:bg-indigo-700">Simpan</Button>
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>

    <!-- Dialog Tambah Maintenance -->
    <Dialog :open="showMaintenance" @update:open="(v:boolean)=> showMaintenance = v">
      <DialogScrollContent class="sm:max-w-xl dark:bg-gray-900 dark:border-gray-800">
        <DialogHeader>
          <DialogTitle class="dark:text-gray-100">Tambah Histori Maintenance</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Tanggal</label>
            <Input v-model="maintenanceForm.tanggal" type="date" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <div v-if="maintenanceForm.errors.tanggal" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.tanggal }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Nama Pelanggan</label>
            <Input v-model="maintenanceForm.nama_pelanggan" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <div v-if="maintenanceForm.errors.nama_pelanggan" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.nama_pelanggan }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">No Tlp</label>
            <Input v-model="maintenanceForm.no_tlp" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <div v-if="maintenanceForm.errors.no_tlp" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.no_tlp }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Produk</label>
            <select v-model="maintenanceForm.product_id" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">-- Pilih Produk --</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
            <div v-if="maintenanceForm.errors.product_id" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.product_id }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Chat</label>
            <select v-model="maintenanceForm.chat" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">-- Pilih Status Chat --</option>
              <option value="Baru">Baru</option>
              <option value="Follow Up">Follow Up</option>
              <option value="Follow Up 2">Follow Up 2</option>
              <option value="Followup 3">Followup 3</option>
            </select>
            <div v-if="maintenanceForm.errors.chat" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.chat }}</div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kota</label>
              <Input v-model="maintenanceForm.kota" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
              <div v-if="maintenanceForm.errors.kota" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.kota }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Provinsi</label>
              <select v-model="maintenanceForm.provinsi" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                <option v-for="province in indonesianProvinces" :key="province" :value="province">{{ province }}</option>
              </select>
              <div v-if="maintenanceForm.errors.provinsi" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.provinsi }}</div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kendala</label>
            <select v-model="maintenanceForm.kendala" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">-- Pilih Kendala --</option>
              <option v-for="k in kendalaOptions" :key="k" :value="k">{{ k }}</option>
            </select>
            <div v-if="maintenanceForm.errors.kendala" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.kendala }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Solusi</label>
            <select v-model="maintenanceForm.solusi" class="h-9 rounded border px-2 w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">-- Pilih Solusi --</option>
              <option v-for="s in solusiOptions" :key="s" :value="s">{{ s }}</option>
            </select>
            <div v-if="maintenanceForm.errors.solusi" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.solusi }}</div>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showMaintenance = false" class="dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700">Batal</Button>
            <Button :disabled="maintenanceForm.processing" @click="submitMaintenance" class="dark:bg-indigo-600 dark:text-white dark:hover:bg-indigo-700">Simpan</Button>
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>
    <!-- Modals: Mitra View & Edit (khusus kartu Daftar Maintenance Mitra) -->
    <Dialog :open="showMitraView" @update:open="(v:boolean)=> showMitraView = v">
      <DialogScrollContent class="sm:max-w-md dark:bg-gray-900 dark:border-gray-800">
        <DialogHeader class="bg-gradient-to-r from-indigo-600 via-sky-600 to-cyan-600 text-white rounded-t-md -mx-6 -mt-6 px-6 py-3">
          <DialogTitle class="text-white">Detail CS Maintenance</DialogTitle>
        </DialogHeader>
        <div v-if="mitraItem" class="space-y-3 text-sm">
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black dark:text-gray-200">Nama</div>
            <div class="col-span-2 font-medium dark:text-gray-300">{{ mitraItem.nama_pelanggan }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black dark:text-gray-200">No Tlp</div>
            <div class="col-span-2 dark:text-gray-300">{{ mitraItem.no_tlp }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black dark:text-gray-200">Kota</div>
            <div class="col-span-2 dark:text-gray-300">{{ mitraItem.kota || '-' }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black dark:text-gray-200">Provinsi</div>
            <div class="col-span-2 dark:text-gray-300">{{ mitraItem.provinsi || '-' }}</div>
          </div>

          <div v-if="mitraTimelineEvents.length >= 1" class="mt-6">
            <div class="text-sm font-semibold text-indigo-700 dark:text-indigo-300 border border-indigo-100/50 dark:border-indigo-900/50 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-900/40 dark:via-sky-900/30 dark:to-cyan-900/30 rounded-md px-3 py-2">Histori Maintenance</div>
            <div class="relative mt-3 pl-8 pr-2 max-h-64 overflow-y-auto">
              <div class="absolute left-3 top-0 h-full w-0.5 bg-indigo-200 dark:bg-indigo-800"></div>
              <div v-for="e in mitraTimelineEvents" :key="e.id" class="relative mb-4">
                <div class="absolute -left-4 top-1 h-3 w-3 rounded-full border-2 border-indigo-500 bg-white dark:bg-gray-900"></div>
                <div class="grid grid-cols-[120px_1fr] gap-3">
                  <div class="text-indigo-700 dark:text-indigo-300 font-semibold">{{ formatDate(e.tanggal) }}</div>
                  <div class="col-span-2">
                    <div class="text-xs"><span class="font-semibold text-black dark:text-gray-200">Kendala:</span> <span class="text-gray-600 dark:text-gray-400">{{ e.kendala || '-' }}</span></div>
                    <div class="text-xs"><span class="font-semibold text-black dark:text-gray-200">Solusi:</span> <span class="text-gray-600 dark:text-gray-400">{{ e.solusi || '-' }}</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <Button variant="outline" @click="showMitraView = false" class="dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700">Tutup</Button>
          <Button v-if="mitraItem" @click="openMitraEdit(mitraItem as any)" class="dark:bg-indigo-600 dark:text-white dark:hover:bg-indigo-700">Edit</Button>
          <Button v-if="mitraItem" variant="secondary" @click="openMaintenanceFromView({
            id: 0,
            nama_pelanggan: mitraItem!.nama_pelanggan,
            no_tlp: mitraItem!.no_tlp,
            product: mitraItem!.product || null,
            tanggal: '',
            chat: '',
            kota: mitraItem!.kota || '',
            provinsi: mitraItem!.provinsi || 'Unknown',
            kendala: '',
            solusi: ''
          } as any)" class="dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">Maintenance</Button>
        </div>
      </DialogScrollContent>
    </Dialog>

    <Dialog :open="showMitraEdit" @update:open="(v:boolean)=> showMitraEdit = v">
      <DialogScrollContent class="sm:max-w-lg dark:bg-gray-900 dark:border-gray-800">
        <DialogHeader>
          <DialogTitle class="dark:text-gray-100">Edit Kendala & Solusi (Mitra)</DialogTitle>
          <DialogDescription class="dark:text-gray-400">Hanya Kendala dan Solusi yang dapat diedit. Data lain bersifat readonly.</DialogDescription>
        </DialogHeader>
        <div class="space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Tanggal Maintenance</label>
              <Input v-model="mitraEditForm.tanggal" type="date" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
              <div v-if="mitraEditForm.errors.tanggal" class="text-sm text-red-600 mt-1">{{ mitraEditForm.errors.tanggal }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Nama Pelanggan</label>
              <Input v-model="mitraEditForm.nama_pelanggan" readonly class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:bg-gray-900/50" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">No Tlp</label>
              <Input v-model="mitraEditForm.no_tlp" readonly class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:bg-gray-900/50" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Bio Pelanggan</label>
              <Input v-model="mitraEditForm.bio_pelanggan" readonly class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:bg-gray-900/50" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Produk</label>
              <Input :model-value="mitraEditProductName" readonly class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:bg-gray-900/50" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kota</label>
              <Input v-model="mitraEditForm.kota" readonly class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:bg-gray-900/50" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Provinsi</label>
              <Input v-model="mitraEditForm.provinsi" readonly class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:bg-gray-900/50" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kendala</label>
              <DropdownMenu :open="kendalaOpen" @update:open="(v:boolean)=> kendalaOpen = v">
                <DropdownMenuTrigger :as-child="true">
                  <button type="button" class="h-9 rounded border px-2 w-full flex items-center justify-between dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                    <span class="truncate">{{ mitraEditForm.kendala || '-- Pilih Kendala --' }}</span>
                    <svg class="h-4 w-4 opacity-60" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z"/></svg>
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-80 dark:bg-gray-800 dark:border-gray-700">
                  <DropdownMenuItem @click="mitraEditForm.kendala = ''; kendalaOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">-- Pilih Kendala --</DropdownMenuItem>
                  <div class="px-2 py-1">
                    <Input
                      v-model="mitraKendalaSearch"
                      placeholder="Cari kendala"
                      @keydown.stop
                      @keyup.stop
                      @keypress.stop
                      @input.stop
                      class="dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
                    />
                  </div>
                  <div class="max-h-48 overflow-y-auto">
                    <DropdownMenuItem v-for="k in filteredKendalasModal" :key="k" @click="mitraEditForm.kendala = k; kendalaOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">
                      {{ k }}
                    </DropdownMenuItem>
                    <div v-if="filteredKendalasModal.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada hasil</div>
                  </div>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1 dark:text-gray-200">Solusi</label>
              <DropdownMenu :open="solusiOpen" @update:open="(v:boolean)=> solusiOpen = v">
                <DropdownMenuTrigger :as-child="true">
                  <button type="button" class="h-9 rounded border px-2 w-full flex items-center justify-between dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                    <span class="truncate">{{ mitraEditForm.solusi || '-- Pilih Solusi --' }}</span>
                    <svg class="h-4 w-4 opacity-60" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z"/></svg>
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-80 dark:bg-gray-800 dark:border-gray-700">
                  <DropdownMenuItem @click="mitraEditForm.solusi = ''; solusiOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">-- Pilih Solusi --</DropdownMenuItem>
                  <div class="px-2 py-1">
                    <Input
                      v-model="mitraSolusiSearch"
                      placeholder="Cari solusi"
                      @keydown.stop
                      @keyup.stop
                      @keypress.stop
                      @input.stop
                      class="dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
                    />
                  </div>
                  <div class="max-h-48 overflow-y-auto">
                    <DropdownMenuItem v-for="s in filteredSolusisModal" :key="s" @click="mitraEditForm.solusi = s; solusiOpen = false" class="dark:text-gray-200 dark:hover:bg-gray-700">
                      {{ s }}
                    </DropdownMenuItem>
                    <div v-if="filteredSolusisModal.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada hasil</div>
                  </div>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showMitraEdit = false" class="dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700">Batal</Button>
            <Button :disabled="mitraEditForm.processing" @click="submitMitraEdit" class="dark:bg-indigo-600 dark:text-white dark:hover:bg-indigo-700">Simpan</Button>
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>

  </AppLayout>
  </template>

<style scoped>
.responsive-table {
  -webkit-overflow-scrolling: touch;
}
</style>
