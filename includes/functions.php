<?php

/**
 * Helper: Convert image to WebP
 * Quality 85 = good balance of size and quality
 * Preserves PNG transparency
 */
function convertToWebp($sourcePath, $destPath, $quality = 85)
{
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $img = false;

    if ($ext === 'jpg' || $ext === 'jpeg') {
        $img = @imagecreatefromjpeg($sourcePath);
    } elseif ($ext === 'png') {
        $img = @imagecreatefrompng($sourcePath);
        if ($img) {
            // Convert palette to true color for proper alpha handling
            imagepalettetotruecolor($img);
            // Disable alpha blending to preserve transparency
            imagealphablending($img, false);
            // Save alpha channel
            imagesavealpha($img, true);
        }
    }

    if ($img) {
        imagewebp($img, $destPath, $quality);
        imagedestroy($img);
        return true;
    }
    return false;
}

/**
 * Helper: Convert image to AVIF (PHP 8.1+ only)
 * Quality 70 = AVIF is more efficient, 70 looks like JPEG 85-90
 * Preserves PNG transparency
 */
function convertToAvif($sourcePath, $destPath, $quality = 70)
{
    if (!function_exists('imageavif')) {
        return false;
    }

    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $img = false;

    if ($ext === 'jpg' || $ext === 'jpeg') {
        $img = @imagecreatefromjpeg($sourcePath);
    } elseif ($ext === 'png') {
        $img = @imagecreatefrompng($sourcePath);
        if ($img) {
            // Convert palette to true color for proper alpha handling
            imagepalettetotruecolor($img);
            // Disable alpha blending to preserve transparency
            imagealphablending($img, false);
            // Save alpha channel
            imagesavealpha($img, true);
        }
    }

    if ($img) {
        // imageavif params: image, file, quality, speed (0-10, lower = better quality)
        imageavif($img, $destPath, $quality, 6);
        imagedestroy($img);
        return true;
    }
    return false;
}

/**
 * imgx()
 * Renders an image using <picture> tag with AVIF + WebP support.
 * Automatically converts .png or .jpg to .avif and .webp if not yet converted.
 * AVIF requires PHP 8.1+ with GD AVIF support, falls back to WebP only if not available.
 *
 * @param string $src           - Base path without extension (e.g. 'banner/home')
 * @param string $alt           - Alt text for the image
 * @param string $class         - Optional class name
 * @param string $mobileSrc     - Optional mobile version (same as $src, auto-converts too)
 * @param string $attributes    - Additional attributes for img tag
 * @param bool   $lazy          - Lazy loading (default: true)
 * @param int    $mobileBreakpoint - Mobile breakpoint in pixels (default: 768)
 */
