# Project Rules

PHP + Tailwind CSS 4 landing page boilerplate. No framework — plain PHP includes.

## Commands

- `npm run dev` — Watch + BrowserSync auto-reload (localhost:3000)
- `npm run watch` — Watch only, no BrowserSync (for existing server)
- `npm run build` — Production build (compile + minify)
- `npm run package` — Production build + ZIP in `build/` (this is what you upload to cPanel — see "Deployment" below)
- `npm install` — First-time setup

## How this project ships (READ THIS FIRST)

This boilerplate is **deployed via direct cPanel upload, not GitHub**. The build pipeline runs locally; the resulting `build/<project>.zip` from `npm run package` gets uploaded to cPanel File Manager and extracted in place. No CI, no remote build step.

What this means for you as an AI editor:
- **Compiled artifacts MATTER and are committed** — `assets/css/min/*.min.css` and `assets/js/min/*.min.js` are not throwaway. Editing source CSS without running `npm run build` ships a broken site
- **Don't add tooling that requires a build server** (no postcss-on-server, no SSR, no edge functions). The shipped artifact must run on plain Apache + PHP
- **Don't add `node_modules` runtime dependencies** — anything in `package.json` is dev-only. Production has zero npm
- **Final shipped tree** is the project root MINUS `node_modules/`, `build/`, `.git/`, `.claude*/`, `.env.example`, source `assets/css/source/`, source `assets/js/source/` (the `.zip` from `npm run package` already excludes these)

## Building from Figma — section-by-section workflow

The user typically implements designs **one section at a time** (hero → features → testimonials → CTA → footer), pasting Figma frame URLs into Claude Code. Follow this loop:

1. **Read the design first** with the Figma MCP tools (`get_design_context`, `get_screenshot`, `get_variable_defs`). Don't write code blind. If MCP isn't available, ask the user for a screenshot
2. **Scope the change** — is this a new section in `partials/main.php`, or should it become its own partial (`partials/<name>.php` included from main.php)? Sections > ~80 lines or reusable across pages → own partial. Otherwise → inline in main.php
3. **Pixel-perfect is the goal, but Figma is often messy.** If exported values don't fit Tailwind's scale, prefer the closest scale value — `padding: 73px` → `p-[72px]` (closest spacing token is `p-18 = 72px`) or `py-20`. Add a `<!-- TODO: confirm spacing -->` comment if you guess
4. **Brand tokens before hex codes.** If Figma's color matches `--brand-primary`, use `bg-primary`. If it doesn't, surface it: *"This badge color doesn't match any brand token — add it as `--brand-warning` or use closest existing?"* Don't silently introduce new colors
5. **SVG = copy verbatim from Figma.** Drop the exported `.svg` byte-for-byte into `assets/images/`. Render via `svgx()` or `svg_inline()` (see "Images and SVG" rules below). Don't redraw, don't substitute icon libraries
6. **No custom CSS unless Tailwind genuinely can't express it.** When you must, use `@apply` (never raw declarations) and put it in the right file: above-fold → `critical.css`, below-fold → `custom.css`, breakpoint overrides → `responsive.css`
7. **Test before declaring done.** Open the site in a browser. The compiled CSS only contains classes Tailwind found in source files (`@source` scans `partials/`, `assets/`, etc.) — if you wrote markup but didn't run `npm run dev` or rebuild, your new classes won't apply. *Always rebuild after writing markup with new utilities.*

### Pixel-perfect — what's achievable, what isn't

Aim for ~95% — the last 5% is usually wasted effort because:
- Figma designers often use arbitrary spacing (`73px`, `27px`) that doesn't match Tailwind's scale. Use the closest scale value, not arbitrary values, unless the design is unambiguous about a precise pixel
- Figma fonts may render slightly different metrics than the actual webfont (especially line-height in headings)
- Mobile breakpoints in Figma are often only one width (e.g. `375px`); production needs to handle 320px → 767px gracefully — that's a `responsive-engineer` problem, not a "match Figma" problem
- Browser rendering differs from Figma's vector preview (subpixel anti-aliasing, font hinting)

**Tell the user when you can't hit pixel-perfect** — flag it as a comment or in your reply. Don't silently approximate and call it done.

### Replacing the welcome page

