<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Catalog\BuildPublicCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CatalogVersionController extends Controller
{
    /**
     * Return the timestamp of the newest catalog change.
     *
     * The response is deliberately uncached: the rebuild pipeline polls it to
     * decide whether the published site is behind the database.
     */
    public function __invoke(BuildPublicCatalog $buildCatalog): JsonResponse
    {
        return response()
            ->json(['version' => $buildCatalog->version()])
            ->header('Cache-Control', 'no-store');
    }
}
