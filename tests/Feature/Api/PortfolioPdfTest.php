<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortfolioPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_is_pdf(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student, 'password' => Hash::make('password')]);
        $this->actingAs($student, 'web');
        $res = $this->get("/api/students/{$student->id}/portfolio-pdf");
        $res->assertOk();
        $res->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $res->getContent() ?: '');
    }
}
