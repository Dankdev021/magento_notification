<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Test\Unit\Model\ResourceModel\Customer\Grid;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MercadoEletronico\TermsOfUse\Model\Config;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Customer\Grid\AcceptanceJoin;
use PHPUnit\Framework\TestCase;

class AcceptanceJoinTest extends TestCase
{
    public function testMapsPlainAndMainTablePrefixedFields(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('quoteInto')->willReturn("terms_acceptance.terms_version = '1.0'");
        $connection->method('getCheckSql')->willReturn(
            new \Zend_Db_Expr('CASE WHEN terms_acceptance.entity_id IS NULL THEN 0 ELSE 1 END')
        );

        $select = $this->createMock(Select::class);
        $select->expects($this->once())->method('joinLeft')->willReturnSelf();

        $mapped = [];
        $collection = $this->createMock(AbstractDb::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);
        $collection->method('addFilterToMap')->willReturnCallback(
            function (string $field, $alias) use (&$mapped, $collection) {
                $mapped[$field] = (string) $alias;

                return $collection;
            }
        );

        $config = $this->createMock(Config::class);
        $config->method('getVersion')->willReturn('1.0');
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getTableName')->willReturnArgument(0);

        (new AcceptanceJoin($config, $resource))->execute($collection);

        foreach ([AcceptanceJoin::FIELD_ACCEPTED, AcceptanceJoin::FIELD_ACCEPTED_AT] as $field) {
            $this->assertArrayHasKey($field, $mapped);
            $this->assertArrayHasKey('main_table.' . $field, $mapped);
            $this->assertSame($mapped[$field], $mapped['main_table.' . $field]);
            $this->assertStringNotContainsString('main_table.' . $field, $mapped[$field]);
        }
    }
}
