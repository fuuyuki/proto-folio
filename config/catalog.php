<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public catalog rebuild trigger
    |--------------------------------------------------------------------------
    |
    | When a product or category that feeds the public catalog changes, ProFolio
    | asks the static website to rebuild itself. The rebuild is delegated to a
    | small endpoint that the website host owns; ProFolio never builds the site
    | itself. Leave either value blank to disable rebuild requests, which keeps
    | this application safe to deploy before the endpoint exists.
    |
    */

    'rebuild_url' => env('CATALOG_REBUILD_URL'),

    'rebuild_token' => env('CATALOG_REBUILD_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Debounce delay
    |--------------------------------------------------------------------------
    |
    | Minutes to wait before asking for a rebuild. Editing a product usually
    | means several saves in a row; waiting collapses them into one build.
    |
    */

    'rebuild_delay_minutes' => (int) env('CATALOG_REBUILD_DELAY_MINUTES', 2),

];
