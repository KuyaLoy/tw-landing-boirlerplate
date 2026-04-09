const fs = require("fs");
const path = require("path");
const postcss = require("postcss");
const tailwindcssPostcss = require("@tailwindcss/postcss");
const cssnano = require("cssnano");
const esbuild = require("esbuild");
const chokidar = require("chokidar");
const browserSync = require("browser-sync").create();
const net = require("net");
const archiver = require("archiver");

// ========== PATHS ==========
// dev/ = editable source files, min/ = compiled minified output
const srcDir = path.join(__dirname, "assets", "css", "dev");
const cssMinDir = path.join(__dirname, "assets", "css", "min");
const jsSrcDir = path.join(__dirname, "assets", "js", "dev");
const jsMinDir = path.join(__dirname, "assets", "js", "min");

// ========== CONFIG ==========
// JS files to compile (add more as needed)
const jsFiles = ["script.js"];

// Main CSS file (Tailwind entry point)
const mainFile = "style.css";

// Files imported by style.css (buildMainCSS inlines these)
const importedFiles = ["custom.css"];

// Files compiled separately (with Tailwind for @apply support)
const separateWithTailwind = ["critical.css", "responsive.css", "reset.css"];

// Files compiled separately (plain CSS, no Tailwind)
const separatePlain = [];

// Files compiled for inline critical CSS (fonts — inlined in <style> tag, no @apply needed)
const inlineCriticalFiles = ["fonts.css"];

// Ensure output directories exist
[cssMinDir, jsMinDir].forEach((dir) => {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
});

// ========== ENVIRONMENT ==========
function isDevelopment() {
  if (process.env.NODE_ENV === "production") return false;
  if (process.env.NODE_ENV === "development") return true;
  if (process.argv.includes("--dev")) return true;
  if (process.argv.includes("--prod")) return false;
  if (process.argv.includes("--watch")) return true;
  return false;
}

const isDev = isDevelopment();

// ========== CSS COMPILATION ==========

// Build main CSS by inlining @import statements
function buildMainCSS() {
  const stylePath = path.join(srcDir, mainFile);
  let mainContent = fs.readFileSync(stylePath, "utf8");

  // Replace @import "./file.css" with actual file contents
  importedFiles.forEach((importFile) => {
    const importPath = path.join(srcDir, importFile);
    if (fs.existsSync(importPath)) {
      const importContent = fs.readFileSync(importPath, "utf8");
      const importPattern = new RegExp(
        `@import\\s+["']\\./${importFile.replace(".", "\\.")}["'];?\\s*`,
        "g"
      );
      mainContent = mainContent.replace(
        importPattern,
        `\n/* ========== ${importFile.toUpperCase().replace(".CSS", "")} ========== */\n${importContent}\n`
      );
    }
  });

  return mainContent;
}

async function compileFile(file, isMain = false) {
  const sourcePath = path.join(srcDir, file);
  const cssMinPath = path.join(cssMinDir, file.replace(".css", ".min.css"));

  if (!fs.existsSync(sourcePath)) {
    return false;
  }

  try {
    const cssContent = isMain ? buildMainCSS() : fs.readFileSync(sourcePath, "utf8");

    // Build PostCSS plugins
    const plugins = [];

    // Tailwind CSS 4 (handles nesting + autoprefixer internally)
    if (isMain || separateWithTailwind.includes(file)) {
      plugins.push(tailwindcssPostcss());
    }

    // Always minify
    plugins.push(cssnano({ preset: "default" }));

    const result = await postcss(plugins).process(cssContent, {
      from: sourcePath,
      to: cssMinPath,
      map: isDev ? { inline: false } : false,
    });

    fs.writeFileSync(cssMinPath, result.css);

    if (result.map && isDev) {
      fs.writeFileSync(cssMinPath + ".map", result.map.toString());
    }

    const fileSize = (fs.statSync(cssMinPath).size / 1024).toFixed(2);
    console.log(`  ✓ ${file} → ${path.basename(cssMinPath)} (${fileSize} KB)`);
    return true;
  } catch (error) {
    console.error(`  ✗ Error compiling ${file}:`);
    console.error(`    ${error.message}`);
    if (error.stack) {
      error.stack.split("\n").slice(0, 3).forEach((line) => console.error(`    ${line}`));
    }
    return false;
  }
}

