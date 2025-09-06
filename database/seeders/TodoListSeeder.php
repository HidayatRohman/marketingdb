<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TodoList;
use App\Models\User;
use Carbon\Carbon;

class TodoListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $sampleTodos = [
            [
                'title' => 'Follow up leads dari social media',
                'description' => 'Menghubungi potential customers yang menunjukkan interest di Instagram dan Facebook',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => Carbon::today(),
                'due_time' => '09:00:00',
            ],
            [
                'title' => 'Buat konten marketing untuk produk baru',
                'description' => 'Membuat design poster dan video promosi untuk launching produk terbaru',
                'priority' => 'medium',
                'status' => 'in_progress',
                'due_date' => Carbon::today()->addDay(),
                'due_time' => '14:00:00',
            ],
            [
                'title' => 'Analisis performa campaign bulan lalu',
                'description' => 'Review ROI, engagement rate, dan conversion rate dari campaign marketing bulan sebelumnya',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => Carbon::today()->addDays(2),
                'due_time' => '10:30:00',
            ],
            [
                'title' => 'Meeting dengan tim kreatif',
                'description' => 'Diskusi konsep campaign untuk Q4 dan brainstorming ide-ide fresh',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => Carbon::today()->addDays(3),
                'due_time' => '15:00:00',
            ],
            [
                'title' => 'Update database customer',
                'description' => 'Memperbarui informasi kontak dan segmentasi customer untuk campaign mendatang',
                'priority' => 'low',
                'status' => 'completed',
                'due_date' => Carbon::yesterday(),
                'due_time' => '11:00:00',
            ],
            [
                'title' => 'Buat laporan mingguan marketing',
                'description' => 'Kompilasi data performance marketing untuk dilaporkan ke management',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => Carbon::today()->addWeek(),
                'due_time' => '16:00:00',
            ],
            [
                'title' => 'Koordinasi dengan influencer',
                'description' => 'Menghubungi dan negosiasi kerjasama dengan micro-influencer lokal',
                'priority' => 'high',
                'status' => 'in_progress',
                'due_date' => Carbon::today()->addDays(5),
                'due_time' => '13:30:00',
            ],
            [
                'title' => 'Optimasi SEO website',
                'description' => 'Review dan update keywords, meta description, dan content untuk meningkatkan ranking',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => Carbon::today()->addWeek()->addDays(2),
                'due_time' => '09:30:00',
            ],
        ];

        foreach ($sampleTodos as $todo) {
            // Assign random user
            $user = $users->random();
            $assignedUser = $users->random();
            
            TodoList::create([
                'title' => $todo['title'],
                'description' => $todo['description'],
                'priority' => $todo['priority'],
                'status' => $todo['status'],
                'due_date' => $todo['due_date'],
                'due_time' => $todo['due_time'],
                'user_id' => $user->id,
                'assigned_to' => rand(0, 1) ? $assignedUser->id : null,
                'tags' => ['marketing', 'campaign', 'urgent']
            ]);
        }

        $this->command->info('TodoList seeder completed successfully!');
    }
}
