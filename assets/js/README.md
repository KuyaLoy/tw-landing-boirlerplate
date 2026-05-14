# `assets/js/`

Mirror of the CSS folder for JavaScript.

| Folder / file       | What's in it                                              | Edit? |
|---------------------|-----------------------------------------------------------|-------|
| `source/`           | Editable source JS (`script.js`)                          | YES   |
| `min/`              | Compiled, minified output. Served to the browser.         | NO — auto-generated |
| `vendor/`           | Third-party JS (AOS, Swiper) shipped as-is                | NO    |
| `live-edit.js`      | Quick fixes loaded LAST so they override `min/`           | YES (sparingly) |

## How it works

Edit `source/script.js`. `npm run dev` watches and rebuilds `min/script.min.js` via esbuild. The browser only loads `min/script.min.js` — same locally and in production. Both folders are committed to git so the boilerplate ships ready-to-deploy without any npm step.

Adding a new JS file? Add its name to the `jsFiles` array in `build.js`.

## `live-edit.js`

Loads after `min/script.min.js`. Empty by default (gated by a filesize check, so an empty file isn't even fetched). Use it when you need to push a quick JS fix to a live site without rebuilding — anything you put here eventually belongs in `source/`.

## Vendor opt-in

`vendor/swiper-bundle.min.js` and `vendor/aos.js` are gated by `$useSwiper` and `$useAos` flags in `config/config.php`. A page only loads them if the flag is `true` for that page. See CLAUDE.md "Vendor scripts" section for the full pattern.
