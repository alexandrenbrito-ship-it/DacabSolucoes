<?php
/**
 * Configuração Principal do Sistema
 * Este arquivo é carregado em todas as páginas
 */

// Define caminho base
define('BASE_PATH', dirname(__DIR__));

// Carrega funções auxiliares primeiro
require_once BASE_PATH . '/includes/functions.php';

// Verifica se o sistema está instalado (exceto na própria página de instalação)
$currentScript = basename($_SERVER['SCRIPT_NAME']);
if ($currentScript !== 'install.php' && !isSystemInstalled()) {
    header('Location: install.php');
    exit;
}

// Carrega classes principais
require_once BASE_PATH . '/includes/Database.php';
require_once BASE_PATH . '/includes/Auth.php';

// Inicia sessão globalmente
Auth::startSession();

// Define fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Define encoding padrão
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
