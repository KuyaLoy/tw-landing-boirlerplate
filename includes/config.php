<?php
// Error reporting - always on for debugging
// On live: add ?debug=1 to URL to see errors
$serverName = $_SERVER['SERVER_NAME'];
$isDevelopment = (
    strpos($serverName, 'localhost') !== false ||
    strpos($serverName, '.test') !== false ||
    strpos($serverName, '.local') !== false ||
    strpos($serverName, '127.0.0.1') !== false ||
    strpos($serverName, '192.168.') !== false
);

$debugMode = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($isDevelopment || $debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Project root on the filesystem (one level up from /includes)
$projectDir = realpath(__DIR__ . '/..');

// Cache busting version - uses latest modification time from CSS or JS
$mainCssFile = $projectDir . '/assets/css/compiled/min/style.min.css';
$mainJsFile  = $projectDir . '/assets/js/compiled/min/script.min.js';
$cssTime = file_exists($mainCssFile) ? filemtime($mainCssFile) : 0;
$jsTime = file_exists($mainJsFile) ? filemtime($mainJsFile) : 0;
$jscssverion = max($cssTime, $jsTime) ?: time();

// Site-wide config
$site = "";

// Determine protocol and host
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];

// Dynamic base path - works in subfolder, subdomain, main domain, local
// Compares project root against DOCUMENT_ROOT so it's correct from ANY entry script
// (index.php, thankyou.php, src/form.php — doesn't matter)
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
$admin_email      = '';

// AIIMS team / branding
$aiims_logo_svg_url = $site_url . 'assets/images/aiims.svg';
$aiimsflogolinks    = "https://www.aiims.com.au/like-our-work/";

// Optional CC and BCC
$cc_email  = "";
//$bcc_email = "kalbassit@aiims.com.au, jayoub@aiims.com.au, rkavirajan@aiims.com.au, robin@aiims.ae";
$bcc_email = "";

// Automatically generate no-reply based on registered domain (strips subdomains)
$host_clean = preg_replace('/^www\./i', '', $host);
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

// Google reCAPTCHA keys
$recaptcha_client_secret = "6LcnI_QrAAAAAMCc-ifqqLiXxTUtnaNRa_YlwHbv";
$recaptcha_server_secret = "6LcnI_QrAAAAADud2ba5w21-E8IHxfXuma9CVicj";

// Global content (shared data or meta)
require_once(__DIR__ . '/../data/global-content.php');
