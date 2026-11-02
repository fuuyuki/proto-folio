<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\BusinessProfile;
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
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'business_name'],
            ['value' => $validated['name'], 'type' => 'string'],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'business_address'],
            ['value' => $validated['address'] ?? null, 'type' => 'text'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business details updated.')]);

        return to_route('business.edit');
    }
}
