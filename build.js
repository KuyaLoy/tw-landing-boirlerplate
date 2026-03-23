const fs = require("fs");
const path = require("path");
const postcss = require("postcss");
const tailwindcss = require("tailwindcss");
const autoprefixer = require("autoprefixer");
const postcssNested = require("postcss-nested");
const cssnano = require("cssnano");
const esbuild = require("esbuild");
const chokidar = require("chokidar");
const browserSync = require("browser-sync").create();
const net = require("net");
const archiver = require("archiver");

// Paths - Clean structure: dev/ for editable source, compiled/min/ for minified output
const srcDir = path.join(__dirname, "assets", "css", "dev");
const cssMinDir = path.join(__dirname, "assets", "css", "compiled", "min");

// JS Paths - dev/ for source, compiled/min/ for minified output (mirrors CSS structure)
const jsSrcDir = path.join(__dirname, "assets", "js", "dev");
const jsMinDir = path.join(__dirname, "assets", "js", "compiled", "min");

// JS files to compile (add more as needed)
const jsFiles = ["script.js"];

// Ensure directories exist
[srcDir, cssMinDir, jsSrcDir, jsMinDir].forEach((dir) => {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
});

// Environment detection
function isDevelopment() {
  if (process.env.NODE_ENV === "production") return false;
  if (process.env.NODE_ENV === "development") return true;
  if (process.argv.includes("--dev")) return true;
  if (process.argv.includes("--prod")) return false;
  if (process.argv.includes("--watch")) return true;
  return false;
}

const isDev = isDevelopment();

// Main CSS file that imports everything
const mainFile = "style.css";

// Import order for main file
const importOrder = [
  "reset.css",
  "fonts.css",
  "critical.css",
  "responsive.css",
  "custom.css",
];

// Separate files that compile independently
// critical.css gets Tailwind support (for @apply), responsive.css doesn't
const separateFiles = ["critical.css", "responsive.css"];

// Files that need Tailwind support (for @apply directives)
const filesWithTailwind = ["critical.css"];

// Files to compile separately for inline critical CSS (reset, fonts)
const criticalFiles = ["reset.css", "fonts.css"];

// Build main CSS by concatenating imports
function buildMainCSS() {
  const stylePath = path.join(srcDir, mainFile);
  let mainContent = fs.readFileSync(stylePath, "utf8");

  // Replace @import statements with actual file contents
  importOrder.forEach((importFile) => {
    const importPath = path.join(srcDir, importFile);
    if (fs.existsSync(importPath)) {
      const importContent = fs.readFileSync(importPath, "utf8");
      const importPattern = new RegExp(
        `@import\\s+["']\\./${importFile.replace(".", "\\.")}["'];?\\s*`,
        "g"
      );
      mainContent = mainContent.replace(
        importPattern,
        `\n/* ========== ${importFile
          .toUpperCase()
          .replace(".CSS", "")} ========== */\n${importContent}\n`
      );
    }
  });

  return mainContent;
}

