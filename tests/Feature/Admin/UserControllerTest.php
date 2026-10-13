<?php

namespace Tests\Feature\Admin;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_manage_users_gate_allows_only_admins(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse(Gate::forUser($user)->allows('manage-users'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage-users'));
    }

    public function test_user_cannot_open_user_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_user_cannot_create_an_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.users.store'), [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
                'type' => UserType::Admin->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_admin_sees_paginated_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Current Admin']);
        User::factory()->create(['name' => 'Report User']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Admin/Users/Index')
                ->where('users.total', 2)
                ->has('users.data', 2)
                ->where('userTypes', ['viewer', 'user', 'admin'])
                ->where('auth.user.type', 'admin'));
    }

    public function test_admin_can_create_a_viewer_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Read Only Viewer',
                'email' => 'viewer@example.com',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
                'type' => UserType::Viewer->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $viewer = User::query()->where('email', 'viewer@example.com')->firstOrFail();

        $this->assertSame(UserType::Viewer, $viewer->type);
    }

    public function test_admin_creates_user_with_selected_type(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Operations Admin',
                'email' => 'operations@example.com',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
                'type' => UserType::Admin->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'အသုံးပြုသူကို အောင်မြင်စွာ ဖန်တီးပြီးပါပြီ။');

        $createdUser = User::query()->where('email', 'operations@example.com')->firstOrFail();

        $this->assertSame('Operations Admin', $createdUser->name);
        $this->assertSame(UserType::Admin, $createdUser->type);
        $this->assertTrue(Hash::check('password-123', $createdUser->password));
    }

    public function test_required_user_fields_show_localized_errors(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.store'), [])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors([
                'name' => 'အမည် ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။',
                'email' => 'အီးမေးလ် ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။',
                'password' => 'စကားဝှက် ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။',
                'type' => 'အသုံးပြုသူအမျိုးအစား ကို ဖြည့်သွင်းရန် လိုအပ်ပါသည်။',
            ]);
    }

    public function test_invalid_user_type_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.store'), [
                'name' => 'Invalid Role',
                'email' => 'invalid@example.com',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
                'type' => 'super-admin',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors([
                'type' => 'ရွေးချယ်ထားသော အသုံးပြုသူအမျိုးအစား သည် မမှန်ကန်ပါ။',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.com']);
    }

    public function test_admin_updates_user_without_changing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['password' => 'original-password']);
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'password' => '',
                'password_confirmation' => '',
                'type' => UserType::Admin->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'အသုံးပြုသူအချက်အလက်ကို အောင်မြင်စွာ ပြင်ဆင်ပြီးပါပြီ။');

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame(UserType::Admin, $user->type);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_admin_cannot_remove_own_admin_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'password_confirmation' => '',
                'type' => UserType::User->value,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors([
                'type' => 'ကိုယ့်အကောင့်၏ Admin အသုံးပြုခွင့်ကို ကိုယ်တိုင်ဖယ်ရှား၍ မရပါ။',
            ]);

        $this->assertSame(UserType::Admin, $admin->fresh()->type);
    }

    public function test_admin_deletes_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect()
            ->assertSessionHas('success', 'အသုံးပြုသူကို အောင်မြင်စွာ ဖျက်ပြီးပါပြီ။');

        $this->assertModelMissing($user);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error', 'ကိုယ့်အကောင့်ကို ကိုယ်တိုင်ဖျက်၍ မရပါ။');

        $this->assertModelExists($admin);
    }
}
