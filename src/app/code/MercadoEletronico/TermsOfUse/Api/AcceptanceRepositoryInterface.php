<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceSearchResultsInterface;

/**
 * Persistência dos registros de aceite.
 *
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

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): AcceptanceSearchResultsInterface;
}
