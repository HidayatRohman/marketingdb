<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * Seeder dijalankan dalam urutan yang tepat untuk menghindari foreign key constraint errors.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive database seeding...');
        
        // 1. User seeding (must be first - required by other tables)
        $this->command->info('📊 Seeding users...');
        $this->call([
            SuperAdminSeeder::class,
            AdminSeeder::class, 
            MarketingSeeder::class,
            CSSeeder::class,
        ]);

        // 2. Master data seeding (independent tables)
        $this->command->info('🏷️  Seeding master data...');
        $this->call([
            LabelSeeder::class,
            BrandSeeder::class,
            PekerjaanSeeder::class,
        ]);

        // 3. Main business data (depends on users, brands, labels)
        $this->command->info('🤝 Seeding business data...');
        $this->call([
            MitraSeeder::class,
            IklanBudgetSeeder::class,
        ]);

        // 4. Task management data (depends on users)
        $this->command->info('📝 Seeding task management...');
        $this->call([
            TodoListSeeder::class,
        ]);

        // 5. System settings and configurations
        $this->command->info('⚙️  Seeding system settings...');
        $this->call([
            SiteSettingsSeeder::class,
        ]);

        // 6. System cleanup (optional - cleans old data)
        $this->command->info('🧹 Running system cleanup...');
        $this->call([
            SystemTablesSeeder::class,
        ]);

        // 7. Additional test data (optional - only in development)
        if (app()->environment(['local', 'development'])) {
            $this->command->info('🧪 Seeding additional test data...');
            // Uncomment if you have additional test seeders
            // $this->call([
            //     TestTodoSeeder::class,
            //     DashboardDataSeeder::class,
            // ]);
        }

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->line('');
        $this->command->info('📈 Summary:');
        $this->command->info('  • Users: ' . \App\Models\User::count() . ' records');
        $this->command->info('  • Brands: ' . \App\Models\Brand::count() . ' records');
        $this->command->info('  • Labels: ' . \App\Models\Label::count() . ' records');
        $this->command->info('  • Pekerjaans: ' . \App\Models\Pekerjaan::count() . ' records');
        $this->command->info('  • Mitras: ' . \App\Models\Mitra::count() . ' records');
        $this->command->info('  • TodoLists: ' . \App\Models\TodoList::count() . ' records');
        $this->command->line('');
        $this->command->info('🎉 Ready to use! You can now login with the seeded users.');
        $this->command->info('   Super Admin: Check SuperAdminSeeder for credentials');
        $this->command->info('   Admin: Check AdminSeeder for credentials');  
        $this->command->info('   Marketing: Check MarketingSeeder for credentials');
        $this->command->info('   CS: Check CSSeeder for credentials');
    }
}
