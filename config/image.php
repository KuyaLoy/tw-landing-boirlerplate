<?php
/**
 * Image helpers
 * ─────────────
 *  Public:
 *    imgx($src, $alt, $class, $mobileSrc, $attributes, $lazy, $mobileBreakpoint, $opts)
 *      Renders <picture> with auto AVIF + WebP fallback, optional mobile
 *      variant, optional retina @2x, fetchpriority, decoding hints.
 *
 *  Conversion utilities (called automatically by imgx, but exposed if needed):
 *    convertToWebp($src, $dest, $quality = 85)
 *    convertToAvif($src, $dest, $quality = 70)
 *
 *  Internal helpers (prefixed with _, do not call directly from templates):
 *    _load_gd_image($path)         — open JPEG/PNG into GD resource (DRY)
 *    _should_reconvert($src, $cached) — mtime check
 *    _cached_imagesize($path)       — getimagesize wrapped in static cache
 *    _resolve_image_path($src, $base, $exts)
 *    _dev_comment($msg)             — silent in prod, HTML comment in dev
 */


// ============================================================
//  Internal helpers
// ============================================================

/**
 * Open a PNG or JPEG into a GD resource.
 * Handles palette → truecolor conversion + alpha for PNG.
 * Returns false if file can't be opened or extension isn't supported.
 */
function _load_gd_image(string $path)
{
    if (!file_exists($path) || !function_exists('imagecreatefromjpeg')) {
        return false;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $img = false;

    if ($ext === 'jpg' || $ext === 'jpeg') {
        $img = @imagecreatefromjpeg($path);
    } elseif ($ext === 'png') {
        $img = @imagecreatefrompng($path);
        if ($img) {
            imagepalettetotruecolor($img);
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }
    }
    return $img;
}

/**
 * Should we (re)convert? Yes if cached version is missing or older than source.
 */
function _should_reconvert(string $sourcePath, string $cachedPath): bool
{
    if (!file_exists($cachedPath)) return true;
    if (!file_exists($sourcePath)) return false;
    return @filemtime($sourcePath) > @filemtime($cachedPath);
}

/**
 * Cached getimagesize() — same path within one request hits disk only once.
 */
function _cached_imagesize(string $path)
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        $cache[$path] = @getimagesize($path) ?: false;
    }
    return $cache[$path];
}

/**
 * Find an image file by trying multiple extensions on a base path.
 * Returns the relative path (with extension) if found, false otherwise.
 *
 *   _resolve_image_path('hero', '/abs/assets/images/', ['png','jpg','jpeg'])
 *     → 'hero.jpg' if /abs/assets/images/hero.jpg exists
 */
function _resolve_image_path(string $src, string $baseDir, array $exts): string|false
{
    $basename = pathinfo($src, PATHINFO_FILENAME);
    $dir      = dirname($src) !== '.' ? dirname($src) . '/' : '';

    // If src already has an extension we recognize, use it as-is.
    $existingExt = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    if ($existingExt && in_array($existingExt, $exts, true)) {
        return file_exists($baseDir . $src) ? $src : false;
    }

    foreach ($exts as $ext) {
        $try = $dir . $basename . '.' . $ext;
        if (file_exists($baseDir . $try)) return $try;
    }
    return false;
}

/**
 * Emit a one-line HTML comment with a debug message — only in dev mode.
 * Silent in production so the deployed source stays clean.
 */
