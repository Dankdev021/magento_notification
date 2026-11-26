<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Test\Unit\CustomerData;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Escaper;
use MercadoEletronico\TermsOfUse\Api\TermsAcceptanceManagementInterface;
use MercadoEletronico\TermsOfUse\CustomerData\TermsOfUse;
use MercadoEletronico\TermsOfUse\Model\Config;
use MercadoEletronico\TermsOfUse\Model\ContentSanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TermsOfUseTest extends TestCase
{
    private CustomerSession&MockObject $session;
    private Config&MockObject $config;
    private TermsAcceptanceManagementInterface&MockObject $management;
    private TermsOfUse $section;

    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn', 'getCustomerId'])
            ->getMock();

        $this->config = $this->createMock(Config::class);
        $this->config->method('getVersion')->willReturn('2026-11');
        $this->config->method('getTitle')->willReturn('Nova <política>');
        $this->config->method('getButtonLabel')->willReturn('Li e aceito');
        $this->config->method('getContent')->willReturn('<p>Texto</p><script>alert(1)</script>');

        $sanitizer = $this->createMock(ContentSanitizer::class);
        $sanitizer->method('sanitize')->willReturnCallback(
            static fn (string $html) => strip_tags($html, '<p>')
        );

        $this->management = $this->createMock(TermsAcceptanceManagementInterface::class);

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(
            static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES)
        );

        $this->section = new TermsOfUse($this->session, $this->config, $sanitizer, $this->management, $escaper);
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
        $this->config->method('isEnabled')->willReturn(false);

        $this->assertFalse($this->section->getSectionData()['show']);
    }

    public function testHiddenWhenCurrentVersionAlreadyAccepted(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->config->method('isEnabled')->willReturn(true);
        $this->management->method('isAccepted')->with(7)->willReturn(true);

        $this->assertFalse($this->section->getSectionData()['show']);
    }

    public function testShownWithEscapedTitleAndSanitizedContentWhenPending(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getCustomerId')->willReturn(7);
        $this->config->method('isEnabled')->willReturn(true);
        $this->management->method('isAccepted')->willReturn(false);

        $data = $this->section->getSectionData();

        $this->assertTrue($data['show']);
        $this->assertSame('2026-11', $data['version']);
        $this->assertSame('Nova &lt;política&gt;', $data['title']);
        $this->assertSame('Li e aceito', $data['button_label']);
        $this->assertSame('<p>Texto</p>alert(1)', $data['content']);
    }
}
