<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MercadoEletronico\TermsOfUse\Api\AcceptanceRepositoryInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterfaceFactory;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance as AcceptanceResource;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance\CollectionFactory;

class AcceptanceRepository implements AcceptanceRepositoryInterface
{
    public function __construct(
        private readonly AcceptanceResource $resource,
        private readonly AcceptanceInterfaceFactory $acceptanceFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(AcceptanceInterface $acceptance): AcceptanceInterface
    {
        try {
            /** @var Acceptance $acceptance */
            $this->resource->save($acceptance);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save the terms of use acceptance: %1', $e->getMessage()),
                $e
            );
        }

        return $this->getById((int) $acceptance->getId());
    }

    /**
     * @inheritdoc
     */
    public function getById(int $acceptanceId): AcceptanceInterface
    {
        /** @var Acceptance $acceptance */
        $acceptance = $this->acceptanceFactory->create();
        $this->resource->load($acceptance, $acceptanceId);

        if (!$acceptance->getId()) {
            throw NoSuchEntityException::singleField(AcceptanceInterface::ENTITY_ID, $acceptanceId);
        }

        return $acceptance;
    }

    /**
     * @inheritdoc
     */
    public function getByCustomerAndVersion(int $customerId, string $version): AcceptanceInterface
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(AcceptanceInterface::CUSTOMER_ID, $customerId)
            ->addFieldToFilter(AcceptanceInterface::TERMS_VERSION, $version)
            ->setPageSize(1);

        /** @var Acceptance $acceptance */
        $acceptance = $collection->getFirstItem();

        if (!$acceptance->getId()) {
            throw new NoSuchEntityException(
                __('Customer %1 has not accepted terms of use version "%2".', $customerId, $version)
            );
        }

        return $acceptance;
    }
}
