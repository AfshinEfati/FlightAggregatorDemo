<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = config('suppliers.suppliers');

        foreach ($suppliers as $slug => $data) {
            Supplier::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'base_url' => $data['base_url'],
                    'poll_interval_minutes' => $data['poll_interval'],
                    'is_active' => true,
                ]
            );
        }
    }
}
