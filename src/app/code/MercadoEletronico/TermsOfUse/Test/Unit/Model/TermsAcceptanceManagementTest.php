<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MercadoEletronico\TermsOfUse\Api\AcceptanceRepositoryInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterfaceFactory;
use MercadoEletronico\TermsOfUse\Api\Data\TermsInterface;
use MercadoEletronico\TermsOfUse\Api\TermsProviderInterface;
use MercadoEletronico\TermsOfUse\Model\TermsAcceptanceManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TermsAcceptanceManagementTest extends TestCase
{
    private const CUSTOMER_ID = 42;
    private const VERSION = '2026-11';
    private const STORE_ID = 1;
    private const IP = '203.0.113.10';

    private TermsInterface&MockObject $terms;
    private AcceptanceRepositoryInterface&MockObject $repository;
    private AcceptanceInterfaceFactory&MockObject $acceptanceFactory;
    private RemoteAddress&MockObject $remoteAddress;
    private TermsAcceptanceManagement $management;

    protected function setUp(): void
    {
        $this->terms = $this->createMock(TermsInterface::class);
        $this->terms->method('getVersion')->willReturn(self::VERSION);

        $termsProvider = $this->createMock(TermsProviderInterface::class);
        $termsProvider->method('getCurrent')->willReturn($this->terms);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->repository = $this->createMock(AcceptanceRepositoryInterface::class);
        $this->acceptanceFactory = $this->getMockBuilder(AcceptanceInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->remoteAddress = $this->createMock(RemoteAddress::class);

        $this->management = new TermsAcceptanceManagement(
            $termsProvider,
            $this->repository,
            $this->acceptanceFactory,
            $storeManager,
            $this->remoteAddress
        );
    }

    public function testAcceptThrowsWhenNotificationIsDisabled(): void
    {
        $this->terms->method('isEnabled')->willReturn(false);
        $this->repository->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);

        $this->management->accept(self::CUSTOMER_ID);
    }

    public function testAcceptIsIdempotentWhenVersionWasAlreadyAccepted(): void
    {
        $this->terms->method('isEnabled')->willReturn(true);
        $existing = $this->createMock(AcceptanceInterface::class);
        $this->repository->method('getByCustomerAndVersion')
            ->with(self::CUSTOMER_ID, self::VERSION)
            ->willReturn($existing);

        $this->acceptanceFactory->expects($this->never())->method('create');
        $this->repository->expects($this->never())->method('save');

        $this->assertSame($existing, $this->management->accept(self::CUSTOMER_ID));
    }

    public function testAcceptPersistsCustomerVersionStoreAndIp(): void
    {
        $this->terms->method('isEnabled')->willReturn(true);
        $this->repository->method('getByCustomerAndVersion')
            ->willThrowException(new NoSuchEntityException());
        $this->remoteAddress->method('getRemoteAddress')->willReturn(self::IP);

        $acceptance = $this->createMock(AcceptanceInterface::class);
        $acceptance->expects($this->once())->method('setCustomerId')->with(self::CUSTOMER_ID)->willReturnSelf();
        $acceptance->expects($this->once())->method('setTermsVersion')->with(self::VERSION)->willReturnSelf();
        $acceptance->expects($this->once())->method('setStoreId')->with(self::STORE_ID)->willReturnSelf();
        $acceptance->expects($this->once())->method('setRemoteIp')->with(self::IP)->willReturnSelf();
        $this->acceptanceFactory->method('create')->willReturn($acceptance);

        $saved = $this->createMock(AcceptanceInterface::class);
        $this->repository->expects($this->once())->method('save')->with($acceptance)->willReturn($saved);

        $this->assertSame($saved, $this->management->accept(self::CUSTOMER_ID));
    }

    public function testAcceptStoresNullIpWhenUnavailable(): void
    {
        $this->terms->method('isEnabled')->willReturn(true);
        $this->repository->method('getByCustomerAndVersion')
            ->willThrowException(new NoSuchEntityException());
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);

        $acceptance = $this->createMock(AcceptanceInterface::class);
        $acceptance->method('setCustomerId')->willReturnSelf();
        $acceptance->method('setTermsVersion')->willReturnSelf();
        $acceptance->method('setStoreId')->willReturnSelf();
        $acceptance->expects($this->once())->method('setRemoteIp')->with(null)->willReturnSelf();
        $this->acceptanceFactory->method('create')->willReturn($acceptance);
        $this->repository->method('save')->willReturn($acceptance);

        $this->management->accept(self::CUSTOMER_ID);
    }

    public function testIsAcceptedReturnsTrueWhenRecordExists(): void
    {
        $this->repository->method('getByCustomerAndVersion')
            ->with(self::CUSTOMER_ID, self::VERSION)
            ->willReturn($this->createMock(AcceptanceInterface::class));

        $this->assertTrue($this->management->isAccepted(self::CUSTOMER_ID));
    }

    public function testIsAcceptedReturnsFalseWhenNoRecord(): void
    {
        $this->repository->method('getByCustomerAndVersion')
            ->willThrowException(new NoSuchEntityException());

        $this->assertFalse($this->management->isAccepted(self::CUSTOMER_ID));
    }
}
