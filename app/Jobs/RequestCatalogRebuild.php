<?php

namespace App\Jobs;

use App\Actions\Catalog\BuildPublicCatalog;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RequestCatalogRebuild implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var list<int>
     */
    public array $backoff = [30, 120, 300];

    /**
     * The number of seconds after which the unique lock is released.
     */
    public int $uniqueFor = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly string $reason = 'catalog changed') {}

    /**
     * A single lock is shared by every pending rebuild request.
     */
    public function uniqueId(): string
    {
        return 'catalog-rebuild';
    }

    /**
     * Ask the website host to rebuild the static site from the current catalog.
     */
    public function handle(BuildPublicCatalog $buildCatalog): void
    {
        $url = config('catalog.rebuild_url');
        $token = config('catalog.rebuild_token');

        if (blank($url) || blank($token)) {
            return;
        }

        // The site build reads the catalog over HTTP, so drop the cached
        // snapshot before asking for the rebuild to avoid building stale data.
        Cache::forget('public-catalog:v1');

        $version = $buildCatalog->version();

        if (Cache::get('catalog:rebuild-requested-version') === $version) {
            return;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->post($url, [
                'version' => $version,
                'reason' => $this->reason,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'The catalog rebuild request failed with HTTP %d: %s',
                $response->status(),
                $response->body(),
            ));
        }

        Cache::forever('catalog:rebuild-requested-version', $version);

        Log::info('Requested a public catalog rebuild.', [
            'version' => $version,
            'reason' => $this->reason,
        ]);
    }
}
