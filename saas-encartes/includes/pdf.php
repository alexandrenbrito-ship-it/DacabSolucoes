<?php
/**
 * EncartePro - Geração de PDF
 * Responsável por gerar PDFs dos encartes usando mPDF
 */

// Inclui o arquivo de configuração
require_once __DIR__ . '/config.php';

/**
 * Gera um PDF a partir de um encarte
 * 
 * @param int $encarteId ID do encarte
 * @return string|null Caminho do PDF gerado ou null em caso de erro
 */
function generateEncartePDF($encarteId) {
    $db = getDB();
    if (!$db) return null;
    
    // Obtém os dados do encarte
    $sql = "SELECT e.*, u.email as user_email 
            FROM encartes e
            INNER JOIN users u ON e.user_id = u.id
            WHERE e.id = :encarte_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':encarte_id', $encarteId, PDO::PARAM_INT);
    $stmt->execute();
    
    $encarte = $stmt->fetch();
    
    if (!$encarte) {
        error_log("Encarte não encontrado: " . $encarteId);
        return null;
    }
    
    // Decodifica os dados JSON do encarte
    $data = json_decode($encarte['data'], true);
    if (!$data) {
        error_log("Dados do encarte inválidos: " . $encarteId);
        return null;
    }
    
    // Renderiza o HTML baseado no template
    $html = renderEncarteTemplate($encarte['template_id'], $data);
    
    if (empty($html)) {
        error_log("Template não encontrado: " . $encarte['template_id']);
        return null;
    }
    
    // Cria o PDF usando mPDF
    $pdfPath = createPDF($html, $encarte['title'], $encarteId);
    
    if ($pdfPath) {
        // Atualiza o caminho do PDF no banco de dados
        $updateSql = "UPDATE encartes SET pdf_url = :pdf_url WHERE id = :encarte_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindValue(':pdf_url', $pdfPath, PDO::PARAM_STR);
        $updateStmt->bindValue(':encarte_id', $encarteId, PDO::PARAM_INT);
        $updateStmt->execute();
    }
    
    return $pdfPath;
}

/**
 * Cria um arquivo PDF usando mPDF
 * 
 * @param string $html Conteúdo HTML para converter em PDF
 * @param string $filename Nome do arquivo (sem extensão)
 * @param int $encarteId ID do encarte (para nomear o arquivo)
 * @return string|null Caminho relativo do PDF ou null em caso de erro
 */
function createPDF($html, $filename, $encarteId) {
    // Verifica se mPDF está disponível
    if (!class_exists('Mpdf\Mpdf')) {
        // Tenta carregar via autoload do Composer
        $autoloadPaths = [
            BASE_PATH . '/vendor/autoload.php',
            BASE_PATH . '/../vendor/autoload.php'
        ];
        
        foreach ($autoloadPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                break;
            }
        }
        
        // Se ainda não estiver disponível, retorna erro
        if (!class_exists('Mpdf\Mpdf')) {
            error_log("mPDF não encontrado. Instale com: composer require mpdf/mpdf");
            return null;
        }
    }
    
    try {
        // Configurações do mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'tempDir' => BASE_PATH . '/tmp'
        ]);
        
        // Configurações adicionais
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->useAdobeCJK = false;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        
        // Escreve o HTML
        $mpdf->WriteHTML($html);
        
        // Cria o diretório de PDFs se não existir
        $pdfDir = BASE_PATH . '/uploads/pdfs';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }
        
        // Gera nome do arquivo seguro
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '-', $filename);
        $safeFilename = substr($safeFilename, 0, 50); // Limita tamanho
        $pdfFilename = 'encarte-' . $encarteId . '-' . $safeFilename . '.pdf';
        $pdfPath = $pdfDir . '/' . $pdfFilename;
        
        // Salva o PDF
        $mpdf->Output($pdfPath, 'F');
        
        // Retorna o caminho relativo
        return '/saas-encartes/uploads/pdfs/' . $pdfFilename;
        
    } catch (\Mpdf\MpdfException $e) {
        error_log("Erro ao gerar PDF: " . $e->getMessage());
        return null;
    }
}

/**
 * Renderiza o HTML de um template de encarte
 * 
 * @param string $templateId ID do template
 * @param array $data Dados do encarte
 * @return string HTML renderizado
 */
function renderEncarteTemplate($templateId, $data) {
    // Mapeia templates para suas funções de renderização
    $templates = [
        'mercado_semanal' => 'renderMercadoSemanalTemplate',
        'promocao_relampago' => 'renderPromocaoRelampagoTemplate',
        'cardapio_simples' => 'renderCardapioSimplesTemplate',
        'oferta_supermercado' => 'renderOfertaSupermercadoTemplate',
        'aniversario_loja' => 'renderAniversarioLojaTemplate',
        'black_friday' => 'renderBlackFridayTemplate'
    ];
    
    if (!isset($templates[$templateId])) {
        // Template padrão como fallback
        return renderDefaultTemplate($data);
    }
    
    // Chama a função de renderização do template
    return call_user_func($templates[$templateId], $data);
}

