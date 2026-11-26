<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Api;

use MercadoEletronico\TermsOfUse\Api\Data\TermsInterface;

/**
 * Fornece os Termos de Uso vigentes.
 *
 * A implementação padrão lê da configuração do admin. Para outra origem
 * (ex.: um cadastro de versões em tabela), basta trocar a preferência no di.xml.
 *
 * @api
 */
interface TermsProviderInterface
{
    /**
     * Termos vigentes da store view atual.
     *
     * @return \MercadoEletronico\TermsOfUse\Api\Data\TermsInterface
     */
    public function getCurrent(): TermsInterface;
}
