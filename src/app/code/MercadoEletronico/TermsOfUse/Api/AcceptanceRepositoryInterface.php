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
interface AcceptanceRepositoryInterface
{
    /**
     * @param \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface $acceptance
     * @return \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(AcceptanceInterface $acceptance): AcceptanceInterface;

    /**
     * @param int $acceptanceId
     * @return \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $acceptanceId): AcceptanceInterface;

    /**
     * @param int $customerId
     * @param string $version
     * @return \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByCustomerAndVersion(int $customerId, string $version): AcceptanceInterface;
}
