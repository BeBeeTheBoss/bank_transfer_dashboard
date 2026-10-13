<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->select(['id', 'name', 'email', 'type', 'created_at'])
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(15),
            'userTypes' => array_column(UserType::cases(), 'value'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return back()->with('success', __('ui.users.created'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->validated();

        if ($request->user()->is($user) && $attributes['type'] !== UserType::Admin->value) {
            throw ValidationException::withMessages([
                'type' => __('ui.users.cannot_demote_self'),
            ]);
        }

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $user->update($attributes);

        return back()->with('success', __('ui.users.updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', __('ui.users.cannot_delete_self'));
        }

        $user->delete();

        return back()->with('success', __('ui.users.deleted'));
    }
}
