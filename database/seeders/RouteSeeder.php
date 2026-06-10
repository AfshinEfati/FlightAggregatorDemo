<?php

namespace Database\Seeders;

use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $routes = [
            ['origin' => 'THR', 'destination' => 'MHD'],
            ['origin' => 'THR', 'destination' => 'SYZ'],
            ['origin' => 'MHD', 'destination' => 'THR'],
            ['origin' => 'SYZ', 'destination' => 'THR'],
        ];

        foreach ($routes as $route) {
            Route::updateOrCreate($route);
        }
    }
}
