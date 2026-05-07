<?php
/**
 * FlyerSaaS - Logout
 */
require_once 'config.php';

$auth = new Auth();
$auth->logout();

redirect(BASE_URL . '/login.php');
