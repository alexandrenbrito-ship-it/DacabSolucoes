<?php
/**
 * /classes/ClerkAuth.php
 * Classe para autenticação via Clerk
 * Validação de JWT usando JWKS e openssl nativo do PHP
 */

require_once __DIR__ . '/../config/clerk.php';
require_once __DIR__ . '/Database.php';

class ClerkAuth {
    
    private static ?PDO $db = null;
    
    /**
     * Obtém instância singleton do banco de dados
     */
    private static function getDb(): PDO {
        if (self::$db === null) {
            self::$db = Database::getInstance()->getConnection();
        }
        return self::$db;
    }
    
    /**
     * Verifica e valida um token JWT do Clerk
     * 
     * @param string $jwt Token JWT recebido do frontend
     * @return array|false Payload decodificado ou false se inválido
     */
    public static function verifyToken(string $jwt): array|false {
        // Separar as partes do JWT
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            error_log("ClerkAuth: Invalid JWT format");
            return false;
        }
        
        [$headerB64, $payloadB64, $signatureB64] = $parts;
        
        // Decodificar header para obter o kid (key ID)
        $header = json_decode(self::base64UrlDecode($headerB64), true);
        if (!$header || !isset($header['kid'])) {
            error_log("ClerkAuth: Invalid JWT header or missing kid");
            return false;
        }
        
        $kid = $header['kid'];
        
        // Obter chaves públicas JWKS
        $jwks = self::getJwks();
        if (!$jwks || !isset($jwks['keys'])) {
            error_log("ClerkAuth: Failed to fetch JWKS");
            return false;
        }
        
        // Encontrar a chave pública correspondente ao kid
        $publicKey = null;
        foreach ($jwks['keys'] as $key) {
            if ($key['kid'] === $kid) {
                $publicKey = self::convertJwkToPem($key);
                break;
            }
        }
        
        if (!$publicKey) {
            error_log("ClerkAuth: Public key not found for kid: $kid");
            return false;
        }
        
        // Verificar assinatura RS256
        $message = "$headerB64.$payloadB64";
        $signature = self::base64UrlDecode($signatureB64);
        
        $verified = openssl_verify(
            $message,
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );
        