`partials/main.php` ships with a Tailwind-only welcome page (between `START WELCOME PAGE` / `END WELCOME PAGE` markers). When implementing the user's first real design:

- **Delete everything between the markers** — nothing else needs cleanup. The welcome page uses zero custom CSS (no entries in `critical.css` / `custom.css` to remove)
- Then drop in the new section markup
- The welcome page is intentionally Tailwind-only so a fresh project has nothing to clean up — keep this convention if you replace it

## Form re-wiring when the design changes

`partials/form.php` is the POST handler for the contact form. Its field names, validation rules, and email template are **tied to whatever form markup is currently in `partials/main.php`** (or wherever the form lives). When the design changes, the handler must be re-wired:

1. **Read the new form markup** in `partials/main.php` — note every `<input name="...">`, `<select name="...">`, `<textarea name="...">`
2. **Update the field-extraction block** in `partials/form.php` (around lines ~127-132 — look for `$_POST['<name>']`). Add new fields, remove deleted ones. Use:
   - `strip_tags(trim($_POST['name'] ?? ''))` for plain text
   - `preg_replace('/\D+/', '', trim($_POST['phone'] ?? ''))` for digit-only phone
   - `filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL)` for email — validate, don't sanitize-then-validate
   - `header_safe($value)` for ANY value that ends up in an email header (Reply-To, From, etc.)
3. **Update validation** — adjust the required-field check and any format-specific regex (the current phone regex `/^0[2|3|4|7|8]\d{8}$/` is Australian — change for other countries)
4. **Update the HTML email template** — the `<<<HTML` heredoc block in `form.php` renders the email body. Add/remove `<tr>` rows to match the new fields. The header bar color is hardcoded `#CAB644` (legacy from old design) — change to match the new brand
5. **Update the form HTML in markup** — make sure `name` attributes match what `form.php` reads, and that the form posts to `<?= $basePath ?>partials/form` (clean URL)
6. **Test**: submit the form on localhost. Check (a) reCAPTCHA token is sent, (b) email arrives in admin inbox, (c) thankyou.php loads, (d) reCAPTCHA is NOT loaded on the thankyou page (waste check)

