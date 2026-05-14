# `assets/css/`

Quick reference for the four sub-folders here.

| Folder / file        | What's in it                                              | Edit? |
|----------------------|-----------------------------------------------------------|-------|
| `source/`            | Editable source CSS (`critical.css`, `style.css`, etc.)   | YES   |
| `min/`               | Compiled, minified output. Served to the browser.         | NO — auto-generated |
| `vendor/`            | Third-party CSS (AOS, Swiper) shipped as-is               | NO    |
| `live-edit.css`      | Quick fixes loaded LAST so they override `min/`           | YES (sparingly) |

## How the pipeline works

You edit a file in `source/`. When `npm run dev` is running it watches and re-compiles to `min/` automatically. The browser only ever sees `min/` — same files locally and in production.

Both `source/` and `min/` are committed to git. That means anyone can clone this repo, **skip the npm step entirely**, FTP-upload the project folder, and the site works immediately. The build pipeline is purely an optional convenience for devs editing source files.

## When to use `live-edit.css`

It's for the "I'm logged into FTP and need to fix this border-radius without running npm" case. It loads after `min/`, so any rule in here wins. Use sparingly — anything you put here should eventually move into `source/` and get re-compiled, otherwise you're fighting the cache-busting versioning.

## Why not call it `dist/` or `build/`?

Because those names imply "you must have built this folder yourself, or it doesn't exist." Plenty of devs and clients deploy this boilerplate via FTP without ever running `npm`. Calling the folder `min/` (universally read as "minified") signals "this is the production version" without implying you have to build to get it.
