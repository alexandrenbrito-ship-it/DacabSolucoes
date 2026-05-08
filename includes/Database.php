<?php
/**
 * Classe de Conexão com Banco de Dados
 * Gerencia conexões PDO com tratamento de erros e criação automática de DB
 */
class Database {
    private static $instance = null;
    private $conn;
    private $dbConfig;

    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    private function loadConfig() {
        $envFile = __DIR__ . '/../.env.php';
        if (file_exists($envFile)) {
            $this->dbConfig = include $envFile;
        } else {
            // Tenta carregar variáveis de ambiente se .env.php não existir
            $this->dbConfig = [
                'DB_HOST' => getenv('DB_HOST') ?: 'localhost',
                'DB_NAME' => getenv('DB_NAME') ?: '',
                'DB_USER' => getenv('DB_USER') ?: '',
                'DB_PASS' => getenv('DB_PASS') ?: '',
                'DB_CHARSET' => getenv('DB_CHARSET') ?: 'utf8mb4'
            ];
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        if (empty($this->dbConfig['DB_NAME'])) {
            throw new Exception("Nome do banco de dados não configurado.");
        }

        $host = $this->dbConfig['DB_HOST'];
        $dbname = $this->dbConfig['DB_NAME'];
        $username = $this->dbConfig['DB_USER'];
        $password = $this->dbConfig['DB_PASS'] ?? '';
        $charset = $this->dbConfig['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host=$host;charset=$charset";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // 1. Tenta conectar SEM selecionar o banco (para criar se necessário)
            $pdoTemp = new PDO($dsn, $username, $password, $options);
            
            // 2. Verifica se o banco existe, se não, cria
            $stmt = $pdoTemp->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
            if ($stmt->rowCount() == 0) {
                $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
            // 3. Conecta SELECIONANDO o banco agora
            $dsnWithDb = "$dsn;dbname=$dbname";
            $this->conn = new PDO($dsnWithDb, $username, $password, $options);
            
        } catch (PDOException $e) {
            // Se falhar ao criar/conectar, lança erro claro
            throw new Exception("Erro de conexão: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getConfig() {
        return $this->dbConfig;
    }
}
