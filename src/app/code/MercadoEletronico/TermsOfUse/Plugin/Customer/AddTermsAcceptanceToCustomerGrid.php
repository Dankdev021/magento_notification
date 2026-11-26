<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Plugin\Customer;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Customer\Grid\AcceptanceJoin;

class AddTermsAcceptanceToCustomerGrid
{
    private const CUSTOMER_GRID_DATA_SOURCE = 'customer_listing_data_source';

    public function __construct(
        private readonly AcceptanceJoin $acceptanceJoin
    ) {
    }

    /**
     * @param CollectionFactory $subject
     * @param mixed $collection
     * @param string $requestName
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetReport(CollectionFactory $subject, $collection, $requestName)
    {
        if ($requestName === self::CUSTOMER_GRID_DATA_SOURCE && $collection instanceof AbstractDb) {
            $this->acceptanceJoin->execute($collection);
        }

        return $collection;
    }
}
