
</main>
<?php if (isset($currentPage) && in_array($currentPage, array('login.php', 'register.php', 'logout.php'))): ?>
<?php elseif (logged_in()): ?>
</div>
</div>
<?php else: ?>
</div>
<?php endif; ?>
<?php if (logged_in()): ?>
<script src="<?= h(url('assets/js/mobile-menu.js')) ?>?v=<?= filemtime(__DIR__ . '/../assets/js/mobile-menu.js') ?>"></script>
<?php endif; ?>
</body>
</html>