**Security non-negotiables** when re-wiring:
- Any value used in an email header MUST be passed through `header_safe()` first. `strip_tags()` does NOT remove `\r\n` and that's a header-injection vector
- The thrown `Exception` messages are surfaced to the user via `alert()` — keep them informative but **never include user input** in the message text (it gets `json_encode`'d into JS but defense-in-depth)
- reCAPTCHA's score check (`$recap->score < 0.5`) is the bot-protection floor. Don't lower it without a reason
- Don't `mail()` or instantiate PHPMailer from anywhere else. All form sending stays centralized in `form.php`

## Deployment — cPanel direct upload

The shipping workflow:

1. `npm run package` — runs production build, then zips the project (excluding `node_modules`, `build`, `.git`, `.claude*`, source CSS/JS, etc.) into `build/<project>.zip`
2. Log into cPanel → File Manager → navigate to the target folder (`public_html/`, or a subfolder like `public_html/landing-page/`)
3. Upload the `.zip` and extract in place
4. **Edit `.env` on the server** via File Manager → Edit. The committed `.env.example` is the template; the real `.env` is gitignored and never in the zip — you fill it in on the server, once, with reCAPTCHA keys + admin email + ALLOWED_HOSTS
5. Open the site URL — the dev banner won't show in production (only on `localhost`/`.test`/`.local`/`192.168.*`), but the HTML comment with config warnings will appear in view-source if anything's missing

What to verify after a cPanel deploy:
- Check the page source for `[CONFIG WARNINGS]` HTML comments — these surface anything missing in `.env` or build artifacts
- Submit the contact form once. Check (a) the admin inbox receives the email, (b) reCAPTCHA logs show a verification, (c) thankyou page loads
- Run a Lighthouse audit. Common red flags: missing `ALLOWED_HOSTS` in `.env` (host-injection guard off), missing `ogimage.png` (social previews break)

`$basePath` auto-resolves whether the site is at the domain root (`example.com/`) or a subfolder (`example.com/landing-page/`) — no config change needed.

## Critical Rules for AI Editors

### Paths — NEVER hardcode paths
- ALL web-facing paths (images, CSS, JS, links, redirects) MUST use `$basePath` prefix
- Example: `<?= $basePath ?>assets/images/logo.png` — NOT `/assets/images/logo.png`
- Filesystem paths use `__DIR__` — Example: `__DIR__ . '/../config/config.php'`
- The site may live in a subfolder (e.g. `site.com/landing-page/`), subdomain, or root domain. `$basePath` handles all cases automatically via `config/config.php`
- Form actions: `action="<?= $basePath ?>partials/form"` (uses clean URLs)
- Redirects in PHP: `header('Location: ' . $basePath . 'thankyou');`

### Tailwind 4 — what's enabled, what's not
- We import **only `theme` and `utilities` layers** (see `assets/css/source/style.css`). Preflight (Tailwind's CSS reset) is INTENTIONALLY DISABLED — `assets/css/source/reset.css` is the single source of reset truth
- Tailwind's `.container` utility exists in the `utilities` layer, but our custom `.container` (and variants `.container-sm`, `.container-md`, etc.) live UNLAYERED in `critical.css`. Per CSS `@layer` rules, **unlayered styles always win over layered styles** — so our `.container` takes precedence. No collision. DO NOT move our container into `@layer utilities` (would lose this precedence)
- **`reset.css` MUST stay wrapped in `@layer base`.** It's inlined in `<head>` BEFORE `style.min.css` loads. If unlayered, its blanket rules (`* { margin: 0 }`, `h1..h6 { font-size: inherit }`, `ul { list-style: none }`) would beat ANY Tailwind utility (`text-4xl`, `mt-6`, `font-bold`, `list-disc`) regardless of specificity, because unlayered always wins over layered. The pre-declaration `@layer theme, base, components, utilities;` at the top of the file locks in the same order `style.css` uses, so utilities reliably override the reset. DO NOT unwrap the reset rules; DO NOT remove the pre-declaration
- Tailwind 4 ships ZERO prebuilt component classes by default — there's no `.btn`, no `.card`, etc. The "components" layer exists in the import order but is empty unless you write your own
- DO NOT add `@import "tailwindcss"` back to `style.css`. That re-enables Preflight and duplicates the reset

### CSS — Tailwind-first; @apply for the rest
- **Default tool is Tailwind utility classes** — express the design with utilities directly in markup
- **If you genuinely need custom CSS**, compose with `@apply`, never raw declarations:
  - ✅ `.feature-card { @apply bg-white rounded-2xl p-6 shadow-sm; }`
  - ❌ `.feature-card { background: white; border-radius: 16px; padding: 24px; }`
- **DO NOT** use inline `style="..."` attributes — bypasses the design system
- **DO NOT** use Tailwind arbitrary values (`p-[17px]`) as a habit — use the closest scale value or extend `@theme`. Arbitrary values are an escape hatch
- **Header + first 2 sections** → `assets/css/source/critical.css` (inlined in `<head>`)
- **Section 3 onward** → `assets/css/source/custom.css` (loaded async)
- **Media query overrides** → `assets/css/source/responsive.css`
- **DO NOT** add custom styles to `style.css` — it's the Tailwind entry point only
- **DO NOT** edit anything in `assets/css/min/` or `assets/js/min/` — auto-generated
- `@apply` works in ALL CSS files (critical.css, custom.css, responsive.css, reset.css) — they all have `@reference "tailwindcss"` and `@reference "./style.css"`
- When using `@apply` with `!important`, use the TW4 syntax: `@apply bg-black!` (NOT `@apply !bg-black`)

### CSS Variables — Use `--brand-*` NOT `--color-*`
- Tailwind CSS 4 uses `--color-*` internally. Our custom colors use `--brand-*` prefix to avoid collision
- Define in `critical.css` `:root`: `--brand-primary`, `--brand-secondary`, `--brand-text`, `--brand-accent`, `--brand-font`
- Mapped to TW utilities in `style.css` `@theme inline`: `bg-primary`, `text-secondary`, `border-accent`, `font-brand`, etc.
- In custom CSS: use `var(--brand-primary)` — NOT `var(--color-primary)`
- In HTML: use Tailwind classes `bg-primary`, `text-accent`, `font-brand`, etc.

### Fonts — Read this before adding/swapping a font
- The boilerplate ships **Manrope** as the default font: 5 weights (400, 500, 600, 700, 800), latin subset, woff2-only, in `assets/fonts/Manrope/`. Open-source (SIL OFL), free for commercial use
- Manrope ships NO italics — italic body copy uses the system fallback's italic glyphs (acceptable for landing pages; if you need italics, use a different font)
- The font is wired through ONE CSS variable: `--brand-font` in `critical.css` `:root`. Body uses `font-family: var(--brand-font)`. Tailwind exposes it as `font-brand` via `--font-brand: var(--brand-font)` in `style.css` `@theme inline`
- To swap in a different font: (1) drop `.woff2` files into `assets/fonts/<YourFont>/`, (2) replace the `@font-face` blocks in `fonts.css` to match, (3) change `--brand-font` in `critical.css` to put your font-family name first in the stack. That's the entire swap.
- DO NOT ship `.woff` files alongside `.woff2`. woff2 is universal since ~2018; .woff fallback only adds bytes
- DO use `font-display: swap` on every `@font-face` to prevent FOIT (invisible text on slow connections)
- The boilerplate already preloads Manrope-400 (body weight) in `partials/header.php`. For other above-the-fold weights, add another `<link rel="preload" as="font" type="font/woff2" crossorigin>` next to it. Only preload weights actually visible on first paint
- DO NOT preload every weight — preloading everything is worse than preloading nothing
- Use `font-brand` (Tailwind utility synced with `--brand-font`) — there's no font-specific utility

### JavaScript
- Custom JS → `assets/js/source/script.js`
- To add a new JS file: add filename to `jsFiles` array in `build.js`
- Vendor JS (AOS, Swiper) → `assets/js/vendor/`
- Quick live JS fix → `assets/js/live-edit.js` (no compile needed)

### Vendor scripts — opt-in, never default-load
- AOS (scroll animations) and Swiper (sliders) are gated by `$useSwiper` and `$useAos` flags in `config.php`. Both DEFAULT to `false`. A bare clone of the boilerplate ships zero vendor bytes
- To enable on a page: in the page's entry script (e.g. `index.php`), set the flag(s) AFTER `require_once config.php` and BEFORE `include header.php`. Example: `$useSwiper = true; $useAos = true;`
- Both `header.php` (CSS preload + `<noscript>` fallback) and `footer.php` (script tag) check these flags before emitting the link/script tags
- `script.js` guards its `AOS.init()` call with `typeof AOS !== "undefined"` so the same compiled script works on AOS-on and AOS-off pages without throwing
- DO NOT remove the `typeof X !== "undefined"` guard pattern when adding new vendor JS that might be opt-in
- For a NEW vendor library: (1) drop file into `assets/js/vendor/` or `assets/css/vendor/`, (2) add a `$useFoo` flag to config.php, (3) add the conditional `<?php if (!empty($useFoo)): ?>` block in header.php and/or footer.php, (4) guard any `Foo.method()` call in `script.js` with a `typeof Foo !== "undefined"` check

### PHP Template Structure
- `index.php` / `thankyou.php` — Entry points (include config + header + main + footer)
- `partials/header.php` — `<head>`, meta tags, inline critical CSS, async stylesheets
- `partials/main.php` — Page content (replace boilerplate sample)
- `partials/footer.php` — reCAPTCHA loader, vendor scripts, compiled JS
- `partials/form.php` — POST handler for contact form with email template
- `config/config.php` — Site settings, reCAPTCHA keys, email config, dynamic `$basePath`
- `config/functions.php` — Image helper functions (imgx, svgx, svg_inline)
- `data/global-content.php` — Shared content variables (PHP includes only, blocked from web access)

### Thank You Page
- `thankyou.php` exists for conversion tracking scripts (Google Ads, Meta Pixel, etc.)
- It shows the same page content as index.php with a success popup overlay
- The close button dismisses the popup — do NOT redirect to home (avoids unnecessary reload)
- Add tracking scripts directly in thankyou.php between the `</style>` and the popup HTML

### Form Handler (`partials/form.php`)
- reCAPTCHA verification uses POST (not GET) to keep secrets out of server logs
- Phone validation is Australian format (`0X XXXX XXXX`) — change regex for other countries
- `$no_reply_email` is auto-generated from the validated host (handles .com.au, .co.uk, etc.)
- **`header_safe()`** is the function that strips control chars (incl. `\r\n`) from user input before it goes into a mail header. ALWAYS pass user-supplied values through it before using in `Reply-To`, `From`, `Subject`, `Cc`, `Bcc`. `strip_tags()` does not strip newlines — using it alone is an email-header-injection vulnerability
- **`alert()` error messages use `json_encode(..., JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`** to escape exception text. Do NOT swap to `addslashes()` — it doesn't escape `</script>` and would break out of the JS context
- The catch block uses `catch (Exception $e)` for the user-facing error and `catch (\PHPMailer\PHPMailer\Exception)` (no var) for the SMTP-internal error (PHP 8 syntax). The SMTP error gets `error_log`'d, never echoed
- See "Form re-wiring when the design changes" above for the playbook when fields change

### Thank-you page (`thankyou.php`)
- Sets `$isThankYou = true` BEFORE including the header partial. This flag is read in `partials/footer.php` to skip the reCAPTCHA loader + 60s token-refresh interval — no point loading grecaptcha after the form has already been submitted
- The popup overlay shows on a 1.5s delay so the page paint completes first; the close button dismisses without redirecting (no extra reload)
- Tracking scripts (Google Ads conversion, Meta Pixel) go between the closing `</style>` and the `<div class="thankyou-message">` block

### Config warnings — Read this when adding new required config
- `get_config_warnings()` in `config/functions.php` returns an array of human-readable strings for any missing/critical issues
- Currently checks five categories — when adding a new check, place it in the right one and follow the prefix convention:
  - `[Config]` — empty values in `config.php` (`$site`, `$description`). `$phone_number` is intentionally NOT checked since many landing pages collect email only
  - `[Env]` — missing values in `.env` that are required for the form to work (`FORM_ADMIN_EMAIL`, reCAPTCHA keys)
  - `[Production]` — production-only checks that are skipped in dev (e.g. `ALLOWED_HOSTS` empty)
  - `[Assets]` — referenced static files that don't exist on disk (`ogimage.png`, `favicon.svg`)
  - `[Build]` — missing or empty files in `assets/css/min/` or `assets/js/min/` — points the dev to `npm install && npm run build`
- `partials/header.php` consumes the array twice: (1) renders an HTML comment near `</head>` always (dev + prod), invisible to users but visible in view-source; (2) renders a red banner at the top of `<body>` ONLY when `$isDevelopment` is true
- Inline styles on the banner are intentional — it must work even if no CSS has loaded yet (e.g. when `[Build]` artifacts are missing)
- When introducing a new required config value, add a check inside `get_config_warnings()` that explains the consequence of leaving it empty AND hints at the fix. Do NOT add ad-hoc error messages elsewhere — the warnings surface is centralized here
- The banner uses `position: relative` so it pushes content down instead of overlaying — keep it that way; fixed positioning during dev hides the page header you're trying to design

### Email transport — auto-detected from `.env`
- Default: PHP `mail()` with envelope sender (`-f $no_reply_email`) and a full header set (Return-Path, Message-ID, Date, X-Mailer, Content-Transfer-Encoding). Works on most shared hosting that auto-DKIMs at the MTA level
- SMTP path: triggered when `SMTP_HOST` is set in `.env`. Uses bundled PHPMailer at `lib/PHPMailer/` (v6.9.1, lazy-required only on the SMTP code path)
- Both transports build the SAME headers and the SAME HTML body — only the wire protocol differs. Keep the two paths producing identical output if you edit either
- DO NOT call `mail()` or instantiate `PHPMailer` outside `partials/form.php`. All form sending is centralized there
- DO NOT add new secrets to `config.php` — add them to `.env.example` + `.env` and read via `env()`
- When PHPMailer ships a CVE patch, replace the three files in `lib/PHPMailer/` with the new tagged release. Pin to a tagged version, never `master`

### Images and SVG
- All images go in `assets/images/`. Pass path **WITHOUT extension** — the helper resolves `.jpg` / `.png` / `.jpeg` automatically
- Image helpers live in `config/image.php`, SVG helpers in `config/svg.php`. Both are loaded by `config/functions.php`
- AVIF + WebP versions are generated on first render and cached on disk. Re-conversion is automatic if the source file is newer than the cached version (mtime check)
- `getimagesize()` and SVG file reads are statically cached per request — calling the same icon 50 times only hits disk once
- Internal helpers (prefixed `_`) are for the helper files only; do NOT call from templates
- DO NOT call `mail()`, instantiate `PHPMailer`, or write image conversion code outside `config/image.php` and `config/svg.php`. Centralized for a reason

#### `imgx()` — raster images via `<picture>` (AVIF + WebP fallback)
- `imgx('hero', 'Alt')` — basic
- `imgx('hero', 'Alt', 'rounded shadow')` — with class
- `imgx('hero-desktop', 'Hero', '', 'hero-mobile')` — mobile variant via `<picture><source media>`
- Above-the-fold (LCP) image: `imgx('hero', 'Hero', '', '', '', false, 768, ['fetchpriority' => 'high'])` — eager + priority hint
- 2x retina is automatic — drop `hero@2x.jpg` next to `hero.jpg` and the rendered `<source srcset>` will include `1x, 2x` descriptors
- Available `$opts` (last positional arg): `fetchpriority`, `decoding` (default `async`), `width`, `height`, `retina` (default `true`), `sizes`
- Always emits `width` + `height` attributes when known (prevents CLS)

#### `svgx()` — SVG as `<img>` tag
- `svgx('icons/logo', 'Logo', 'h-8')` — auto-detects width/height **including viewBox-only SVGs** (no layout shift)
- `$opts`: `lazy` (default true), `decoding` (default `async`), `width`, `height`, `fetchpriority`

#### `svg_inline()` — inline SVG markup (for CSS/JS styling)
- `svg_inline('icons/arrow', 'arrow-icon')` — drops the SVG markup directly into HTML
- Strips `<?xml...?>` and `<!DOCTYPE>` declarations automatically (safe to inline)
- Class injection MERGES with any existing class on the root `<svg>` (no duplicate `class=""` attributes)
- SECURITY: only inline SVGs you control. SVG can contain `<script>` — never inline user-uploaded SVG without sanitizing

#### SVG from Figma — copy EXACT markup
- When the design has SVGs (icons, logos, illustrations), copy the SVG byte-for-byte from Figma into `assets/images/<name>.svg` — preserve the original `viewBox`, paths, and fill/stroke colors
- DO NOT redraw SVGs from scratch and DO NOT swap to an icon library. The Figma export IS the design source
- DO NOT inline raw `<svg>` markup in PHP partials — always render via `svgx()` or `svg_inline()`
- For icons that need CSS-driven color: edit the SVG once, change `fill="#xxx"` → `fill="currentColor"`, then use `svg_inline('icons/x', 'text-primary')` so the icon inherits the Tailwind text color

#### Background images — NEVER `background-image: url()`
- DO NOT use CSS `background-image: url(...)`, Tailwind `bg-[url('...')]`, or inline `style="background-image: ..."`. They skip AVIF/WebP/retina/fetchpriority/CLS
- Instead render the bg as a real `<img>` via `imgx()`, wrapped in the `.bg-cover` utility (defined in `critical.css`):

```php
<section class="relative py-20 md:py-32 overflow-hidden">
  <div class="bg-cover">
    <?php imgx('hero-bg', '', '', 'hero-bg-mobile', '', false, 768, ['fetchpriority' => 'high']); ?>
  </div>
  <div class="container relative z-10">
    <h1 class="text-white text-5xl">Headline over background</h1>
  </div>
</section>
```

- Parent gets `relative overflow-hidden`; foreground content uses `relative z-10` to stack above
- The `.bg-cover` utility positions the wrapper `absolute inset-0` and applies `object-cover` to the `<img>` inside

### Container System
Custom containers in `critical.css` (Tailwind's built-in container is NOT used):
- `.container` = 1280px (default), `.container-sm` = 640px, `.container-md` = 768px
- `.container-lg` = 1024px, `.container-2xl` = 1536px, `.container-max` = 1920px
- Sizes configurable via `:root` CSS variables in `critical.css`

### Secrets & .env — Read this before touching config.php
- Secrets (reCAPTCHA keys, future SMTP creds, API tokens) live in `.env` at the project root, NEVER in `config.php` or any committed file
- `config.php` reads them via `env('KEY_NAME', '')` — see `config/env.php` for the loader
- New secrets workflow: add the key to `.env.example` (committed, empty value) AND to `.env` (gitignored, real value), then read with `env()` in `config.php`
- Variable names exposed by `config.php` (e.g. `$recaptcha_client_secret`) are part of the contract — keep them stable so `form.php` / `footer.php` / `partials/*.php` keep working
- `.env` is gitignored and blocked from the web in `.htaccess`. Do NOT remove either guard

### Trusted host & email From: — Read this before touching the top of config.php
- The block at the top of `config.php` validates `$_SERVER['HTTP_HOST']` against `ALLOWED_HOSTS` from `.env` to defend against Host header injection
- Allowlist syntax: comma-separated hostnames, optionally with `*.suffix` wildcards (e.g. `angel.com, *.angel.com`)
- `$hostName` (port-stripped) feeds the `$emailDomain` derivation so no-reply emails are clean. `$host` (with port) feeds `$site_url` and `$baseUrl`
- The no-reply address is intentionally derived from the registered domain: `landing.angel.com` and `www.angel.com` both produce `no-reply@angel.com`. Two-part TLDs (`.com.au`, `.co.uk`) are handled
- Local dev hosts (`localhost`, `127.0.0.1`, `192.168.*`, `*.test`, `*.local`) are always trusted regardless of `ALLOWED_HOSTS`. Don't break this — `npm run dev` depends on it
- DO NOT use raw `$_SERVER['HTTP_HOST']` or `$_SERVER['SERVER_NAME']` anywhere downstream — use `$host` (validated) or `$hostName` (validated, no port). Both are set by the block at the top of `config.php`
- DO NOT touch `$basePath` derivation — it's filesystem-based (`realpath()` against `DOCUMENT_ROOT`) and is what makes the page work on root / subfolder / sub-sub-folder / subdomain identically

### Security — DO NOT
- DO NOT expose `.env`, `config.php`, `build.js`, `CLAUDE.md`, or `data/` to web (blocked by `.htaccess`)
- DO NOT hardcode reCAPTCHA keys, API tokens, or any secret in `config.php` or any committed file — use `.env` + `env()`
- DO NOT put reCAPTCHA server secret in client-side code
- DO NOT remove honeypot field from forms
- DO NOT use `$_GET` or `$_POST` values without sanitizing (use `strip_tags`, `filter_var`, `htmlspecialchars`)

### Build System (`build.js`)
- Do not modify unless adding new CSS/JS files
- To add a CSS file with `@apply` support: add to `separateWithTailwind` array + add `@reference` directives to the file
- To add a plain CSS file: add to `separatePlain` array
- To add a JS file: add to `jsFiles` array
- `live-edit.css` and `live-edit.js` are excluded from watch (they're live-fix files, not compiled)
- Watch strategy is auto-platform: polling on Windows (FS events unreliable), native fsevents/inotify on Mac/Linux. Do NOT hardcode `usePolling: true`
- BrowserSync detection priority: `<project>.test` and `<project>.local` (clean vhost URLs) before `localhost/<project>` (subfolder fallback). Override with `LOCAL_URL=http://your-url npm run dev`
- `injectChanges: true` is intentional — CSS edits hot-inject without full reload (preserves scroll/form state). Don't change to `false`

### Cross-platform — Read this before adding/renaming asset files
- **Always lowercase asset filenames** (`hero.jpg`, not `Hero.jpg`). Mac and Windows are case-INsensitive — a file referenced as `hero.jpg` resolves to `Hero.jpg` locally without complaint. Linux production servers are case-SENSITIVE — same reference 404s. Same rule for folders inside `assets/`
- This boilerplate runs identically on Windows (Laragon/XAMPP), Mac (Valet/Herd/MAMP), and Linux. The only OS-touching code is in `build.js` and is auto-detected
- For local dev, prefer a clean vhost URL (`<project>.test`) over the localhost subfolder URL — the subfolder URL works but shows `localhost:3000/<project>/` instead of `localhost:3000/`
  - **Windows (Laragon)**: enable Preferences → "Auto Virtual Hosts"
  - **Mac (Valet)**: `cd <project> && valet link`
  - **Mac (Herd)**: drag the project folder into the Herd app
  - Or override the detection: `LOCAL_URL=http://my-custom-url npm run dev`
- DO NOT commit OS-generated files (`.DS_Store`, `Thumbs.db`, `desktop.ini`) — already in `.gitignore`
- Line endings (`\r\n` vs `\n`): handled transparently by Git via `core.autocrlf` and by Node — no special handling needed in PHP/JS/CSS
