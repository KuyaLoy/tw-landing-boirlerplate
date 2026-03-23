# Tailwind CSS Landing Page Boilerplate

A ready-to-use landing page starter with Tailwind CSS, PHP, and a built-in contact form with email.

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
npm run dev
```

This opens your browser at `http://localhost:3000` with **live reload** — every time you save a file, the browser refreshes automatically.

### Build for Upload

```bash
npm run build
```

Creates a ready-to-upload ZIP in the `build/` folder. No Node.js needed on the server.

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
  --color-primary: #3b82f6;    ← main brand color
  --color-secondary: #8b5cf6;  ← second color
  --color-accent: #f59e0b;     ← highlight color
  --color-text: #1a1a1a;       ← text color
}
```

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
│   ├── fonts/               ← Custom font files
│   ├── css/
│   │   ├── dev/             ← Your CSS files (edit these)
│   │   ├── compiled/min/    ← Auto-generated (don't edit)
│   │   ├── edit.css         ← Quick live fixes (no compile needed)
│   │   └── vendor/          ← Third-party CSS (AOS, Swiper)
│   └── js/
│       ├── dev/             ← Your JS files (edit these)
│       ├── compiled/min/    ← Auto-generated (don't edit)
│       ├── edit.js          ← Quick live fixes (no compile needed)
│       └── vendor/          ← Third-party JS (AOS, Swiper)
│
├── tailwind.config.js       ← Tailwind settings
└── build.js                 ← Build system (don't touch)
```

---

## CSS Files Explained

| File | What it's for | Needs `npm run dev`? |
|---|---|---|
| `css/dev/critical.css` | Colors, containers, above-fold styles | Yes |
| `css/dev/style.css` | Main styles (imports everything) | Yes |
| `css/dev/custom.css` | Your custom CSS | Yes |
| `css/dev/responsive.css` | Mobile/tablet adjustments | Yes |
| `css/dev/reset.css` | Browser reset (rarely touch) | Yes |
| `css/dev/fonts.css` | @font-face declarations | Yes |
| **`css/edit.css`** | **Quick fixes on live site** | **No** |

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
- reCAPTCHA v3 spam protection
- Honeypot field for bots
- HTML email template with your logo
- Auto-redirect to `thankyou.php` after success

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
| Upload to server | `npm run build`, upload ZIP contents |
| See errors on live | Add `?debug=1` to URL |
