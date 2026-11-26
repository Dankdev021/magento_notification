<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Api\Data;

/**
 * Termos de Uso vigentes para uma store view.
 *
 * @api
 */
interface TermsInterface
{
    public const ENABLED = 'enabled';
    public const VERSION = 'version';
    public const TITLE = 'title';
    public const CONTENT = 'content';
    public const BUTTON_LABEL = 'button_label';

    /**
     * A notificação está ativa?
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self;

    /**
     * Identificador da versão vigente (ex.: "2026-11").
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version): self;

    /**
     * Título do modal (texto puro).
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;

    /**
     * Texto dos termos em HTML já sanitizado (apenas tags permitidas).
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): self;

    /**
     * Rótulo do botão de confirmação (texto puro).
     *
     * @return string
     */
    public function getButtonLabel(): string;

    /**
     * @param string $buttonLabel
     * @return $this
     */
    public function setButtonLabel(string $buttonLabel): self;
}
