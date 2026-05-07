<?php
/**
 * FlyerSaaS - Página Inicial (Redireciona para login ou dashboard)
 * 
 * Este arquivo verifica automaticamente:
 * 1. Se o sistema está instalado (.env existe com INSTALLED=true)
 * 2. Se todas as tabelas do banco de dados existem
 * 
 * Se qualquer uma das verificações falhar, redireciona para install.php
 */

// Config já faz a verificação automática e redireciona se necessário
require_once 'config.php';

// Se chegou aqui, sistema está instalado e pronto
// Verificar sessão e redirecionar

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        redirect(BASE_URL . '/admin/index.php');
    } else {
        redirect(BASE_URL . '/user/dashboard.php');
    }
}

// Usuário não logado, mostrar página de login
redirect(BASE_URL . '/login.php');
