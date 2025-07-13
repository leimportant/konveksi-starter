<?php

use Illuminate\Support\Str;

if (!function_exists('storage_url')) {
    function storage_url($path)
    {
        if (!$path) return '';

        if (Str::startsWith($path, 'http')) return $path;
        if (Str::startsWith($path, 'storage/')) return asset($path);
        if (Str::startsWith($path, '/storage/')) return asset(ltrim($path, '/'));
        
        return asset('storage/' . ltrim($path, '/'));
    }
}