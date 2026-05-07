<?php
/**
 * /classes/Database.php
 * Classe Singleton para conexão PDO com o banco de dados
 * Garante uma única instância de conexão em toda a aplicação
 */

class Database {
    
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    
    /**
     * Construtor privado para impedir instanciação direta
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Impede clonagem da instância
     */
    private function __clone() {}
    
    /**
     * Impede desserialização da instância
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Obtém a instância única da classe Database
     * 
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Estabelece conexão com o banco de dados
     * 
     * @throws PDOException
     */
    private function connect(): void {
        // Carregar configurações se ainda não estiverem definidas
        if (!defined('DB_HOST')) {
            require_once dirname(__DIR__) . '/config/database.php';
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci",
            PDO::ATTR_PERSISTENT         => false // Desativado para evitar problemas em hospedagem compartilhada
        ];
        
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            
            if (getenv('APP_ENV') === 'development') {
                throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
            } else {
                throw new Exception("Erro interno do servidor. Por favor, tente novamente mais tarde.");
            }
        }
    }
    
    /**
     * Retorna a instância PDO da conexão
     * 
     * @return PDO
     */
    public function getConnection(): PDO {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Executa uma query preparada e retorna os resultados
     * 
     * @param string $sql Query SQL com placeholders
     * @param array $params Parâmetros para bind
     * @return array Resultados da query
     */
    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Executa uma query preparada e retorna um único registro
     * 
     * @param string $sql Query SQL com placeholders
     * @param array $params Parâmetros para bind
     * @return array|null Registro ou null se não encontrado
     */
    public function fetch(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Executa uma query de inserção/atualização e retorna o ID inserido
     * 
     * @param string $sql Query SQL com placeholders
     * @param array $params Parâmetros para bind
     * @return int|string ID do último insert
     */
    public function execute(string $sql, array $params = []): int|string {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Inicia uma transação
     * 
     * @return bool
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit da transação
     * 
     * @return bool
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Rollback da transação
     * 
     * @return bool
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }
}
