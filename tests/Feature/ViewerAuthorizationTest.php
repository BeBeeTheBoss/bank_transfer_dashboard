<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ViewerAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_transfer_permissions_allow_users_and_admins_but_not_viewers(): void
    {
        $viewer = User::factory()->viewer()->create();
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        foreach (['respond-to-transfers', 'export-transfer-reports'] as $ability) {
            $this->assertFalse(Gate::forUser($viewer)->allows($ability));
            $this->assertTrue(Gate::forUser($user)->allows($ability));
            $this->assertTrue(Gate::forUser($admin)->allows($ability));
        }
    }

    public function test_viewer_can_open_the_report_with_read_only_permissions(): void
    {
        $viewer = User::factory()->viewer()->create();
        config(['database.connections.reports' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->actingAs($viewer)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Reports/Index')
                ->where('auth.user.type', 'viewer')
                ->where('permissions.respondToTransfers', false)
                ->where('permissions.exportTransferReports', false));
    }

    #[DataProvider('responseEndpointRoutes')]
    public function test_viewer_cannot_call_response_endpoints(string $routeName): void
    {
        $viewer = User::factory()->viewer()->create();
        Http::preventStrayRequests();

        $this->actingAs($viewer)
            ->postJson(route($routeName))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_viewer_cannot_export_reports(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('reports.export'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_open_user_management(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function responseEndpointRoutes(): array
    {
        return [
            'batch photo response' => ['response-photo-imports.store'],
            'manual photo response' => ['manual-response-photo-imports.store'],
            'manual failed response' => ['manual-failed-responses.store'],
        ];
    }
}
