<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Catalog\BuildPublicCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    /**
     * Return the public product catalog as a single cached snapshot.
     */
    public function __invoke(Request $request, BuildPublicCatalog $buildCatalog): JsonResponse
    {
        $payload = Cache::flexible(
            'public-catalog:v1',
            [60, 600],
            fn (): array => $buildCatalog->handle(),
        );

        $response = response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=60')
            ->setEtag($payload['version'], weak: true);

        if ($response->isNotModified($request)) {
            return $response->setNotModified();
        }

        return $response;
    }
}
