<?php
// Load .env helpers FIRST so secrets can be pulled from outside source control
require_once(__DIR__ . '/env.php');

// ----------------------------------------------
// Trusted host — defends against Host header injection.
// ----------------------------------------------
// $_SERVER['HTTP_HOST'] is set by the client and CAN be spoofed, which would
// poison $baseUrl, $domain, and the From: address on outgoing form emails.
// We sanitize the value, then validate it against ALLOWED_HOSTS from .env
// before letting it flow anywhere user-visible.
//
// Allowlist syntax: comma-separated. Supports *.suffix wildcards.
//   ALLOWED_HOSTS=angel.com, www.angel.com, *.angel.com
// Leave ALLOWED_HOSTS blank for local development — localhost / .test /
// .local / 192.168.* are always trusted automatically.

$rawHost = strtolower(trim($_SERVER['HTTP_HOST'] ?? ''));
// Strict charset: only valid hostname characters (incl. IPv6 brackets)
if ($rawHost === '' || !preg_match('/^[a-z0-9.\-:\[\]]+$/', $rawHost)) {
    $rawHost = '';
}

// Split host:port — keep both, but compare/derive emails from hostname only
$hostName = $rawHost;
$port     = '';
if ($rawHost !== '' && $rawHost[0] !== '[' && ($p = strrpos($rawHost, ':')) !== false) {
    $port     = substr($rawHost, $p);          // ":3000"
    $hostName = substr($rawHost, 0, $p);       // "localhost"
}

// Local dev hosts are always trusted, no allowlist needed
$isLocalDev = (
    $hostName === 'localhost' ||
    $hostName === '127.0.0.1' ||
    strpos($hostName, '192.168.') === 0 ||
    (strlen($hostName) > 5 && substr($hostName, -5) === '.test') ||
    (strlen($hostName) > 6 && substr($hostName, -6) === '.local')
);

// Parse allowlist
$allowedHosts = array_values(array_filter(array_map(
    function ($h) { return strtolower(trim($h)); },
    explode(',', env('ALLOWED_HOSTS', ''))
)));

// Match against allowlist (exact host or *.suffix wildcard)
$hostAllowed = false;
foreach ($allowedHosts as $pattern) {
    if ($pattern === '') continue;
    if ($pattern === $hostName) { $hostAllowed = true; break; }
    if (substr($pattern, 0, 2) === '*.') {
        $suffix = substr($pattern, 1);         // ".angel.com"
        $hl = strlen($hostName);
        $sl = strlen($suffix);
        if ($hl > $sl && substr($hostName, -$sl) === $suffix) {
            $hostAllowed = true;
            break;
        }
    }
}

// Enforcement: production with allowlist set + host doesn't match → reject.
// Dev hosts and the "no allowlist configured" case fall through unchanged.
if (!empty($allowedHosts) && !$hostAllowed && !$isLocalDev) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Invalid host');
}

// Anything below this line uses validated values
if ($hostName === '') {
    $hostName = 'localhost';
}
$host = $hostName . $port;

// Error reporting — dev verbose, prod silent. ?debug=1 forces verbose.
$isDevelopment = $isLocalDev;
$debugMode     = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($isDevelopment || $debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Project root on the filesystem (one level up from /config)
$projectDir = realpath(__DIR__ . '/..');

// Cache busting version - uses latest modification time from CSS or JS
$mainCssFile = $projectDir . '/assets/css/min/style.min.css';
$mainJsFile  = $projectDir . '/assets/js/min/script.min.js';
$cssTime = file_exists($mainCssFile) ? filemtime($mainCssFile) : 0;
$jsTime = file_exists($mainJsFile) ? filemtime($mainJsFile) : 0;
$jscssversion = max($cssTime, $jsTime) ?: time();

// Site-wide config
$site = "";

// Determine protocol — $host was already validated at the top of this file
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

// Dynamic base path - works in subfolder, subdomain, main domain, local
// Compares project root against DOCUMENT_ROOT so it's correct from ANY entry script
// (index.php, thankyou.php, partials/form.php — doesn't matter)
$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
if ($projectDir && $docRoot && stripos($projectDir, $docRoot) === 0) {
    $basePath = str_replace('\\', '/', substr($projectDir, strlen($docRoot)));
    $basePath = ($basePath === '' || $basePath === '/') ? '/' : '/' . trim($basePath, '/') . '/';
} else {
    $basePath = '/';
}

// Base URLs (includes subfolder path)
$site_url = $protocol . '://' . $host . $basePath;
$baseUrl  = $site_url;

// Meta & contact info
$domain              = $host;
$description         = "";
$phone_number        = "";
$href_phone_number   = "";

// Contact emails
$emailcont        = "";
$href_emailcont   = "mailto:{$emailcont}";

// Form recipient (To:) — loaded from .env so the production address never
// gets committed. Form submissions in partials/form.php send here.
$admin_email      = env('FORM_ADMIN_EMAIL', '');

// Optional CC and BCC — loaded from .env so deployment-specific addresses
// never get committed. Comma-separated, e.g. FORM_BCC_EMAIL="a@x.com, b@x.com"
$cc_email  = env('FORM_CC_EMAIL', '');
$bcc_email = env('FORM_BCC_EMAIL', '');

// Automatically generate no-reply from the registered domain.
// Uses the port-stripped $hostName so emails are clean even on dev (localhost:3000 → localhost).
// Strips leading "www." and any subdomain layers — landing.angel.com → angel.com → no-reply@angel.com.
$host_clean = preg_replace('/^www\./i', '', $hostName);
$parts      = explode('.', $host_clean);
$len        = count($parts);

// if TLD is two letters and SLD ≤ 3 letters (e.g., com.au, co.uk), grab last 3 parts
if ($len >= 3 && strlen($parts[$len - 1]) === 2 && strlen($parts[$len - 2]) <= 3) {
    $emailDomain = implode('.', array_slice($parts, -3));
}
// otherwise grab last 2 parts
elseif ($len >= 2) {
    $emailDomain = implode('.', array_slice($parts, -2));
} else {
    $emailDomain = $host_clean;
}

$no_reply_email      = 'no-reply@' . $emailDomain;
$href_no_reply_email = 'mailto:' . $no_reply_email;

// Google reCAPTCHA keys — loaded from .env (see .env.example)
// Variable names kept for backward compatibility with form.php / footer.php
$recaptcha_client_secret = env('RECAPTCHA_SITE_KEY', '');
$recaptcha_server_secret = env('RECAPTCHA_SECRET_KEY', '');

// ----------------------------------------------
// Vendor scripts — opt-in per page
// ----------------------------------------------
// Default OFF so the boilerplate ships zero vendor bytes. Pages that actually
// need a slider or scroll animations override these BEFORE including header.php:
//
//   require_once __DIR__ . '/config/config.php';
//   $useSwiper = true;  // this page has a Swiper slider
//   $useAos    = true;  // this page uses data-aos scroll animations
//   include __DIR__ . '/partials/header.php';
//
// Saves ~185 KB of CSS+JS on pages that don't need them.
$useSwiper = false;  // Swiper.js + CSS — sliders / carousels
$useAos    = false;  // AOS.js + CSS — scroll-triggered animations

// Global content (shared data or meta)
require_once(__DIR__ . '/../data/global-content.php');
