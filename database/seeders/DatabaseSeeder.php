<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true
        ]);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'is_active' => true
        ]);

        $member1 = User::create([
            'name' => 'John Doe',
            'email' => 'member1@test.com',
            'password' => Hash::make('password123'),
            'role' => 'team_member',
            'is_active' => true
        ]);

        $member2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'member2@test.com',
            'password' => Hash::make('password123'),
            'role' => 'team_member',
            'is_active' => true
        ]);

        $member3 = User::create([
            'name' => 'Bob Johnson',
            'email' => 'member3@test.com',
            'password' => Hash::make('password123'),
            'role' => 'team_member',
            'is_active' => true
        ]);

        // Create teams
        $engineering = Team::create([
            'name' => 'Engineering',
            'created_by' => $manager->id
        ]);

        $marketing = Team::create([
            'name' => 'Marketing',
            'created_by' => $manager->id
        ]);

        $sales = Team::create([
            'name' => 'Sales',
            'created_by' => $admin->id
        ]);

        // Add members to teams
        $engineering->members()->attach([$member1->id, $member2->id], ['role' => 'member']);
        $engineering->members()->attach($manager->id, ['role' => 'lead']);
        
        $marketing->members()->attach($member3->id, ['role' => 'member']);
        $marketing->members()->attach($manager->id, ['role' => 'lead']);
        
        $sales->members()->attach($member1->id, ['role' => 'member']);
        $sales->members()->attach($admin->id, ['role' => 'lead']);

        // Create tasks
        Task::create([
            'title' => 'Setup database',
            'description' => 'Configure and set up the production database',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $member1->id,
            'created_by' => $manager->id,
            'team_id' => $engineering->id,
            'due_date' => now()->addDays(3)
        ]);

        Task::create([
            'title' => 'Write API documentation',
            'description' => 'Document all API endpoints using Swagger',
            'status' => 'pending',
            'priority' => 'medium',
            'assigned_to' => $member2->id,
            'created_by' => $manager->id,
            'team_id' => $engineering->id,
            'due_date' => now()->addDays(5)
        ]);

        Task::create([
            'title' => 'Fix login bug',
            'description' => 'Resolve authentication issue on mobile devices',
            'status' => 'completed',
            'priority' => 'high',
            'assigned_to' => $member1->id,
            'created_by' => $manager->id,
            'team_id' => $engineering->id,
            'due_date' => now()->subDays(2)
        ]);

        Task::create([
            'title' => 'Design dashboard',
            'description' => 'Create UI/UX design for analytics dashboard',
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => $member3->id,
            'created_by' => $manager->id,
            'team_id' => $marketing->id,
            'due_date' => now()->addDays(7)
        ]);
    }
}