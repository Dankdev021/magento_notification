<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Api;

use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;

/**
 * @api
 */
interface TermsAcceptanceManagementInterface
{
    /**
     * @param int $customerId
     * @return \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function accept(int $customerId): AcceptanceInterface;

    /**
     * @param int $customerId
     * @return bool
     */
    public function isAccepted(int $customerId): bool;
}
