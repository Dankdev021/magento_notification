<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model;

use Magento\Framework\Escaper;

/**
 * Mantém apenas as tags de formatação permitidas no texto dos termos,
 * evitando que HTML/JS arbitrário salvo no admin chegue ao storefront.
 */
class ContentSanitizer
{
    /**
     * @param Escaper $escaper
     * @param string[] $allowedTags Configurável via di.xml
     */
    public function __construct(
        private readonly Escaper $escaper,
        private readonly array $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u',
            'ul', 'ol', 'li', 'a', 'h2', 'h3', 'h4', 'span',
        ]
    ) {
    }

    public function sanitize(string $html): string
    {
        return (string) $this->escaper->escapeHtml($html, $this->allowedTags);
    }
}
