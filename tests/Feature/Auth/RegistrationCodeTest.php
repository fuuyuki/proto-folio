<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\RegistrationCode;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

function registrationTotp(): string
{
    return (new Google2FA)->getCurrentOtp(app(RegistrationCode::class)->secret());
}

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function registrationData(array $overrides = []): array
{
    return [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ];
}

function wrongRegistrationTotp(): string
{
    return (new Google2FA)->getCurrentOtp(
        (new Google2FA)->generateSecretKey(32),
    );
}

test('registration requires the authenticator code', function () {
    $this->post(route('register.store'), registrationData())
        ->assertSessionHasErrors('registration_code');

    expect(User::query()->count())->toBe(0);
});

test('an invalid authenticator code is rejected', function () {
    $this->post(route('register.store'), registrationData([
        'registration_code' => wrongRegistrationTotp(),
    ]))->assertSessionHasErrors('registration_code');

    expect(User::query()->count())->toBe(0);
});

test('a valid authenticator code registers the user', function () {
    $this->post(route('register.store'), registrationData([
        'registration_code' => registrationTotp(),
    ]))->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
});

test('the secret is generated on first use', function () {
    expect(Setting::query()->where('key', 'registration_totp_secret')->exists())->toBeFalse();

    $secret = app(RegistrationCode::class)->secret();

    expect($secret)->toHaveLength(32)
        ->and(Setting::query()->where('key', 'registration_totp_secret')->value('value'))->toBe($secret);
});

test('rotating the secret invalidates previously issued codes', function () {
    $codes = app(RegistrationCode::class);
    $staleCode = (new Google2FA)->getCurrentOtp($codes->secret());

    $codes->rotate();

    $this->post(route('register.store'), registrationData([
        'registration_code' => $staleCode,
    ]))->assertSessionHasErrors('registration_code');

    expect(User::query()->count())->toBe(0);
});

test('registration attempts are rate limited per ip', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('register.store'), registrationData([
            'registration_code' => wrongRegistrationTotp(),
        ]))->assertSessionHasErrors('registration_code');
    }

    $this->post(route('register.store'), registrationData([
        'registration_code' => registrationTotp(),
    ]))->assertSessionHasErrors('registration_code');

    expect(session('errors')->first('registration_code'))->toContain('Too many');
    expect(User::query()->count())->toBe(0);
});

test('the registration secret can be shown from the console', function () {
    $this->artisan('registration:secret')
        ->expectsOutputToContain('Secret:')
        ->assertSuccessful();
});

test('the registration secret can be rotated from the console', function () {
    $secret = app(RegistrationCode::class)->secret();

    $this->artisan('registration:secret --rotate')->assertSuccessful();

    expect(app(RegistrationCode::class)->secret())->not->toBe($secret);
});
