<?php

require_once __DIR__ . '/includes/functions.php';

header(
    'Location: ' .
    url(logged_in() ? 'dashboard.php' : 'login.php')
);

exit;