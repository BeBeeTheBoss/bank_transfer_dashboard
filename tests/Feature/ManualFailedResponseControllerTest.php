<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ManualFailedResponseControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->post(route('manual-failed-responses.store'))
            ->assertRedirect(route('login'));
    }

    public function test_remark_and_transaction_number_are_required(): void
    {
        $user = User::factory()->create();
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->postJson(route('manual-failed-responses.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['remark', 'transaction_no'])
            ->assertJsonPath('errors.remark.0', 'မှတ်ချက် ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။')
            ->assertJsonPath('errors.transaction_no.0', 'Transaction နံပါတ် ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။');

        Http::assertNothingSent();
    }

    public function test_remark_cannot_exceed_one_thousand_characters(): void
    {
        $user = User::factory()->create();
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->postJson(route('manual-failed-responses.store'), [
                'remark' => str_repeat('a', 1001),
                'transaction_no' => '276866544947',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('remark')
            ->assertJsonPath('errors.remark.0', 'မှတ်ချက် သည် စာလုံးရေ 1000 ထက် မကျော်ရပါ။');

        Http::assertNothingSent();
    }

    public function test_returns_service_unavailable_when_failed_response_api_is_not_configured(): void
    {
        $user = User::factory()->create();
        config(['services.manual_failed_response_api.url' => null]);
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->postJson(route('manual-failed-responses.store'), [
                'remark' => 'The transfer receipt does not match.',
                'transaction_no' => '276866544947',
            ])
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Manual failed-response API ကို မသတ်မှတ်ရသေးပါ။',
            ]);

        Http::assertNothingSent();
    }

    public function test_failed_response_is_forwarded_to_its_own_api_with_only_the_required_payload(): void
    {
        $user = User::factory()->create([
            'name' => 'Manual Responder',
        ]);
        config([
            'services.manual_failed_response_api.url' => 'https://failed.example.test/respond',
            'services.manual_failed_response_api.key' => 'failed-api-key',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://failed.example.test/respond' => Http::response([
                'status' => 200,
                'message' => 'Transaction marked as failed',
            ]),
        ]);

        $this->actingAs($user)
            ->postJson(route('manual-failed-responses.store'), [
                'remark' => '  The transfer receipt does not match.  ',
                'transaction_no' => '276866544947',
            ])
            ->assertOk()
            ->assertExactJson([
                'status' => 200,
                'message' => 'Transaction marked as failed',
            ]);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://failed.example.test/respond'
            && $request->hasHeader('bank-transfer-api-key', 'failed-api-key')
            && $request->data() === [
                'username' => 'Manual Responder',
                'remark' => 'The transfer receipt does not match.',
                'transaction_no' => '276866544947',
            ]);
    }

    public function test_error_status_in_a_successful_http_response_is_returned_as_an_error(): void
    {
        $user = User::factory()->create();
        config(['services.manual_failed_response_api.url' => 'https://failed.example.test/respond']);
        Http::preventStrayRequests();
        Http::fake([
            'https://failed.example.test/respond' => Http::response([
                'status' => 504,
                'message' => 'The failed response could not be processed',
            ]),
        ]);

        $this->actingAs($user)
            ->postJson(route('manual-failed-responses.store'), [
                'remark' => 'The transfer receipt does not match.',
                'transaction_no' => '276866544947',
            ])
            ->assertStatus(504)
            ->assertExactJson([
                'status' => 504,
                'message' => 'The failed response could not be processed',
            ]);

        Http::assertSentCount(1);
    }
}
