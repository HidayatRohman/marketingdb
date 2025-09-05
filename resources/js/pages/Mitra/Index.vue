<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, MoreHorizontal, Eye, Edit, Trash2 } from 'lucide-vue-next';
import { defineProps } from 'vue';

interface Mitra {
    id: number;
    nama: string;
    no_telp: string;
    produk: string;
    chat: 'masuk' | 'followup';
    kota: string;
    provinsi: string;
    transaksi: number | null;
    komentar: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginatedMitras {
    data: Mitra[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

const props = defineProps<{
    mitras: PaginatedMitras;
}>();

const deleteMitra = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus mitra ini?')) {
        router.delete(`/mitras/${id}`);
    }
};

const formatCurrency = (amount: number | null) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getChatBadgeVariant = (chat: string) => {
    return chat === 'masuk' ? 'default' : 'secondary';
};
</script>

<template>
    <Head title="Mitra" />

    <AppLayout>
        <AppContent variant="sidebar">
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Mitra</h1>
                        <p class="text-muted-foreground">
                            Kelola data mitra bisnis Anda
                        </p>
                    </div>
                    <Button as-child>
                        <Link href="/mitras/create">
                            <Plus class="mr-2 h-4 w-4" />
                            Tambah Mitra
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Mitra</CardTitle>
                        <CardDescription>
                            Total: {{ mitras.total }} mitra
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>No Telp</TableHead>
                                        <TableHead>Produk</TableHead>
                                        <TableHead>Chat</TableHead>
                                        <TableHead>Lokasi</TableHead>
                                        <TableHead>Transaksi</TableHead>
                                        <TableHead class="w-[70px]">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="mitra in mitras.data" :key="mitra.id">
                                        <TableCell class="font-medium">
                                            {{ mitra.nama }}
                                        </TableCell>
                                        <TableCell>{{ mitra.no_telp }}</TableCell>
                                        <TableCell>{{ mitra.produk }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="getChatBadgeVariant(mitra.chat)">
                                                {{ mitra.chat }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ mitra.kota }}, {{ mitra.provinsi }}</TableCell>
                                        <TableCell>{{ formatCurrency(mitra.transaksi) }}</TableCell>
                                        <TableCell>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button variant="ghost" class="h-8 w-8 p-0">
                                                        <MoreHorizontal class="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem as-child>
                                                        <Link :href="`/mitras/${mitra.id}`">
                                                            <Eye class="mr-2 h-4 w-4" />
                                                            Lihat
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem as-child>
                                                        <Link :href="`/mitras/${mitra.id}/edit`">
                                                            <Edit class="mr-2 h-4 w-4" />
                                                            Edit
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        @click="deleteMitra(mitra.id)"
                                                        class="text-destructive"
                                                    >
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        Hapus
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="mitras.last_page > 1" class="mt-4 flex items-center justify-between">
                            <p class="text-sm text-muted-foreground">
                                Menampilkan {{ mitras.from }} sampai {{ mitras.to }} dari {{ mitras.total }} hasil
                            </p>
                            <div class="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="mitras.current_page === 1"
                                    as-child
                                >
                                    <Link :href="`/mitras?page=${mitras.current_page - 1}`">
                                        Previous
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="mitras.current_page === mitras.last_page"
                                    as-child
                                >
                                    <Link :href="`/mitras?page=${mitras.current_page + 1}`">
                                        Next
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppContent>
    </AppLayout>
</template>
