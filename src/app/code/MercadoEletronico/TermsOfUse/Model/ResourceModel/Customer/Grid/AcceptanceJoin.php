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

/**
 * Adiciona à collection da grid de clientes as colunas:
 *  - terms_accepted    (1/0): aceitou a versão VIGENTE?
 *  - terms_accepted_at (data do aceite da versão vigente)
 *
 * O join é feito em tempo de consulta (e não no índice customer_grid_flat)
 * para que, ao mudar a versão, todos passem a "Não" imediatamente, sem reindex.
 */
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

        // A grid do admin compara com a versão do escopo padrão.
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

        // Permite filtrar a grid pelas colunas calculadas. O SearchResult da
        // grid prefixa campos sem tabela com "main_table." antes de filtrar,
        // então os dois nomes precisam estar mapeados para a expressão.
        foreach ([
            self::FIELD_ACCEPTED => $acceptedExpression,
            self::FIELD_ACCEPTED_AT => $acceptedAtExpression,
        ] as $field => $expression) {
            $collection->addFilterToMap($field, $expression);
            $collection->addFilterToMap('main_table.' . $field, $expression);
        }
    }
}
