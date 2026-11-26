<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Api;

use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface;

/**
 * Regras de negócio do aceite dos Termos de Uso.
 *
 * Exposto via REST (etc/webapi.xml); qualquer outra "porta" (GraphQL, CLI,
 * controller) deve reutilizar este serviço.
 *
 * @api
 */
interface TermsAcceptanceManagementInterface
{
    /**
     * Registra o aceite da versão vigente. Idempotente: se o cliente já
     * aceitou essa versão, devolve o registro existente.
     *
     * @param int $customerId
     * @return \MercadoEletronico\TermsOfUse\Api\Data\AcceptanceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function accept(int $customerId): AcceptanceInterface;

    /**
     * O cliente já aceitou a versão vigente?
     *
     * @param int $customerId
     * @return bool
     */
    public function isAccepted(int $customerId): bool;
}
