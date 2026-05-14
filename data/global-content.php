<?php
/**
 * data/global-content.php
 * ───────────────────────
 * Site-wide content variables — anything you reference from multiple
 * partials (header, main, footer, form) lives here so you only edit it
 * in one place.
 *
 * Loaded automatically by config/config.php at the bottom, AFTER all
 * config values are defined — so you can compose strings using $site,
 * $phone_number, $emailcont, etc.
 *
 * NOT web-accessible: this folder is blocked by .htaccess.
 *
 * Examples (uncomment + customize):
 *
 *   // $tagline   = "Loans approved in 60 seconds.";
 *   // $cta_text  = "Get your free quote";
 *   // $usp_list  = [
 *   //     "Bank-level encryption",
 *   //     "No credit check until you accept",
 *   //     "Same-day funding",
 *   // ];
 *   // $review_count = 1247;
 *   // $review_score = 4.9;
 *
 * Use in partials/main.php:
 *
 *   <h1><?= htmlspecialchars($tagline) ?></h1>
 *   <button><?= htmlspecialchars($cta_text) ?></button>
 */