// ========== JAVASCRIPT COMPILATION ==========

async function compileJS(file) {
  const sourcePath = path.join(jsSrcDir, file);
  const outputPath = path.join(jsMinDir, file.replace(".js", ".min.js"));

  if (!fs.existsSync(sourcePath)) {
    console.error(`  ✗ JS file not found: ${file}`);
    return false;
  }

  try {
    await esbuild.build({
      entryPoints: [sourcePath],
      outfile: outputPath,
      bundle: false,
      minify: true,
      sourcemap: isDev,
      target: ["es2018"],
    });

    const fileSize = (fs.statSync(outputPath).size / 1024).toFixed(2);
    console.log(`  ✓ ${file} → ${path.basename(outputPath)} (${fileSize} KB)`);
    return true;
  } catch (error) {
    console.error(`  ✗ Error compiling ${file}: ${error.message}`);
    return false;
  }
}

// ========== BUILD ALL ==========

async function buildAll() {
  const mode = isDev ? "dev" : "prod";
  console.log(`\n── CSS (${mode}) ──`);

  // Build main file (Tailwind + imports)
  const mainResult = await compileFile(mainFile, true);

  // Build separate files with Tailwind (for @apply)
  const twResults = await Promise.all(
    separateWithTailwind.map(async (file) => ({ file, success: await compileFile(file) }))
  );

  // Build separate plain CSS files
  const plainResults = await Promise.all(
    separatePlain.map(async (file) => ({ file, success: await compileFile(file) }))
  );

  // Build inline critical CSS files (reset, fonts)
  const inlineResults = await Promise.all(
    inlineCriticalFiles.map(async (file) => ({ file, success: await compileFile(file) }))
  );

  const allCss = [{ file: mainFile, success: mainResult }, ...twResults, ...plainResults, ...inlineResults];

  console.log(`\n── JS (${mode}) ──`);
  const jsResults = await Promise.all(
    jsFiles.map(async (file) => ({ file, success: await compileJS(file) }))
  );

  const successCount = allCss.filter((r) => r.success).length + jsResults.filter((r) => r.success).length;
  const failCount = allCss.filter((r) => !r.success).length + jsResults.filter((r) => !r.success).length;

  return { successCount, failCount };
}

// ========== PACKAGING ==========

const excludePatterns = [
  /^\.git$/, /^node_modules$/, /^build$/, /^\.DS_Store$/, /^Thumbs\.db$/,
  /^desktop\.ini$/, /^\.env$/, /^\.env\./, /^\.vscode$/, /^\.idea$/,
  /\.log$/, /\.tmp$/, /\.swp$/, /\.swo$/, /~$/, /\.cache$/,
  /\.css\.map$/, /\.js\.map$/, /^package-lock\.json$/,
];

function shouldExclude(filePath, relativePath) {
  const fileName = path.basename(filePath);
  const parts = relativePath.split(path.sep);

  for (const pattern of excludePatterns) {
    if (pattern.test(fileName) || pattern.test(relativePath)) return true;
    for (const part of parts) {
      if (pattern.test(part)) return true;
    }
  }
  return false;
}

