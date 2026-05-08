<?php
/**
 * EncartePro - Renderizador de Templates
 * Função central para renderizar encartes baseados em templates
 * Usado tanto no preview admin quanto na geração de PDF
 */

/**
 * Renderiza um encarte completo baseado no template e dados fornecidos
 * 
 * @param array $encarte Dados do encarte (tabela encartes)
 * @param array $template Dados do template (tabela templates)
 * @param bool $preview Se true, usa dados fictícios para preview
 * @return string HTML completo do encarte
 */
function renderEncarte($encarte, $template, $preview = false) {
    // Se for preview, usa dados fictícios
    if ($preview) {
        $data = getPreviewData();
    } else {
        $data = buildEncarteData($encarte);
    }
    
    // Carrega CSS base
    $cssPath = __DIR__ . '/../assets/css/encarte.css';
    $css = file_exists($cssPath) ? file_get_contents($cssPath) : '';
    
    // Monta variáveis CSS customizadas do template
    $customVars = buildCSSVariables($template);
    
    // Carrega fontes do Google Fonts
    $fonts = loadGoogleFonts($template);
    
    // Renderiza HTML
    $html = renderTemplateHTML($template, $data);
    
    // Retorna HTML completo
    return "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    {$fonts}
    <style>
        {$css}
        {$customVars}
    </style>
    {$template['custom_css']}
</head>
<body>
    {$html}
</body>
</html>";
}

/**
 * Dados fictícios para preview
 */
function getPreviewData() {
    return [
        'store_name' => '🏪 Mercadinho Exemplo',
        'store_logo' => '',
        'store_phone' => '(11) 99999-9999',
        'store_whatsapp' => '(11) 99999-9999',
        'store_address' => 'Rua Exemplo, 123',
        'store_city' => 'São Paulo - SP',
        'store_website' => 'www.mercadinho.com.br',
        'store_instagram' => '@mercadinho',
        'header_title' => 'OFERTAS DA SEMANA',
        'header_subtitle' => 'Preços imperdíveis para você!',
        'validity_text' => 'Válido de 01/07 a 07/07',
        'footer_text' => 'Confira nossas ofertas!',
        'products' => [
            ['name' => 'Arroz Tipo 1 5kg', 'price' => '22,90', 'old_price' => '28,90', 'image_base64' => '', 'highlight' => true],
            ['name' => 'Feijão Carioca 1kg', 'price' => '7,50', 'old_price' => '9,90', 'image_base64' => '', 'highlight' => false],
            ['name' => 'Óleo de Soja 900ml', 'price' => '8,90', 'old_price' => '', 'image_base64' => '', 'highlight' => false],
            ['name' => 'Açúcar Cristal 1kg', 'price' => '4,90', 'old_price' => '', 'image_base64' => '', 'highlight' => false],
            ['name' => 'Café Torrado 500g', 'price' => '12,90', 'old_price' => '15,90', 'image_base64' => '', 'highlight' => true],
            ['name' => 'Macarrão 500g', 'price' => '3,50', 'old_price' => '', 'image_base64' => '', 'highlight' => false],
            ['name' => 'Molho de Tomate 340g', 'price' => '4,20', 'old_price' => '', 'image_base64' => '', 'highlight' => false],
            ['name' => 'Leite Integral 1L', 'price' => '4,99', 'old_price' => '', 'image_base64' => '', 'highlight' => false],
        ]
    ];
}

/**
 * Constrói dados do encarte a partir do banco
 */
function buildEncarteData($encarte) {
    $products = json_decode($encarte['products'] ?? '[]', true) ?: [];
    
    return [
        'store_name' => $encarte['store_name'] ?? 'Sua Loja',
        'store_logo' => $encarte['store_logo'] ?? '',
        'store_phone' => $encarte['store_phone'] ?? '',
        'store_whatsapp' => $encarte['store_whatsapp'] ?? '',
        'store_address' => $encarte['store_address'] ?? '',
        'store_city' => $encarte['store_city'] ?? '',
        'store_website' => $encarte['store_website'] ?? '',
        'store_instagram' => $encarte['store_instagram'] ?? '',
        'header_title' => $encarte['header_title'] ?? 'OFERTAS',
        'header_subtitle' => $encarte['header_subtitle'] ?? '',
        'validity_text' => $encarte['validity_text'] ?? '',
        'footer_text' => $encarte['footer_text'] ?? '',
        'products' => $products
    ];
}

