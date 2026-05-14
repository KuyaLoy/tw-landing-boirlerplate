  <!-- ===== reCAPTCHA v3 loader & refresher =====
       Skipped on thankyou.php (the form is already submitted, no token needed).
       Set $isThankYou = true before including this partial to skip. -->
  <?php if (empty($isThankYou)): ?>
  <script>
      (function() {
          let recaptchaLoaded = false;
          let recaptchaInterval = null;

          function requestRecaptchaToken() {
              if (typeof grecaptcha === 'undefined' || !recaptchaLoaded) return;

              grecaptcha.execute('<?= $recaptcha_client_secret ?>', {
                      action: 'submit'
                  })
                  .then(function(token) {
                      document.querySelectorAll('.recaptchaResponse')
                          .forEach(function(el) {
                              el.value = token;
                          });
                  })
                  .catch(function(error) {
                      console.warn('reCAPTCHA token request failed:', error);
                  });
          }

          function loadRecaptcha() {
              if (recaptchaLoaded) return;

              var s = document.createElement('script');
              s.src = "https://www.google.com/recaptcha/api.js?render=<?= $recaptcha_client_secret ?>";
              s.async = true;
              s.defer = true;
              s.onload = function() {
                  if (window.grecaptcha) {
                      grecaptcha.ready(function() {
                          recaptchaLoaded = true;
                          requestRecaptchaToken(); // initial token
                          recaptchaInterval = setInterval(requestRecaptchaToken, 60000); // refresh every 60s
                      });
                  } else {
                      console.warn("grecaptcha failed to load");
                  }
              };
              s.onerror = function() {
                  console.warn("Failed to load reCAPTCHA script");
              };
              document.head.appendChild(s);
          }

          // Load reCAPTCHA after page is fully loaded and user has interacted
          function initRecaptcha() {
              // Load immediately if user has already scrolled or interacted
              if (window.scrollY > 0 || document.hasFocus()) {
                  loadRecaptcha();
              } else {
                  // Wait for first user interaction or 5 seconds, whichever comes first
                  let timeout = setTimeout(loadRecaptcha, 5000);

                  ['scroll', 'click', 'touchstart', 'keydown'].forEach(event => {
                      document.addEventListener(event, function() {
                          clearTimeout(timeout);
                          loadRecaptcha();
                      }, {
                          once: true,
                          passive: true
                      });
                  });
              }
          }

          // Initialize when DOM is ready
          if (document.readyState === 'loading') {
              document.addEventListener('DOMContentLoaded', initRecaptcha);
          } else {
              initRecaptcha();
          }

          // Cleanup on page unload
          window.addEventListener('beforeunload', function() {
              if (recaptchaInterval) {
                  clearInterval(recaptchaInterval);
              }
          });
      })();
  </script>

  <!-- Resource Hints for External Resources -->
  <link rel="dns-prefetch" href="//www.google.com">
  <link rel="preconnect" href="//www.google.com" crossorigin>
  <?php endif; // /reCAPTCHA loader (skipped on thankyou) ?>

  <!-- JS Scripts - Load in order of priority -->
  <?php if (!empty($useSwiper)): ?>
    <script src="<?= $basePath ?>assets/js/vendor/swiper-bundle.min.js?v=<?= $jscssversion; ?>" defer></script>
  <?php endif; ?>
  <?php if (!empty($useAos)): ?>
    <script src="<?= $basePath ?>assets/js/vendor/aos.js?v=<?= $jscssversion; ?>" defer></script>
  <?php endif; ?>
  <script src="<?= $basePath ?>assets/js/min/script.min.js?v=<?= $jscssversion; ?>" defer></script>

  <!-- Live-edit JS loads LAST to override all compiled scripts (for quick edits without rebuild) -->
  <?php $editJsFile = $projectDir . '/assets/js/live-edit.js'; ?>
  <?php if (file_exists($editJsFile) && filesize($editJsFile) > 0): ?>
      <script src="<?= $basePath ?>assets/js/live-edit.js?v=<?= $jscssversion; ?>" defer></script>
  <?php endif; ?>

  </body>

  </html>