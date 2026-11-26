<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Escaper;
use MercadoEletronico\TermsOfUse\Api\TermsAcceptanceManagementInterface;
use MercadoEletronico\TermsOfUse\Model\Config;
use MercadoEletronico\TermsOfUse\Model\ContentSanitizer;

/**
 * Seção "terms-of-use" do customer-data.
 *
 * O customer-data é carregado via AJAX e fica fora do Full Page Cache, por
 * isso é o lugar correto para decidir, por cliente, se o modal deve aparecer.
 */
class TermsOfUse implements SectionSourceInterface
{
    public const SECTION_NAME = 'terms-of-use';

    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly Config $config,
        private readonly ContentSanitizer $contentSanitizer,
        private readonly TermsAcceptanceManagementInterface $acceptanceManagement,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @inheritdoc
     *
     * A configuração é lida no escopo da store view atual.
     */
    public function getSectionData(): array
    {
        if (!$this->customerSession->isLoggedIn()) {
            return ['show' => false];
        }

        $version = $this->config->getVersion();
        $customerId = (int) $this->customerSession->getCustomerId();

        if (!$this->config->isEnabled() || $this->acceptanceManagement->isAccepted($customerId)) {
            return ['show' => false, 'version' => $version];
        }

        return [
            'show' => true,
            'version' => $version,
            // O template do modal do Magento renderiza título/botão como HTML.
            'title' => $this->escaper->escapeHtml($this->config->getTitle()),
            'button_label' => $this->escaper->escapeHtml($this->config->getButtonLabel()),
            // Somente as tags de formatação permitidas chegam ao modal.
            'content' => $this->contentSanitizer->sanitize($this->config->getContent()),
        ];
    }
}
