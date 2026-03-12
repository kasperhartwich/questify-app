<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'General Knowledge', 'icon' => 'brain'],
            ['name' => 'Geography', 'icon' => 'globe'],
            ['name' => 'History', 'icon' => 'landmark'],
            ['name' => 'Science', 'icon' => 'flask'],
            ['name' => 'Nature & Animals', 'icon' => 'leaf'],
            ['name' => 'Math', 'icon' => 'calculator'],
            ['name' => 'Art & Culture', 'icon' => 'palette'],
            ['name' => 'Sports', 'icon' => 'trophy'],
            ['name' => 'Custom / Other', 'icon' => 'puzzle'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                ],
            );
        }
    }
}
