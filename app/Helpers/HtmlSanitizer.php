<?php

namespace App\Helpers;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><ul><ol><li><span><div><h1><h2><h3><h4><h5><h6><a><table><thead><tbody><tr><th><td>';

    // Attributes whose values may contain javascript: or data: URIs
    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction'];

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $stripped = strip_tags($html, self::ALLOWED_TAGS);

        return self::stripDangerousAttributes($stripped);
    }

    private static function stripDangerousAttributes(string $html): string
    {
        if (! class_exists(\DOMDocument::class)) {
            return $html;
        }

        $dom = new \DOMDocument();

        // Suppress parse warnings; UTF-8 wrapper prevents charset corruption.
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('*') as $node) {
            foreach (self::URL_ATTRIBUTES as $attr) {
                if (! $node->hasAttribute($attr)) {
                    continue;
                }

                $value = trim($node->getAttribute($attr));

                // Block javascript: and data: URI schemes (case-insensitive, handles obfuscation like &#106;avascript:)
                $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $scheme  = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', $decoded));

                if (
                    str_starts_with($scheme, 'javascript:')
                    || str_starts_with($scheme, 'vbscript:')
                    || str_starts_with($scheme, 'data:')
                ) {
                    $node->removeAttribute($attr);
                }
            }

            // Remove event handler attributes (onclick, onmouseover, etc.)
            $attrsToRemove = [];
            foreach ($node->attributes as $attribute) {
                if (str_starts_with(strtolower($attribute->nodeName), 'on')) {
                    $attrsToRemove[] = $attribute->nodeName;
                }
            }
            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        // Extract only the body content to avoid html/head wrapper injection.
        $body = $dom->getElementsByTagName('body')->item(0);

        if (! $body) {
            return $html;
        }

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }
}
