<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LocalizationController extends Controller
{
    /**
     * Show the language settings.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Localization', [
            'locale' => Setting::query()->where('key', 'locale')->value('value') ?? config('app.locale'),
            'locales' => $this->locales(),
        ]);
    }

    /**
     * Store the selected language.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys($this->locales()))],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'locale'],
            ['value' => $request->string('locale')->toString(), 'type' => 'string'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language updated.')]);

        return to_route('localization.edit');
    }

    /**
     * The languages the application supports.
     *
     * @return array<string, string>
     */
    private function locales(): array
    {
        return (array) config('app.supported_locales', []);
    }
}
