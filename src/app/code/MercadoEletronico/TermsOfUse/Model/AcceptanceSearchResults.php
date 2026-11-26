<?php
/**
 * Copyright © Mercado Eletrônico. All rights reserved.
 */
declare(strict_types=1);

namespace MercadoEletronico\TermsOfUse\Model;

use Magento\Framework\Api\SearchResults;
use MercadoEletronico\TermsOfUse\Api\Data\AcceptanceSearchResultsInterface;

/**
 * Resultado de busca dos aceites.
 *
 * Precisa implementar a interface específica: o AcceptanceRepository::getList()
 * declara AcceptanceSearchResultsInterface como retorno, e o SearchResults
 * genérico do framework não a implementa (TypeError na rota de auditoria).
 */
class AcceptanceSearchResults extends SearchResults implements AcceptanceSearchResultsInterface
{
}
