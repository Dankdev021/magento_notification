<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Test\Unit\CustomerData;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Escaper;
use MercadoEletronico\TermsOfUse\Api\Data\TermsInterface;
use MercadoEletronico\TermsOfUse\Api\TermsAcceptanceManagementInterface;
use MercadoEletronico\TermsOfUse\Api\TermsProviderInterface;
use MercadoEletronico\TermsOfUse\CustomerData\TermsOfUse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TermsOfUseTest extends TestCase
{
    private CustomerSession&MockObject $session;
    private TermsInterface&MockObject $terms;
    private TermsAcceptanceManagementInterface&MockObject $management;
    private TermsOfUse $section;

    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn', 'getCustomerId'])
            ->getMock();

        $this->terms = $this->createMock(TermsInterface::class);
        $this->terms->method('getVersion')->willReturn('2026-11');
        $this->terms->method('getTitle')->willReturn('Nova <política>');
        $this->terms->method('getButtonLabel')->willReturn('Li e aceito');
        $this->terms->method('getContent')->willReturn('<p>Texto</p>');

        $provider = $this->createMock(TermsProviderInterface::class);
        $provider->method('getCurrent')->willReturn($this->terms);

        $this->management = $this->createMock(TermsAcceptanceManagementInterface::class);

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(
            static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES)
        );

        $this->section = new TermsOfUse($this->session, $provider, $this->management, $escaper);
    }

    public function testGuestNeverSeesTheModal(): void
    {
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->management->expects($this->never())->method('isAccepted');

        $this->assertSame(['show' => false], $this->section->getSectionData());
    }

    public function testHiddenWhenNotificationDisabled(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->terms->method('isEnabled')->willReturn(false);

        $this->assertFalse($this->section->getSectionData()['show']);
    }

    public function testHiddenWhenCurrentVersionAlreadyAccepted(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->terms->method('isEnabled')->willReturn(true);
        $this->management->method('isAccepted')->with(7)->willReturn(true);

        $this->assertFalse($this->section->getSectionData()['show']);
    }

    public function testShownWithEscapedTitleWhenPending(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->terms->method('isEnabled')->willReturn(true);
        $this->management->method('isAccepted')->willReturn(false);

        $data = $this->section->getSectionData();

        $this->assertTrue($data['show']);
        $this->assertSame('2026-11', $data['version']);
        $this->assertSame('Nova &lt;política&gt;', $data['title']);
        $this->assertSame('Li e aceito', $data['button_label']);
        $this->assertSame('<p>Texto</p>', $data['content']);
    }
}
