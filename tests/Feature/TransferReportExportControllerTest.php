<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransferReportExportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get(route('reports.export'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_export_filtered_records_for_excel(): void
    {
        $this->configureReportsDatabase();
        $user = User::factory()->create();
        app()->setLocale('en');

        DB::connection('reports')->table(config('reporting.transfer_requests_table'))->insert([
            [
                'id' => 1,
                'transaction_no' => '276866544947',
                'branch' => 'Yangon',
                'requested_telegram_username' => 'Henry',
                'requested_telegram_user_id' => '8661762636',
                'requested_at' => '2026-10-10 15:49:37',
                'responded_at' => '2026-10-10 15:52:37',
                'transfer_amount' => '230800',
                'status' => 'complete',
                'responded_username' => 'Admin',
                'remark' => '=unsafe formula',
                'requested_screenshot' => 'https://example.test/request.jpg',
                'responded_screenshot' => 'https://example.test/response.jpg',
            ],
            [
                'id' => 2,
                'transaction_no' => '999999999999',
                'branch' => 'Mandalay',
                'requested_telegram_username' => 'Other',
                'requested_telegram_user_id' => '123',
                'requested_at' => '2026-10-11 10:00:00',
                'responded_at' => null,
                'transfer_amount' => '1000',
                'status' => 'success',
                'responded_username' => null,
                'remark' => 'Not included',
                'requested_screenshot' => null,
                'responded_screenshot' => null,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.export', ['branch' => 'Yangon']));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Transaction No.', $content);
        $this->assertStringContainsString('276866544947', $content);
        $this->assertStringContainsString('Yangon', $content);
        $this->assertStringContainsString('Complete', $content);
        $this->assertStringContainsString('180', $content);
        $this->assertStringContainsString("'=unsafe formula", $content);
        $this->assertStringNotContainsString('999999999999', $content);
    }

    private function configureReportsDatabase(): void
    {
        config(['database.connections.reports' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        DB::purge('reports');

        Schema::connection('reports')->create(config('reporting.transfer_requests_table'), function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_no');
            $table->string('branch')->nullable();
            $table->string('requested_telegram_username')->nullable();
            $table->string('requested_telegram_user_id')->nullable();
            $table->dateTime('requested_at');
            $table->dateTime('responded_at')->nullable();
            $table->decimal('transfer_amount', 18, 2);
            $table->string('status');
            $table->string('responded_username')->nullable();
            $table->text('remark')->nullable();
            $table->text('requested_screenshot')->nullable();
            $table->text('responded_screenshot')->nullable();
        });
    }
}
