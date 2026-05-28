<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Mitra;
use App\Models\Sumber;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BrandOwnerDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BrandOwnerSeeder::class,
            MarketingSeeder::class,
            SumberSeeder::class,
        ]);

        $brandOwner = User::where('email', 'bo@marketingdb.com')->first();
        $marketing = User::where('email', 'marketing@marketingdb.com')->first()
            ?? User::where('role', 'marketing')->orderBy('id')->first();

        if (! $brandOwner || ! $marketing) {
            $this->command?->error('Brand owner atau marketing user tidak ditemukan.');
            return;
        }

        $brandA = Brand::firstOrCreate(['nama' => 'BO Demo - Brand Alpha'], ['logo' => null]);
        $brandB = Brand::firstOrCreate(['nama' => 'BO Demo - Brand Beta'], ['logo' => null]);

        $brandOwner->brands()->syncWithoutDetaching([$brandA->id, $brandB->id]);

        $mitras = [];
        $mitraRows = [
            [
                'nama' => 'BO Demo Mitra 1',
                'no_telp' => '081234567801',
                'brand_id' => $brandA->id,
                'kota' => 'Jakarta Selatan',
                'provinsi' => 'DKI Jakarta',
            ],
            [
                'nama' => 'BO Demo Mitra 2',
                'no_telp' => '081234567802',
                'brand_id' => $brandA->id,
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
            ],
            [
                'nama' => 'BO Demo Mitra 3',
                'no_telp' => '081234567803',
                'brand_id' => $brandB->id,
                'kota' => 'Surabaya',
                'provinsi' => 'Jawa Timur',
            ],
            [
                'nama' => 'BO Demo Mitra 4',
                'no_telp' => '081234567804',
                'brand_id' => $brandB->id,
                'kota' => 'Medan',
                'provinsi' => 'Sumatera Utara',
            ],
        ];

        foreach ($mitraRows as $row) {
            $mitras[] = Mitra::updateOrCreate(
                ['nama' => $row['nama']],
                [
                    'no_telp' => $row['no_telp'],
                    'brand_id' => $row['brand_id'],
                    'user_id' => $marketing->id,
                    'chat' => 'followup',
                    'kota' => $row['kota'],
                    'provinsi' => $row['provinsi'],
                    'komentar' => 'Data contoh untuk Brand Owner',
                    'tanggal_lead' => Carbon::now()->subDays(14)->format('Y-m-d'),
                ]
            );
        }

        $sumberIds = Sumber::select('id', 'nama')->get()->keyBy('nama');
        $sumberMap = [
            'IG' => $sumberIds['Instagram']->id ?? null,
            'FB' => $sumberIds['Facebook']->id ?? null,
            'WA' => $sumberIds['WhatsApp']->id ?? null,
            'Tiktok' => $sumberIds['TikTok']->id ?? null,
            'Web' => $sumberIds['Website']->id ?? null,
            'Google' => $sumberIds['Google Ads']->id ?? null,
        ];

        Transaksi::where('nama_paket', 'like', 'BO Demo - %')->delete();

        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $sources = ['IG', 'FB', 'WA', 'Tiktok', 'Web', 'Google'];
        $statuses = ['Dp / TJ', 'Tambahan Dp', 'Pelunasan'];
        $amounts = [1000000, 1500000, 2500000, 3000000, 5000000, 7500000];

        for ($i = 1; $i <= 12; $i++) {
            $tfDate = Carbon::now()->subDays(30 - ($i * 2));
            $leadDate = $tfDate->copy()->subDays(5);
            $periode = $bulanMap[(int) $tfDate->format('n')] ?? 'Januari';
            $source = $sources[($i - 1) % count($sources)];
            $status = $statuses[($i - 1) % count($statuses)];
            $amount = $amounts[($i - 1) % count($amounts)];
            $brandPick = ($i % 2 === 0) ? $brandA : $brandB;
            $mitraPick = $mitras[($i - 1) % count($mitras)];

            $payload = [
                'user_id' => $marketing->id,
                'tanggal_tf' => $tfDate->format('Y-m-d'),
                'tanggal_lead_masuk' => $leadDate->format('Y-m-d'),
                'periode_lead' => $periode,
                'no_wa' => $mitraPick->no_telp,
                'usia' => 28 + ($i % 10),
                'nama_mitra' => $mitraPick->nama,
                'paket_brand_id' => $brandPick->id,
                'lead_awal_brand_id' => $brandPick->id,
                'sumber' => $source,
                'kabupaten' => $mitraPick->kota,
                'provinsi' => $mitraPick->provinsi,
                'status_pembayaran' => $status,
                'nominal_masuk' => $amount,
                'harga_paket' => $amount,
                'nama_paket' => "BO Demo - Paket {$i}",
            ];

            if (Schema::hasColumn('transaksis', 'mitra_id')) {
                $payload['mitra_id'] = $mitraPick->id;
            }

            if (Schema::hasColumn('transaksis', 'sumber_id')) {
                $payload['sumber_id'] = $sumberMap[$source] ?? null;
            }

            Transaksi::create($payload);
        }

        $this->command?->info('✅ BrandOwnerDemoDataSeeder: Brand + Mitra + Transaksi demo untuk bo@marketingdb.com berhasil dibuat.');
    }
}
