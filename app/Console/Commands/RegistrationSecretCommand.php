<?php

namespace App\Console\Commands;

use App\Support\RegistrationCode;
use Illuminate\Console\Command;

class RegistrationSecretCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registration:secret
                            {--rotate : Generate a new secret, invalidating existing authenticator entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the time-based code that gates account registration';

    /**
     * Execute the console command.
     */
    public function handle(RegistrationCode $codes): int
    {
        if ($this->option('rotate')) {
            $codes->rotate();

            $this->warn('A new secret was generated. Existing authenticator entries no longer work.');
            $this->newLine();
        }

        $this->line('Secret:      '.$codes->secret());
        $this->line('Type:        TOTP, 30 seconds, 6 digits');
        $this->line('otpauth URI: '.$codes->otpauthUri((string) config('app.name')));
        $this->newLine();
        $this->comment('Add the secret (or the otpauth URI) to your authenticator app as a');
        $this->comment('time-based account, then share the current code with new staff.');
        $this->comment('Rotate it with: php artisan registration:secret --rotate');

        return self::SUCCESS;
    }
}
