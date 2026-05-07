<?php
/**
 * /api/gallery.php
 * API para galerias (pública e pessoal)
 */

require_once '../config/database.php';
require_once '../classes/ClerkAuth.php';
require_once '../classes/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Autenticar usuário
    $user = ClerkAuth::requireAuth();
    $userModel = new User();
    
    $action = $_GET['action'] ?? '';
    $db = Database::getInstance()->getConnection();
    
    switch ($action) {
        case 'get_public':
            // Listar galeria pública (categorias e itens)
            $categoryId = (int)($_GET['category_id'] ?? 0);
            
            if ($categoryId) {
                $sql = "SELECT * FROM public_gallery_items WHERE category_id = :category_id AND is_active = 1 ORDER BY created_at DESC";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            } else {
                $sql = "SELECT * FROM public_gallery_items WHERE is_active = 1 ORDER BY created_at DESC";
                $stmt = $db->query($sql);
            }
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $items]);
            break;
            
        case 'get_categories':
            // Listar categorias da galeria pública
            $sql = "SELECT * FROM gallery_categories WHERE is_active = 1 ORDER BY sort_order ASC";
            $categories = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $categories]);
            break;
            
        case 'get_personal':
            // Listar galeria pessoal do usuário autenticado
            $sql = "SELECT * FROM user_galleries WHERE user_id = :user_id AND is_active = 1 ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $items]);
            break;
            
        case 'upload_personal':
            // Upload para galeria pessoal
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            // Verificar limites do plano
            $limits = $userModel->getPlanLimits($user['id']);
            if ($limits['remaining_uploads'] <= 0) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => "Limite de uploads atingido no plano {$limits['plan_name']}. Faça upgrade para continuar."
                ]);
                exit;
            }
            
            if (!isset($_FILES['image'])) {
                throw new Exception('Nenhuma imagem enviada');
            }
            
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Tipo de arquivo não permitido. Apenas JPG, PNG, GIF e WebP.');
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
                throw new Exception('Arquivo muito grande. Máximo 5MB.');
            }
            
            // Gerar nome único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $uploadDir = __DIR__ . '/../assets/uploads/galleries/' . $user['id'] . '/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filePath = $uploadDir . $filename;
            $relativePath = 'assets/uploads/galleries/' . $user['id'] . '/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception('Erro ao salvar arquivo');
            }
            
            // Obter dimensões da imagem
            $imageInfo = getimagesize($filePath);
            $width = $imageInfo[0] ?? 0;
            $height = $imageInfo[1] ?? 0;
            
            // Inserir no banco
            $sql = "INSERT INTO user_galleries (user_id, title, file_path, original_name, file_type, file_size, width, height)
                    VALUES (:user_id, :title, :file_path, :original_name, :file_type, :file_size, :width, :height)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $user['id'],
                'title' => $file['name'],
                'file_path' => $relativePath,
                'original_name' => $file['name'],
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'width' => $width,
                'height' => $height
            ]);
            
            $insertId = (int) $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Imagem enviada com sucesso',
                'data' => [
                    'id' => $insertId,
                    'path' => $relativePath,
                    'remaining_uploads' => $limits['remaining_uploads'] - 1
                ]
            ]);
            break;
            
        case 'delete_personal':
            // Remover imagem da galeria pessoal
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $imageId = (int)($data['id'] ?? 0);
            
            if (!$imageId) {
                throw new Exception('ID inválido');
            }
            
            // Verificar se a imagem pertence ao usuário
            $sql = "SELECT * FROM user_galleries WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $imageId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt->execute();
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$image) {
                throw new Exception('Imagem não encontrada');
            }
            
            // Remover arquivo físico
            $fullPath = __DIR__ . '/../' . $image['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            // Remover do banco (hard delete)
            $sql = "DELETE FROM user_galleries WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $imageId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Imagem removida com sucesso']);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
