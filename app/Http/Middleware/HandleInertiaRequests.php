<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->only('id', 'name', 'email'),
                    'type' => $request->user()->type->value,
                ] : null,
            ],
            'permissions' => [
                'respondToTransfers' => $request->user()?->can('respond-to-transfers') ?? false,
                'exportTransferReports' => $request->user()?->can('export-transfer-reports') ?? false,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'locale' => app()->getLocale(),
            'translations' => trans('ui'),
        ];
    }
}
