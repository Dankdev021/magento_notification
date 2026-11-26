<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model\ResourceModel\Customer\Grid;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Sql\Expression;
use MercadoEletronico\TermsOfUse\Model\Config;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance as AcceptanceResource;

class AcceptanceJoin
{
    public const FIELD_ACCEPTED = 'terms_accepted';
    public const FIELD_ACCEPTED_AT = 'terms_accepted_at';

    private const TABLE_ALIAS = 'terms_acceptance';

    public function __construct(
        private readonly Config $config,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    public function execute(AbstractDb $collection): void
    {
        $connection = $collection->getConnection();
        $alias = self::TABLE_ALIAS;

        $joinCondition = implode(' AND ', [
            "{$alias}.customer_id = main_table.entity_id",
            $connection->quoteInto("{$alias}.terms_version = ?", $this->config->getVersion()),
        ]);

        $acceptedExpression = new Expression(
            (string) $connection->getCheckSql("{$alias}.entity_id IS NULL", '0', '1')
        );
        $acceptedAtExpression = new Expression("{$alias}.accepted_at");

        $collection->getSelect()->joinLeft(
            [$alias => $this->resourceConnection->getTableName(AcceptanceResource::TABLE_NAME)],
            $joinCondition,
            [
                self::FIELD_ACCEPTED => $acceptedExpression,
                self::FIELD_ACCEPTED_AT => $acceptedAtExpression,
            ]
        );

        foreach ([
            self::FIELD_ACCEPTED => $acceptedExpression,
            self::FIELD_ACCEPTED_AT => $acceptedAtExpression,
        ] as $field => $expression) {
            $collection->addFilterToMap($field, $expression);
            $collection->addFilterToMap('main_table.' . $field, $expression);
        }
    }
}
