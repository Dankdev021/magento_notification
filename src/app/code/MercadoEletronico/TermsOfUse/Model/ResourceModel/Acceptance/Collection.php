<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MercadoEletronico\TermsOfUse\Model\Acceptance;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance as AcceptanceResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(Acceptance::class, AcceptanceResource::class);
    }
}