/**
 * Template: Mercado Semanal
 */
function renderMercadoSemanalTemplate($data) {
    $storeName = $data['store_name'] ?? 'Sua Loja';
    $mainTitle = $data['main_title'] ?? 'Ofertas da Semana';
    $primaryColor = $data['primary_color'] ?? '#e8401c';
    $secondaryColor = $data['secondary_color'] ?? '#f5a623';
    $footerText = $data['footer_text'] ?? 'Válido até XXXX/XX/XX';
    $products = $data['products'] ?? [];
    
    $productsHTML = '';
    foreach ($products as $product) {
        $name = htmlspecialchars($product['name'] ?? 'Produto');
        $price = $product['price'] ?? '0,00';
        $image = !empty($product['image']) ? $product['image'] : 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="%23eee" width="100" height="100"/></svg>';
        
        $productsHTML .= '
        <div class="product-card">
            <img src="' . $image . '" alt="' . $name . '" class="product-image">
            <h3 class="product-name">' . $name . '</h3>
            <p class="product-price">R$ ' . $price . '</p>
        </div>';
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; background: #fff; }
            .header { background: ' . $primaryColor . '; color: white; padding: 30px; text-align: center; }
            .header h1 { font-size: 36px; margin-bottom: 10px; }
            .header p { font-size: 18px; opacity: 0.9; }
            .products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; padding: 30px; }
            .product-card { border: 2px solid ' . $secondaryColor . '; border-radius: 10px; padding: 15px; text-align: center; }
            .product-image { width: 100%; height: 120px; object-fit: cover; border-radius: 5px; margin-bottom: 10px; }
            .product-name { font-size: 16px; color: #333; margin-bottom: 10px; min-height: 40px; }
            .product-price { font-size: 24px; font-weight: bold; color: ' . $primaryColor . '; }
            .footer { background: ' . $secondaryColor . '; color: white; padding: 20px; text-align: center; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . $storeName . '</h1>
            <p>' . $mainTitle . '</p>
        </div>
        <div class="products-grid">
            ' . $productsHTML . '
        </div>
        <div class="footer">
            <p>' . $footerText . '</p>
        </div>
    </body>
    </html>';
}

/**
 * Template: Promoção Relâmpago
 */
function renderPromocaoRelampagoTemplate($data) {
    $storeName = $data['store_name'] ?? 'Sua Loja';
    $mainTitle = $data['main_title'] ?? 'PROMOÇÃO RELÂMPAGO';
    $primaryColor = $data['primary_color'] ?? '#1a1a1a';
    $footerText = $data['footer_text'] ?? 'Corra! Oferta por tempo limitado';
    $products = $data['products'] ?? [];
    
    $productsHTML = '';
    foreach ($products as $product) {
        $name = htmlspecialchars($product['name'] ?? 'Produto');
        $price = $product['price'] ?? '0,00';
        
        $productsHTML .= '
        <div class="product-item">
            <div class="product-info">
                <h3 class="product-name">' . $name . '</h3>
                <p class="product-price">R$ ' . $price . '</p>
            </div>
        </div>';
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; background: #fff; }
            .header { background: ' . $primaryColor . '; color: #f5a623; padding: 40px; text-align: center; }
            .header h1 { font-size: 48px; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 10px; }
            .header .store { font-size: 24px; color: white; }
            .products { padding: 30px; }
            .product-item { border-bottom: 2px solid #333; padding: 20px 0; display: flex; justify-content: space-between; align-items: center; }
            .product-name { font-size: 20px; color: #333; text-transform: uppercase; }
            .product-price { font-size: 32px; font-weight: bold; color: ' . $primaryColor . '; }
            .footer { background: #f5a623; color: ' . $primaryColor . '; padding: 20px; text-align: center; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <p class="store">' . $storeName . '</p>
            <h1>' . $mainTitle . '</h1>
        </div>
        <div class="products">
            ' . $productsHTML . '
        </div>
        <div class="footer">
            ' . $footerText . '
        </div>
    </body>
    </html>';
}

/**
 * Template: Cardápio Simples
 */
function renderCardapioSimplesTemplate($data) {
    $storeName = $data['store_name'] ?? 'Seu Restaurante';
    $mainTitle = $data['main_title'] ?? 'CARDÁPIO';
    $primaryColor = $data['primary_color'] ?? '#2c3e50';
    $categories = $data['categories'] ?? [['name' => 'Categoria', 'items' => []]];
    
    $categoriesHTML = '';
    foreach ($categories as $category) {
        $catName = htmlspecialchars($category['name'] ?? 'Categoria');
        $items = $category['items'] ?? [];
        
        $itemsHTML = '';
        foreach ($items as $item) {
            $itemName = htmlspecialchars($item['name'] ?? 'Item');
            $itemPrice = $item['price'] ?? '0,00';
            $itemDesc = htmlspecialchars($item['description'] ?? '');
            
            $itemsHTML .= '
            <div class="menu-item">
                <div class="item-header">
                    <span class="item-name">' . $itemName . '</span>
                    <span class="item-dots"></span>
                    <span class="item-price">R$ ' . $itemPrice . '</span>
                </div>
                ' . (!empty($itemDesc) ? '<p class="item-desc">' . $itemDesc . '</p>' : '') . '
            </div>';
        }
        
        $categoriesHTML .= '
        <div class="category">
            <h2 class="category-title">' . $catName . '</h2>
            <div class="items-list">
                ' . $itemsHTML . '
            </div>
        </div>';
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Georgia, serif; background: #fff; color: #333; }
            .header { text-align: center; padding: 40px 20px; border-bottom: 3px double ' . $primaryColor . '; margin-bottom: 30px; }
            .header h1 { font-size: 42px; color: ' . $primaryColor . '; letter-spacing: 5px; margin-bottom: 10px; }
            .header .store { font-size: 18px; color: #666; }
            .category { margin-bottom: 30px; }
            .category-title { font-size: 24px; color: ' . $primaryColor . '; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px; }
            .menu-item { margin-bottom: 15px; }
            .item-header { display: flex; align-items: baseline; }
            .item-name { font-size: 16px; font-weight: bold; }
            .item-dots { flex: 1; border-bottom: 1px dotted #ccc; margin: 0 10px; }
            .item-price { font-size: 16px; font-weight: bold; color: ' . $primaryColor . '; }
            .item-desc { font-size: 14px; color: #666; font-style: italic; margin-top: 5px; }
        </style>
    </head>
    <body>
        <div class="header">
            <p class="store">' . $storeName . '</p>
            <h1>' . $mainTitle . '</h1>
        </div>
        ' . $categoriesHTML . '
    </body>
    </html>';
}

/**
 * Templates restantes (simplificados para economizar espaço)
 */
function renderOfertaSupermercadoTemplate($data) {
    return renderMercadoSemanalTemplate($data);
}

function renderAniversarioLojaTemplate($data) {
    $storeName = $data['store_name'] ?? 'Sua Loja';
    $mainTitle = $data['main_title'] ?? 'ANIVERSÁRIO';
    $primaryColor = $data['primary_color'] ?? '#e8401c';
    $products = $data['products'] ?? [];
    
    $productsHTML = '';
    foreach ($products as $product) {
        $name = htmlspecialchars($product['name'] ?? 'Produto');
        $price = $product['price'] ?? '0,00';
        $productsHTML .= '<div class="product"><strong>' . $name . '</strong><br>R$ ' . $price . '</div>';
    }
    
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:Arial;background:#fff}.header{background:' . $primaryColor . ';color:#fff;padding:40px;text-align:center}h1{font-size:48px}.products{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;padding:30px}.product{border:3px solid #f5a623;padding:20px;text-align:center;font-size:18px}</style></head><body><div class="header"><h1>🎉 ' . $storeName . ' 🎉</h1><p>' . $mainTitle . '</p></div><div class="products">' . $productsHTML . '</div></body></html>';
}

function renderBlackFridayTemplate($data) {
    $storeName = $data['store_name'] ?? 'Sua Loja';
    $mainTitle = $data['main_title'] ?? 'BLACK FRIDAY';
    $products = $data['products'] ?? [];
    
    $productsHTML = '';
    foreach ($products as $product) {
        $name = htmlspecialchars($product['name'] ?? 'Produto');
        $price = $product['price'] ?? '0,00';
        $discount = $product['discount'] ?? '0';
        $productsHTML .= '<div class="product"><h3>' . $name . '</h3><p class="old-price">De: R$ ' . $price . '</p><p class="new-price">Por: R$ ' . number_format((float)$price * (1 - $discount/100), 2, ',', '.') . '</p><span class="discount">-' . $discount . '%</span></div>';
    }
    
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:Arial;background:#000;color:#fff}.header{background:linear-gradient(45deg,#000,#333);padding:50px;text-align:center;border-bottom:3px solid #f5a623}h1{font-size:60px;color:#f5a623;text-transform:uppercase}.products{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;padding:30px}.product{border:2px solid #f5a623;padding:20px;text-align:center}.old-price{text-decoration:line-through;color:#999}.new-price{font-size:28px;font-weight:bold;color:#f5a623}.discount{display:inline-block;background:#f5a623;color:#000;padding:5px 15px;font-weight:bold;margin-top:10px}</style></head><body><div class="header"><h1>' . $mainTitle . '</h1><p>' . $storeName . '</p></div><div class="products">' . $productsHTML . '</div></body></html>';
}

function renderDefaultTemplate($data) {
    return renderMercadoSemanalTemplate($data);
}

/**
 * Faz download de um PDF existente
 * 
 * @param string $pdfPath Caminho do PDF
 * @return void
 */
function downloadPDF($pdfPath) {
    $fullPath = BASE_PATH . $pdfPath;
    
    if (!file_exists($fullPath)) {
        http_response_code(404);
        echo "PDF não encontrado";
        exit;
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($pdfPath) . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}
