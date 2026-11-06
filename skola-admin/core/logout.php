<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_logout();
admin_redirect('../index.php');