# Tailwind CSS Landing Page Boilerplate

A ready-to-use landing page starter with Tailwind CSS 4, PHP, and a built-in contact form with email.

**Requires:** Node.js >= 18, PHP 7.4+, Apache with mod_rewrite

---

## Getting Started

### First Time Setup (one time only)

1. Copy this folder and rename it to your project name
2. Open terminal in the project folder
3. Run:

```bash
npm install
```

### Start Working

```bash
npm run dev              # Watch + BrowserSync auto-reload (localhost:3000)
npm run watch            # Watch only, no BrowserSync (for existing server)
```

`npm run dev` opens your browser at `http://localhost:3000` with **live reload**:

- **CSS changes** — injected without full reload (scroll position, form state, animations preserved)
- **PHP / JS changes** — full page reload
- **Watch strategy** — auto-detected: polling on Windows, native FS events on Mac/Linux

#### Local URL setup (clean vs subfolder)

For the cleanest URL bar (just `http://localhost:3000/`), point your dev environment at a vhost URL like `<project>.test`. The build script detects clean vhost URLs **first** and falls back to `http://localhost/<project>` only if no vhost is found.

| OS / tool | One-time setup |
|---|---|
| **Windows + Laragon** | Open Laragon → Menu → Preferences → check **"Auto Virtual Hosts"**. Restart Laragon. The vhost is created automatically. |
| **Mac + Laravel Valet** | `cd <your-project>` then `valet link`. Optionally `valet secure` for HTTPS. |
| **Mac + Laravel Herd** | Drag the project folder into the Herd app. Done. |
| **Other** | Set `LOCAL_URL=http://your-custom-url` before running `npm run dev` |

If no vhost is found, the build still works — but your URL bar will show `http://localhost:3000/<project-name>/` instead of `http://localhost:3000/`. Functional, just less clean.

### Build for Upload

```bash
npm run build            # Compile only (no ZIP)
npm run package          # Compile + create ZIP in build/
```

The ZIP in `build/` is ready to upload. No Node.js needed on the server.

### Cross-platform note (read this once)

This boilerplate runs identically on Windows, Mac, and Linux — there's no OS-specific code in the project itself. The only OS-aware bit is the watcher in `build.js`, which auto-detects platform.

There is **one footgun** worth knowing: **always use lowercase asset filenames** (`hero.jpg`, not `Hero.jpg`). Mac and Windows are case-INsensitive — a reference to `hero.jpg` resolves to `Hero.jpg` locally and works fine. Linux production servers are case-SENSITIVE — the same reference 404s after upload. This bites everyone who develops on Mac/Windows and ships to Linux at least once. Just lowercase everything in `assets/images/` and `assets/fonts/` and you'll never see it.

---

## How to Deploy — cPanel direct upload

This boilerplate is designed for **direct cPanel upload, not GitHub deployment**. The full workflow:

1. **Build the production package locally**
   ```bash
   npm run package
   ```
   This compiles + minifies CSS/JS and creates `build/<project-name>.zip`. The ZIP excludes `node_modules`, `build/`, `.git/`, `.claude*/`, source CSS/JS, and dev-only files — only what's needed at runtime ships.

2. **Open cPanel → File Manager** → navigate to the target folder:
   - Root domain: `public_html/`
   - Subfolder: `public_html/landing-page/`
   - Subdomain: `public_html/landing.example.com/` (or wherever cPanel routes the subdomain)

3. **Upload the ZIP and extract in place** (cPanel File Manager → Upload → then Extract).

4. **Edit `.env` on the server.** The committed `.env.example` is the template; the real `.env` is gitignored and never in the ZIP. Use File Manager → right-click `.env.example` → Copy → rename to `.env` → Edit, fill in:
   ```
   RECAPTCHA_SITE_KEY=…
   RECAPTCHA_SECRET_KEY=…
   FORM_ADMIN_EMAIL=you@yourdomain.com
   ALLOWED_HOSTS=yourdomain.com, *.yourdomain.com
   ```