function createPackage() {
  return new Promise((resolve, reject) => {
    const projectName = path.basename(__dirname);
    const buildDir = path.join(__dirname, "build");

    if (!fs.existsSync(buildDir)) {
      fs.mkdirSync(buildDir, { recursive: true });
    }

    const timestamp = new Date().toISOString().replace(/[-:]/g, "").replace(/T/, "-").split(".")[0];
    const zipFileName = `${projectName}-${timestamp}.zip`;
    const zipFilePath = path.join(buildDir, zipFileName);

    const output = fs.createWriteStream(zipFilePath);
    const archive = archiver("zip", { zlib: { level: 9 } });

    output.on("close", () => {
      const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
      console.log(`\n${"=".repeat(50)}`);
      console.log(`  Package: ${zipFileName} (${sizeInMB} MB)`);
      console.log(`  Location: ${path.relative(__dirname, zipFilePath)}`);
      console.log(`${"=".repeat(50)}\n`);
      resolve();
    });

    archive.on("error", (err) => reject(err));
    archive.pipe(output);

    function addFiles(dir, baseDir = __dirname) {
      for (const file of fs.readdirSync(dir)) {
        const filePath = path.join(dir, file);
        const relativePath = path.relative(baseDir, filePath);
        const stat = fs.statSync(filePath);

        if (shouldExclude(filePath, relativePath)) continue;

        if (stat.isDirectory()) {
          addFiles(filePath, baseDir);
        } else {
          archive.file(filePath, { name: relativePath.split(path.sep).join("/") });
        }
      }
    }

    console.log(`\nPackaging ${projectName}...`);
    addFiles(__dirname);
    archive.finalize();
  });
}

// ========== CLI ==========

const watchMode = process.argv.includes("--watch");
const packageMode = process.argv.includes("--package");
const noBrowserSync = process.argv.includes("--no-sync");