async function compileFile(file, isMain = false) {
  const sourcePath = path.join(srcDir, file);
  // Only generate minified files (we always use minified)
  const cssMinPath = path.join(cssMinDir, file.replace(".css", ".min.css"));

  if (!fs.existsSync(sourcePath)) {
    return false;
  }

  try {
    // Get CSS content (build main file with imports, or read directly)
    let cssContent;
    if (isMain) {
      cssContent = buildMainCSS();
    } else {
      cssContent = fs.readFileSync(sourcePath, "utf8");
    }

    // Build PostCSS plugins array - ORDER MATTERS!
    const postcssPlugins = [];

    // Step 1: Nesting support
    postcssPlugins.push(postcssNested());

    // Step 2: Tailwind (for main file and critical.css which uses @apply)
    if (isMain || file === "critical.css") {
      const tailwindConfig = require(path.join(
        __dirname,
        "tailwind.config.js"
      ));
      postcssPlugins.push(tailwindcss(tailwindConfig));
    }

    // Step 3: Autoprefixer
    postcssPlugins.push(autoprefixer());

    // Step 4: Always minify (we only use minified files)
    postcssPlugins.push(cssnano({ preset: "default" }));

    // Process CSS with PostCSS (directly to minified)
    const result = await postcss(postcssPlugins).process(cssContent, {
      from: sourcePath,
      to: cssMinPath,
      map: isDev ? { inline: false } : false,
    });

    // Write minified CSS file
    fs.writeFileSync(cssMinPath, result.css);

    // Generate source map for debugging in development
    if (result.map && isDev) {
      fs.writeFileSync(cssMinPath + ".map", result.map.toString());
    }

    const mode = isDev ? "development" : "production";
    const fileSize = (fs.statSync(cssMinPath).size / 1024).toFixed(2);
    console.log(
      `✓ Compiled ${file} → ${path.basename(
        cssMinPath
      )} (${mode} mode) - ${fileSize} KB`
    );
    return true;
  } catch (error) {
    console.error(`✗ Error compiling ${file}:`);
    console.error(`  ${error.message}`);
    if (error.formatted) console.error(`  ${error.formatted}`);
    if (error.stack) {
      const stackLines = error.stack.split("\n").slice(0, 5);
      stackLines.forEach((line) => console.error(`  ${line}`));
    }
    return false;
  }
}

// ========== JAVASCRIPT COMPILATION ==========

async function compileJS(file) {
  const sourcePath = path.join(jsSrcDir, file);
  const outputPath = path.join(jsMinDir, file.replace(".js", ".min.js"));

  if (!fs.existsSync(sourcePath)) {
    console.error(`✗ JS file not found: ${file}`);
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

    const mode = isDev ? "development" : "production";
    const fileSize = (fs.statSync(outputPath).size / 1024).toFixed(2);
    console.log(
      `✓ Compiled ${file} → ${path.basename(
        outputPath
      )} (${mode} mode) - ${fileSize} KB`
    );
    return true;
  } catch (error) {
    console.error(`✗ Error compiling ${file}:`);
    console.error(`  ${error.message}`);
    return false;
  }
}

async function buildAllJS() {
  const results = await Promise.all(
    jsFiles.map(async (file) => ({
      file,
      success: await compileJS(file),
    }))
  );

  const successCount = results.filter((r) => r.success).length;
  const failCount = results.filter((r) => !r.success).length;

  return { successCount, failCount };
}

// ========== BUILD ALL ==========

async function buildAll() {
  console.log("── CSS ──");
  // Build main file (with Tailwind and imports)
  const mainResult = await compileFile(mainFile, true);

  // Build separate files (PostCSS only, no Tailwind)
  const separateResults = await Promise.all(
    separateFiles.map(async (file) => ({
      file,
      success: await compileFile(file, false),
    }))
  );

  // Build critical files for inline CSS (reset, fonts)
  const criticalResults = await Promise.all(
    criticalFiles.map(async (file) => ({
      file,
      success: await compileFile(file, false),
    }))
  );

  const cssResults = [
    { file: mainFile, success: mainResult },
    ...separateResults,
    ...criticalResults,
  ];

  console.log("\n── JS ──");
  // Build JavaScript files
  const jsResults = await buildAllJS();

  const cssSuccessCount = cssResults.filter((r) => r.success).length;
  const cssFailCount = cssResults.filter((r) => !r.success).length;

  return {
    successCount: cssSuccessCount + jsResults.successCount,
    failCount: cssFailCount + jsResults.failCount,
  };
}

// ========== PACKAGING FUNCTIONS ==========

