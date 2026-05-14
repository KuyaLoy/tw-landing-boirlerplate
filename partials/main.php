<?php /*
  ╔══════════════════════════════════════════════════════════════════════╗
  ║  WELCOME PAGE — DELETE EVERYTHING BETWEEN THE START / END MARKERS    ║
  ║  ──────────────────────────────────────────────────────────────────  ║
  ║  Replace this entire block with your own design.                     ║
  ║  • All styling is via Tailwind utility classes — zero custom CSS.    ║
  ║  • No <style> tags, no @apply rules, nothing in critical.css or      ║
  ║    custom.css to clean up. Delete and start fresh.                   ║
  ║  • Brand tokens already wired: bg-primary, text-secondary,           ║
  ║    text-accent, font-brand. See critical.css :root to change values. ║
  ╚══════════════════════════════════════════════════════════════════════╝
*/ ?>
<!-- ═══════════════ START WELCOME PAGE — DELETE BELOW ═══════════════ -->

<!-- ===== Header ===== -->
<header class="border-b border-gray-100 bg-white">
  <div class="container">
    <div class="flex items-center justify-between gap-4 py-5">
      <a href="<?= $basePath ?>" class="flex items-center gap-2.5 text-lg font-bold text-primary">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-white">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z" />
          </svg>
        </span>
        <span>Boilerplate</span>
      </a>

      <nav class="hidden items-center gap-7 text-sm font-medium text-gray-600 md:flex" aria-label="Primary">
        <a href="#features" class="transition hover:text-primary">Features</a>
        <a href="#stack" class="transition hover:text-primary">Stack</a>
        <a href="#start" class="transition hover:text-primary">Get started</a>
      </nav>

      <a href="#start" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
        Start building
      </a>
    </div>
  </div>
</header>

