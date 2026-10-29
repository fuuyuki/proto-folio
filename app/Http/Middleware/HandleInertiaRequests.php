<?php

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'currency' => $this->baseCurrency(),
            'locale' => app()->getLocale(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The base currency used to display money throughout the interface.
     *
     * @return array{code: string, symbol: string, decimal_places: int}
     */
    private function baseCurrency(): array
    {
        $code = Setting::query()->where('key', 'base_currency')->value('value') ?? 'IDR';

        $currency = Currency::query()->where('code', $code)->first();

        return [
            'code' => $currency->code ?? $code,
            'symbol' => $currency->symbol ?? $code,
            'decimal_places' => $currency->decimal_places ?? 2,
        ];
    }
}
