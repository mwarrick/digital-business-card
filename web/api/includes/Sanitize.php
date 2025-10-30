<?php
/**
 * Sanitization helpers for URLs, usernames and limited HTML.
 */

class Sanitize {
    /** Allow only http/https URLs; returns null if invalid. */
    public static function url(?string $url): ?string {
        if (!$url) return null;
        $url = trim($url);
        if (!preg_match('/^https?:\/\//i', $url)) return null;
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /** Basic username allowlist: letters, numbers, underscore, dot, hyphen (no @ prefix). */
    public static function username(?string $name): ?string {
        if (!$name) return null;
        $name = ltrim(trim($name), '@');
        if (!preg_match('/^[A-Za-z0-9_\.\-]{1,50}$/', $name)) return null;
        return $name;
    }

    /**
     * Sanitize a small subset of HTML for landing content. Removes scripts and event handlers.
     * Allowed tags: p, br, strong, em, b, i, ul, ol, li, a, h1-h3.
     * Allowed attributes: href (http/https), target, rel on <a>.
     */
    public static function landingHtml(?string $html): string {
        if (!$html) return '';
        $allowed = '<p><br><strong><em><b><i><ul><ol><li><a><h1><h2><h3>';
        $clean = strip_tags($html, $allowed);
        // Remove on* attributes and javascript: URLs
        $clean = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace('/\son[a-z]+\s*=\s*\'[^\']*\'/i', '', $clean);
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        // Validate anchor hrefs
        $clean = preg_replace_callback('/<a\b([^>]*)>/i', function($m) {
            $attrs = $m[1];
            // Extract href
            if (preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $attrs, $am)) {
                $href = $am[2];
                if (!preg_match('/^https?:\/\//i', $href)) {
                    // drop href if not http(s)
                    $attrs = preg_replace('/href\s*=\s*(["\']).*?\1/i', '', $attrs);
                }
            }
            // Ensure rel for target=_blank
            if (preg_match('/target\s*=\s*(["\'])_blank\1/i', $attrs)) {
                if (preg_match('/rel\s*=\s*(["\']).*?\1/i', $attrs)) {
                    // ok
                } else {
                    $attrs .= ' rel="noopener noreferrer"';
                }
            }
            return '<a' . $attrs . '>';
        }, $clean);
        return $clean;
    }
}


