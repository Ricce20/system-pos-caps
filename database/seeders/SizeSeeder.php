<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Size;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = [
            ['name' => 'S',  'measurement' => '54cm'],
            ['name' => 'M',  'measurement' => '57cm'],
            ['name' => 'L',  'measurement' => '59cm'],
            ['name' => 'XL', 'measurement' => '62cm'],
            ['name' => 'Uni','measurement' => 'Unitalla'],
        ];

        foreach ($sizes as $size) {
            Size::firstOrCreate($size);
        }
    }
} 