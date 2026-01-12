<!-- footer template -->
    <main class="container">
        <footer class="footer">© 2025 JohnQDeveloper</footer>
    </main>

    <script>
      document.querySelector('.hamburger').addEventListener('click', function() {
        document.querySelector('.nav-menu').classList.toggle('active');
      });

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
