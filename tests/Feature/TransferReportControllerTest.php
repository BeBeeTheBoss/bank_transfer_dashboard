<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransferReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_report_with_database_connection_notice(): void
    {
        $user = User::factory()->create();
        config(['database.connections.reports' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Reports/Index')
                ->where('records.total', 0)
                ->has('databaseError'));
    }

    public function test_invalid_sort_value_is_rejected_before_building_query(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('reports.index'))
            ->get(route('reports.index', ['sort' => 'responded_at; DROP TABLE users']))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('sort');
    }

    #[DataProvider('responseTimeFilters')]
    public function test_five_minute_response_time_filters_are_accepted(string $filter): void
    {
        $user = User::factory()->create();
        config(['database.connections.reports' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->actingAs($user)
            ->get(route('reports.index', ['response_time' => $filter]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Reports/Index')
                ->where('filters.response_time', $filter));
    }

    public function test_unsupported_response_time_filter_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('reports.index'))
            ->get(route('reports.index', ['response_time' => 'over_15m']))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('response_time');
    }

    public function test_phone_confirm_status_filter_is_accepted(): void
    {
        $user = User::factory()->create();
        config(['database.connections.reports' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->actingAs($user)
            ->get(route('reports.index', ['status' => 'phone-confirm']))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Reports/Index')
                ->where('filters.status', 'phone-confirm')
                ->where('translations.status.phone_confirm', 'ဖုန်းဖြင့် အတည်ပြုနေသည်'));
    }

    public function test_page_number_must_be_a_positive_integer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('reports.index'))
            ->get(route('reports.index', ['page' => 0]))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('page');
    }

    public function test_branch_filter_cannot_exceed_one_hundred_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('reports.index'))
            ->get(route('reports.index', ['branch' => str_repeat('a', 101)]))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('branch');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function responseTimeFilters(): array
    {
        return [
            'five minutes or less' => ['under_5m'],
            'over five minutes' => ['over_5m'],
        ];
    }
}
