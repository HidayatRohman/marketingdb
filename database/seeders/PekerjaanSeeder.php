<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pekerjaan;

class PekerjaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('📚 Seeding daftar pekerjaan di Indonesia...');

        // Daftar pekerjaan dikelompokkan per kategori dengan warna label agar rapi di UI
        $categories = [
            [
                'label' => 'Teknologi Informasi',
                'color' => '#3B82F6',
                'items' => [
                    'Software Engineer', 'Frontend Developer', 'Backend Developer', 'Full Stack Developer', 'Mobile Developer',
                    'DevOps Engineer', 'Cloud Engineer', 'Data Scientist', 'Data Analyst', 'Machine Learning Engineer',
                    'UI/UX Designer', 'Product Manager TI', 'QA Engineer', 'System Administrator', 'Network Engineer',
                    'Cybersecurity Analyst', 'Database Administrator', 'IT Support', 'IT Consultant', 'Scrum Master',
                ],
            ],
            [
                'label' => 'Keuangan',
                'color' => '#10B981',
                'items' => [
                    'Akuntan', 'Auditor', 'Financial Analyst', 'Tax Consultant', 'Staf Keuangan',
                    'Analis Kredit', 'Investment Analyst', 'Budget Analyst', 'Risk Analyst', 'Actuary',
                    'Teller Bank', 'Customer Service Bank', 'Manajer Keuangan', 'Manajer Akuntansi',
                ],
            ],
            [
                'label' => 'Kesehatan',
                'color' => '#EF4444',
                'items' => [
                    'Dokter Umum', 'Dokter Spesialis Penyakit Dalam', 'Dokter Spesialis Anak', 'Dokter Kandungan', 'Dokter Gigi',
                    'Perawat', 'Bidan', 'Apoteker', 'Asisten Apoteker', 'Ahli Gizi',
                    'Fisioterapis', 'Radiografer', 'Analis Kesehatan', 'Psikolog Klinis', 'Petugas Laboratorium',
                    'Rekam Medis', 'Sanitarian', 'Tenaga Kesehatan Lingkungan',
                ],
            ],
            [
                'label' => 'Pendidikan',
                'color' => '#F59E0B',
                'items' => [
                    'Guru PAUD', 'Guru TK', 'Guru SD', 'Guru SMP', 'Guru SMA',
                    'Guru Matematika', 'Guru Bahasa Indonesia', 'Guru Bahasa Inggris', 'Guru IPA', 'Dosen',
                    'Tutor', 'Kepala Sekolah', 'Staf Akademik', 'Pengembang Kurikulum',
                ],
            ],
            [
                'label' => 'Pemerintahan & Hukum',
                'color' => '#8B5CF6',
                'items' => [
                    'Pegawai Negeri Sipil (PNS)', 'ASN', 'Analis Kebijakan', 'Staf Administrasi Pemerintah', 'Lurah', 'Camat',
                    'Hakim', 'Jaksa', 'Pengacara', 'Notaris', 'PPAT', 'Polisi', 'TNI', 'Satpol PP', 'Petugas Imigrasi', 'Petugas Bea Cukai',
                ],
            ],
            [
                'label' => 'Media & Kreatif',
                'color' => '#EC4899',
                'items' => [
                    'Editor', 'Jurnalis', 'Reporter', 'Fotografer', 'Videografer',
                    'Content Creator', 'Social Media Specialist', 'Copywriter', 'Penulis', 'Desainer Grafis',
                    'Animator', 'Art Director', 'Creative Director', 'Produser', 'Sutradara', 'Public Relations',
                ],
            ],
            [
                'label' => 'Perhotelan & Pariwisata',
                'color' => '#06B6D4',
                'items' => [
                    'Resepsionis', 'Housekeeping', 'Chef', 'Koki', 'Barista', 'Bartender', 'Pramusaji', 'Bellboy', 'Front Office',
                    'Tour Guide', 'Travel Consultant', 'Ticketing',
                ],
            ],
            [
                'label' => 'Ritel & Penjualan',
                'color' => '#F97316',
                'items' => [
                    'Sales', 'Sales Executive', 'Sales Supervisor', 'Sales Manager', 'Account Executive', 'Account Manager',
                    'Business Development', 'Merchandiser', 'Store Manager', 'Pramuniaga', 'Kasir', 'Customer Service',
                ],
            ],
            [
                'label' => 'Logistik & Transportasi',
                'color' => '#84CC16',
                'items' => [
                    'Supir', 'Supir Truk', 'Supir Bus', 'Kurir', 'Petugas Gudang', 'Staff Logistik', 'Dispatcher', 'Operator Forklift',
                    'Inventory Controller', 'Ekspedisi', 'Petugas Pelabuhan', 'Petugas Bandara',
                ],
            ],
            [
                'label' => 'Konstruksi & Properti',
                'color' => '#10B981',
                'items' => [
                    'Arsitek', 'Insinyur Sipil', 'Mandor', 'Tukang Bangunan', 'Tukang Kayu', 'Tukang Las',
                    'Surveyor', 'Estimator', 'Quantity Surveyor', 'Drafter', 'Project Manager Konstruksi', 'Pengawas Konstruksi',
                ],
            ],
            [
                'label' => 'Manufaktur',
                'color' => '#6B7280',
                'items' => [
                    'Operator Produksi', 'Quality Control', 'QA Inspector', 'Teknisi Mesin', 'Maintenance', 'PPIC',
                    'R&D Engineer', 'Process Engineer', 'Supervisor Produksi', 'Manager Pabrik', 'Operator CNC', 'Planner Produksi',
                ],
            ],
            [
                'label' => 'Energi & Pertambangan',
                'color' => '#374151',
                'items' => [
                    'Insinyur Pertambangan', 'Geolog', 'Petugas K3', 'Operator Alat Berat', 'Insinyur Perminyakan', 'Drilling Engineer',
                    'Reservoir Engineer', 'Field Operator', 'Teknisi Listrik', 'Inspektor K3',
                ],
            ],
            [
                'label' => 'Pertanian, Kehutanan & Perikanan',
                'color' => '#22C55E',
                'items' => [
                    'Petani', 'Pekebun', 'Peternak', 'Nelayan', 'Penyuluh Pertanian', 'Agronomist', 'Ahli Hortikultura',
                    'Teknisi Perikanan', 'Teknisi Peternakan', 'Manajer Kebun', 'Mandor Kebun', 'Pengolah Hasil Pertanian',
                ],
            ],
            [
                'label' => 'UMKM & Layanan',
                'color' => '#0EA5E9',
                'items' => [
                    'Wirausaha', 'Pengusaha', 'Pemilik Toko', 'Pemilik Warung', 'Tukang Cukur', 'Montir', 'Tukang Kayu', 'Tukang Las',
                    'Tukang Listrik', 'Teknisi AC', 'Teknisi Elektronik', 'Penjahit', 'Perias', 'Event Planner', 'Wedding Organizer',
                    'Penerjemah', 'Sekretaris', 'Admin', 'HRD', 'Office Boy', 'Satpam', 'Cleaning Service',
                ],
            ],
            [
                'label' => 'Seni & Hiburan',
                'color' => '#A855F7',
                'items' => [
                    'Musisi', 'Penyanyi', 'Aktor', 'Aktris', 'Komedian', 'Koreografer', 'Penari', 'Penulis Skenario', 'Influencer', 'MC',
                ],
            ],
            [
                'label' => 'Ilmu Pengetahuan & Riset',
                'color' => '#3B82F6',
                'items' => [
                    'Peneliti', 'Analis Data', 'Ilmuwan', 'Asisten Peneliti', 'Lab Assistant', 'Research Assistant', 'Statistisi',
                ],
            ],
            [
                'label' => 'Agama & Sosial',
                'color' => '#F59E0B',
                'items' => [
                    'Ustadz', 'Pendeta', 'Pastor', 'Biksu', 'Pekerja Sosial', 'Relawan', 'Konselor', 'Penyuluh Sosial',
                ],
            ],
            [
                'label' => 'Administrasi & Perkantoran',
                'color' => '#6B7280',
                'items' => [
                    'Sekretaris', 'Staf Administrasi', 'Admin HR', 'Admin GA', 'Staf Operasional', 'Receptionist',
                    'Data Entry', 'Document Controller', 'Office Manager', 'Customer Support',
                ],
            ],
        ];

        $totalInserted = 0;
        foreach ($categories as $category) {
            foreach ($category['items'] as $nama) {
                Pekerjaan::firstOrCreate(
                    ['nama' => $nama],
                    ['warna' => $category['color']]
                );
                $totalInserted++;
            }
        }

        $this->command?->info("✅ Pekerjaan seeded: {$totalInserted} entries");
    }
}