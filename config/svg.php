<?php
/**
 * SVG helpers
 * ───────────
 *  Public:
 *    svgx($src, $alt, $class, $opts)
 *      Renders an .svg file as a regular <img> tag with auto-detected
 *      dimensions (incl. viewBox fallback).
 *
 *    svg_inline($src, $class, $opts)
 *      Inlines an .svg file's contents into the HTML — lets you style
 *      paths/fills via CSS or JS. Safely merges class with any existing
 *      class on the <svg> root, strips XML declarations.
 *
 *  Internal helpers (prefixed with _, do not call directly from templates):
 *    _read_svg_cached($path)     — file_get_contents wrapped in static cache
 *    _svg_dimensions($content)   — width/height with viewBox fallback
 *    _svg_inject_class($content, $class) — class merge (no duplicate attrs)
 *    _strip_svg_decl($content)   — drop <?xml...?> and <!DOCTYPE...>
 */


// ============================================================
//  Internal helpers
// ============================================================

/**
 * Read SVG file contents, cached per-request so the same icon
 * inlined N times only hits disk once.
 */
function _read_svg_cached(string $path): string|false
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        $cache[$path] = @file_get_contents($path);
    }
    return $cache[$path];
}

/**
 * Extract width/height from an SVG, with viewBox fallback.
 * Many modern SVGs ship with viewBox only — without this fallback the
 * <img> tag has no intrinsic size and the layout shifts on render.
 *
 * Returns ['width' => string|null, 'height' => string|null].
 */
function _svg_dimensions(string $content): array
{
    $w = null;
    $h = null;

    if (preg_match('/<svg[^>]*\swidth="([\d.]+)(?:px)?"/i', $content, $m))  $w = $m[1];
    if (preg_match('/<svg[^>]*\sheight="([\d.]+)(?:px)?"/i', $content, $m)) $h = $m[1];

    // viewBox fallback — pulls width/height from the box's third + fourth values
    if ($w === null || $h === null) {
        if (preg_match('/<svg[^>]*\sviewBox="\s*[-\d.]+\s+[-\d.]+\s+([\d.]+)\s+([\d.]+)/i', $content, $m)) {
            if ($w === null) $w = $m[1];
            if ($h === null) $h = $m[2];
        }
    }

    return ['width' => $w, 'height' => $h];
}

/**
 * Add a CSS class to the root <svg> tag.
 * If a class attribute already exists, append to it (avoids invalid HTML
 * with duplicate `class="..."` attributes).
 */
function _svg_inject_class(string $content, string $class): string
{
    if ($class === '') return $content;

    // Case 1: <svg> already has class="..." — merge
    if (preg_match('/<svg([^>]*?)\sclass="([^"]*)"([^>]*)>/i', $content, $m)) {
        $merged = trim($m[2] . ' ' . $class);
        return preg_replace(
            '/<svg([^>]*?)\sclass="([^"]*)"([^>]*)>/i',
            '<svg$1 class="' . htmlspecialchars($merged, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"$3>',
            $content,
            1
        );
    }

    // Case 2: no existing class — inject one
    return preg_replace(
        '/<svg\b([^>]*)>/i',
        '<svg$1 class="' . htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">',
        $content,
        1
    );
}

/**
 * Strip the XML declaration and DOCTYPE from SVG content so it can be
 * inlined into HTML without breaking the parser.
 */
function _strip_svg_decl(string $content): string
{
    $content = preg_replace('/<\?xml[^?]*\?>\s*/', '', $content);
    $content = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $content);
    return ltrim($content);
}


// ============================================================
//  Public render — svgx() (SVG as <img>)
// ============================================================

