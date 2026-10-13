<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResponsePhotoImportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->post(route('response-photo-imports.store'))
            ->assertRedirect(route('login'));
    }

    public function test_photo_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('response-photo-imports.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo'])
            ->assertJsonPath('errors.photo.0', 'ပုံ ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။');
    }

    public function test_non_image_files_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_multiple_photos_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('response-photo-imports.store'), [
                'photo' => [
                    UploadedFile::fake()->image('first.png'),
                    UploadedFile::fake()->image('second.jpg'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_returns_service_unavailable_when_api_is_not_configured(): void
    {
        $user = User::factory()->create();
        config(['services.transaction_image_api.url' => null]);
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->image('response.png'),
            ])
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Transaction image API ကို မသတ်မှတ်ရသေးပါ။',
            ]);

        Http::assertNothingSent();
    }

    public function test_valid_photo_is_forwarded_to_the_configured_api(): void
    {
        $user = User::factory()->create([
            'name' => 'Report Operator',
        ]);
        $this->travelTo('2026-10-09 12:34:56 UTC');
        config([
            'services.transaction_image_api.url' => 'https://images.example.test/process',
            'services.transaction_image_api.key' => 'test-api-key',
            'services.transaction_image_api.field' => 'image',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://images.example.test/process' => Http::response([
                'status' => 200,
                'message' => 'All transactions are responded',
            ]),
        ]);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->image('response.png'),
            ])
            ->assertOk()
            ->assertExactJson([
                'status' => 200,
                'message' => 'All transactions are responded',
            ]);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://images.example.test/process'
            && $request->hasHeader('bank-transfer-api-key', 'test-api-key')
            && collect($request->data())->contains(fn (array $part): bool => ($part['name'] ?? null) === 'username'
                && ($part['contents'] ?? null) === 'Report Operator')
            && collect($request->data())->contains(fn (array $part): bool => ($part['name'] ?? null) === 'timestamp'
                && ($part['contents'] ?? null) === '1791549296')
            && ! collect($request->data())->contains(fn (array $part): bool => ($part['name'] ?? null) === 'transaction_no')
            && $request->hasFile('image', filename: 'response.png'));
    }

    public function test_invalid_photo_status_in_successful_http_response_is_returned_as_an_error(): void
    {
        $user = User::factory()->create();
        config([
            'services.transaction_image_api.url' => 'https://images.example.test/process',
            'services.transaction_image_api.field' => 'image',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://images.example.test/process' => Http::response([
                'status' => 504,
                'message' => 'Your uploaded photo is invalid',
            ], 200),
        ]);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('response-photo-imports.store'), [
                'photo' => UploadedFile::fake()->image('invalid.png'),
            ])
            ->assertStatus(504)
            ->assertExactJson([
                'status' => 504,
                'message' => 'Your uploaded photo is invalid',
            ]);

        Http::assertSentCount(1);
    }
}
