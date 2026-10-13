<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_login_page_uses_myanmar_as_the_default_locale(): void
    {
        $response = $this->get(route('login'));

        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Auth/Login')
            ->where('locale', 'my')
            ->where('translations.login.heading', 'ပြန်လည်ကြိုဆိုပါသည်'));
    }

    public function test_guest_can_switch_to_english_and_the_choice_persists_in_the_session(): void
    {
        $response = $this->from(route('login'))->post(route('locale.update'), [
            'locale' => 'en',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'en');

        $this->get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('locale', 'en')
                ->where('translations.login.heading', 'Welcome back'));
    }

    public function test_guest_can_switch_to_thai_and_receives_thai_translations(): void
    {
        $response = $this->from(route('login'))->post(route('locale.update'), [
            'locale' => 'th',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'th');

        $this->get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('locale', 'th')
                ->where('translations.language.th', 'ไทย')
                ->where('translations.login.heading', 'ยินดีต้อนรับกลับ'));
    }

    public function test_unsupported_locale_is_rejected_without_changing_the_session(): void
    {
        $response = $this
            ->withSession(['locale' => 'my'])
            ->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'fr']);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('locale')
            ->assertSessionHas('locale', 'my');
    }
}
