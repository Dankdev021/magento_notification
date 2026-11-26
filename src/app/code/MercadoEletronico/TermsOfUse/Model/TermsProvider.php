<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model;

use Magento\Store\Model\StoreManagerInterface;
use MercadoEletronico\TermsOfUse\Api\Data\TermsInterface;
use MercadoEletronico\TermsOfUse\Api\Data\TermsInterfaceFactory;
use MercadoEletronico\TermsOfUse\Api\TermsProviderInterface;

/**
 * Implementação padrão: termos vindos da configuração do admin.
 */
class TermsProvider implements TermsProviderInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ContentSanitizer $contentSanitizer,
        private readonly StoreManagerInterface $storeManager,
        private readonly TermsInterfaceFactory $termsFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): TermsInterface
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        /** @var TermsInterface $terms */
        $terms = $this->termsFactory->create();

        return $terms
            ->setEnabled($this->config->isEnabled($storeId))
            ->setVersion($this->config->getVersion($storeId))
            ->setTitle($this->config->getTitle($storeId))
            ->setContent($this->contentSanitizer->sanitize($this->config->getContent($storeId)))
            ->setButtonLabel($this->config->getButtonLabel($storeId));
    }
}