<main>

  <!-- ===== Hero ===== -->
  <section class="relative overflow-hidden bg-gradient-to-b from-white via-white to-gray-50 py-20 md:py-28 lg:py-36">
    <div class="container">
      <div class="mx-auto max-w-3xl text-center">

        <span class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-primary">
          <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
          PHP + Tailwind 4 boilerplate
        </span>

        <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-gray-900 md:text-5xl lg:text-6xl">
          Ship a landing page <br class="hidden sm:block">
          <span class="text-primary">in minutes, not days.</span>
        </h1>

        <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-gray-600">
          A production-ready boilerplate with Tailwind 4, AVIF/WebP images, reCAPTCHA-protected forms,
          SMTP-aware email, and AI-friendly conventions. Built for landing pages that load fast and
          rank on Lighthouse.
        </p>

        <div id="start" class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <a href="#features" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 font-semibold text-white transition hover:opacity-90">
            Explore features
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14M13 5l7 7-7 7" />
            </svg>
          </a>
          <a href="#stack" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-6 py-3 font-semibold text-gray-900 transition hover:border-primary hover:text-primary">
            View the stack
          </a>
        </div>

        <p class="mt-8 text-sm text-gray-500">
          Edit
          <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[0.85em] text-gray-800">partials/main.php</code>
          to start, or paste a Figma URL into Claude Code.
        </p>

      </div>
    </div>
  </section>


  <!-- ===== Features grid ===== -->
  <section id="features" class="py-20 md:py-28">
    <div class="container">

      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 md:text-4xl">
          Everything wired, nothing in your way
        </h2>
        <p class="mt-4 text-lg text-gray-600">
          Six things that usually take a week, already done.
        </p>
      </div>

      <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">

        <!-- Feature 1 -->
        <article class="group rounded-2xl border border-gray-100 bg-white p-6 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
              <path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12" />
            </svg>
          </div>
          <h3 class="mt-5 text-lg font-semibold text-gray-900">Tailwind 4 ready</h3>
          <p class="mt-2 text-sm leading-relaxed text-gray-600">
            CSS-first config, brand tokens via <code class="rounded bg-gray-100 px-1 font-mono text-[0.85em]">@theme inline</code>,
            no Preflight collisions, Preflight intentionally disabled.
          </p>
        </article>

        <!-- Feature 2 -->
        <article class="group rounded-2xl border border-gray-100 bg-white p-6 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="9" cy="9" r="2" />
              <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
            </svg>
          </div>
          <h3 class="mt-5 text-lg font-semibold text-gray-900">Smart image pipeline</h3>
          <p class="mt-2 text-sm leading-relaxed text-gray-600">
            <code class="rounded bg-gray-100 px-1 font-mono text-[0.85em]">imgx()</code> renders
            &lt;picture&gt; with auto AVIF + WebP fallback, retina @2x, mobile variants, and CLS-safe dimensions.
          </p>
        </article>

        <!-- Feature 3 -->
        <article class="group rounded-2xl border border-gray-100 bg-white p-6 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </div>
          <h3 class="mt-5 text-lg font-semibold text-gray-900">Form, hardened</h3>
          <p class="mt-2 text-sm leading-relaxed text-gray-600">
            reCAPTCHA v3 + honeypot + header-injection-safe email. Auto-detects SMTP from
            <code class="rounded bg-gray-100 px-1 font-mono text-[0.85em]">.env</code>; falls back to native mail().
          </p>
        </article>

        <!-- Feature 4 -->
        <article class="group rounded-2xl border border-gray-100 bg-white p-6 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z" />
            </svg>
          </div>
          <h3 class="mt-5 text-lg font-semibold text-gray-900">Critical CSS inlined</h3>
          <p class="mt-2 text-sm leading-relaxed text-gray-600">
            Reset + fonts + above-fold styles inlined in &lt;head&gt;. Below-fold loads async.
            Perfect Lighthouse FCP without ceremony.
          </p>
        </article>

        <!-- Feature 5 -->
        <article class="group rounded-2xl border border-gray-100 bg-white p-6 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="12" r="10" />
              <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
            </svg>
          </div>
          <h3 class="mt-5 text-lg font-semibold text-gray-900">Subfolder-safe paths</h3>
          <p class="mt-2 text-sm leading-relaxed text-gray-600">
            <code class="rounded bg-gray-100 px-1 font-mono text-[0.85em]">$basePath</code>
            auto-resolves on root, subfolder, subdomain — even Laragon's localhost subpath.
            One codebase, every host.
          </p>
        </article>

        <!-- Feature 6 -->
        <article class="group rounded-2xl border border-gray-100 bg-white p-6 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 2 2 7l10 5 10-5-10-5z" />
              <path d="m2 17 10 5 10-5M2 12l10 5 10-5" />
            </svg>
          </div>
          <h3 class="mt-5 text-lg font-semibold text-gray-900">AI-friendly</h3>
          <p class="mt-2 text-sm leading-relaxed text-gray-600">
            Bundled <code class="rounded bg-gray-100 px-1 font-mono text-[0.85em]">.claude-template/</code>
            with agents for Figma → code, accessibility, performance, and security review.
          </p>
        </article>

      </div>
    </div>
  </section>


  <!-- ===== Stack strip ===== -->
  <section id="stack" class="border-y border-gray-100 bg-gray-50 py-16">
    <div class="container">
      <p class="text-center text-xs font-semibold uppercase tracking-widest text-gray-500">
        Built on a tight, modern stack
      </p>
      <div class="mt-8 grid grid-cols-2 gap-x-6 gap-y-8 text-center sm:grid-cols-3 lg:grid-cols-6">
        <div>
          <p class="text-2xl font-bold text-gray-900">PHP 8</p>
          <p class="mt-1 text-xs text-gray-500">Plain partials</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-900">Tailwind 4</p>
          <p class="mt-1 text-xs text-gray-500">CSS-first config</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-900">esbuild</p>
          <p class="mt-1 text-xs text-gray-500">JS minification</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-900">PostCSS</p>
          <p class="mt-1 text-xs text-gray-500">CSS pipeline</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-900">PHPMailer</p>
          <p class="mt-1 text-xs text-gray-500">Optional SMTP</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-900">BrowserSync</p>
          <p class="mt-1 text-xs text-gray-500">Live reload</p>
        </div>
      </div>
    </div>
  </section>


  <!-- ===== Quick-start CTA ===== -->
  <section class="py-20 md:py-28">
    <div class="container">
      <div class="mx-auto max-w-3xl rounded-3xl bg-gray-900 px-8 py-14 text-center shadow-xl md:px-14">
        <h2 class="text-3xl font-bold tracking-tight text-white md:text-4xl">
          Ready to build?
        </h2>
        <p class="mx-auto mt-4 max-w-xl text-lg text-gray-300">
          Replace this <code class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-[0.85em] text-white">partials/main.php</code>
          with your design. Run <code class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-[0.85em] text-white">npm run dev</code>
          and ship.
        </p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <a href="https://github.com" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-gray-900 transition hover:bg-gray-100">
            Read the README
          </a>
          <a href="#" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/20 px-6 py-3 font-semibold text-white transition hover:bg-white/10">
            Open CLAUDE.md
          </a>
        </div>
      </div>
    </div>
  </section>

</main>


<!-- ===== Footer ===== -->
<footer class="border-t border-gray-100 py-10">
  <div class="container">
    <div class="flex flex-col items-center justify-between gap-4 text-sm text-gray-500 sm:flex-row">
      <p>
        &copy; <?= date('Y'); ?> <?= htmlspecialchars($site ?: 'Your Company') ?>. Built with this boilerplate.
      </p>
      <div class="flex items-center gap-5">
        <a href="#" class="transition hover:text-primary">Privacy</a>
        <a href="#" class="transition hover:text-primary">Terms</a>
        <a href="#" class="transition hover:text-primary">Contact</a>
      </div>
    </div>
  </div>
</footer>

<!-- ═══════════════ END WELCOME PAGE — DELETE ABOVE ═══════════════ -->
