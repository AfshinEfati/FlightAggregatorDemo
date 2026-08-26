<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Console\Command;
use Tests\TestCase;

class PollSuppliersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_an_invalid_departure_date(): void
    {
        $this->artisan('suppliers:poll', ['--date' => '10-09-2026'])
            ->expectsOutput('The --date option must use the Y-m-d format.')
            ->assertExitCode(Command::FAILURE);
    }
}
