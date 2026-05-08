<?php
/**
 * FlyerSaaS - Página Inicial
 * Redireciona para dashboard ou login conforme autenticação
 */

require_once __DIR__ . '/config.php';

// Se já estiver logado, redireciona para dashboard apropriado
if (Auth::check()) {
    $user = Auth::user();
    if ($user['is_admin']) {
        header('Location: admin/index.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

// Redireciona para login
header('Location: login.php');
exit;
