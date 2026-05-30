<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_rankings_page_is_accessible()
    {
        Season::create(['name' => 'Keraza 2026', 'is_active' => true]);

        $response = $this->get('/rankings');

        $response->assertStatus(200);
        $response->assertSee('لوحة الشرف');
    }
}
