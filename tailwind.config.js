/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./thankyou.php",
    "./src/**/*.php",
    "./includes/**/*.php",
    "./data/**/*.php",
    "./assets/js/**/*.js",
  ],
  theme: {
    // Container is disabled - using custom CSS containers in critical.css
    // Variants: .container-sm, .container-md, .container-lg, .container (default), .container-2xl, .container-max, .container-full
    extend: {
      // Custom color variables - allows using bg-primary, text-primary, etc. in HTML/PHP
      // The actual color values are defined in CSS (critical.css :root)
      colors: {
        primary: "var(--color-primary)",
        secondary: "var(--color-secondary)",
        text: "var(--color-text)",
        accent: "var(--color-accent)",
      },
      // Custom font family - allows using font-articulat utility class
      fontFamily: {
        articulat: [
          '"Articulat"',
          "-apple-system",
          "BlinkMacSystemFont",
          '"Segoe UI"',
          "Roboto",
          '"Helvetica Neue"',
          "Arial",
          "sans-serif",
        ],
      },
    },
  },
  plugins: [],
  corePlugins: {
    container: false, // Using custom CSS containers in critical.css
  },
  // Optimize for production
  future: {
    hoverOnlyWhenSupported: true,
  },
};
