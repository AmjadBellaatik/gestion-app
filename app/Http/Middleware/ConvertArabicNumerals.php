<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertArabicNumerals
{
    private const AR_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const LA_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->getLocale() !== 'ar') {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        $isHtml = str_contains($contentType, 'text/html');
        $isJson = str_contains($contentType, 'application/json');

        if (! $isHtml && ! $isJson) {
            return $response;
        }

        $content = $response->getContent();

        if (! $content) {
            return $response;
        }

        $converted = str_replace(self::AR_DIGITS, self::LA_DIGITS, $content);

        if ($converted !== $content) {
            $response->setContent($converted);
        }

        return $response;
    }
}
