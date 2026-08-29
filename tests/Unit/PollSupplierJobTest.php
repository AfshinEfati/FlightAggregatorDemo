<?php

namespace Tests\Unit;

use App\Jobs\PollSupplierJob;
use App\Models\Route;
use App\Models\Supplier;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use PHPUnit\Framework\TestCase;

class PollSupplierJobTest extends TestCase
{
    public function test_it_locks_by_supplier_route_and_departure_date(): void
    {
        $supplier = (new Supplier)->forceFill(['id' => 11]);
        $route = (new Route)->forceFill(['id' => 22]);

        $job = new PollSupplierJob($supplier, $route, '2026-09-10');
        $middleware = $job->middleware();

        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
        $this->assertStringContainsString(
            'supplier:11:route:22:date:2026-09-10',
            $middleware[0]->getLockKey($job),
        );
    }
}
