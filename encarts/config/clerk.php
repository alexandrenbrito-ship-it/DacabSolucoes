<?php
/**
 * /config/clerk.php
 * Configuração do Clerk para autenticação
 * Lê as chaves de API do arquivo .env ou variáveis de ambiente
 */

// Carregar variáveis do .env se ainda não foram carregadas
if (!getenv('CLERK_PUBLISHABLE_KEY')) {
    $envPath = dirname(__DIR__) . '/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }
    }
}

// Constantes do Clerk
define('CLERK_PUBLISHABLE_KEY', getenv('CLERK_PUBLISHABLE_KEY') ?: '');
define('CLERK_SECRET_KEY', getenv('CLERK_SECRET_KEY') ?: '');
define('CLERK_WEBHOOK_SECRET', getenv('CLERK_WEBHOOK_SECRET') ?: '');
define('CLERK_JWKS_URL', getenv('CLERK_JWKS_URL') ?: 'https://api.clerk.com/v1/jwks');
define('CLERK_ISSUER', getenv('CLERK_ISSUER') ?: 'https://trusted-primate-39.clerk.accounts.dev');

// Cache path para JWKS
define('CLERK_JWKS_CACHE_PATH', sys_get_temp_dir() . '/clerk_jwks_cache.json');
define('CLERK_JWKS_CACHE_TTL', 3600); // 1 hora em segundos