5. **Visit the site.** If anything's missing, view-source and look for `[CONFIG WARNINGS]` HTML comments near `</head>` — they'll tell you exactly what to fix (missing `.env` value, missing `ogimage.png`, etc.). Production hides this from rendered HTML, so users never see it.

6. **Smoke-test the form**: submit once, verify email arrives in admin inbox, verify thank-you page loads.

### What about updates / re-deploys?

For small text or markup changes, you can edit `partials/main.php` directly via cPanel File Manager — no rebuild needed. For CSS or JS changes, rebuild locally (`npm run build`) and upload only the changed `assets/css/min/*.min.css` or `assets/js/min/*.min.js` files. The cache-busting `?v=<mtime>` query string in the `<link>` tags forces browsers to fetch the fresh version on next page load.

Layout works identically in any location — `$basePath` figures out subfolder depth from the filesystem, no config change needed:

| Location | Example | You do |
|---|---|---|
| Root domain | `site.com` | Upload files to `public_html/` |
| Subfolder | `site.com/landing-page` | Upload folder inside `public_html/` |
| Deep subfolder | `site.com/campaigns/landing-page` | Same — just upload |
| Subdomain | `landing.site.com` | Point subdomain to folder |
| Localhost | `localhost/my-project` | Just works during development |

**All paths (images, links, redirects, emails) adjust automatically.** No config changes needed.

---

## Building from Figma with Claude Code

This boilerplate ships with a `.claude-template/` folder containing pre-tuned agents and skills for AI-assisted development. The intended workflow is:

1. **Rename `.claude-template/` to `.claude/`** in your project (one-time setup, merging any existing `settings.local.json`). Claude Code auto-loads agents and skills from `.claude/`.
2. **Open Figma**, select the section you want to build, copy the frame URL.
3. **In Claude Code**, paste the URL with a prompt like:
   > Implement this Figma frame as the hero section in `partials/main.php`: <frame-url>
4. The `frontend-builder` agent reads the design via the Figma MCP, then writes Tailwind utility markup that respects the boilerplate's brand tokens (`bg-primary`, `font-brand`, etc.), uses the helpers (`imgx`, `svgx`), and lives in the right partial.
5. **Build sections one at a time** — hero → features → testimonials → CTA → footer. Each run keeps context focused on a single section, which produces tighter markup than asking for a whole page in one shot.

**Pixel-perfect — what's realistic.** Aim for ~95%. Figma designs are often messy (arbitrary `73px` paddings that don't match Tailwind's scale, font metrics that render slightly different than the actual webfont). Accept the closest scale value (`p-18 = 72px`) for Figma's `73px` and move on. The `frontend-builder` agent flags these decisions in comments — review them and tweak as needed.

**Replacing the welcome page.** `partials/main.php` ships with a Tailwind-only welcome page between `START WELCOME PAGE` / `END WELCOME PAGE` markers. Delete everything between the markers — there's no companion CSS to clean up — and start your design.

**When the form needs re-wiring.** If your design changes the contact form fields, `partials/form.php` (the POST handler + email template) needs to match. Ask Claude Code:
> The form in `partials/main.php` now has fields `<list new fields>`. Update `partials/form.php` to handle them and update the email template to render them.

The handler enforces security (header-injection-safe values via `header_safe()`, reCAPTCHA verification, honeypot) — the agent will preserve those.

For the full list of agents and slash commands, see [`.claude-template/README.md`](.claude-template/README.md).

---

## What to Edit for Each New Project

### 1. Site Info — `config/config.php`

