# Project Rules

PHP + Tailwind CSS 4 landing page boilerplate. No framework — plain PHP includes.

## Commands

- `npm run dev` — Watch + BrowserSync auto-reload (localhost:3000)
- `npm run watch` — Watch only, no BrowserSync (for existing server)
- `npm run build` — Production build (compile + minify)
- `npm run package` — Production build + ZIP in `build/`
- `npm install` — First-time setup

## Critical Rules for AI Editors

### Paths — NEVER hardcode paths
- ALL web-facing paths (images, CSS, JS, links, redirects) MUST use `$basePath` prefix
- Example: `<?= $basePath ?>assets/images/logo.png` — NOT `/assets/images/logo.png`
- Filesystem paths use `__DIR__` — Example: `__DIR__ . '/../includes/config.php'`
- The site may live in a subfolder (e.g. `site.com/landing-page/`), subdomain, or root domain. `$basePath` handles all cases automatically via `includes/config.php`
- Form actions: `action="<?= $basePath ?>src/form"` (uses clean URLs)
- Redirects in PHP: `header('Location: ' . $basePath . 'thankyou');`

### CSS — Where to put custom styles
- **Header + first 2 sections** → `assets/css/dev/critical.css` (inlined in `<head>`)
- **Section 3 onward** → `assets/css/dev/custom.css` (loaded async)
- **Media query overrides** → `assets/css/dev/responsive.css`
- **DO NOT** add custom styles to `style.css` — it's the Tailwind entry point only
- **DO NOT** edit anything in `assets/css/min/` or `assets/js/min/` — auto-generated
- `@apply` works in ALL CSS files (critical.css, custom.css, responsive.css, reset.css) — they all have `@reference "tailwindcss"` and `@reference "./style.css"`
- When using `@apply` with `!important`, use the TW4 syntax: `@apply bg-black!` (NOT `@apply !bg-black`)

### CSS Variables — Use `--brand-*` NOT `--color-*`
- Tailwind CSS 4 uses `--color-*` internally. Our custom colors use `--brand-*` prefix to avoid collision
- Define in `critical.css` `:root`: `--brand-primary`, `--brand-secondary`, `--brand-text`, `--brand-accent`
- Mapped to TW utilities in `style.css` `@theme inline`: `bg-primary`, `text-secondary`, `border-accent`, etc.
- In custom CSS: use `var(--brand-primary)` — NOT `var(--color-primary)`
- In HTML: use Tailwind classes `bg-primary`, `text-accent`, etc.

### JavaScript
- Custom JS → `assets/js/dev/script.js`
- To add a new JS file: add filename to `jsFiles` array in `build.js`
- Vendor JS (AOS, Swiper) → `assets/js/vendor/`
- Quick live JS fix → `assets/js/edit.js` (no compile needed)

### PHP Template Structure
- `index.php` / `thankyou.php` — Entry points (include config + header + main + footer)
- `src/header.php` — `<head>`, meta tags, inline critical CSS, async stylesheets
- `src/main.php` — Page content (replace boilerplate sample)
- `src/footer.php` — reCAPTCHA loader, vendor scripts, compiled JS
- `src/form.php` — POST handler for contact form with email template
- `includes/config.php` — Site settings, reCAPTCHA keys, email config, dynamic `$basePath`
- `includes/functions.php` — Image helper functions (imgx, svgx, svg_inline)
- `data/global-content.php` — Shared content variables (PHP includes only, blocked from web access)

### Thank You Page
- `thankyou.php` exists for conversion tracking scripts (Google Ads, Meta Pixel, etc.)
- It shows the same page content as index.php with a success popup overlay
- The close button dismisses the popup — do NOT redirect to home (avoids unnecessary reload)
- Add tracking scripts directly in thankyou.php between the `</style>` and the popup HTML

### Form Handler (`src/form.php`)
- reCAPTCHA verification uses POST (not GET) to keep secrets out of server logs
- Phone validation is Australian format (`0X XXXX XXXX`) — change regex for other countries
- Email uses PHP `mail()` — works on most shared hosting. For SMTP, replace with PHPMailer
- `$no_reply_email` is auto-generated from the domain (handles .com.au, .co.uk, etc.)

### Images
- All images go in `assets/images/`. Pass path WITHOUT extension
- `imgx('hero', 'Alt text', 'class')` — auto-converts to AVIF + WebP, lazy loads by default
- `imgx('hero', 'Alt', '', '', '', false)` — eager loading for above-fold images
- `imgx('hero-desktop', 'Hero', 'class', 'hero-mobile')` — with mobile variant
- `svgx('icons/logo', 'Logo', 'class')` — SVG as `<img>` tag
- `svg_inline('icons/arrow', 'class')` — inline SVG for CSS/JS styling

### Container System
Custom containers in `critical.css` (Tailwind's built-in container is NOT used):
- `.container` = 1280px (default), `.container-sm` = 640px, `.container-md` = 768px
- `.container-lg` = 1024px, `.container-2xl` = 1536px, `.container-max` = 1920px
- Sizes configurable via `:root` CSS variables in `critical.css`

### Security — DO NOT
- DO NOT expose `.env`, `config.php`, `build.js`, `CLAUDE.md`, or `data/` to web (blocked by `.htaccess`)
- DO NOT put reCAPTCHA server secret in client-side code
- DO NOT remove honeypot field from forms
- DO NOT use `$_GET` or `$_POST` values without sanitizing (use `strip_tags`, `filter_var`, `htmlspecialchars`)

### Build System (`build.js`)
- Do not modify unless adding new CSS/JS files
- To add a CSS file with `@apply` support: add to `separateWithTailwind` array + add `@reference` directives to the file
- To add a plain CSS file: add to `separatePlain` array
- To add a JS file: add to `jsFiles` array
- `edit.css` and `edit.js` are excluded from watch (they're live-fix files, not compiled)
