<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Brand;
use App\Models\Mitra;
use App\Models\Sumber;
use Carbon\Carbon;

class TransaksiSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk membuat 5 transaksi dengan nominal sesuai permintaan.
     */
    public function run(): void
    {
        // Ambil user marketing (atau user pertama jika tidak ada marketing)
        $userId = User::where('role', 'marketing')->value('id') ?? User::value('id');

        // Pastikan ada Brand dan Mitra
        $brandIds = Brand::pluck('id')->toArray();
        $mitra = Mitra::first();

        if (!$userId || empty($brandIds) || !$mitra) {
            $this->command?->error('Data wajib (user/brand/mitra) belum tersedia. Jalankan seeder terkait terlebih dahulu.');
            return;
        }

        $paketBrandId = $brandIds[0];
        $leadAwalBrandId = $brandIds[1] ?? $paketBrandId;

        // Peta nama bulan Indonesia untuk periode_lead
        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // Peta sumber -> sumber_id berdasarkan SumberSeeder yang ada
        $sumberMap = [
            'IG' => Sumber::where('nama', 'Instagram')->value('id'),
            'FB' => Sumber::where('nama', 'Facebook')->value('id'),
            'WA' => Sumber::where('nama', 'WhatsApp')->value('id'),
            'Web' => Sumber::where('nama', 'Website')->value('id'),
            'Google' => Sumber::where('nama', 'Google Ads')->value('id'),
        ];

        $now = Carbon::now();

        // Definisi 5 transaksi sesuai nominal: 1jt x2, 3jt x2, 5jt x1
        $rows = [
            ['nominal_masuk' => 1000000, 'sumber' => 'IG',     'status_pembayaran' => 'Dp / TJ'],
            ['nominal_masuk' => 1000000, 'sumber' => 'FB',     'status_pembayaran' => 'Tambahan Dp'],
            ['nominal_masuk' => 3000000, 'sumber' => 'Web',    'status_pembayaran' => 'Pelunasan'],
            ['nominal_masuk' => 3000000, 'sumber' => 'WA',     'status_pembayaran' => 'Pelunasan'],
            ['nominal_masuk' => 5000000, 'sumber' => 'Google', 'status_pembayaran' => 'Pelunasan'],
        ];

        foreach ($rows as $i => $row) {
            $tfDate = $now->copy()->subDays(4 - $i);
            $leadDate = $tfDate->copy()->subDays(3);
            $periode = $bulanMap[(int) $tfDate->format('n')] ?? $bulanMap[(int) $now->format('n')];

            Transaksi::create([
                'user_id' => $userId,
                // Tetap isi mitra_id untuk kompatibilitas SQLite (kolom masih ada)
                'mitra_id' => $mitra->id,
                'nama_mitra' => $mitra->nama,
                'tanggal_tf' => $tfDate->format('Y-m-d'),
                'tanggal_lead_masuk' => $leadDate->format('Y-m-d'),
                'periode_lead' => $periode,
                'no_wa' => $mitra->no_telp ?? '081234567890',
                'usia' => 30,
                'paket_brand_id' => $paketBrandId,
                'lead_awal_brand_id' => $leadAwalBrandId,
                'sumber' => $row['sumber'],
                'sumber_id' => $sumberMap[$row['sumber']] ?? null,
                'kabupaten' => $mitra->kota ?? 'Jakarta Selatan',
                'provinsi' => $mitra->provinsi ?? 'DKI Jakarta',
                'status_pembayaran' => $row['status_pembayaran'],
                'nominal_masuk' => $row['nominal_masuk'],
                'harga_paket' => $row['nominal_masuk'],
                'nama_paket' => 'Paket ' . ($i + 1),
            ]);
        }

        $this->command?->info('✅ TransaksiSeeder: 5 transaksi berhasil dibuat.');
    }
}