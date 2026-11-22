<?php
require_once __DIR__ . '/../src/web.php';
requirePost();
$_SESSION = [];
$options = session_get_cookie_params();
setcookie(session_name(), '', time() - 3600, $options['path'], $options['domain'], $options['secure'], true);
session_destroy();
redirectTo('index.php');
