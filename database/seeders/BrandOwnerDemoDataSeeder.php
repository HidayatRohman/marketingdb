<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\IklanBudget;
use App\Models\Label;
use App\Models\Mitra;
use App\Models\Pekerjaan;
use App\Models\Seminar;
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
            LabelSeeder::class,
            SumberSeeder::class,
            PekerjaanSeeder::class,
        ]);

        $brandOwner = User::where('email', 'bo@marketingdb.com')->first();
        $marketing = User::where('email', 'marketing@marketingdb.com')->first()
            ?? User::where('role', 'marketing')->orderBy('id')->first();
        $marketingIds = User::where('role', 'marketing')->pluck('id')->all();

        if (! $brandOwner || ! $marketing) {
            $this->command?->error('Brand owner atau marketing user tidak ditemukan.');
            return;
        }

        $brandA = Brand::firstOrCreate(['nama' => 'BO Demo - Brand Alpha'], ['logo' => null]);
        $brandB = Brand::firstOrCreate(['nama' => 'BO Demo - Brand Beta'], ['logo' => null]);
        $brandC = Brand::firstOrCreate(['nama' => 'BO Demo - Brand Gamma (Not Owned)'], ['logo' => null]);

        $brandOwner->brands()->syncWithoutDetaching([$brandA->id, $brandB->id]);

        $closingLabelId = Label::where('nama', 'Closing')->value('id');
        $labelIds = Label::pluck('id')->all();

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
            [
                'nama' => 'BO Demo Mitra X',
                'no_telp' => '081234567899',
                'brand_id' => $brandC->id,
                'kota' => 'Denpasar',
                'provinsi' => 'Bali',
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
                    'label_id' => $closingLabelId,
                ]
            );
        }

        $analysisLeadCount = 60;
        for ($i = 1; $i <= $analysisLeadCount; $i++) {
            $tanggalLead = Carbon::now()->subDays(($i * 2) % 28)->format('Y-m-d');
            $brandPick = ($i % 3 === 0) ? $brandC : (($i % 2 === 0) ? $brandA : $brandB);
            $marketingPickId = $marketingIds ? $marketingIds[($i - 1) % count($marketingIds)] : $marketing->id;
            $chatPick = ($i % 2 === 0) ? 'masuk' : 'followup';
            $labelPick = null;
            if (Schema::hasColumn('mitras', 'label_id') && !empty($labelIds)) {
                $labelPick = ($i % 5 === 0) ? $closingLabelId : $labelIds[($i - 1) % count($labelIds)];
            }

            Mitra::updateOrCreate(
                ['nama' => "BO Demo AB Lead {$i}"],
                [
                    'no_telp' => '081260000' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'brand_id' => $brandPick->id,
                    'user_id' => $marketingPickId,
                    'chat' => $chatPick,
                    'kota' => 'Jakarta',
                    'provinsi' => 'DKI Jakarta',
                    'komentar' => 'Data contoh untuk analisa-bisnis',
                    'tanggal_lead' => $tanggalLead,
                    'label_id' => $labelPick,
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
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $sources = ['IG', 'FB', 'WA', 'Tiktok', 'Web', 'Google'];
        $statuses = ['Dp / TJ', 'Tambahan Dp', 'Pelunasan'];
        $amounts = [1000000, 1500000, 2500000, 3000000, 5000000, 7500000];
        $pekerjaanIds = Pekerjaan::pluck('id')->all();

        for ($i = 1; $i <= 24; $i++) {
            $tfDate = Carbon::now()->subDays(30 - ($i * 2));
            $leadDate = $tfDate->copy()->subDays(5);
            $periode = $bulanMap[(int) $tfDate->format('n')] ?? 'Januari';
            $source = $sources[($i - 1) % count($sources)];
            $status = $statuses[($i - 1) % count($statuses)];
            $amount = $amounts[($i - 1) % count($amounts)];
            $brandPick = ($i % 3 === 0) ? $brandC : (($i % 2 === 0) ? $brandA : $brandB);
            $mitraPick = $mitras[($i - 1) % count($mitras)];

            $paketBrandId = $brandPick->id;
            $leadAwalBrandId = $brandPick->id;
            if ($i % 4 === 0) {
                $paketBrandId = $brandA->id;
                $leadAwalBrandId = $brandC->id;
            } elseif ($i % 5 === 0) {
                $paketBrandId = $brandC->id;
                $leadAwalBrandId = $brandB->id;
            }

            $payload = [
                'user_id' => $marketing->id,
                'tanggal_tf' => $tfDate->format('Y-m-d'),
                'tanggal_lead_masuk' => $leadDate->format('Y-m-d'),
                'periode_lead' => $periode,
                'no_wa' => $mitraPick->no_telp,
                'usia' => 28 + ($i % 10),
                'nama_mitra' => $mitraPick->nama,
                'pekerjaan_id' => (Schema::hasColumn('transaksis', 'pekerjaan_id') && !empty($pekerjaanIds))
                    ? $pekerjaanIds[($i - 1) % count($pekerjaanIds)]
                    : null,
                'paket_brand_id' => $paketBrandId,
                'lead_awal_brand_id' => $leadAwalBrandId,
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

        $today = Carbon::now()->startOfDay();
        for ($d = 0; $d < 14; $d++) {
            $tanggal = $today->copy()->subDays($d)->format('Y-m-d');
            $spentA = 100000 + ($d * 10000);
            $spentB = 120000 + ($d * 8000);
            $spentC = 90000 + ($d * 5000);

            IklanBudget::updateOrCreate(
                ['tanggal' => $tanggal, 'brand_id' => $brandA->id],
                ['budget_amount' => 500000, 'spent_amount' => $spentA]
            );
            IklanBudget::updateOrCreate(
                ['tanggal' => $tanggal, 'brand_id' => $brandB->id],
                ['budget_amount' => 500000, 'spent_amount' => $spentB]
            );
            IklanBudget::updateOrCreate(
                ['tanggal' => $tanggal, 'brand_id' => $brandC->id],
                ['budget_amount' => 500000, 'spent_amount' => $spentC]
            );
        }

        $yearCursor = Carbon::now()->startOfMonth();
        for ($m = 0; $m < 12; $m++) {
            $tanggal = $yearCursor->copy()->subMonths($m)->startOfMonth()->format('Y-m-d');
            $mul = 1 + ($m * 0.2);

            IklanBudget::updateOrCreate(
                ['tanggal' => $tanggal, 'brand_id' => $brandA->id],
                ['budget_amount' => 12000000, 'spent_amount' => (int) round(800000 * $mul)]
            );
            IklanBudget::updateOrCreate(
                ['tanggal' => $tanggal, 'brand_id' => $brandB->id],
                ['budget_amount' => 12000000, 'spent_amount' => (int) round(950000 * $mul)]
            );
            IklanBudget::updateOrCreate(
                ['tanggal' => $tanggal, 'brand_id' => $brandC->id],
                ['budget_amount' => 12000000, 'spent_amount' => (int) round(650000 * $mul)]
            );
        }

        if (Schema::hasColumn('mitras', 'webinar')) {
            $webinarStart = Carbon::now()->subMonths(11)->startOfMonth();
            $cursor = $webinarStart->copy();
            $idx = 1;
            while ($cursor->lte(Carbon::now()->endOfMonth())) {
                $date = $cursor->copy()->addDays(3)->format('Y-m-d');

                $webinarMitraRows = [
                    [
                        'nama' => "BO Demo Webinar - A{$idx}",
                        'no_telp' => '081230000' . str_pad((string) $idx, 2, '0', STR_PAD_LEFT),
                        'brand_id' => $brandA->id,
                    ],
                    [
                        'nama' => "BO Demo Webinar - B{$idx}",
                        'no_telp' => '081240000' . str_pad((string) $idx, 2, '0', STR_PAD_LEFT),
                        'brand_id' => $brandB->id,
                    ],
                    [
                        'nama' => "BO Demo Webinar - X{$idx}",
                        'no_telp' => '081250000' . str_pad((string) $idx, 2, '0', STR_PAD_LEFT),
                        'brand_id' => $brandC->id,
                    ],
                ];

                foreach ($webinarMitraRows as $row) {
                    Mitra::updateOrCreate(
                        ['nama' => $row['nama']],
                        [
                            'no_telp' => $row['no_telp'],
                            'brand_id' => $row['brand_id'],
                            'user_id' => $marketing->id,
                            'chat' => 'followup',
                            'kota' => 'Jakarta',
                            'provinsi' => 'DKI Jakarta',
                            'komentar' => 'Data contoh peserta seminar/webinar',
                            'tanggal_lead' => $date,
                            'webinar' => 'Ikut',
                        ]
                    );
                }

                $idx++;
                $cursor->addMonth();
            }
        }

        if (Schema::hasTable('seminars')) {
            $seminarRows = [
                [
                    'judul' => 'BO Demo Seminar: Growth & Sales Funnel',
                    'tanggal' => Carbon::now()->subDays(10)->format('Y-m-d'),
                    'lokasi' => 'Jakarta',
                    'deskripsi' => 'Seminar demo untuk melihat tampilan halaman seminar.',
                ],
                [
                    'judul' => 'BO Demo Seminar: Optimasi Iklan & ROAS',
                    'tanggal' => Carbon::now()->addDays(7)->format('Y-m-d'),
                    'lokasi' => 'Bandung',
                    'deskripsi' => 'Materi iklan, tracking, dan evaluasi performa.',
                ],
                [
                    'judul' => 'BO Demo Seminar: CRM & Follow Up',
                    'tanggal' => Carbon::now()->addDays(21)->format('Y-m-d'),
                    'lokasi' => 'Surabaya',
                    'deskripsi' => 'Pengelolaan leads, follow up, dan closing.',
                ],
                [
                    'judul' => 'BO Demo Seminar: Branding & Positioning',
                    'tanggal' => Carbon::now()->subMonths(2)->addDays(5)->format('Y-m-d'),
                    'lokasi' => 'Online',
                    'deskripsi' => 'Workshop singkat strategi branding.',
                ],
                [
                    'judul' => 'BO Demo Seminar: Analisa Bisnis',
                    'tanggal' => Carbon::now()->subMonths(4)->addDays(12)->format('Y-m-d'),
                    'lokasi' => 'Online',
                    'deskripsi' => 'Cara membaca laporan dan pengambilan keputusan.',
                ],
            ];

            foreach ($seminarRows as $row) {
                Seminar::updateOrCreate(
                    ['judul' => $row['judul']],
                    [
                        'tanggal' => $row['tanggal'],
                        'lokasi' => $row['lokasi'],
                        'deskripsi' => $row['deskripsi'],
                    ]
                );
            }
        }

        $this->command?->info('✅ BrandOwnerDemoDataSeeder: Brand + Mitra + Transaksi + IklanBudget + Seminar demo untuk bo@marketingdb.com berhasil dibuat.');
    }
}
