/**
 * ========================================
 *  live-edit.js — quick fixes without rebuild
 * ========================================
 *
 * Loads LAST, after the compiled script.min.js, so anything you put
 * here can override or extend the compiled JavaScript. Not processed
 * by the build system — vanilla JS only, refresh the page to see
 * changes.
 *
 * Use cases:
 *   - Fix something on a deployed site without running npm
 *   - Add tracking/analytics snippets
 *   - One-off scripts that don't yet justify editing source/
 *
 * Anything that becomes permanent should eventually move into
 * assets/js/source/script.js and get re-compiled. The boilerplate
 * skips loading this file entirely if it's empty.
 *
 * ========================================
 */

