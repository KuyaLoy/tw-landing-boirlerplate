<?php
/**
 * config/functions.php
 * ────────────────────
 * Entry point for project-wide helpers. Loaded by index.php and thankyou.php
 * after config.php.
 *
 * Responsibilities:
 *   1. Load image and SVG helpers (split into separate files for clarity)
 *   2. Define get_config_warnings() — surfaces missing/critical config in
 *      a dev-only banner (see partials/header.php) and an always-on
 *      HTML comment in <head>.
 *
 * To extend with new helpers, prefer creating a focused file (e.g.
 * config/forms.php) and require_once'ing it here, instead of adding
 * unrelated functions to this entry file.
 */

require_once __DIR__ . '/image.php';
require_once __DIR__ . '/svg.php';


// ============================================================
//  Config warnings — surfaces missing/critical config to devs
// ============================================================

/**
 * Inspect site config + assets + build artifacts and return any
 * missing/critical warnings.
 *
 * Used by partials/header.php to render a dev-only warning banner and
 * an always-on HTML comment near </head>. Add new checks here when
 * introducing new required config values — keeps the warning surface
 * in one place.
 *
 * Returns an array of strings (empty = nothing missing).
 */
function get_config_warnings(): array
{
    global $site, $description, $admin_email,
           $recaptcha_client_secret, $recaptcha_server_secret,
           $isDevelopment, $projectDir;

    $warnings = [];

    // ---- config.php (public values rendered in HTML) ----
    if (!isset($site) || trim((string) $site) === '') {
        $warnings[] = '[Config] $site is empty in config.php — page <title>, OG title, Twitter card, and email From-name will all be blank.';
    }
    if (!isset($description) || trim((string) $description) === '') {
        $warnings[] = '[Config] $description is empty in config.php — meta description and social previews (OG, Twitter) will be blank.';
    }
    // $phone_number is intentionally NOT checked — many landing pages collect
    // email only and have no phone display. If your project requires a phone,
    // add a specific check inside this function.

    // ---- .env (secrets and deployment-specific values) ----
    if (!isset($admin_email) || trim((string) $admin_email) === '') {
        $warnings[] = '[Env] FORM_ADMIN_EMAIL is not set in .env — form submissions have nowhere to send (silent failure).';
    }
    if (!isset($recaptcha_client_secret) || trim((string) $recaptcha_client_secret) === '') {
        $warnings[] = '[Env] RECAPTCHA_SITE_KEY is not set in .env — captcha widget will not render.';
    }
    if (!isset($recaptcha_server_secret) || trim((string) $recaptcha_server_secret) === '') {
        $warnings[] = '[Env] RECAPTCHA_SECRET_KEY is not set in .env — captcha verification will reject every submission.';
    }

    // ---- Production hardening (only flag when NOT in dev) ----
    if (empty($isDevelopment)) {
        $allowedHosts = trim((string) env('ALLOWED_HOSTS', ''));
        if ($allowedHosts === '') {
            $warnings[] = '[Production] ALLOWED_HOSTS is not set in .env — host header injection protection is OFF. Add: ALLOWED_HOSTS=yourdomain.com, *.yourdomain.com';
        }
    }

    // ---- Static assets referenced in templates ----
    if ($projectDir) {
        if (!file_exists($projectDir . '/ogimage.png')) {
            $warnings[] = '[Assets] ogimage.png missing at project root — Slack / Twitter / iMessage social previews will 404.';
        }
        if (!file_exists($projectDir . '/assets/images/favicon.svg')) {
            $warnings[] = '[Assets] assets/images/favicon.svg missing — browser tab icon will be broken.';
        }

        // ---- Build artifacts (compiled CSS/JS in /min/) ----
        $artifacts = [
            '/assets/css/min/reset.min.css',
            '/assets/css/min/fonts.min.css',
            '/assets/css/min/critical.min.css',
            '/assets/css/min/style.min.css',
            '/assets/css/min/responsive.min.css',
            '/assets/js/min/script.min.js',
        ];
        $missing = [];
        foreach ($artifacts as $rel) {
            $abs = $projectDir . $rel;
            if (!file_exists($abs) || filesize($abs) === 0) {
                $missing[] = ltrim($rel, '/');
            }
        }
        if (!empty($missing)) {
            $warnings[] = '[Build] missing or empty: ' . implode(', ', $missing)
                . '. Run "npm install && npm run build" (or "npm run dev" while developing) to compile.';
        }
    }

    return $warnings;
}
