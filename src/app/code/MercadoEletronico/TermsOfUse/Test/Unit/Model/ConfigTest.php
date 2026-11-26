<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoEletronico\TermsOfUse\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testIsEnabledWhenFlagOnAndVersionDefined(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);
        $this->scopeConfig->method('getValue')->with(Config::XML_PATH_VERSION)->willReturn('2026-11');

        $this->assertTrue($this->config->isEnabled(1));
    }

    public function testIsDisabledWhenFlagOff(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(false);
        $this->scopeConfig->method('getValue')->willReturn('2026-11');

        $this->assertFalse($this->config->isEnabled(1));
    }

    public function testIsDisabledWhenVersionIsBlank(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);
        $this->scopeConfig->method('getValue')->willReturn('   ');

        $this->assertFalse($this->config->isEnabled(1));
    }

    public function testValuesAreTrimmed(): void
    {
        $this->scopeConfig->method('getValue')->willReturn("  Li e aceito \n");

        $this->assertSame('Li e aceito', $this->config->getButtonLabel());
    }
}
