<?php
/**
 * EncartePro - Download de PDF
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pdf.php';

$requireLogin = true;

$db = getDB();
$userId = $_SESSION['user_id'];
$encarteId = $_GET['id'] ?? null;

if (!$encarteId) {
    http_response_code(400);
    echo "ID do encarte não fornecido";
    exit;
}

// Verifica se o encarte pertence ao usuário
$stmt = $db->prepare("SELECT * FROM encartes WHERE id = :id AND user_id = :user_id");
$stmt->bindValue(':id', $encarteId, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$encarte = $stmt->fetch();

if (!$encarte) {
    http_response_code(404);
    echo "Encarte não encontrado";
    exit;
}

// Gera ou obtém o PDF
$pdfPath = $encarte['pdf_url'];

if (!$pdfPath || !file_exists(BASE_PATH . $pdfPath)) {
    // Gera novo PDF
    $pdfPath = generateEncartePDF($encarteId);
    
    if (!$pdfPath) {
        setFlashMessage('error', 'Erro ao gerar PDF. Tente novamente.');
        redirect(SITE_URL . '/dashboard/my-encartes.php');
    }
}

// Faz download do PDF
downloadPDF($pdfPath);
