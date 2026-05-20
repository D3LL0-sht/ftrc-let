</div><!-- end main-content -->

<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand">
      🎓 <strong>FTRC LET Review</strong>
    </div>
    <div class="footer-text">
      Falculan Twins Review Center &copy; <?= date('Y') ?> — LET English Specialization
    </div>
    <div class="footer-links">
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/questions.php">Questions</a>
        <a href="/admin/students.php">Students</a>
      <?php else: ?>
        <a href="/pages/dashboard.php">Dashboard</a>
        <a href="/pages/quiz.php?mode=mock">Mock Exam</a>
        <a href="/pages/analytics.php">My Progress</a>
      <?php endif; ?>
      <a href="/pages/logout.php">Logout</a>
    </div>
  </div>
</footer>

<script>
function toggleNav() {
  document.getElementById('nav-links').classList.toggle('open');
}
</script>

</body>
</html>