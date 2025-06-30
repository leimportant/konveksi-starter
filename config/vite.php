<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vite Manifest Path
    |--------------------------------------------------------------------------
    |
    | This value determines the path to your Vite "manifest.json" file.
    | You may need to change this if your build assets are located
    | in a different directory than the default "public/build".
    |
    */

    #'manifest_path' => public_path('build/manifest.json'),
    'manifest_path' => public_path('build/manifest.json'),



    /*
    |--------------------------------------------------------------------------
    | Hot File Path (for Vite Dev Server)
    |--------------------------------------------------------------------------
    |
    | This value determines the path to the "hot" file which tells Laravel
    | to use the Vite dev server instead of the built assets.
    |
    */

    'hot_file' => public_path('hot'),

    /*
    |--------------------------------------------------------------------------
    | Dev Server URL (optional)
    |--------------------------------------------------------------------------
    |
    | This value sets the full URL to your Vite dev server when in development.
    |
    */

    'dev_server_url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),

];