if (watchMode) {
  // Watch patterns: CSS source + JS source + PHP files (for Tailwind class scanning)
  const watchPaths = [
    path.join(srcDir, "**", "*.css"),
    path.join(jsSrcDir, "**", "*.js"),
    path.join(__dirname, "src", "**", "*.php"),
    path.join(__dirname, "index.php"),
    path.join(__dirname, "thankyou.php"),
    path.join(__dirname, "includes", "**", "*.php"),
    path.join(__dirname, "data", "**", "*.php"),
  ];

  const watcher = chokidar.watch(watchPaths, {
    ignored: [
      path.join(__dirname, "assets", "css", "edit.css"),
      path.join(__dirname, "assets", "js", "edit.js"),
    ],
    ignoreInitial: true,
  });

  // All CSS files that should trigger a main rebuild when changed
  const allCssFiles = [...importedFiles, ...separateWithTailwind, ...separatePlain, ...inlineCriticalFiles];

  watcher.on("change", async (filePath) => {
    const relativePath = path.relative(__dirname, filePath);
    console.log(`\n  Changed: ${relativePath}`);

    if (filePath.endsWith(".css") && filePath.startsWith(srcDir)) {
      const fileName = path.basename(filePath);

      // Rebuild main if the main file or any imported file changes
      if (fileName === mainFile || importedFiles.includes(fileName)) {
        await compileFile(mainFile, true);
      }

      // Rebuild separately compiled files
      if (separateWithTailwind.includes(fileName) || separatePlain.includes(fileName) || inlineCriticalFiles.includes(fileName)) {
        await compileFile(fileName);
      }

      // If a non-imported CSS file changes (like critical.css), also rebuild main
      // because Tailwind may use @apply or theme values that changed
      if (!importedFiles.includes(fileName) && fileName !== mainFile && allCssFiles.includes(fileName)) {
        await compileFile(mainFile, true);
      }
    } else if (filePath.endsWith(".js") && filePath.includes(path.join("assets", "js", "dev"))) {
      const fileName = path.basename(filePath);
      if (jsFiles.includes(fileName)) {
        await compileJS(fileName);
      }
    } else if (filePath.endsWith(".php")) {
      console.log("  Rebuilding CSS (Tailwind class scan)...");
      await compileFile(mainFile, true);
    }
  });

  // Initial build
  console.log("Building + watching...\n");
  buildAll().then(async ({ successCount, failCount }) => {
    console.log(`\n${"=".repeat(50)}`);
    if (failCount === 0) {
      console.log(`  ${successCount} file(s) compiled successfully`);
    } else {
      console.log(`  ${successCount} compiled, ${failCount} failed`);
    }
    console.log(`${"=".repeat(50)}`);
    console.log("\nWatching for changes... (Ctrl+C to stop)\n");

    // Start BrowserSync (unless --no-sync flag)
    if (isDev && !noBrowserSync) {
      const http = require("http");
      const https = require("https");

      function isPortAvailable(port) {
        return new Promise((resolve) => {
          const server = net.createServer();
          server.listen(port, () => {
            server.once("close", () => resolve(true));
            server.close();
          });
          server.on("error", () => resolve(false));
        });
      }

      async function findAvailablePort(startPort = 3000) {
        for (let i = 0; i < 200; i++) {
          if (await isPortAvailable(startPort + i)) return startPort + i;
        }
        return 3999;
      }

      function isUrlReachable(url, timeout = 2000) {
        return new Promise((resolve) => {
          const urlObj = new URL(url);
          const isHttps = urlObj.protocol === "https:";
          const requestModule = isHttps ? https : http;

          const req = requestModule.request({
            hostname: urlObj.hostname,
            port: urlObj.port || (isHttps ? 443 : 80),
            path: urlObj.pathname || "/",
            method: "HEAD",
            timeout,
            rejectUnauthorized: false,
          }, (res) => resolve(res.statusCode < 500));

          req.on("error", () => resolve(false));
          req.on("timeout", () => { req.destroy(); resolve(false); });
          req.end();
        });
      }

      const projectName = path.basename(__dirname);
      let proxyUrl;

      if (process.env.LOCAL_URL) {
        proxyUrl = process.env.LOCAL_URL;
        console.log(`  Using: ${proxyUrl}\n`);
      } else {
        const urlsToTest = [
          `http://${projectName}.test`,
          `https://${projectName}.test`,
          `http://localhost/${projectName}`,
          `https://localhost/${projectName}`,
        ];

        console.log("  Detecting local server...");
        let foundUrl = null;
        for (const url of urlsToTest) {
          if (await isUrlReachable(url)) { foundUrl = url; break; }
        }

        if (foundUrl) {
          proxyUrl = foundUrl;
          console.log(`  Found: ${proxyUrl}\n`);
        } else {
          console.log(`\n  No local server detected!`);
          console.log(`  Start Laragon/XAMPP/WAMP, then run: npm run dev`);
          console.log(`  Or set manually: LOCAL_URL=http://your-url npm run dev\n`);
          process.exit(1);
        }
      }

      const bsPort = await findAvailablePort(3000);

      browserSync.init({
        proxy: { target: proxyUrl, ws: true },
        port: bsPort,
        ui: { port: bsPort + 1 },
        startPath: "/",
        files: [
          "assets/css/min/**/*.css",
          "assets/js/min/**/*.js",
          "assets/css/edit.css",
          "assets/js/edit.js",
          "*.php",
          "src/**/*.php",
          "includes/**/*.php",
          "data/**/*.php",
        ],
        watchOptions: {
          usePolling: true,
          interval: 300,
          awaitWriteFinish: { stabilityThreshold: 150, pollInterval: 100 },
        },
        notify: false,
        open: true,
        reloadOnRestart: true,
        injectChanges: false,
        reloadDelay: 100,
        logLevel: "info",
        logConnections: false,
        logFileChanges: true,
      });

      console.log(`${"=".repeat(50)}`);
      console.log(`  BrowserSync: http://localhost:${bsPort}/`);
      console.log(`  Proxy: ${proxyUrl}`);
      console.log(`${"=".repeat(50)}\n`);
    }
  });
} else {
  // One-time build
  console.log(`Building (${isDev ? "dev" : "production"})...\n`);

  buildAll().then(async ({ successCount, failCount }) => {
    console.log(`\n${"=".repeat(50)}`);
    if (failCount === 0) {
      console.log(`  ${successCount} file(s) compiled successfully`);
    } else {
      console.log(`  ${successCount} compiled, ${failCount} failed`);
      process.exit(1);
    }
    console.log(`${"=".repeat(50)}`);

    if (packageMode) {
      try {
        await createPackage();
      } catch (error) {
        console.error(`  Package failed: ${error.message}`);
        process.exit(1);
      }
    }
  });
}
