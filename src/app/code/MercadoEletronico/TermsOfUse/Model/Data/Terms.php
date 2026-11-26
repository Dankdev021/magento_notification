<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use MercadoEletronico\TermsOfUse\Api\Data\TermsInterface;

/**
 * DTO imutável na prática: só é preenchido pelo TermsProvider.
 */
class Terms extends AbstractSimpleObject implements TermsInterface
{
    public function isEnabled(): bool
    {
        return (bool) $this->_get(self::ENABLED);
    }

    public function setEnabled(bool $enabled): TermsInterface
    {
        return $this->setData(self::ENABLED, $enabled);
    }

    public function getVersion(): string
    {
        return (string) $this->_get(self::VERSION);
    }

    public function setVersion(string $version): TermsInterface
    {
        return $this->setData(self::VERSION, $version);
    }

    public function getTitle(): string
    {
        return (string) $this->_get(self::TITLE);
    }

    public function setTitle(string $title): TermsInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getContent(): string
    {
        return (string) $this->_get(self::CONTENT);
    }

    public function setContent(string $content): TermsInterface
    {
        return $this->setData(self::CONTENT, $content);
    }

    public function getButtonLabel(): string
    {
        return (string) $this->_get(self::BUTTON_LABEL);
    }

    public function setButtonLabel(string $buttonLabel): TermsInterface
    {
        return $this->setData(self::BUTTON_LABEL, $buttonLabel);
    }
}
