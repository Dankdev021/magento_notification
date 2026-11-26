<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;

class Acceptance extends AbstractDb
{
    public const TABLE_NAME = 'mercadoeletronico_terms_of_use_acceptance';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, AcceptanceInterface::ENTITY_ID);
    }
}
