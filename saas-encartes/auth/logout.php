<?php
/**
 * EncartePro - Logout de Usuário
 */

require_once __DIR__ . '/../includes/config.php';

// Destrói todas as variáveis de sessão
$_SESSION = [];

// Destrói o cookie da sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destrói o cookie remember me se existir
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destrói a sessão
session_destroy();

// Redireciona para a página inicial
redirect(SITE_URL . '/');
