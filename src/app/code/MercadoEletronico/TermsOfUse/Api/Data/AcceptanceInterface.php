<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Api\Data;

/**
 * Registro de aceite de uma versão dos Termos de Uso por um cliente.
 *
 * @api
 */
interface AcceptanceInterface
{
    public const ENTITY_ID = 'entity_id';
    public const CUSTOMER_ID = 'customer_id';
    public const TERMS_VERSION = 'terms_version';
    public const STORE_ID = 'store_id';
    public const REMOTE_IP = 'remote_ip';
    public const ACCEPTED_AT = 'accepted_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * @return string|null
     */
    public function getTermsVersion(): ?string;

    /**
     * @param string $version
     * @return $this
     */
    public function setTermsVersion(string $version): self;

    /**
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * @param int|null $storeId
     * @return $this
     */
    public function setStoreId(?int $storeId): self;

    /**
     * @return string|null
     */
    public function getRemoteIp(): ?string;

    /**
     * @param string|null $remoteIp
     * @return $this
     */
    public function setRemoteIp(?string $remoteIp): self;

    /**
     * Data/hora do aceite (preenchida pelo banco).
     *
     * @return string|null
     */
    public function getAcceptedAt(): ?string;
}
