<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NgrokSkipWarning
{
    /**
     * Tambahkan header 'ngrok-skip-browser-warning' ke setiap response.
     * Ini memastikan browser dapat memuat aset CSS/JS lewat ngrok
     * tanpa diblokir oleh halaman interstitial ngrok.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('ngrok-skip-browser-warning', 'true');
        return $response;
    }
}
