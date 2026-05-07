<?php
/**
 * FlyerSaaS - Página Inicial (Redireciona para login ou dashboard)
 */
require_once 'config.php';

// Verificar se está instalado
if (!isInstalled()) {
    redirect(BASE_URL . '/install.php');
}

// Se estiver logado, redirecionar para dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        redirect(BASE_URL . '/admin/index.php');
    } else {
        redirect(BASE_URL . '/user/dashboard.php');
    }
}

// Redirecionar para login
redirect(BASE_URL . '/login.php');