// Files and folders to exclude from build package
const excludePatterns = [
  /^\.git$/,
  /^node_modules$/,
  /^build$/,
  /^\.DS_Store$/,
  /^Thumbs\.db$/,
  /^desktop\.ini$/,
  /^\.env$/,
  /^\.env\./,
  /^\.vscode$/,
  /^\.idea$/,
  /\.log$/,
  /\.tmp$/,
  /\.swp$/,
  /\.swo$/,
  /~$/,
  /\.cache$/,
  /\.css\.map$/,
  /\.js\.map$/,
];

// Check if a path should be excluded
function shouldExclude(filePath, relativePath) {
  const fileName = path.basename(filePath);
  const relativeParts = relativePath.split(path.sep);

  for (const pattern of excludePatterns) {
    if (pattern.test(fileName) || pattern.test(relativePath)) {
      return true;
    }
    for (const part of relativeParts) {
      if (pattern.test(part)) {
        return true;
      }
    }
  }
  return false;
}

// Create build package (zip file)
function createPackage() {
  return new Promise((resolve, reject) => {
    const projectName = path.basename(__dirname);
    const buildDir = path.join(__dirname, "build");

    if (!fs.existsSync(buildDir)) {
      fs.mkdirSync(buildDir, { recursive: true });
    }

    const now = new Date();
    const timestamp = now
      .toISOString()
      .replace(/[-:]/g, "")
      .replace(/T/, "-")
      .split(".")[0];

    const zipFileName = `${projectName}-${timestamp}.zip`;
    const zipFilePath = path.join(buildDir, zipFileName);

    const output = fs.createWriteStream(zipFilePath);
    const archive = archiver("zip", { zlib: { level: 9 } });

    output.on("close", () => {
      const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
      console.log(`\n${"=".repeat(60)}`);
      console.log(`✅ Build package created successfully!`);
      console.log(`   📦 File: ${zipFileName}`);
      console.log(`   📍 Location: ${path.relative(__dirname, zipFilePath)}`);
      console.log(`   📊 Size: ${sizeInMB} MB`);
      console.log(`${"=".repeat(60)}\n`);
      resolve();
    });

    archive.on("error", (err) => {
      console.error(`❌ Error creating zip: ${err.message}`);
      reject(err);
    });

    archive.pipe(output);

    function addFiles(dir, baseDir = __dirname) {
      const files = fs.readdirSync(dir);
      for (const file of files) {
        const filePath = path.join(dir, file);
        const relativePath = path.relative(baseDir, filePath);
        const stat = fs.statSync(filePath);

        if (shouldExclude(filePath, relativePath)) continue;

        if (stat.isDirectory()) {
          addFiles(filePath, baseDir);
        } else {
          const zipPath = relativePath.split(path.sep).join("/");
          archive.file(filePath, { name: zipPath });
        }
      }
    }

    console.log(`\n📦 Creating build package...`);
    console.log(`   Project: ${projectName}`);
    console.log(
      `   Excluding: .git, node_modules, build, .env, source maps, etc.`
    );

    addFiles(__dirname);
    archive.finalize();
  });
}

// Watch mode
const watchMode = process.argv.includes("--watch");
const packageMode = process.argv.includes("--package");