function _dev_comment(string $msg): void
{
    global $isDevelopment;
    if (!empty($isDevelopment)) {
        echo "<!-- [boilerplate] " . htmlspecialchars($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8') . " -->";
    }
}


// ============================================================
//  Conversion utilities
// ============================================================

/**
 * Convert PNG/JPEG to WebP. Quality 85 ≈ JPEG quality 90 visually.
 */
function convertToWebp(string $sourcePath, string $destPath, int $quality = 85): bool
{
    $img = _load_gd_image($sourcePath);
    if (!$img) return false;
    $ok = @imagewebp($img, $destPath, $quality);
    imagedestroy($img);
    return (bool) $ok;
}

/**
 * Convert PNG/JPEG to AVIF (PHP 8.1+ with GD AVIF support required).
 * Quality 70 ≈ JPEG quality 85-90 visually. Speed 6 = balanced encode time.
 */
function convertToAvif(string $sourcePath, string $destPath, int $quality = 70): bool
{
    if (!function_exists('imageavif')) return false;
    $img = _load_gd_image($sourcePath);
    if (!$img) return false;
    $ok = @imageavif($img, $destPath, $quality, 6);
    imagedestroy($img);
    return (bool) $ok;
}


// ============================================================
//  Public render — imgx()
// ============================================================

/**
 * Render an image as <picture> with AVIF + WebP fallback.
 *
 * @param string $src                Path WITHOUT extension, relative to assets/images/
 *                                   e.g. 'hero', 'banners/spring-2025'
 * @param string $alt                Alt text (rendered with htmlspecialchars)
 * @param string $class              Optional CSS class for the <img>
 * @param string $mobileSrc          Optional mobile-specific image (auto AVIF/WebP)
 * @param string $attributes         Extra raw attributes (e.g. 'data-foo="bar"')
 * @param bool   $lazy               loading="lazy" (default) vs "eager"
 * @param int    $mobileBreakpoint   px below which the mobile variant kicks in
 * @param array  $opts               Advanced options (preferred over juggling positional empties):
 *                                   [
 *                                     'fetchpriority' => 'high'|'low'|'auto',
 *                                     'decoding'      => 'async'|'sync'|'auto'  (default 'async'),
 *                                     'width'         => int   (override detected),
 *                                     'height'        => int   (override detected),
 *                                     'retina'        => true|false (auto @2x srcset, default true),
 *                                     'sizes'         => string (img sizes attr),
 *                                   ]
 *
 * Examples:
 *   imgx('hero', 'Hero');                                           // basic
 *   imgx('hero', 'Hero', 'rounded');                                // with class
 *   imgx('hero-desktop', 'Hero', '', 'hero-mobile');                // mobile variant
 *   imgx('hero', 'Hero', '', '', '', false);                        // eager
 *   imgx('hero', 'Hero', '', '', '', false, 768,                    // hero LCP fix:
 *        ['fetchpriority' => 'high']);                              // priority hint
 */
function imgx(
    string $src,
    string $alt = '',
    string $class = '',
    string $mobileSrc = '',
    string $attributes = '',
    bool   $lazy = true,
    int    $mobileBreakpoint = 768,
    array  $opts = []
): void {
    global $basePath;

    $webPath = ($basePath ?? '/') . 'assets/images/';
    $baseDir = realpath(__DIR__ . '/../assets/images/');

    if ($baseDir === false) {
        _dev_comment("Images directory not found");
        return;
    }
    $baseDir .= '/';

    $rasterExts = ['png', 'jpg', 'jpeg'];

    // ---- Resolve source ----
    $resolved = _resolve_image_path($src, $baseDir, $rasterExts);
    if ($resolved === false) {
        _dev_comment("Image not found: $src");
        return;
    }
    $src = $resolved;

    $fullSrcPath = $baseDir . $src;
    $fullWebPath = $webPath . $src;
    $avifSupported = function_exists('imageavif');

    // ---- Helper: ensure converted formats exist + return their absolute web URL ----
    $ensureConvert = function (string $relPath, string $newExt) use ($baseDir, $webPath, $avifSupported) {
        $converted = preg_replace('/\.(png|jpe?g)$/i', '.' . $newExt, $relPath);
        $absSrc    = $baseDir . $relPath;
        $absConv   = $baseDir . $converted;

        if (!file_exists($absSrc)) return null;

        if ($newExt === 'avif') {
            if (!$avifSupported) return file_exists($absConv) ? $webPath . $converted : null;
            if (_should_reconvert($absSrc, $absConv)) convertToAvif($absSrc, $absConv);
        } elseif ($newExt === 'webp') {
            if (_should_reconvert($absSrc, $absConv)) convertToWebp($absSrc, $absConv);
        }

        return file_exists($absConv) ? $webPath . $converted : null;
    };

    // ---- Build sources for desktop (and 2x retina if available) ----
    $retina      = !isset($opts['retina']) || $opts['retina'] !== false;
    $retina2xSrc = null;
    if ($retina) {
        $retinaName = preg_replace('/(\.[^.]+)$/', '@2x$1', $src);
        if (file_exists($baseDir . $retinaName)) {
            $retina2xSrc = $retinaName;
        }
    }

    $desktopAvif = $ensureConvert($src, 'avif');
    $desktopWebp = $ensureConvert($src, 'webp');
    $desktopAvif2x = $retina2xSrc ? $ensureConvert($retina2xSrc, 'avif') : null;
    $desktopWebp2x = $retina2xSrc ? $ensureConvert($retina2xSrc, 'webp') : null;

    // ---- Build attributes for the fallback <img> ----
    $altSafe   = htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"' : '';

    // Loading / decoding / fetchpriority
    $loading = '';
    if (stripos($attributes, 'loading=') === false) {
        $loading = $lazy ? ' loading="lazy"' : ' loading="eager"';
    }
    $decoding = $opts['decoding'] ?? 'async';
    $decodingAttr = (stripos($attributes, 'decoding=') === false) ? ' decoding="' . $decoding . '"' : '';
    $fetchPriorityAttr = '';
    if (!empty($opts['fetchpriority']) && in_array($opts['fetchpriority'], ['high', 'low', 'auto'], true)) {
        $fetchPriorityAttr = ' fetchpriority="' . $opts['fetchpriority'] . '"';
    }

    // Width / height (explicit > detected). Always emit if known — prevents CLS.
    $width  = $opts['width']  ?? null;
    $height = $opts['height'] ?? null;
    if ($width === null || $height === null) {
        $info = _cached_imagesize($fullSrcPath);
        if ($info && isset($info[0], $info[1])) {
            $width  ??= $info[0];
            $height ??= $info[1];
        }
    }
    $sizeAttr = '';
    if ($width !== null)  $sizeAttr .= ' width="' . (int) $width . '"';
    if ($height !== null) $sizeAttr .= ' height="' . (int) $height . '"';

    $sizesAttr = !empty($opts['sizes']) ? ' sizes="' . htmlspecialchars($opts['sizes'], ENT_QUOTES) . '"' : '';

    // ---- Render ----
    echo '<picture>';

    // ---- Mobile sources (only if mobileSrc was provided AND found) ----
    if ($mobileSrc !== '') {
        $mResolved = _resolve_image_path($mobileSrc, $baseDir, $rasterExts);
        if ($mResolved !== false) {
            $maxW = $mobileBreakpoint - 1;
            $media = '(max-width: ' . $maxW . 'px)';

            $mAvif = $ensureConvert($mResolved, 'avif');
            $mWebp = $ensureConvert($mResolved, 'webp');

            if ($mAvif) echo '<source srcset="' . $mAvif . '" media="' . $media . '" type="image/avif">';
            if ($mWebp) echo '<source srcset="' . $mWebp . '" media="' . $media . '" type="image/webp">';
        }
    }

    // ---- Desktop sources (with optional 2x retina) ----
    if ($desktopAvif) {
        $srcset = $desktopAvif;
        if ($desktopAvif2x) $srcset .= ' 1x, ' . $desktopAvif2x . ' 2x';
        echo '<source srcset="' . $srcset . '" type="image/avif">';
    }
    if ($desktopWebp) {
        $srcset = $desktopWebp;
        if ($desktopWebp2x) $srcset .= ' 1x, ' . $desktopWebp2x . ' 2x';
        echo '<source srcset="' . $srcset . '" type="image/webp">';
    }

    // ---- Fallback <img> (the original) ----
    echo '<img src="' . $fullWebPath . '"'
        . ' alt="' . $altSafe . '"'
        . $classAttr
        . $loading
        . $decodingAttr
        . $fetchPriorityAttr
        . $sizeAttr
        . $sizesAttr
        . ($attributes !== '' ? ' ' . $attributes : '')
        . '>';

    echo '</picture>';
}
