<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MercadoEletronico\TermsOfUse\CustomerData\TermsOfUse;

class Modal implements ArgumentInterface
{
    private const ACCEPT_ENDPOINT = 'V1/terms-of-use/mine/accept';

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Json $json
    ) {
    }

    public function getJsConfig(): string
    {
        return (string) $this->json->serialize([
            'acceptUrl' => $this->getAcceptUrl(),
            'sectionName' => TermsOfUse::SECTION_NAME,
        ]);
    }

    public function getAcceptUrl(): string
    {
        $store = $this->storeManager->getStore();

        return $store->getBaseUrl(UrlInterface::URL_TYPE_WEB)
            . 'rest/' . $store->getCode() . '/' . self::ACCEPT_ENDPOINT;
    }
}
