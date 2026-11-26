<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface AcceptanceSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface[]
     */
    public function getItems();

    /**
     * @param \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
