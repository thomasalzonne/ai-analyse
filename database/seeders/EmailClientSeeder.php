<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emailClients = [
            [
                'domain_id' => 1, // 'PCR'
                'remote_id' => 'e6350cc946381ea7620be15cd05ffd0f',
                'name' => 'PCR',
                'api_key' => 'lAM2e6ij+TEmygn6HUqsVBiwOVAFVNxtZgKz2GRaLnwMaAaa5QOln2QbNMwCBuKRE4N6ShFQJQLEh2BrwNQI1oPpwN+qOGic3zu/NNYQajcPW/e/6NxQ/Pjl6g8xvxAoUTG6kjFc/NKOwGmTw30dYQ==',
            ],
            [
                'domain_id' => 2, // 'Cardio'
                'remote_id' => '1abbb7e6d54fef5112009512d4ff18e5',
                'name' => 'Cardio',
                'api_key' => 'PskNeMwL2UBhR8VgjzdkYY7k9JBb+AOWeQLYyVVOfSih0iCz9NwzDv1KPpjb/X6HFmy2O2GRo/CqSiFTgXAT7GErIPI/LzNAMKqol62rxdpaGtm/XFjOKf/gId5BUVFnbq8O4/GlCgT5w+BvkMkyQQ==',
            ],
            [
                'domain_id' => 3, // 'L Encéphale'
                'remote_id' => '4a53da49a5a2cc9c9f18ab37e27ba0da',
                'name' => 'L Encéphale',
                'api_key' => 'miSfkRKRBUTeA3Vt2krJs9agkFQaF3bMJ2R8FN8GlDBEH6gali7kuQYZIG6DNi1Tj4+Z7iIcQunXsk8HrRLKglnrklqIg6lKdEumx8rBFg/pbVMEyfOfbc/HCmsAacUpzcbv6pOUKq+1y5PO7rasmQ==',
            ],
            [
                'domain_id' => 4, // 'LINNC'
                'remote_id' => 'b0864e665e8b03370e56d4c86c7e5350',
                'name' => 'LINNC',
                'api_key' => '0Hj2PLynwjkk1YEMC1CB0A39VewuUOCGHSbnSqY8LMAjYFkrWgUp5H1ty/k7gT6TaoHcPWjvEv1JXj1N0A0Vq1BrCvDOM0yhbXTuYEQjZclKHJlGA+vQMhjB/H9Ccf4W01VNj+lboigDm7zruIZoyw==',
            ],
            [
                'domain_id' => 5, // 'PCO'
                'remote_id' => 'd76d59fcb65be48373a49536fb62de39',
                'name' => 'PCO',
                'api_key' => '6qHEQN9d4Efup9bLX7wushX7LmSis7jMQzZKRId2yKJFA/3ALMrxhbZXtaaQzJA3S01uwRXAzDYrAQ+PZJGr8pRtSvme7GiF9AFT6FvMaI3mGgD5lZtfsGeTY+0WRM9ACghn2Z5Wnx5hD9T3e3eImQ==',
            ],
            [
                'domain_id' => 6, // 'PVI'
                'remote_id' => '92ca76d50dd2c9591426bbd73f3fe352',
                'name' => 'PVI',
                'api_key' => '1E9EzHyIrmC21zw7NKBZgeUAYX2sncuydtuUplfcZHJuzROKKzMpiOJJfxPcIL2SfUuXSuyONw0rUF5alQuSZ38pQMiCmavf9ty02z1H3dv/ED52T8Nm/TxhFEJ46ztXu02ZZu1cGV7LdOc8wP9ppw==',
            ],
        ];

        foreach ($emailClients as $emailClient) {
            \App\Models\EmailClient::create($emailClient);
        }
    }
}
