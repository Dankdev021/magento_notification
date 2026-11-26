<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'terms_of_use/general/enabled';
    public const XML_PATH_VERSION = 'terms_of_use/general/version';
    public const XML_PATH_TITLE = 'terms_of_use/general/title';
    public const XML_PATH_CONTENT = 'terms_of_use/general/content';
    public const XML_PATH_BUTTON_LABEL = 'terms_of_use/general/button_label';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId)
            && $this->getVersion($storeId) !== '';
    }

    public function getVersion(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_VERSION, $storeId);
    }

    public function getTitle(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_TITLE, $storeId);
    }

    public function getContent(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_CONTENT, $storeId);
    }

    public function getButtonLabel(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_BUTTON_LABEL, $storeId);
    }

    private function getString(string $path, ?int $storeId): string
    {
        return trim((string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId));
    }
}
