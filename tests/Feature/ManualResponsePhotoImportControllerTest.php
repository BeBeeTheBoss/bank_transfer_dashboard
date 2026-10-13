<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ManualResponsePhotoImportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->post(route('manual-response-photo-imports.store'))
            ->assertRedirect(route('login'));
    }

    public function test_photo_and_transaction_number_are_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manual-response-photo-imports.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo', 'transaction_no'])
            ->assertJsonPath('errors.photo.0', 'ပုံ ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။')
            ->assertJsonPath('errors.transaction_no.0', 'Transaction နံပါတ် ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။');
    }

    public function test_transaction_number_must_be_a_string(): void
    {
        $user = User::factory()->create();
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('manual-response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->image('manual-response.png'),
                'transaction_no' => ['276866544947'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['transaction_no'])
            ->assertJsonPath('errors.transaction_no.0', 'Transaction နံပါတ် သည် စာသားဖြစ်ရပါမည်။');

        Http::assertNothingSent();
    }

    public function test_returns_service_unavailable_when_manual_api_is_not_configured(): void
    {
        $user = User::factory()->create();
        config(['services.manual_response_api.url' => null]);
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('manual-response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->image('manual-response.png'),
                'transaction_no' => '276866544947',
            ])
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Manual response API ကို မသတ်မှတ်ရသေးပါ။',
            ]);

        Http::assertNothingSent();
    }

    public function test_manual_response_is_forwarded_to_its_own_api(): void
    {
        $user = User::factory()->create([
            'name' => 'Manual Responder',
        ]);
        $this->travelTo('2026-10-09 12:34:56 UTC');
        config([
            'services.transaction_image_api.url' => 'https://batch.example.test/process',
            'services.manual_response_api.url' => 'https://manual.example.test/respond',
            'services.manual_response_api.key' => 'manual-api-key',
            'services.manual_response_api.field' => 'image',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://manual.example.test/respond' => Http::response([
                'status' => 200,
                'message' => 'All transactions are responded',
            ]),
        ]);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('manual-response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->image('manual-response.png'),
                'transaction_no' => '276866544947',
            ])
            ->assertOk()
            ->assertExactJson([
                'status' => 200,
                'message' => 'All transactions are responded',
            ]);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://manual.example.test/respond'
            && $request->hasHeader('bank-transfer-api-key', 'manual-api-key')
            && collect($request->data())->contains(fn (array $part): bool => ($part['name'] ?? null) === 'username'
                && ($part['contents'] ?? null) === 'Manual Responder')
            && collect($request->data())->contains(fn (array $part): bool => ($part['name'] ?? null) === 'timestamp'
                && ($part['contents'] ?? null) === '1791549296')
            && collect($request->data())->contains(fn (array $part): bool => ($part['name'] ?? null) === 'transaction_no'
                && ($part['contents'] ?? null) === '276866544947')
            && $request->hasFile('image', filename: 'manual-response.png'));
    }

    public function test_invalid_photo_status_is_returned_as_an_error(): void
    {
        $user = User::factory()->create();
        config([
            'services.manual_response_api.url' => 'https://manual.example.test/respond',
            'services.manual_response_api.field' => 'image',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://manual.example.test/respond' => Http::response([
                'status' => 504,
                'message' => 'Your uploaded photo is invalid',
            ], 200),
        ]);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('manual-response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->image('invalid.png'),
                'transaction_no' => '276866544947',
            ])
            ->assertStatus(504)
            ->assertExactJson([
                'status' => 504,
                'message' => 'Your uploaded photo is invalid',
            ]);

        Http::assertSentCount(1);
    }
}
