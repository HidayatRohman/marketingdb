<?php

namespace Database\Seeders;

use App\Models\TodoList;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestTodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        // Clear existing todos for clean test
        TodoList::where('user_id', $user->id)->delete();

        $today = Carbon::today();
        
        // Create todos for current month to test calendar
        $todos = [
            // Today's todos
            [
                'title' => 'Meeting dengan Tim Marketing',
                'description' => 'Diskusi strategi pemasaran Q4',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => $today->format('Y-m-d'),
                'due_time' => '09:00',
            ],
            [
                'title' => 'Review Campaign Results',
                'description' => 'Analisis performa campaign bulan ini',
                'priority' => 'medium',
                'status' => 'in_progress',
                'due_date' => $today->format('Y-m-d'),
                'due_time' => '14:00',
            ],
            
            // Tomorrow's todos
            [
                'title' => 'Presentasi ke Klien',
                'description' => 'Presentasi proposal marketing untuk klien baru',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => $today->copy()->addDay()->format('Y-m-d'),
                'due_time' => '10:00',
            ],
            [
                'title' => 'Update Website Content',
                'description' => 'Update konten halaman utama website',
                'priority' => 'low',
                'status' => 'pending',
                'due_date' => $today->copy()->addDay()->format('Y-m-d'),
                'due_time' => '16:00',
            ],
            
            // Next few days
            [
                'title' => 'Social Media Planning',
                'description' => 'Plan konten social media untuk minggu depan',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => $today->copy()->addDays(2)->format('Y-m-d'),
                'due_time' => '11:00',
            ],
            [
                'title' => 'Budget Review',
                'description' => 'Review budget marketing untuk bulan depan',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => $today->copy()->addDays(3)->format('Y-m-d'),
                'due_time' => '13:00',
            ],
            [
                'title' => 'Email Campaign Setup',
                'description' => 'Setup email campaign untuk produk baru',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => $today->copy()->addDays(5)->format('Y-m-d'),
                'due_time' => '15:00',
            ],
            
            // Completed tasks
            [
                'title' => 'Market Research',
                'description' => 'Research kompetitor untuk strategi pricing',
                'priority' => 'medium',
                'status' => 'completed',
                'due_date' => $today->copy()->subDay()->format('Y-m-d'),
                'due_time' => '09:00',
            ],
            [
                'title' => 'Content Calendar Update',
                'description' => 'Update calendar konten untuk bulan ini',
                'priority' => 'low',
                'status' => 'completed',
                'due_date' => $today->copy()->subDays(2)->format('Y-m-d'),
                'due_time' => '14:00',
            ],
            
            // Multiple todos for one day to test "lainnya" feature
            [
                'title' => 'Task A - Test Multiple',
                'description' => 'Test task A untuk hari yang sama',
                'priority' => 'low',
                'status' => 'pending',
                'due_date' => $today->copy()->addDays(7)->format('Y-m-d'),
                'due_time' => '09:00',
            ],
            [
                'title' => 'Task B - Test Multiple',
                'description' => 'Test task B untuk hari yang sama',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => $today->copy()->addDays(7)->format('Y-m-d'),
                'due_time' => '11:00',
            ],
            [
                'title' => 'Task C - Test Multiple',
                'description' => 'Test task C untuk hari yang sama',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => $today->copy()->addDays(7)->format('Y-m-d'),
                'due_time' => '13:00',
            ],
            [
                'title' => 'Task D - Test Multiple',
                'description' => 'Test task D untuk hari yang sama',
                'priority' => 'low',
                'status' => 'in_progress',
                'due_date' => $today->copy()->addDays(7)->format('Y-m-d'),
                'due_time' => '15:00',
            ],
        ];

        foreach ($todos as $todoData) {
            TodoList::create([
                'user_id' => $user->id,
                'assigned_to' => null,
                'tags' => ['marketing', 'test'],
                ...$todoData
            ]);
        }

        $this->command->info('Test todos created successfully!');
        $this->command->info('Created ' . count($todos) . ' todos for user: ' . $user->name);
        $this->command->info('Date range: ' . $today->copy()->subDays(2)->format('Y-m-d') . ' to ' . $today->copy()->addDays(7)->format('Y-m-d'));
    }
}
