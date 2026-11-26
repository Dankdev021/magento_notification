<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterfaceFactory;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceSearchResultsInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceSearchResultsInterfaceFactory;
use MercadoEletronico\TermsOfUse\Model\AcceptanceRepository;
use MercadoEletronico\TermsOfUse\Model\AcceptanceSearchResults;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance as AcceptanceResource;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance\Collection;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance\CollectionFactory;
use PHPUnit\Framework\TestCase;

class AcceptanceRepositoryTest extends TestCase
{
    /**
     * A preference do di.xml precisa implementar a interface usada como tipo
     * de retorno do getList(); senão a rota de auditoria quebra com TypeError.
     */
    public function testSearchResultsPreferenceImplementsInterface(): void
    {
        $di = simplexml_load_file(__DIR__ . '/../../../etc/di.xml');
        $preference = $di->xpath(sprintf(
            '//preference[@for="%s"]',
            AcceptanceSearchResultsInterface::class
        ));

        $this->assertCount(1, $preference);
        $type = (string) $preference[0]['type'];
        $this->assertTrue(
            is_subclass_of($type, AcceptanceSearchResultsInterface::class),
            sprintf('%s must implement %s', $type, AcceptanceSearchResultsInterface::class)
        );
    }

    public function testGetListReturnsItemsAndTotal(): void
    {
        $item = $this->createMock(AcceptanceInterface::class);
        $criteria = $this->createMock(SearchCriteriaInterface::class);

        $collection = $this->createMock(Collection::class);
        $collection->method('getItems')->willReturn([$item]);
        $collection->method('getSize')->willReturn(1);

        $collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $collectionFactory->method('create')->willReturn($collection);

        $searchResultsFactory = $this->getMockBuilder(AcceptanceSearchResultsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $searchResultsFactory->method('create')->willReturn(new AcceptanceSearchResults());

        $processor = $this->createMock(CollectionProcessorInterface::class);
        $processor->expects($this->once())->method('process')->with($criteria, $collection);

        $repository = new AcceptanceRepository(
            $this->createMock(AcceptanceResource::class),
            $this->getMockBuilder(AcceptanceInterfaceFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMock(),
            $collectionFactory,
            $searchResultsFactory,
            $processor
        );

        $result = $repository->getList($criteria);

        $this->assertSame([$item], $result->getItems());
        $this->assertSame(1, $result->getTotalCount());
        $this->assertSame($criteria, $result->getSearchCriteria());
    }
}