/**
 * Constrói variáveis CSS customizadas do template
 */
function buildCSSVariables($template) {
    return ":root {
        --header-bg: {$template['header_bg_color']};
        --header-text: {$template['header_text_color']};
        --body-bg: {$template['body_bg_color']};
        --body-text: {$template['body_text_color']};
        --product-card-bg: {$template['product_card_bg']};
        --product-card-border: {$template['product_card_border']};
        --product-name-color: {$template['product_name_color']};
        --product-price-color: {$template['product_price_color']};
        --product-old-price-color: {$template['product_old_price_color']};
        --badge-bg: {$template['badge_bg_color']};
        --badge-text: {$template['badge_text_color']};
        --footer-bg: {$template['footer_bg_color']};
        --footer-text: {$template['footer_text_color']};
        --product-card-radius: {$template['product_card_radius']}px;
        --primary-font: '{$template['primary_font']}';
        --secondary-font: '{$template['secondary_font']}';
    }";
}

/**
 * Carrega fonts do Google Fonts
 */
function loadGoogleFonts($template) {
    $fonts = array_unique([$template['primary_font'], $template['secondary_font']]);
    $fontFamilies = [];
    
    foreach ($fonts as $font) {
        $fontFamilies[] = urlencode(str_replace(' ', '+', $font)) . ':wght@400;600;700';
    }
    
    if (empty($fontFamilies)) {
        return '';
    }
    
    $fontString = implode('|', $fontFamilies);
    return "<link href='https://fonts.googleapis.com/css2?family={$fontString}&display=swap' rel='stylesheet'>";
}

/**
 * Renderiza HTML do template
 */
function renderTemplateHTML($template, $data) {
    $html = '';
    
    // Cabeçalho
    $html .= renderHeader($template, $data);
    
    // Área de produtos
    $html .= renderProductsArea($template, $data);
    
    // Rodapé
    $html .= renderFooter($template, $data);
    
    // HTML customizado do cabeçalho
    if (!empty($template['custom_html_header'])) {
        $html .= $template['custom_html_header'];
    }
    
    // HTML customizado do rodapé
    if (!empty($template['custom_html_footer'])) {
        $html .= $template['custom_html_footer'];
    }
    
    return "<div class='encarte' data-template='{$template['id']}'>{$html}</div>";
}

/**
 * Renderiza cabeçalho do encarte
 */
function renderHeader($template, $data) {
    $layout = $template['header_layout'];
    $height = $template['header_height'];
    $showLogo = $template['header_show_logo'];
    $showPhone = $template['header_show_phone'];
    
    $logoHtml = '';
    if ($showLogo && !empty($data['store_logo'])) {
        $logoSrc = isBase64($data['store_logo']) ? $data['store_logo'] : htmlspecialchars($data['store_logo']);
        $logoHtml = "<img src='{$logoSrc}' alt='Logo' class='store-logo'>";
    }
    
    $phoneHtml = '';
    if ($showPhone && !empty($data['store_phone'])) {
        $phoneHtml = "<div class='header-phone'>📞 " . htmlspecialchars($data['store_phone']) . "</div>";
    }
    
    return "
    <header class='encarte-header header-{$layout} header-{$height}'>
        {$logoHtml}
        <div class='header-texts'>
            <h1 class='store-name'>" . htmlspecialchars($data['store_name']) . "</h1>
            <h2 class='promo-title'>" . htmlspecialchars($data['header_title']) . "</h2>
            " . (!empty($data['header_subtitle']) ? "<p class='promo-subtitle'>" . htmlspecialchars($data['header_subtitle']) . "</p>" : "") . "
        </div>
        {$phoneHtml}
    </header>";
}

/**
 * Renderiza área de produtos
 */
function renderProductsArea($template, $data) {
    $layout = $template['layout_style'];
    $colsDesktop = $template['product_cols_desktop'];
    $showImage = $template['show_product_image'];
    $showOldPrice = $template['show_old_price'];
    $badgeStyle = $template['badge_style'];
    
    $productsHtml = '';
    foreach ($data['products'] as $product) {
        $productsHtml .= renderProductCard($template, $product, $showImage, $showOldPrice, $badgeStyle);
    }
    
    return "
    <main class='encarte-body layout-{$layout} cols-desktop-{$colsDesktop}'>
        {$productsHtml}
    </main>";
}

