<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'talzonne-ext@europa-group.com',
        //     'password' => 'password',
        // ]);
        $this->call(DomainSeeder::class);
        $this->call(EmailClientSeeder::class);
    }
}
