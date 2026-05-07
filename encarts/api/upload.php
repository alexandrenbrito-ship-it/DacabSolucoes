<?php
/**
 * /api/upload.php
 * API para upload de imagens dos usuários
 * Valida mime-type, extensão, tamanho e renomeia para hash seguro
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar classes necessárias
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/ClerkAuth.php';

// Verificar autenticação
$user = ClerkAuth::requireAuth();
$userId = (int)$user['id'];

// Apenas método POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Configurações de upload
$maxFileSize = (int)(getenv('MAX_UPLOAD_SIZE') ?: 5242880); // 5MB padrão
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif'
];
$uploadDir = dirname(__DIR__) . '/assets/uploads/';

// Verificar se arquivo foi enviado
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do PHP).',
        UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário).',
        UPLOAD_ERR_PARTIAL => 'Upload parcial.',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário indisponível.',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo.',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão.'
    ];
    
    $errorCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMessage = $errorMessages[$errorCode] ?? 'Erro desconhecido no upload.';
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}

$file = $_FILES['image'];

// Validar tamanho do arquivo
if ($file['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Arquivo muito grande. Tamanho máximo: ' . formatBytes($maxFileSize)
    ]);
    exit;
}

// Validar mime-type real usando finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$realMimeType = $finfo->file($file['tmp_name']);

if (!isset($allowedMimeTypes[$realMimeType])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tipo de arquivo não permitido. Permitidos: JPG, PNG, WebP, GIF.'
    ]);
    exit;
}

// Gerar nome seguro para o arquivo
$fileExtension = $allowedMimeTypes[$realMimeType];
$newFileName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $fileExtension;
$destinationPath = $uploadDir . $newFileName;

// Mover arquivo para diretório de uploads
if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo.']);
    exit;
}

// Definir permissões do arquivo
chmod($destinationPath, 0644);

// Registrar upload no banco de dados
try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO user_uploads (user_id, filename, original_name, file_size, mime_type)
        VALUES (:user_id, :filename, :original_name, :file_size, :mime_type)
    ");
    $stmt->execute([
        'user_id' => $userId,
        'filename' => $newFileName,
        'original_name' => basename($file['name']),
        'file_size' => $file['size'],
        'mime_type' => $realMimeType
    ]);
    
    $uploadId = (int)$db->lastInsertId();
    
    // URL pública do arquivo
    $publicUrl = '/encarts/assets/uploads/' . $newFileName;
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Upload realizado com sucesso!',
        'data' => [
            'id' => $uploadId,
            'url' => $publicUrl,
            'filename' => $newFileName,
            'original_name' => basename($file['name']),
            'size' => $file['size'],
            'mime_type' => $realMimeType,
            'width' => null, // Pode ser calculado com getimagesize se necessário
            'height' => null
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Upload database error: " . $e->getMessage());
    
    // Remover arquivo em caso de erro no banco
    if (file_exists($destinationPath)) {
        unlink($destinationPath);
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar upload.']);
}

/**
 * Formata bytes para leitura humana
 */
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
