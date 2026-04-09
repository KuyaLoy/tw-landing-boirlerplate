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

`npm run dev` opens your browser at `http://localhost:3000` with **live reload** — every time you save a file, the browser refreshes automatically.

### Build for Upload

```bash
npm run build            # Compile only (no ZIP)
npm run package          # Compile + create ZIP in build/
```

The ZIP in `build/` is ready to upload. No Node.js needed on the server.

---

## How to Deploy (Upload)

Upload the folder to your server. It works automatically in any location:

| Location | Example | You do |
|---|---|---|
| Root domain | `site.com` | Upload files to root folder |
| Subfolder | `site.com/landing-page` | Upload folder inside main site |
| Deep subfolder | `site.com/campaigns/landing-page` | Same — just upload |
| Subdomain | `landing.site.com` | Point subdomain to folder |
| Localhost | `localhost/my-project` | Just works during development |

**All paths (images, links, redirects, emails) adjust automatically.** No config changes needed.

---

## What to Edit for Each New Project

### 1. Site Info — `includes/config.php`

Fill in your project details:

```
$site           = "My Landing Page";        ← site name
$description    = "Short page description";  ← SEO description
$phone_number   = "1300 000 000";            ← display number
$admin_email    = "you@company.com";         ← form emails go here
```

Also update your **reCAPTCHA keys** (get them from [Google reCAPTCHA](https://www.google.com/recaptcha/admin)):

```
$recaptcha_client_secret = "your-site-key";
$recaptcha_server_secret = "your-secret-key";
```

### 2. Page Content — `src/main.php`

This is your main page HTML. Replace the boilerplate sample with your actual content.

### 3. Colors — `assets/css/dev/critical.css`

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

### 5. Fonts — `assets/fonts/` + `assets/css/dev/fonts.css`

Replace the font files and update `fonts.css` with your `@font-face` rules.

### 6. Global Content — `data/global-content.php`

Add shared content variables here (e.g., repeating text, phone numbers) to reuse across pages.

---

## Folder Structure (What Goes Where)

```
project/
├── index.php                ← Homepage (don't rename)
├── thankyou.php             ← Thank you page after form submit
│
├── src/                     ← Page sections
│   ├── header.php           ← <head> tag, meta, CSS loading
│   ├── main.php             ← Your page content goes here
│   ├── footer.php           ← Scripts, JS loading
│   └── form.php             ← Contact form handler + email template
│
├── includes/
│   ├── config.php           ← Site settings, emails, reCAPTCHA
│   └── functions.php        ← Image helpers (imgx, svgx, etc.)
│
├── data/
│   └── global-content.php   ← Shared content variables
│
├── assets/
│   ├── images/              ← All images (auto-converts to WebP/AVIF)
│   ├── fonts/               ← Custom font files (woff2 + woff)
│   ├── css/
│   │   ├── dev/             ← Your CSS files (edit these)
│   │   ├── min/             ← Auto-generated (don't edit)
│   │   ├── edit.css         ← Quick live fixes (no compile needed)
│   │   └── vendor/          ← Third-party CSS (AOS, Swiper)
│   └── js/
│       ├── dev/             ← Your JS files (edit these)
│       ├── min/             ← Auto-generated (don't edit)
│       ├── edit.js          ← Quick live fixes (no compile needed)
│       └── vendor/          ← Third-party JS (AOS, Swiper)
│
├── CLAUDE.md                ← AI assistant rules for this project
└── build.js                 ← Build system (don't touch)
```

**Note:** Tailwind CSS 4 uses CSS-first configuration. Theme settings live in `assets/css/dev/style.css` (`@theme inline` block), not in a separate config file.

**AI Rules:** `CLAUDE.md` contains rules for AI assistants (Claude, Cursor, Copilot, etc.) to follow when editing this project — path conventions, CSS placement rules, security guidelines, and more.

---

## CSS Files Explained

| File | What it's for | `@apply` works? | Needs `npm run dev`? |
|---|---|---|---|
| `css/dev/critical.css` | Colors, containers, above-fold styles (header + first 2 sections) | Yes | Yes |
| `css/dev/style.css` | Tailwind entry point + theme config (don't add custom styles here) | Yes | Yes |
| `css/dev/custom.css` | Your custom CSS (section 3 onward) | Yes | Yes |
| `css/dev/responsive.css` | Media query overrides (when Tailwind utilities aren't enough) | Yes | Yes |
| `css/dev/reset.css` | Browser reset (rarely touch) | Yes | Yes |
| `css/dev/fonts.css` | @font-face declarations only | No | Yes |
| **`css/edit.css`** | **Quick fixes on live site** | **No** | **No** |

### Using `@apply` in CSS

All CSS files (except `fonts.css` and `edit.css`) support Tailwind's `@apply` directive. Use TW4 syntax for `!important`:

```css
/* Correct TW4 syntax */
body { @apply bg-black!; }

/* Also works with custom theme colors */
.header { @apply bg-primary text-white; }
```

This works because each file has `@reference "tailwindcss"` and `@reference "./style.css"` at the top — these give `@apply` access to all utilities without injecting Tailwind's full output.

### Quick Fix on a Live Site

Need to fix something on a live site fast without running `npm`? Just edit `assets/css/edit.css` or `assets/js/edit.js` — they load last and override everything. Upload and done.

---

## Using Images in Your HTML

### Regular images (auto WebP/AVIF)

```php
<?php imgx('hero-banner', 'Hero Image', 'my-class'); ?>
```

Put `hero-banner.jpg` or `hero-banner.png` in `assets/images/` — it automatically creates WebP and AVIF versions for faster loading.

### With mobile version

```php
<?php imgx('hero-desktop', 'Hero', 'hero-img', 'hero-mobile'); ?>
```

### SVG as image

```php
<?php svgx('logo', 'Company Logo', 'logo-class'); ?>
```

### SVG inline (for CSS styling)

```php
<?php svg_inline('icon-arrow', 'arrow-class'); ?>
```

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

The form handler is in `src/form.php`. It includes:
- reCAPTCHA v3 spam protection (verified via POST for security)
- Honeypot field for bots
- Input sanitization and validation
- HTML email template with your logo
- Auto-redirect to `thankyou.php` after success (for conversion tracking scripts)

To wire up your form, point it to `src/form.php`:

```html
<form method="POST" action="<?= $basePath ?>src/form.php">
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
| Change colors | Edit `assets/css/dev/critical.css` `:root` variables |
| Add page content | Edit `src/main.php` |
| Add images | Drop into `assets/images/`, use `imgx()` |
| Fix something on live | Edit `assets/css/edit.css` and upload |
| Change email template | Edit `src/form.php` |
| Change thank you page | Edit `thankyou.php` |
| Upload to server | `npm run package`, upload ZIP contents |
| See errors on live | Add `?debug=1` to URL |
