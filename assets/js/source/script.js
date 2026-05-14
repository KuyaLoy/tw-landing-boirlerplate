// AOS scroll animations — only init if the library was loaded (opt-in via $useAos in config.php)
window.addEventListener("load", () => {
  if (typeof AOS !== "undefined") {
    AOS.init({ disable: "mobile" });
  }
});

document.querySelectorAll('a[href="#"]').forEach((link) => {
  link.addEventListener("click", (e) => e.preventDefault());
});

// Smooth scroll for anchor links without updating the URL hash
// Select all anchor links that start with # but are not just '#'
document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach((link) => {
  link.addEventListener("click", function (e) {
    const targetId = this.getAttribute("href").slice(1);
    const target = document.getElementById(targetId);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth" });
      // Do not update the URL hash
      // Optionally, you can manage focus for accessibility:
      target.setAttribute("tabindex", "-1");
      target.focus({ preventScroll: true });
    }
  });
});