        if ($verified !== 1) {
            error_log("ClerkAuth: Signature verification failed");
            return false;
        }
        
        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!$payload) {
            error_log("ClerkAuth: Failed to decode payload");
            return false;
        }
        
        // Validar expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            error_log("ClerkAuth: Token expired");
            return false;
        }
        
        // Validar issuer
        if (isset($payload['iss']) && $payload['iss'] !== CLERK_ISSUER) {
            // Aceitar também variações do issuer
            if (!str_contains($payload['iss'], 'clerk.accounts')) {
                error_log("ClerkAuth: Invalid issuer: " . $payload['iss']);
                return false;
            }
        }
        
        return $payload;
    }
    
    /**
     * Obtém ou cria usuário no banco baseado no payload do Clerk
     * 
     * @param array $clerkPayload Payload do token JWT
     * @return array Dados do usuário do banco
     */
    public static function getOrCreateUser(array $clerkPayload): array {
        $db = self::getDb();
        
        $clerkUserId = $clerkPayload['sub'] ?? null;
        if (!$clerkUserId) {
            throw new Exception("Clerk user ID (sub) not found in token");
        }
        
        // Extrair email do payload
        $email = $clerkPayload['email'] ?? $clerkPayload['https://clerk.com/email'] ?? null;
        if (!$email) {
            // Tentar extrair do campo email_verified ou primary_email_address
            if (isset($clerkPayload['email_addresses']) && is_array($clerkPayload['email_addresses'])) {
                foreach ($clerkPayload['email_addresses'] as $ea) {
                    if (($ea['verified'] ?? false) || true) {
                        $email = $ea['email_address'] ?? null;
                        break;
                    }
                }
            }
        }
        
        // Extrair nome
        $name = $clerkPayload['name'] ?? 
                $clerkPayload['first_name'] . ' ' . ($clerkPayload['last_name'] ?? '') ??
                $clerkPayload['https://clerk.com/name'] ??
                explode('@', $email)[0] ?? 'Usuário';
        
        // Extrair avatar
        $avatarUrl = $clerkPayload['image_url'] ?? $clerkPayload['picture'] ?? null;
        
        try {
            // Buscar usuário existente pelo clerk_user_id
            $stmt = $db->prepare("
                SELECT id, clerk_user_id, clerk_email, name, email, plan, avatar_url, 
                       created_at, updated_at, last_login, is_active
                FROM users
                WHERE clerk_user_id = :clerk_user_id
            ");
            $stmt->execute(['clerk_user_id' => $clerkUserId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Atualizar informações se necessário
                $updateData = [];
                if ($user['clerk_email'] !== $email) {
                    $updateData[] = "clerk_email = :clerk_email";
                }
                if ($user['name'] !== $name && $name !== explode('@', $user['email'])[0]) {
                    $updateData[] = "name = :name";
                }
                if ($user['avatar_url'] !== $avatarUrl) {
                    $updateData[] = "avatar_url = :avatar_url";
                }
                
                if (!empty($updateData)) {
                    $updateSql = "UPDATE users SET " . implode(', ', $updateData) . ", updated_at = NOW() 
                                  WHERE clerk_user_id = :clerk_user_id";
                    $updateStmt = $db->prepare($updateSql);
                    $updateStmt->execute([
                        'clerk_email' => $email,
                        'name' => $name,
                        'avatar_url' => $avatarUrl,
                        'clerk_user_id' => $clerkUserId
                    ]);
                    
                    // Atualizar dados retornados
                    $user['clerk_email'] = $email;
                    $user['name'] = $name;
                    $user['avatar_url'] = $avatarUrl;
                }
                
                return $user;
            }
            
            // Criar novo usuário
            $stmt = $db->prepare("
                INSERT INTO users (clerk_user_id, clerk_email, name, email, plan, avatar_url, is_active)
                VALUES (:clerk_user_id, :clerk_email, :name, :email, 'free', :avatar_url, 1)
            ");
            $stmt->execute([
                'clerk_user_id' => $clerkUserId,
                'clerk_email' => $email,
                'name' => $name,
                'email' => $email,
                'avatar_url' => $avatarUrl
            ]);
            
            $userId = (int)$db->lastInsertId();
            
            return [
                'id' => $userId,
                'clerk_user_id' => $clerkUserId,
                'clerk_email' => $email,
                'name' => $name,
                'email' => $email,
                'plan' => 'free',
                'avatar_url' => $avatarUrl,
                'is_active' => 1
            ];
            
        } catch (PDOException $e) {
            error_log("ClerkAuth getOrCreateUser error: " . $e->getMessage());
            throw new Exception("Erro ao buscar/criar usuário");
        }
    }
    
    /**
     * Método principal para autenticação em APIs
     * Lê o header Authorization, valida o token e retorna o usuário
     * 
     * @return array Dados do usuário autenticado
     */
    public static function requireAuth(): array {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Não autorizado. Token de autenticação ausente.'
            ]);
            exit;
        }
        
        $jwt = substr($authHeader, 7); // Remover "Bearer "
        
        $payload = self::verifyToken($jwt);
        if ($payload === false) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token de autenticação inválido ou expirado.'
            ]);
            exit;
        }
        
        try {
            return self::getOrCreateUser($payload);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno de autenticação.'
            ]);
            exit;
        }
    }
    
    /**
     * Obtém JWKS do Clerk com cache em arquivo
     * 
     * @return array|null Chaves públicas JWKS ou null em caso de erro
     */
    private static function getJwks(): ?array {
        $cacheFile = CLERK_JWKS_CACHE_PATH;
        
        // Tentar obter do cache
        $cached = self::getCachedJwks();
        if ($cached !== null) {
            return $cached;
        }
        
        // Buscar JWKS da API do Clerk
        $ch = curl_init(CLERK_JWKS_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            error_log("ClerkAuth: Failed to fetch JWKS from API");
            return null;
        }
        
        $jwks = json_decode($response, true);
        if (!$jwks) {
            error_log("ClerkAuth: Invalid JWKS response");
            return null;
        }
        
        // Salvar no cache
        self::cacheJwks($jwks);
        
        return $jwks;
    }
    
    /**
     * Salva JWKS no cache em arquivo
     * 
     * @param array $jwks Dados JWKS
     */
    public static function cacheJwks(array $jwks): void {
        $cacheData = [
            'timestamp' => time(),
            'jwks' => $jwks
        ];
        
        file_put_contents(
            CLERK_JWKS_CACHE_PATH,
            json_encode($cacheData),
            LOCK_EX
        );
    }
    
    /**
     * Obtém JWKS do cache se válido (menos de 1 hora)
     * 
     * @return array|null JWKS em cache ou null se expirado/inexistente
     */
    public static function getCachedJwks(): ?array {
        $cacheFile = CLERK_JWKS_CACHE_PATH;
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $content = file_get_contents($cacheFile);
        $cacheData = json_decode($content, true);
        
        if (!$cacheData || 
            !isset($cacheData['timestamp']) || 
            !isset($cacheData['jwks'])) {
            return null;
        }
        
        // Verificar se o cache ainda é válido (1 hora)
        if (time() - $cacheData['timestamp'] > CLERK_JWKS_CACHE_TTL) {
            return null;
        }
        
        return $cacheData['jwks'];
    }
    
    /**
     * Converte uma chave JWK para formato PEM (usado pelo openssl)
     * 
     * @param array $jwk Chave JWK
     * @return string Chave PEM
     */
    private static function convertJwkToPem(array $jwk): string {
        if (!isset($jwk['n']) || !isset($jwk['e'])) {
            throw new Exception("Invalid JWK: missing n or e");
        }
        
        $modulus = self::base64UrlDecode($jwk['n']);
        $exponent = self::base64UrlDecode($jwk['e']);
        
        // Construir sequência RSA DER
        $modulus = self::encodeLength(strlen($modulus)) . $modulus;
        $exponent = self::encodeLength(strlen($exponent)) . $exponent;
        
        $rsaSequence = "\x02" . $modulus . "\x02" . $exponent;
        $rsaSequence = "\x30" . self::encodeLength(strlen($rsaSequence)) . $rsaSequence;
        
        // Adicionar OID do RSA
        $oid = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        
        $bitString = "\x03" . self::encodeLength(strlen($rsaSequence) + 1) . "\x00" . $rsaSequence;
        
        $sequence = $oid . $bitString;
        $sequence = "\x30" . self::encodeLength(strlen($sequence)) . $sequence;
        
        $pem = "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($sequence), 64, "\n") .
               "-----END PUBLIC KEY-----";
        
        return $pem;
    }
    
    /**
     * Codifica comprimento para formato DER
     */
    private static function encodeLength(int $length): string {
        if ($length < 128) {
            return chr($length);
        }
        
        $encoded = '';
        while ($length > 0) {
            $encoded = chr($length & 0xFF) . $encoded;
            $length >>= 8;
        }
        
        return chr(0x80 | strlen($encoded)) . $encoded;
    }
    
    /**
     * Decodificação base64url
     */
    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