if (watchMode) {
  const mode = isDev ? "development" : "production";
  console.log(`👀 Watching files for changes... (${mode} mode)\n`);
  console.log(
    "Watching: CSS + JS source files + PHP files (for Tailwind classes)\n"
  );
  console.log("Press Ctrl+C to stop.\n");

  // Watch patterns
  const watchPaths = [
    path.join(srcDir, "**", "*.css"),
    path.join(jsSrcDir, "**", "*.js"), // Watch JS dev source files
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

  watcher.on("change", async (filePath) => {
    const relativePath = path.relative(__dirname, filePath);
    console.log(`\n📝 File changed: ${relativePath}`);

    if (filePath.endsWith(".css") && filePath.startsWith(srcDir)) {
      const fileName = path.basename(filePath);

      // If main file or any imported file changes, rebuild main
      if (
        fileName === mainFile ||
        importOrder.includes(fileName) ||
        fileName === "custom.css"
      ) {
        await compileFile(mainFile, true);
      }
      // If separate file changes, rebuild that file
      if (
        separateFiles.includes(fileName) ||
        criticalFiles.includes(fileName)
      ) {
        await compileFile(fileName, false);
      }
      // BrowserSync auto-reloads when compiled CSS changes
    } else if (
      filePath.endsWith(".js") &&
      filePath.includes(path.join("assets", "js", "dev"))
    ) {
      // JS file changed - recompile it
      const fileName = path.basename(filePath);
      if (jsFiles.includes(fileName)) {
        await compileJS(fileName);
      }
      // BrowserSync auto-reloads when compiled JS changes
    } else if (filePath.endsWith(".php")) {
      // PHP file changed - rebuild main file to rescan Tailwind classes
      console.log("Rebuilding CSS to rescan Tailwind classes...");
      await compileFile(mainFile, true);
      // BrowserSync auto-reloads when PHP changes
    }
  });

  // Initial build
  console.log("Running initial build...\n");
  buildAll().then(async ({ successCount, failCount }) => {
    console.log(`\n${"=".repeat(50)}`);
    if (failCount === 0) {
      console.log(`✅ Initial build complete! (${mode} mode)`);
      console.log(`   ✓ ${successCount} file(s) compiled successfully`);
    } else {
      console.log(`⚠️  Build completed with errors (${mode} mode)`);
      console.log(`   ✓ ${successCount} file(s) compiled`);
      console.log(`   ✗ ${failCount} file(s) failed`);
    }
    console.log(`${"=".repeat(50)}\n`);
    console.log("✅ Watching for changes...\n");

    // Start BrowserSync for auto-refresh (only in dev mode)
    if (isDev) {
      const http = require("http");
      const https = require("https");

      // Function to check if port is available
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

      // Function to find available port starting from 3000
      async function findAvailablePort(startPort = 3000, maxAttempts = 200) {
        for (let i = 0; i < maxAttempts; i++) {
          const port = startPort + i;
          const available = await isPortAvailable(port);
          if (available) {
            return port;
          }
        }
        // If all ports taken, return a high port
        return 3999;
      }

      // Function to check if URL is reachable (supports both HTTP and HTTPS)
      function isUrlReachable(url, timeout = 2000) {
        return new Promise((resolve) => {
          const urlObj = new URL(url);
          const isHttps = urlObj.protocol === "https:";
          const requestModule = isHttps ? https : http;
          const defaultPort = isHttps ? 443 : 80;

          const options = {
            hostname: urlObj.hostname,
            port: urlObj.port || defaultPort,
            path: urlObj.pathname || "/",
            method: "HEAD",
            timeout: timeout,
            rejectUnauthorized: false, // Allow self-signed certs for local dev
          };

          const req = requestModule.request(options, (res) => {
            resolve(res.statusCode < 500);
          });
          req.on("error", () => resolve(false));
          req.on("timeout", () => {
            req.destroy();
            resolve(false);
          });
          req.end();
        });
      }

      // Auto-detect project name from directory
      const projectName = path.basename(__dirname);

      // Detect local server URL
      let proxyUrl;

      if (process.env.LOCAL_URL) {
        // Custom URL provided via environment variable
        proxyUrl = process.env.LOCAL_URL;
        console.log(`   Using custom URL: ${proxyUrl}\n`);
      } else {
        // Auto-detect: Test URLs in order of preference
        const urlsToTest = [
          `http://${projectName}.test`,
          `https://${projectName}.test`,
          `http://localhost/${projectName}`,
          `https://localhost/${projectName}`,
        ];

        console.log(`   🔍 Auto-detecting local server...`);

        let foundUrl = null;
        for (const url of urlsToTest) {
          console.log(`      Testing: ${url}`);
          const works = await isUrlReachable(url);
          if (works) {
            foundUrl = url;
            console.log(`      ✓ Found!`);
            break;
          }
        }

        if (foundUrl) {
          proxyUrl = foundUrl;
        } else {
          // No server responding - show clear error
          console.log(`\n${"=".repeat(60)}`);
          console.log(`❌ ERROR: No local server detected!`);
          console.log(`${"=".repeat(60)}`);
          console.log(`\n   Your PHP server is not running. Please start:`);
          console.log(`   • Laragon (recommended - gives ${projectName}.test)`);
          console.log(`   • XAMPP/WAMP (gives localhost/${projectName})`);
          console.log(`\n   After starting your server, run: npm run dev\n`);
        console.log(
            `   Or specify URL manually: LOCAL_URL=http://your-url npm run dev\n`
        );
          process.exit(1);
        }
        console.log(`\n   📍 Proxy: ${proxyUrl}\n`);
      }

      console.log(`🌐 Starting BrowserSync...`);

      // Find available port (start from 3000, try next if taken)
      const bsPort = await findAvailablePort(3000);
      const uiPort = bsPort + 1;

      if (bsPort !== 3000) {
        console.log(`   ⚠️  Port 3000 in use, using ${bsPort}`);
      }

      browserSync.init({
        proxy: {
          target: proxyUrl,
          ws: true,
        },
        port: bsPort,
        ui: {
          port: uiPort,
        },
        startPath: "/", // Always clean localhost:3000/
        // Files to watch for auto-reload
        files: [
          // Compiled assets (triggers reload after CSS/JS compilation)
          "assets/css/compiled/min/**/*.css",
          "assets/js/compiled/min/**/*.js",
          // Edit overrides (instant reload)
          "assets/css/edit.css",
          "assets/js/edit.js",
          // PHP files (all common locations)
          "*.php",
          "src/**/*.php",
          "includes/**/*.php",
          "data/**/*.php",
          "pages/**/*.php",
          "templates/**/*.php",
        ],
        watchOptions: {
          usePolling: true, // Required for Windows reliability
          interval: 300, // Balanced polling for reliable detection
          awaitWriteFinish: {
            stabilityThreshold: 150,
            pollInterval: 100,
          },
        },
        notify: false,
        open: true,
        reloadOnRestart: true,
        injectChanges: false, // Force full reload for PHP changes
        reloadDelay: 100, // Small delay to ensure file is fully saved
        https: false,
        logLevel: "info", // Show reload events for debugging
        logConnections: false,
        logFileChanges: true, // Show which files trigger reload
      });

      const bsUrl = `http://localhost:${bsPort}/`;
      console.log(`\n${"=".repeat(50)}`);
      console.log(`✅ BrowserSync ready!`);
      console.log(`${"=".repeat(50)}`);
      console.log(`   🌐 URL: ${bsUrl}`);
      console.log(`   🔄 Auto-reload: ON (save any file to refresh)`);
      console.log(`   📁 Watching: CSS, JS, PHP files`);
      console.log(`${"=".repeat(50)}\n`);
    }
  });
} else {
  // One-time build
  const mode = isDev ? "development" : "production";
  console.log(`Building in ${mode} mode...\n`);

  buildAll().then(async ({ successCount, failCount }) => {
    console.log(`\n${"=".repeat(50)}`);
    if (failCount === 0) {
      console.log(`✅ Build complete! (${mode} mode)`);
      console.log(`   ✓ ${successCount} file(s) compiled successfully`);
    } else {
      console.log(`⚠️  Build completed with errors (${mode} mode)`);
      console.log(`   ✓ ${successCount} file(s) compiled`);
      console.log(`   ✗ ${failCount} file(s) failed`);
      process.exit(1);
    }
    console.log(`${"=".repeat(50)}`);

    // Create package if --package flag is set
    if (packageMode) {
      try {
        await createPackage();
      } catch (error) {
        console.error(`❌ Failed to create package: ${error.message}`);
        process.exit(1);
      }
    }
  });
}
