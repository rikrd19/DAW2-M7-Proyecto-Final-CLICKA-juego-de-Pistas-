<?php
/**
 * DiceBear HTTP avatar URL helpers (shared by api/* and processes/*).
 *
 * DiceBear is an image URL endpoint, not a JSON document API. Our JSON
 * contract lives in api/dicebear_gallery.php; this file only builds URLs
 * and validates stored remote URLs.
 */

if (!defined('DICEBEAR_HOST')) {
    define('DICEBEAR_VERSION_PATH', '7.x');
    define('DICEBEAR_HOST', 'https://api.dicebear.com');
}

/**
 * @return list<string>
 */
function dicebear_gallery_styles(): array
{
    return [
        'avataaars', 'bottts', 'pixel-art', 'adventurer', 'big-smile', 'croodles',
        'micah', 'miniavs', 'lorelei', 'notionists', 'open-peeps', 'personas',
        'thumbs', 'identicon', 'rings', 'shapes',
    ];
}

/**
 * @return list<string>
 */
function dicebear_quick_styles(): array
{
    return ['avataaars', 'bottts', 'pixel-art', 'adventurer'];
}

/**
 * Build a DiceBear 7.x SVG URL. Extra query keys may be used for cache busting.
 */
function dicebear_avatar_url(string $style, string $seed, array $extraQuery = []): string
{
    static $allowed = null;
    if ($allowed === null) {
        $allowed = array_flip(dicebear_gallery_styles());
    }

    if (!isset($allowed[$style])) {
        $style = 'avataaars';
    }

    $path = '/' . DICEBEAR_VERSION_PATH . '/' . rawurlencode($style) . '/svg';
    $query = array_merge(['seed' => $seed], $extraQuery);

    return DICEBEAR_HOST . $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function dicebear_is_allowed_remote_avatar_url(string $url): bool
{
    $parts = parse_url($url);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host']) || empty($parts['path'])) {
        return false;
    }

    if (strtolower($parts['scheme']) !== 'https' || strtolower($parts['host']) !== 'api.dicebear.com') {
        return false;
    }

    return (bool) preg_match('#^/' . preg_quote(DICEBEAR_VERSION_PATH, '#') . '/[a-z0-9][a-z0-9\-]*/svg$#i', $parts['path']);
}
