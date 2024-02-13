<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = [
            [
                'name' => 'PCR',
            ],
            [
                'name' => 'Cardio',
            ],
            [
                'name' => 'L’Encéphale',
            ],
            [
                'name' => 'LINNC',
            ],
            [
                'name' => 'PCO',
            ],
            [
                'name' => 'PVI',
            ],
        ];

        foreach ($domains as $domain) {
            \App\Models\Domain::create($domain);
        }
    }
}
