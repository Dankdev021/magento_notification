<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model;

use Magento\Framework\Model\AbstractModel;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;
use MercadoEletronico\TermsOfUse\Model\ResourceModel\Acceptance as AcceptanceResource;

class Acceptance extends AbstractModel implements AcceptanceInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'mercadoeletronico_terms_of_use_acceptance';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(AcceptanceResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        $id = parent::getId();

        return $id === null ? null : (int) $id;
    }

    public function getCustomerId(): ?int
    {
        $value = $this->getData(self::CUSTOMER_ID);

        return $value === null ? null : (int) $value;
    }

    public function setCustomerId(int $customerId): AcceptanceInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getTermsVersion(): ?string
    {
        $value = $this->getData(self::TERMS_VERSION);

        return $value === null ? null : (string) $value;
    }

    public function setTermsVersion(string $version): AcceptanceInterface
    {
        return $this->setData(self::TERMS_VERSION, $version);
    }

    public function getStoreId(): ?int
    {
        $value = $this->getData(self::STORE_ID);

        return $value === null ? null : (int) $value;
    }

    public function setStoreId(?int $storeId): AcceptanceInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getRemoteIp(): ?string
    {
        $value = $this->getData(self::REMOTE_IP);

        return $value === null ? null : (string) $value;
    }

    public function setRemoteIp(?string $remoteIp): AcceptanceInterface
    {
        return $this->setData(self::REMOTE_IP, $remoteIp);
    }

    public function getAcceptedAt(): ?string
    {
        $value = $this->getData(self::ACCEPTED_AT);

        return $value === null ? null : (string) $value;
    }
}
