<!-- footer template -->
    <main class="container">
        <footer class="footer"><?php echo t('footer.copyright'); ?></footer>
    </main>

    <!-- Analytics Script -->
    <script defer src="https://analytics.johnqdeveloper.com/script.js" data-website-id="27f59e5d-bcba-4a9e-91dc-94d1925661c6"></script>

    <script>
      const hamburger = document.querySelector('.hamburger');
      const navMenu = document.querySelector('.nav-menu');
      if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
          navMenu.classList.toggle('active');
        });
      }

      // Handle dropdown clicks on mobile
      document.querySelectorAll('.dropdown > a').forEach(function(dropdownLink) {
        dropdownLink.addEventListener('click', function(e) {
          // Only apply on mobile (when hamburger is visible)
          if (window.innerWidth <= 768) {
            e.preventDefault();
            this.parentElement.classList.toggle('active');
          }
        });
      });
    </script>
  </body>

</html>
