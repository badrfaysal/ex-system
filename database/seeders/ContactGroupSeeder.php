<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'مجموعة السائقين', 'description' => 'السائقين العاملين بالشركة'],
            ['name' => 'مجموعة المناديب', 'description' => 'مناديب المبيعات والتسليم'],
            ['name' => 'مجموعة العملاء', 'description' => 'عملاء الشركة'],
            ['name' => 'مجموعة الموردين', 'description' => 'موردي البضائع والخامات'],
            ['name' => 'مجموعة الموظفين', 'description' => 'موظفي الشركة داخلياً'],
        ];

        foreach ($groups as $group) {
            \App\Models\ContactGroup::firstOrCreate(['name' => $group['name']], $group);
        }
    }
}
