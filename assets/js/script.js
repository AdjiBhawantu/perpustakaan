document.addEventListener("DOMContentLoaded", () => {
  // Mobile menu toggle
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");
  const mobileMenu = document.getElementById("mobileMenu");

  if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener("click", () => {
      mobileMenuBtn.classList.toggle("active");
      mobileMenu.classList.toggle("active");
    });

    // Close mobile menu when clicking on a link
    document.querySelectorAll(".mobile-nav-links a").forEach((link) => {
      link.addEventListener("click", () => {
        mobileMenuBtn.classList.remove("active");
        mobileMenu.classList.remove("active");
      });
    });
  }

  // Navbar scroll effect
  const navbar = document.getElementById("navbar");
  let lastScroll = 0;

  window.addEventListener("scroll", () => {
    const currentScroll = window.pageYOffset;

    if (currentScroll > 50) {
      navbar.classList.add("scrolled");
    } else {
      navbar.classList.remove("scrolled");
    }

    lastScroll = currentScroll;
  });

  // Active nav link on click
  document.querySelectorAll(".nav-links a").forEach((link) => {
    link.addEventListener("click", function (e) {
      // Remove active from all
      document.querySelectorAll(".nav-links a").forEach((l) => l.classList.remove("active"));
      // Add to clicked
      this.classList.add("active");
    });
  });

  // Borrow button interaction (Untuk List Popular)
  document.querySelectorAll(".borrow-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      // Animasi tombol saat diklik
      const originalText = this.textContent;
      this.textContent = "✓ Ditambahkan";
      this.style.background = "linear-gradient(135deg, #48bb78 0%, #38a169 100%)";

      setTimeout(() => {
        this.textContent = originalText;
        this.style.background = "linear-gradient(135deg, #63b3ed 0%, #4299e1 100%)";
      }, 2000);
    });
  });

  // Borrow button interaction (Untuk Grid Catalog)
  document.querySelectorAll(".btn-card-action").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const originalText = this.textContent;
      this.textContent = "✓";
      this.style.background = "#48bb78";
      this.style.color = "white";

      setTimeout(() => {
        this.textContent = originalText;
        this.style.background = "#ebf8ff";
        this.style.color = "#3182ce";
      }, 2000);
    });
  });

  // Book item click animation (Leaderboard)
  document.querySelectorAll(".book-item").forEach((item) => {
    item.addEventListener("click", function (e) {
      if (!e.target.classList.contains("borrow-btn")) {
        this.style.transform = "scale(0.98)";
        setTimeout(() => {
          this.style.transform = "";
        }, 200);
      }
    });
  });
});