/**
 * Renderiza card individual de produto
 */
function renderProductCard($template, $product, $showImage, $showOldPrice, $badgeStyle) {
    $name = htmlspecialchars($product['name'] ?? '');
    $price = formatPrice($product['price'] ?? '0');
    $oldPrice = !empty($product['old_price']) ? formatPrice($product['old_price']) : '';
    $highlight = !empty($product['highlight']) ? 'featured' : '';
    
    $imageHtml = '';
    if ($showImage && !empty($product['image_base64'])) {
        $imageHtml = "<img src='{$product['image_base64']}' alt='{$name}' class='product-image'>";
    } elseif ($showImage) {
        $imageHtml = "<div class='product-image-placeholder'></div>";
    }
    
    $oldPriceHtml = '';
    if ($showOldPrice && $oldPrice) {
        $oldPriceHtml = "<p class='product-old-price'>de R$ {$oldPrice}</p>";
    }
    
    $badgeClass = $badgeStyle !== 'none' ? "badge badge-{$badgeStyle}" : "product-price-plain";
    
    return "
    <div class='product-card {$highlight}' style='border-radius: var(--product-card-radius);'>
        {$imageHtml}
        <p class='product-name'>{$name}</p>
        {$oldPriceHtml}
        <div class='{$badgeClass}'>R$ {$price}</div>
    </div>";
}

/**
 * Renderiza rodapé do encarte
 */
function renderFooter($template, $data) {
    $showAddress = $template['footer_show_address'];
    $showPhone = $template['footer_show_phone'];
    $showWhatsapp = $template['footer_show_whatsapp'];
    $showWebsite = $template['footer_show_website'];
    $showSocial = $template['footer_show_social'];
    
    $lines = [];
    
    if ($showAddress && !empty($data['store_address'])) {
        $address = htmlspecialchars($data['store_address']);
        $city = htmlspecialchars($data['store_city']);
        $lines[] = "📍 {$address}" . ($city ? " — {$city}" : "");
    }
    
    if ($showPhone && !empty($data['store_phone'])) {
        $lines[] = "📞 " . htmlspecialchars($data['store_phone']);
    }
    
    if ($showWhatsapp && !empty($data['store_whatsapp'])) {
        $lines[] = "💬 " . htmlspecialchars($data['store_whatsapp']);
    }
    
    if ($showWebsite && !empty($data['store_website'])) {
        $lines[] = "🌐 " . htmlspecialchars($data['store_website']);
    }
    
    if ($showSocial && !empty($data['store_instagram'])) {
        $lines[] = "📷 " . htmlspecialchars($data['store_instagram']);
    }
    
    if (!empty($data['footer_text'])) {
        $lines[] = htmlspecialchars($data['footer_text']);
    }
    
    if (!empty($data['validity_text'])) {
        $lines[] = "<strong>" . htmlspecialchars($data['validity_text']) . "</strong>";
    }
    
    $footerContent = implode("</p><p>", $lines);
    
    return "
    <footer class='encarte-footer'>
        <p>{$footerContent}</p>
    </footer>";
}

/**
 * Formata preço para exibição
 */
function formatPrice($value) {
    if (empty($value)) return '';
    // Remove caracteres não numéricos exceto vírgula e ponto
    $clean = preg_replace('/[^0-9,.]/', '', $value);
    // Se tiver vírgula, já está no formato brasileiro
    if (strpos($clean, ',') !== false) {
        return $clean;
    }
    // Se for número inteiro ou com ponto decimal
    return number_format((float)$clean, 2, ',', '.');
}

/**
 * Verifica se string é base64
 */
function isBase64($string) {
    return preg_match('/^data:image\/[a-z]+;base64,/', $string);
}

/**
 * Converte URL de imagem para base64 (para PDF)
 */
function imageUrlToBase64($url) {
    if (isBase64($url)) {
        return $url;
    }
    
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $data = curl_exec($ch);
        curl_close($ch);
        
        if ($data) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($data);
            return 'data:' . $mimeType . ';base64,' . base64_encode($data);
        }
    } catch (Exception $e) {
        error_log("Erro ao converter imagem para base64: " . $e->getMessage());
    }
    
    return $url;
}