/**
 * Render an SVG file as an <img> tag. Auto-detects width/height (with
 * viewBox fallback). Suitable for SVGs you don't need to style internally.
 *
 * @param string $src    Path WITHOUT extension, relative to assets/images/
 *                       e.g. 'logo', 'icons/check'
 * @param string $alt    Alt text
 * @param string $class  Optional CSS class on the <img>
 * @param array  $opts   Advanced options:
 *                       [
 *                         'lazy'     => true|false (default true),
 *                         'decoding' => 'async'|'sync'|'auto' (default 'async'),
 *                         'width'    => int (override),
 *                         'height'   => int (override),
 *                         'fetchpriority' => 'high'|'low'|'auto',
 *                       ]
 */
function svgx(string $src, string $alt = '', string $class = '', array $opts = []): void
{
    global $basePath;

    $webPath = ($basePath ?? '/') . 'assets/images/';
    $baseDir = realpath(__DIR__ . '/../assets/images/');

    if ($baseDir === false) {
        _dev_comment("Images directory not found");
        return;
    }
    $baseDir .= '/';

    $basename = pathinfo($src, PATHINFO_FILENAME);
    $dir      = dirname($src) !== '.' ? dirname($src) . '/' : '';
    $svgFile  = $dir . $basename . '.svg';

    $absPath  = $baseDir . $svgFile;
    $webSrc   = $webPath . $svgFile;

    if (!file_exists($absPath)) {
        _dev_comment("SVG not found: $svgFile");
        return;
    }

    // Read once, parse dimensions
    $content = _read_svg_cached($absPath);
    $dims    = $content !== false ? _svg_dimensions($content) : ['width' => null, 'height' => null];

    $width  = $opts['width']  ?? $dims['width'];
    $height = $opts['height'] ?? $dims['height'];

    $altSafe   = htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"' : '';
    $widthAttr  = $width  !== null ? ' width="' . (int) $width . '"' : '';
    $heightAttr = $height !== null ? ' height="' . (int) $height . '"' : '';

    $lazy     = !isset($opts['lazy']) || $opts['lazy'] !== false;
    $loading  = ' loading="' . ($lazy ? 'lazy' : 'eager') . '"';
    $decoding = ' decoding="' . ($opts['decoding'] ?? 'async') . '"';

    $fetchPriority = '';
    if (!empty($opts['fetchpriority']) && in_array($opts['fetchpriority'], ['high', 'low', 'auto'], true)) {
        $fetchPriority = ' fetchpriority="' . $opts['fetchpriority'] . '"';
    }

    echo '<img src="' . $webSrc . '"'
        . ' alt="' . $altSafe . '"'
        . $classAttr
        . $widthAttr
        . $heightAttr
        . $loading
        . $decoding
        . $fetchPriority
        . '>';
}


// ============================================================
//  Public render — svg_inline() (inline SVG markup)
// ============================================================

/**
 * Inline an SVG file's contents into the HTML so individual paths/fills
 * can be styled via CSS or animated via JS.
 *
 * Strips XML declarations and DOCTYPEs (which break HTML parsing).
 * Merges $class with any existing class attribute on the root <svg>.
 *
 * SECURITY: only use with trusted SVGs you control. Inline SVG can contain
 * <script> tags — never inline user-uploaded SVG without sanitizing.
 *
 * @param string $src    Path WITHOUT extension, relative to assets/images/
 * @param string $class  Optional class added to the root <svg> tag
 */
function svg_inline(string $src, string $class = ''): void
{
    $baseDir = realpath(__DIR__ . '/../assets/images/');
    if ($baseDir === false) {
        _dev_comment("Images directory not found");
        return;
    }
    $baseDir .= '/';

    $basename = pathinfo($src, PATHINFO_FILENAME);
    $dir      = dirname($src) !== '.' ? dirname($src) . '/' : '';
    $svgFile  = $dir . $basename . '.svg';
    $absPath  = $baseDir . $svgFile;

    if (!file_exists($absPath)) {
        _dev_comment("Inline SVG not found: $svgFile");
        return;
    }

    $content = _read_svg_cached($absPath);
    if ($content === false) {
        _dev_comment("Failed to read SVG: $svgFile");
        return;
    }

    $content = _strip_svg_decl($content);
    $content = _svg_inject_class($content, $class);

    echo $content;
}
