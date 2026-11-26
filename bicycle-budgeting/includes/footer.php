</main>
<?php if (isset($currentPage) && in_array($currentPage, array('login.php', 'register.php', 'logout.php'))): ?>
<?php elseif (logged_in()): ?>
</div>
</div>
<?php else: ?>
</div>
<?php endif; ?>
</body>
</html>