Fill in your project details (these live in `config.php` because they're public — they appear in the rendered HTML):

```
$site           = "My Landing Page";        ← site name
$description    = "Short page description";  ← SEO description
$phone_number   = "1300 000 000";            ← display number
```

> If you forget to fill these in, the boilerplate will warn you. In dev mode (`localhost`, `*.test`, `*.local`, `192.168.*`) you'll see a red banner at the top of the page listing every empty critical config value (`$site`, `$description`, `FORM_ADMIN_EMAIL`, reCAPTCHA keys). The banner only renders during local development. In production, no banner is shown — but an HTML comment near `</head>` lists the same warnings, so a quick view-source on a deployed site tells you what's missing. The check lives in `get_config_warnings()` in `config/functions.php` — extend it when you add new required config values.

Everything **secret or deployment-specific** lives in a `.env` file at the project root — never in `config.php`. Get reCAPTCHA keys from [Google reCAPTCHA](https://www.google.com/recaptcha/admin) — use **v3** keys.

```bash
# 1. Copy the template
cp .env.example .env

# 2. Edit .env and fill in:
RECAPTCHA_SITE_KEY=your-site-key
RECAPTCHA_SECRET_KEY=your-secret-key

FORM_ADMIN_EMAIL=you@company.com    # form submissions go here (required)
FORM_CC_EMAIL=                       # optional, comma-separated
FORM_BCC_EMAIL=                      # optional, comma-separated

ALLOWED_HOSTS=angel.com, *.angel.com  # production hosts that may serve this site
```

`.env` is gitignored and blocked from the web by `.htaccess`. `config.php` reads the values automatically via `env()` (see `config/env.php`).

**About `ALLOWED_HOSTS`** — defends against Host header injection. Whatever real domain(s) your landing page lives on, list them here. Wildcards (`*.angel.com`) cover all subdomains in one entry, so root + www + landing-subdomain + staging all work without listing each one. The page works identically on root, subfolder, sub-sub-folder, and subdomain — `$basePath` figures that out from the filesystem and is independent of the Host header.

**Email From: address** — derived automatically from your validated host. `landing.angel.com` and `www.angel.com` and `angel.com` all send mail as `no-reply@angel.com`, which keeps SPF/DKIM aligned and stays out of spam folders. Two-part TLDs like `.com.au` and `.co.uk` are handled (`site.com.au` → `no-reply@site.com.au`).

Local dev (`localhost`, `*.test`, `*.local`, `192.168.*`) is always trusted — leave `ALLOWED_HOSTS` blank during development and it just works.

> **Heads up:** if you forked an older version of this boilerplate where keys or recipient emails were hardcoded in `config.php`, **rotate the reCAPTCHA keys** and consider that any committed email addresses are in your git history.

#### Email transport — `mail()` by default, SMTP on demand

The form handler in `partials/form.php` automatically picks its transport based on `.env`:

- `SMTP_HOST` is **blank** → form uses PHP's built-in `mail()` (default). The handler sets `Return-Path`, `Message-ID`, `Date`, and `X-Mailer` headers and passes `-f no-reply@yourdomain.com` as the envelope sender, which gives SPF a chance to align on hosts that whitelist the web user as a trusted MTA user (most shared hosts do). Works on cPanel, Plesk, Hostinger, SiteGround, Namecheap, A2, etc., where the host already does auto-DKIM at the MTA level.
- `SMTP_HOST` is **set** → form uses authenticated SMTP via the bundled PHPMailer (`lib/PHPMailer/`). No composer install required — PHPMailer 6.9.1 ships in the boilerplate. Fill in `SMTP_USER`, `SMTP_PASS`, `SMTP_PORT` (default `587`), and `SMTP_ENCRYPTION` (`tls` for STARTTLS, `ssl` for port 465).

Recommended SMTP providers when `mail()` isn't enough — all four give you SPF + DKIM + DMARC alignment automatically once you verify your sending domain in their panel:

- **Resend** — free 100/day, modern dev experience: <https://resend.com>
- **MailerSend** — free 3,000/month: <https://mailersend.com>
- **Brevo** (formerly Sendinblue) — free 300/day: <https://brevo.com>
- **Amazon SES** — pay-as-you-go, ~$0.10 per 1,000 emails: <https://aws.amazon.com/ses>

The "via syn01dd.syd6.hostyourservices.net" warning that Gmail sometimes shows on `mail()` emails goes away the moment you switch to one of the above + verify your domain.

### 2. Page Content — `partials/main.php`

This is your main page HTML. Replace the boilerplate sample with your actual content.

### 3. Colors — `assets/css/source/critical.css`

Change the brand colors at the top:

```css
:root {
  --brand-primary: #3b82f6;    ← main brand color
  --brand-secondary: #8b5cf6;  ← second color
  --brand-accent: #f59e0b;     ← highlight color
  --brand-text: #1a1a1a;       ← text color
}
```

These are mapped to Tailwind utilities (`bg-primary`, `text-secondary`, etc.) via `@theme inline` in `style.css`.

### 4. Images — `assets/images/`

Drop your images here. Required files:
- `favicon.svg` — browser tab icon
- `logo.png` — used in emails
- `ogimage.png` — social sharing preview (1200x630)

### 5. Fonts — `assets/fonts/` + `assets/css/source/fonts.css`

The boilerplate ships with **Manrope** as the default font — 5 weights (400, 500, 600, 700, 800), latin subset, woff2-only. Manrope is open source under the SIL OFL license, free for commercial use. To swap in your own font:

```bash
# 1. Drop your .woff2 files into assets/fonts/<YourFont>/
# 2. Mirror the @font-face pattern in assets/css/source/fonts.css
#    (woff2-only, font-display: swap, one block per weight + style)
# 3. Update --brand-font in assets/css/source/critical.css :root
#    so your font-family name comes first in the stack
```

That's it — body text, Tailwind's `font-brand` utility, and any `var(--brand-font)` reference all sync through the one CSS variable.

**Don't ship `.woff` files.** Modern browsers (anything since ~2018) all use `.woff2` first; the `.woff` legacy is for IE9–11 only and is dead weight.

**For above-the-fold weights, add a `<link rel="preload">` in `partials/header.php`** so the font is fetched in parallel with CSS rather than after CSS parses. Only preload weights that actually appear on first paint — preloading everything is worse than preloading nothing.

### 6. Global Content — `data/global-content.php`

Add shared content variables here (e.g., repeating text, phone numbers) to reuse across pages.

### 7. Vendor Scripts — `$useSwiper` / `$useAos`

The boilerplate includes Swiper (sliders) and AOS (scroll animations) as vendor libraries, but loads them **only on pages that opt in**. The default in `config.php` is off, so a stripped landing page ships zero vendor JS/CSS (~185 KB saved).

To enable them on a page, override the flag **before** including `header.php`:

```php
<?php
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/functions.php');

$useSwiper = true;  // page has a Swiper slider
$useAos    = true;  // page uses data-aos attributes

include(__DIR__ . '/partials/header.php');
include(__DIR__ . '/partials/main.php');
include(__DIR__ . '/partials/footer.php');
```

Each flag controls the `.css` preload in `header.php` and the `.js` script tag in `footer.php`. `script.js` already has a `typeof AOS !== "undefined"` guard, so the AOS init call is a no-op when AOS isn't loaded — the same compiled script works for both AOS-on and AOS-off pages.

---

## Folder Structure (What Goes Where)

```
project/
├── index.php                ← Homepage (don't rename)
├── thankyou.php             ← Thank you page after form submit
├── ogimage.png              ← Social-share image (1200x630)
│
├── partials/                ← PHP page sections
│   ├── header.php           ← <head> tag, meta, inline critical CSS
│   ├── main.php             ← Your page content goes here
│   ├── footer.php           ← Scripts, JS loading
│   └── form.php             ← Contact form handler + email template
│
├── config/                  ← Your config (read by partials)
│   ├── config.php           ← Site settings, dynamic $basePath
│   ├── env.php              ← .env loader (env() helper)
│   └── functions.php        ← Image helpers, config warnings
│
├── lib/                     ← Bundled 3rd-party libraries
│   └── PHPMailer/           ← SMTP library (used when SMTP_HOST is set)
│
├── data/
│   └── global-content.php   ← Shared content variables
│
├── assets/
│   ├── images/              ← All images (auto-converts to WebP/AVIF)
│   ├── fonts/               ← Custom font files (woff2 only)
│   ├── css/
│   │   ├── source/          ← EDIT THESE (your CSS files)
│   │   ├── min/             ← Compiled output (committed, served live)
│   │   ├── live-edit.css    ← Quick fixes without rebuild — loads LAST
│   │   ├── vendor/          ← Third-party CSS (AOS, Swiper)
│   │   └── README.md        ← Folder reference
│   └── js/
│       ├── source/          ← EDIT THESE
│       ├── min/             ← Compiled output (committed, served live)
│       ├── live-edit.js     ← Quick fixes without rebuild — loads LAST
│       ├── vendor/          ← Third-party JS (AOS, Swiper)
│       └── README.md        ← Folder reference
│
├── CLAUDE.md                ← AI assistant rules for this project
└── build.js                 ← Build system (don't touch)
```

**Note:** Tailwind CSS 4 uses CSS-first configuration. Theme settings live in `assets/css/source/style.css` (`@theme inline` block), not in a separate config file.

**AI Rules:** `CLAUDE.md` contains rules for AI assistants (Claude, Cursor, Copilot, etc.) to follow when editing this project — path conventions, CSS placement rules, security guidelines, and more.

---

## CSS Files Explained

| File | What it's for | `@apply` works? | Needs `npm run dev`? |
|---|---|---|---|
| `css/source/critical.css` | Colors, containers, above-fold styles (header + first 2 sections) | Yes | Yes |
| `css/source/style.css` | Tailwind entry point + theme config (don't add custom styles here) | Yes | Yes |
| `css/source/custom.css` | Your custom CSS (section 3 onward) | Yes | Yes |
| `css/source/responsive.css` | Media query overrides (when Tailwind utilities aren't enough) | Yes | Yes |
| `css/source/reset.css` | Browser reset (rarely touch) | Yes | Yes |
| `css/source/fonts.css` | @font-face declarations only | No | Yes |
| **`css/live-edit.css`** | **Quick fixes on live site** | **No** | **No** |

### Using `@apply` in CSS

All CSS files (except `fonts.css` and `live-edit.css`) support Tailwind's `@apply` directive. Use TW4 syntax for `!important`:

```css
/* Correct TW4 syntax */
body { @apply bg-black!; }

/* Also works with custom theme colors */
.header { @apply bg-primary text-white; }
```

This works because each file has `@reference "tailwindcss"` and `@reference "./style.css"` at the top — these give `@apply` access to all utilities without injecting Tailwind's full output.

### Quick Fix on a Live Site

Need to fix something on a live site fast without running `npm`? Just edit `assets/css/live-edit.css` or `assets/js/live-edit.js` — they load last and override everything. Upload and done.

---

## Using Images in Your HTML

The boilerplate has two helper files for images:

- **`config/image.php`** — `imgx()` for raster images (JPEG/PNG → AVIF + WebP fallback)
- **`config/svg.php`** — `svgx()` and `svg_inline()` for SVG

Both are loaded automatically via `config/functions.php`. Place files in `assets/images/` and pass the path **without extension** — the helpers resolve `.jpg` / `.png` / `.jpeg` automatically.

### Regular images — `imgx()`

```php
<?php imgx('hero-banner', 'Hero Image', 'my-class'); ?>
```

What you get for free:
- AVIF + WebP versions auto-generated on first render and cached on disk
- Re-converted automatically if you replace the source file (mtime check)
- `width` + `height` attributes always emitted (no layout shift / CLS)
- `loading="lazy"` and `decoding="async"` by default
- `getimagesize()` cached per request

### With mobile version

```php
<?php imgx('hero-desktop', 'Hero', 'hero-img', 'hero-mobile'); ?>
```

The mobile variant is wired via `<picture><source media="(max-width: 767px)">` so the browser picks it on small screens.

### Hero / above-the-fold image (LCP optimization)

```php
<?php
imgx('hero', 'Hero', 'w-full', '', '', false, 768, [
    'fetchpriority' => 'high',
]);
?>
```

`'fetchpriority' => 'high'` tells the browser to fetch this image with priority — measurable LCP improvement. Combined with `$lazy = false` it's eager-loaded.

### Retina (2x) images

Drop a `hero@2x.jpg` next to your `hero.jpg`:

```
assets/images/hero.jpg       (e.g. 800×400)
assets/images/hero@2x.jpg    (e.g. 1600×800)
```

`imgx('hero', 'Hero')` automatically emits `srcset="hero.webp 1x, hero@2x.webp 2x"` so high-DPI displays get the sharper version. Disable with `['retina' => false]`.

### Advanced opts

```php
imgx('hero', 'Hero', '', '', '', true, 768, [
    'fetchpriority' => 'high'|'low'|'auto',  // browser priority hint
    'decoding'      => 'async'|'sync'|'auto', // default 'async'
    'width'         => 800,                   // override detected width
    'height'        => 600,                   // override detected height
    'retina'        => true,                  // auto @2x (default true)
    'sizes'         => '(max-width: 768px) 100vw, 50vw', // for responsive
]);
```

### SVG as image — `svgx()`

```php
<?php svgx('logo', 'Company Logo', 'logo-class'); ?>
```

Auto-detects width/height — including viewBox-only SVGs (most modern icons). Lazy + async decoding by default. Pass `$opts` array as 4th arg for eager loading or fetchpriority.

### SVG inline (for CSS / JS styling) — `svg_inline()`

```php
<?php svg_inline('icon-arrow', 'arrow-class'); ?>
```

Drops the SVG markup directly into HTML so paths/fills can be styled via CSS. Strips XML declarations automatically. Class merges with any existing class on the root `<svg>` (no duplicate `class=""` attributes).

> **Security note:** `svg_inline()` outputs raw SVG markup. SVG can contain `<script>` tags — only inline SVGs you control. Never inline user-uploaded SVG without sanitizing.

---

## Container Sizes

Wrap content in a container to center it with max-width:

```html
<div class="container">        <!-- 1280px (default) -->
<div class="container-sm">     <!-- 640px -->
<div class="container-md">     <!-- 768px -->
<div class="container-lg">     <!-- 1024px -->
<div class="container-2xl">    <!-- 1536px -->
<div class="container-max">    <!-- 1920px -->
<div class="container-full">   <!-- 100% with padding -->
```

---

## Contact Form Setup

The form handler is in `partials/form.php`. It includes:
- reCAPTCHA v3 spam protection (verified via POST for security)
- Honeypot field for bots
- Input sanitization and validation
- HTML email template with your logo
- Auto-redirect to `thankyou.php` after success (for conversion tracking scripts)

To wire up your form, point it to `partials/form.php`:

```html
<form method="POST" action="<?= $basePath ?>partials/form">
    <!-- your fields -->
    <input type="hidden" name="token" class="recaptchaResponse">
    <input type="hidden" name="honeypot">
</form>
```

---

## Debugging on Live

Add `?debug=1` to any URL to see PHP errors:

```
https://yoursite.com/?debug=1
https://yoursite.com/landing-page/?debug=1
```

---

## BrowserSync (Dev Server)

`npm run dev` auto-detects your local server:

1. Tries `http://projectname.test` (Laragon)
2. Falls back to `http://localhost/projectname`

If it guesses wrong, set it manually:

```bash
# Windows PowerShell
$env:LOCAL_URL="http://mysite.test"; npm run dev

# Windows CMD
set LOCAL_URL=http://mysite.test && npm run dev

# Mac/Linux
LOCAL_URL=http://mysite.test npm run dev
```

---

## Quick Reference

| I want to... | Do this |
|---|---|
| Start a new project | Copy folder, `npm install`, edit `config.php` |
| Change colors | Edit `assets/css/source/critical.css` `:root` variables |
| Add page content | Edit `partials/main.php` |
| Add images | Drop into `assets/images/`, use `imgx()` |
| Fix something on live | Edit `assets/css/live-edit.css` and upload |
| Change email template | Edit `partials/form.php` |
| Change thank you page | Edit `thankyou.php` |
| Upload to server | `npm run package`, upload ZIP contents |
| See errors on live | Add `?debug=1` to URL |
