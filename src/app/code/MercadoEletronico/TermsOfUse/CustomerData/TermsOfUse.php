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
use MercadoEletronico\TermsOfUse\Api\TermsProviderInterface;

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
        private readonly TermsProviderInterface $termsProvider,
        private readonly TermsAcceptanceManagementInterface $acceptanceManagement,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getSectionData(): array
    {
        if (!$this->customerSession->isLoggedIn()) {
            return ['show' => false];
        }

        $terms = $this->termsProvider->getCurrent();
        $customerId = (int) $this->customerSession->getCustomerId();

        if (!$terms->isEnabled() || $this->acceptanceManagement->isAccepted($customerId)) {
            return ['show' => false, 'version' => $terms->getVersion()];
        }

        return [
            'show' => true,
            'version' => $terms->getVersion(),
            // O template do modal do Magento renderiza título/botão como HTML.
            'title' => $this->escaper->escapeHtml($terms->getTitle()),
            'button_label' => $this->escaper->escapeHtml($terms->getButtonLabel()),
            // Já sanitizado pelo TermsProvider (somente tags permitidas).
            'content' => $terms->getContent(),
        ];
    }
}
