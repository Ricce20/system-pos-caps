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
            ['name' => 'XS', 'measurement' => '16cm'],
            ['name' => 'S',  'measurement' => '18cm'],
            ['name' => 'M',  'measurement' => '20cm'],
            ['name' => 'L',  'measurement' => '22cm'],
            ['name' => 'XL', 'measurement' => '24cm'],
            ['name' => 'XXL','measurement' => '26cm'],
        ];

        foreach ($sizes as $size) {
            Size::firstOrCreate($size);
        }
    }
} 