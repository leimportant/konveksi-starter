<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CrossDomainAuthController extends Controller
{
    /**
     * Extract auth cookies from parent domain and redirect to child domain
     */
    public function transferAuth(Request $request)
    {
        Log::info('Starting cross-domain auth transfer');   
        $aninkafashionToken = 'eZjk+'. $request->cookie('aninkafashion-token'); 
        Log::info('aninkafashion-token: ' . $aninkafashionToken);
        // Ambil raw cookie langsung dari header, bukan dari helper cookie()
        $rawCookies = $request->header('cookie');
        preg_match('/aninkafashion-token=([^;]+)/', $rawCookies, $matches);
        $encryptedToken = $matches[1] ?? null;

        if (!$encryptedToken) {
            return response()->json(['error' => 'Missing raw encrypted cookie'], 401);
        }

        // Kirim token terenkripsi ke child
        $childUrl = $request->input('childUrl');
        Log::info('Sending token to child URL: ' . $childUrl);
        $response = Http::asJson()->post($childUrl . '/auth/set-cookie', [
            'token' => $encryptedToken
        ]);

        return response()->json([
            'status' => 'ok',
            '_token'=> $aninkafashionToken,
            'child_response' => $response->json()
        ]);
    }




}
