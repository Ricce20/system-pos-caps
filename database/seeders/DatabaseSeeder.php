<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\BrandFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ModelCapFactory;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        \App\Models\Brand::factory(10)->create();
        \App\Models\ModelCap::factory(10)->create();
        \App\Models\Category::factory(10)->create();
    }
}