function imgx($src, $alt = '', $class = '', $mobileSrc = '', $attributes = '', $lazy = true, $mobileBreakpoint = 768)
{
    global $basePath;
    $webPath = ($basePath ?? '/') . 'assets/images/';
    $baseDir = __DIR__ . '/../assets/images/';
    $filePath = realpath($baseDir);

    // Handle case where directory doesn't exist
    if ($filePath === false) {
        echo "<!-- Images directory not found -->";
        return;
    }
    $filePath .= '/';

    $basename = pathinfo($src, PATHINFO_FILENAME);
    $dir = dirname($src) !== '.' ? dirname($src) . '/' : '';
    $extensions = ['png', 'jpg', 'jpeg'];
    $found = false;
    $avifSupported = function_exists('imageavif');

    foreach ($extensions as $ext) {
        $try = $dir . $basename . '.' . $ext;
        if (file_exists($filePath . $try)) {
            $src = $try;
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo "<!-- Image not found: $src -->";
        return;
    }

    $fullSrcPath = $filePath . $src;
    $fullWebPath = $webPath . $src;

    // File paths for converted formats
    $webpFileName = preg_replace('/\.(png|jpe?g)$/i', '.webp', $src);
    $webpPath = $webPath . $webpFileName;
    $webpFile = $filePath . $webpFileName;

    $avifFileName = preg_replace('/\.(png|jpe?g)$/i', '.avif', $src);
    $avifPath = $webPath . $avifFileName;
    $avifFile = $filePath . $avifFileName;

    // Convert to AVIF (if supported)
    if ($avifSupported && !file_exists($avifFile) && file_exists($fullSrcPath)) {
        convertToAvif($fullSrcPath, $avifFile);
    }

    // Convert to WebP
    if (!file_exists($webpFile) && file_exists($fullSrcPath)) {
        convertToWebp($fullSrcPath, $webpFile);
    }

    // Get dimensions (with fallback if getimagesize fails)
    $imageInfo = @getimagesize($fullSrcPath);
    $size = '';
    if ($imageInfo && isset($imageInfo[0], $imageInfo[1])) {
        $w = $imageInfo[0];
        $h = $imageInfo[1];
        $size = " width=\"$w\" height=\"$h\"";
    }
    $alt = htmlspecialchars($alt);
    $class = $class ? " class=\"$class\"" : '';
    $loading = '';
    if ($lazy && stripos($attributes, 'loading=') === false) {
        $loading = ' loading="lazy"';
    } elseif (!$lazy && stripos($attributes, 'loading=') === false) {
        $loading = ' loading="eager"';
    }

    echo '<picture>';

    // Mobile version sources
    if (!empty($mobileSrc)) {
        $mobileBase = pathinfo($mobileSrc, PATHINFO_FILENAME);
        $mobileDir  = dirname($mobileSrc) !== '.' ? dirname($mobileSrc) . '/' : '';
        $mobileFound = false;

        foreach ($extensions as $ext) {
            $mobileTry = $mobileDir . $mobileBase . '.' . $ext;
            if (file_exists($filePath . $mobileTry)) {
                $mobileSrc = $mobileTry;
                $mobileFound = true;
                break;
            }
        }

        if ($mobileFound) {
            $fullMobilePath = $filePath . $mobileSrc;
            $maxWidth = $mobileBreakpoint - 1;

            // Mobile AVIF - only CREATE if PHP supports it, but OUTPUT if file exists
            $mobileAvifFileName = preg_replace('/\.(png|jpe?g)$/i', '.avif', $mobileSrc);
            $mobileAvifPath = $webPath . $mobileAvifFileName;
            $mobileAvifFile = $filePath . $mobileAvifFileName;
            if ($avifSupported && !file_exists($mobileAvifFile) && file_exists($fullMobilePath)) {
                convertToAvif($fullMobilePath, $mobileAvifFile);
            }
            // Output AVIF source if file exists (regardless of PHP version)
            if (file_exists($mobileAvifFile)) {
                echo '<source srcset="' . $mobileAvifPath . '" media="(max-width: ' . $maxWidth . 'px)" type="image/avif">';
            }

            // Mobile WebP
            $mobileWebpFileName = preg_replace('/\.(png|jpe?g)$/i', '.webp', $mobileSrc);
            $mobileWebpPath = $webPath . $mobileWebpFileName;
            $mobileWebpFile = $filePath . $mobileWebpFileName;
            if (!file_exists($mobileWebpFile) && file_exists($fullMobilePath)) {
                convertToWebp($fullMobilePath, $mobileWebpFile);
            }
            // Output WebP source if file exists
            if (file_exists($mobileWebpFile)) {
                echo '<source srcset="' . $mobileWebpPath . '" media="(max-width: ' . $maxWidth . 'px)" type="image/webp">';
            }
        }
    }

    // Desktop AVIF - output if file exists (works on PHP 7.4 if file was generated on 8.1)
    if (file_exists($avifFile)) {
        echo '<source srcset="' . $avifPath . '" type="image/avif">';
    }

    // Desktop WebP - output if file exists
    if (file_exists($webpFile)) {
        echo '<source srcset="' . $webpPath . '" type="image/webp">';
    }

    // Fallback original
    echo '<img src="' . $fullWebPath . "\" alt=\"$alt\"$class$loading$size $attributes>";
    echo '</picture>';
}


/**
 * svgx()
 * Renders a .svg file as a regular <img> tag.
 * Automatically detects width and height from the file.
 *
 * @param string $src    - Path without extension (e.g. 'icons/arrow-left')
 * @param string $alt    - Alt text
 * @param string $class  - Optional class name
 *
 * Example:
 * svgx('icons/close', 'Close Icon', 'icon');
 */
function svgx($src, $alt = '', $class = '')
{
    global $basePath;
    $webPath = ($basePath ?? '/') . 'assets/images/';
    $filePath = realpath(__DIR__ . '/../assets/images/');
    if ($filePath === false) {
        echo "<!-- Images directory not found -->";
        return;
    }
    $filePath .= '/';

    $basename = pathinfo($src, PATHINFO_FILENAME);
    $dir = dirname($src) !== '.' ? dirname($src) . '/' : '';
    $svgFile = $dir . $basename . '.svg';

    $fullSrcPath = $filePath . $svgFile;
    $webSrcPath  = $webPath . $svgFile;

    if (!file_exists($fullSrcPath)) {
        echo "<!-- SVG not found: $svgFile -->";
        return;
    }

    // Default empty size
    $width = '';
    $height = '';

    // Read SVG content and parse width/height from the <svg> tag
    $svgContent = file_get_contents($fullSrcPath);

    if (preg_match('/<svg[^>]*\swidth="([\d.]+)(px)?"/i', $svgContent, $wMatch)) {
        $width = ' width="' . $wMatch[1] . '"';
    }

    if (preg_match('/<svg[^>]*\sheight="([\d.]+)(px)?"/i', $svgContent, $hMatch)) {
        $height = ' height="' . $hMatch[1] . '"';
    }

    $altAttr = htmlspecialchars($alt);
    $classAttr = $class ? " class=\"$class\"" : '';

    echo "<img src=\"$webSrcPath\" alt=\"$altAttr\"$classAttr$width$height>";
}




/**
 * svg_inline()
 * Outputs inline SVG code directly into HTML.
 * Allows styling/animation of internal SVG elements via CSS or JS.
 *
 * @param string $src    - Path without extension (e.g. 'icons/arrow-right')
 * @param string $class  - Optional class added to <svg> tag
 *
 * Example:
 * svg_inline('icons/arrow-right', 'icon-inline');
 */
function svg_inline($src, $class = '')
{
    $filePath = realpath(__DIR__ . '/../assets/images/');
    if ($filePath === false) {
        echo "<!-- Images directory not found -->";
        return;
    }
    $filePath .= '/';
    $basename = pathinfo($src, PATHINFO_FILENAME);
    $dir = dirname($src) !== '.' ? dirname($src) . '/' : '';
    $svgFile = $dir . $basename . '.svg';
    $fullSrcPath = $filePath . $svgFile;

    if (!file_exists($fullSrcPath)) {
        echo "<!-- Inline SVG not found: $svgFile -->";
        return;
    }

    $svgContent = file_get_contents($fullSrcPath);
    if ($class) {
        $svgContent = preg_replace('/<svg\b([^>]*)>/', '<svg$1 class="' . $class . '">', $svgContent, 1);
    }

    echo $svgContent;
}
