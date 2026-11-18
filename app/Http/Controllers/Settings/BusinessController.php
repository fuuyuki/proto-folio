<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\BusinessProfile;
use App\Support\QrisPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    /**
     * Show the business details used on printed invoices.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Business', [
            'business' => BusinessProfile::toArray(),
            'qris_payload' => (string) Setting::query()
                ->where('key', 'qris_payload')
                ->value('value'),
        ]);
    }

    /**
     * Store the business details.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'qris_payload' => [
                'nullable',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (filled($value) && ! QrisPayload::isValid((string) $value)) {
                        $fail('This is not a valid static QRIS payload. Paste the complete string from your merchant app.');
                    }
                },
            ],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'business_name'],
            ['value' => $validated['name'], 'type' => 'string'],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'business_address'],
            ['value' => $validated['address'] ?? null, 'type' => 'text'],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'qris_payload'],
            [
                'value' => filled($validated['qris_payload'] ?? null)
                    ? trim((string) $validated['qris_payload'])
                    : null,
                'type' => 'text',
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business details updated.')]);

        return to_route('business.edit');
    }
}
