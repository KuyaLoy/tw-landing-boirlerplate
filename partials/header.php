<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Basic Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= $site_url ?>">

  <!-- Open Graph Meta Tags (for social sharing) -->
  <meta property="og:title" content="<?= $site ?>" />
  <meta property="og:description" content="<?= $description ?>" />
  <meta property="og:image" content="<?= $site_url ?>ogimage.png" />
  <meta property="og:url" content="<?= $site_url ?>" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="<?= $site ?>" />

  <!-- HTML Meta Tags -->
  <title><?= $site; ?></title>
  <meta name="description" content="<?= $description; ?>">
  <meta name="author" content="<?= $site ?>">

  <!-- Twitter Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta property="twitter:domain" content="<?= $domain ?>">
  <meta property="twitter:url" content="<?= $site_url ?>">
  <meta name="twitter:title" content="<?= $site ?>">
  <meta name="twitter:description" content="<?= $description ?>">
  <meta name="twitter:image" content="<?= $site_url ?>ogimage.png">

  <!-- Favicon and Touch Icons -->
  <link rel="icon" href="<?= $basePath ?>assets/images/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= $basePath ?>assets/images/favicon.svg">
  <link rel="icon" type="image/svg+xml" sizes="32x32" href="<?= $basePath ?>assets/images/favicon.svg">

  <!-- Preload the body weight (Manrope 400) so it's fetched in parallel with
       CSS, not after CSS parses. Add similar tags for any other weight that
       appears above the fold (e.g. Manrope-700.woff2 for the hero headline).
       Preloading every weight is worse than preloading nothing — only the
       above-fold ones are worth the extra request priority. -->
  <link rel="preload"
        href="<?= $basePath ?>assets/fonts/Manrope/Manrope-400.woff2"
        as="font"
        type="font/woff2"
        crossorigin>

  <!-- Critical inline CSS: reset, fonts, critical (header + first 2 sections) -->
  <style>
    <?php
    // Auto-refresh inline CSS when files change (cache busting via file modification time)
    // Always use minified files for optimal performance
    $resetFile    = $projectDir . '/assets/css/min/reset.min.css';
    $fontsFile    = $projectDir . '/assets/css/min/fonts.min.css';
    $criticalFile = $projectDir . '/assets/css/min/critical.min.css';

    if (file_exists($resetFile)) include($resetFile);
    if (file_exists($fontsFile)) include($fontsFile);
    if (file_exists($criticalFile)) include($criticalFile);
    ?>
  </style>

  <!-- Non-Critical CSS — plain synchronous load. Bulletproof: always applies. -->
  <!-- The 18-20 KB load adds ~10ms to FCP, totally fine for landing pages. -->
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/min/style.min.css?v=<?= $jscssversion; ?>">
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/min/responsive.min.css?v=<?= $jscssversion; ?>">
  <?php if (!empty($useSwiper)): ?>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/vendor/swiper-bundle.min.css">
  <?php endif; ?>
  <?php if (!empty($useAos)): ?>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/vendor/aos.css">
  <?php endif; ?>
  <!-- Live-edit CSS loads LAST to override all compiled styles (quick fixes without rebuild) -->
  <?php $editCssFile = $projectDir . '/assets/css/live-edit.css'; ?>
  <?php if (file_exists($editCssFile) && filesize($editCssFile) > 100): ?>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/live-edit.css?v=<?= $jscssversion; ?>">
  <?php endif; ?>

  <!-- Fallback for No JavaScript -->
  <noscript>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/min/style.min.css?v=<?= $jscssversion; ?>">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/min/responsive.min.css?v=<?= $jscssversion; ?>">
    <?php if (!empty($useSwiper)): ?>
      <link rel="stylesheet" href="<?= $basePath ?>assets/css/vendor/swiper-bundle.min.css">
    <?php endif; ?>
    <?php if (!empty($useAos)): ?>
      <link rel="stylesheet" href="<?= $basePath ?>assets/css/vendor/aos.css">
    <?php endif; ?>
    <?php if (file_exists($editCssFile) && filesize($editCssFile) > 100): ?>
      <link rel="stylesheet" href="<?= $basePath ?>assets/css/live-edit.css?v=<?= $jscssversion; ?>">
    <?php endif; ?>
  </noscript>

  <?php
  // Always-emitted HTML comment listing any missing config values.
  // Invisible to users, visible in view-source — useful for debugging deployed
  // sites that look broken (blank <title>, no captcha, etc.).
  $configWarnings = get_config_warnings();
  if (!empty($configWarnings)):
  ?>
  <!--
    [CONFIG WARNINGS] (<?= count($configWarnings) ?> issue<?= count($configWarnings) === 1 ? '' : 's' ?>)
    <?php foreach ($configWarnings as $w): ?>
    - <?= htmlspecialchars(strip_tags($w), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
    <?php endforeach; ?>
  -->
  <?php endif; ?>

</head>

<body>

<?php
// Dev-only warning banner. Shows ONLY when $isDevelopment is true (localhost,
// .test, .local, 192.168.*) so production users never see this. Inline styles
// are intentional — banner must work even before any CSS loads.
if (!empty($isDevelopment) && !empty($configWarnings)):
?>
<div style="position:relative;background:#dc2626;color:#fff;padding:12px 20px;font:13px/1.5 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;z-index:99999;box-shadow:0 2px 8px rgba(0,0,0,0.2);">
  <div style="font-weight:bold;font-size:14px;margin-bottom:6px;">
    Config warnings (DEV ONLY — won't show in production)
  </div>
  <ul style="margin:0;padding-left:20px;">
    <?php foreach ($configWarnings as $w): ?>
      <li style="margin:2px 0;"><?= htmlspecialchars($w, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
  <div style="margin-top:6px;opacity:0.85;font-size:12px;">
    Edit <code style="background:rgba(0,0,0,0.25);padding:1px 4px;border-radius:3px;">config/config.php</code> or your <code style="background:rgba(0,0,0,0.25);padding:1px 4px;border-radius:3px;">.env</code> to fix.
  </div>
</div>
<?php endif; ?>
