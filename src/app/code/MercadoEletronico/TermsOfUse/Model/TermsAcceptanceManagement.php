<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\StoreManagerInterface;
use MercadoEletronico\TermsOfUse\Api\AcceptanceRepositoryInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterfaceFactory;
use MercadoEletronico\TermsOfUse\Api\TermsAcceptanceManagementInterface;

class TermsAcceptanceManagement implements TermsAcceptanceManagementInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly AcceptanceRepositoryInterface $acceptanceRepository,
        private readonly AcceptanceInterfaceFactory $acceptanceFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    /**
     * @inheritdoc
     */
    public function accept(int $customerId): AcceptanceInterface
    {
        $storeId = $this->getStoreId();

        if (!$this->config->isEnabled($storeId)) {
            throw new LocalizedException(__('The terms of use notification is disabled.'));
        }

        $version = $this->config->getVersion($storeId);
        $existing = $this->findAcceptance($customerId, $version);
        if ($existing !== null) {
            return $existing;
        }

        $remoteIp = $this->remoteAddress->getRemoteAddress();

        /** @var AcceptanceInterface $acceptance */
        $acceptance = $this->acceptanceFactory->create();
        $acceptance->setCustomerId($customerId)
            ->setTermsVersion($version)
            ->setStoreId($storeId)
            ->setRemoteIp($remoteIp !== false ? (string) $remoteIp : null);

        return $this->acceptanceRepository->save($acceptance);
    }

    /**
     * @inheritdoc
     */
    public function isAccepted(int $customerId): bool
    {
        $version = $this->config->getVersion($this->getStoreId());

        return $version !== '' && $this->findAcceptance($customerId, $version) !== null;
    }

    private function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    private function findAcceptance(int $customerId, string $version): ?AcceptanceInterface
    {
        try {
            return $this->acceptanceRepository->getByCustomerAndVersion($customerId, $version);
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
