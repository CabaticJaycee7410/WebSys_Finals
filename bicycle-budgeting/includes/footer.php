
</main>
<?php if (isset($currentPage) && in_array($currentPage, array('login.php', 'register.php', 'logout.php'))): ?>
<?php elseif (logged_in()): ?>
<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-brand">
            <div class="footer-brand-name">
                <svg class="brand-logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-label="Bicycle logo" role="img">
                    <g fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="15" cy="43" r="11"/>
                        <circle cx="49" cy="43" r="11"/>
                        <path d="M15 43L27 22L38 43H15L32 32L49 43M25 22H33M27 22L38 43M43 20H49L52 25M23 18H30"/>
                    </g>
                    <circle cx="27" cy="22" r="3" fill="#08b9d0"/>
                </svg>
                <strong><?= h(APP_NAME) ?></strong>
            </div>
            <p>Plan your bicycle build. Compare components. Manage your budget.</p>
        </div>
        <div class="footer-copyright">&copy; <?= date('Y') ?> Bicycle Budgeting System</div>
    </div>
</footer>
</div>
</div>
<?php else: ?>
<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-brand">
            <div class="footer-brand-name">
                <svg class="brand-logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-label="Bicycle logo" role="img">
                    <g fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="15" cy="43" r="11"/>
                        <circle cx="49" cy="43" r="11"/>
                        <path d="M15 43L27 22L38 43H15L32 32L49 43M25 22H33M27 22L38 43M43 20H49L52 25M23 18H30"/>
                    </g>
                    <circle cx="27" cy="22" r="3" fill="#08b9d0"/>
                </svg>
                <strong><?= h(APP_NAME) ?></strong>
            </div>
            <p>Plan your bicycle build. Compare components. Manage your budget.</p>
        </div>
        <div class="footer-copyright">&copy; <?= date('Y') ?> Bicycle Budgeting System</div>
    </div>
</footer>
</div>
<?php endif; ?>
</body>
</html>
