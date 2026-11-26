<?php
/**
 * Cria (se ainda não existir) um cliente de teste para validar o fluxo de
 * login na loja. Credenciais vêm das variáveis MAGENTO_CUSTOMER_*.
 */
declare(strict_types=1);

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

require '/var/www/html/app/bootstrap.php';

$email = getenv('MAGENTO_CUSTOMER_EMAIL') ?: 'cliente@example.com';
$password = getenv('MAGENTO_CUSTOMER_PASSWORD') ?: 'Cliente@12345';

$objectManager = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$objectManager->get(State::class)->setAreaCode(Area::AREA_FRONTEND);

$store = $objectManager->get(StoreManagerInterface::class)->getDefaultStoreView();
$websiteId = (int) $store->getWebsiteId();

try {
    $objectManager->get(CustomerRepositoryInterface::class)->get($email, $websiteId);
    echo "Cliente {$email} já existe.\n";
    exit(0);
} catch (NoSuchEntityException) {
    // segue para a criação
}

$customer = $objectManager->get(CustomerInterfaceFactory::class)->create()
    ->setWebsiteId($websiteId)
    ->setStoreId((int) $store->getId())
    ->setGroupId(1)
    ->setFirstname('Cliente')
    ->setLastname('Teste')
    ->setEmail($email);

$objectManager->get(AccountManagementInterface::class)->createAccount($customer, $password);
echo "Cliente {$email} criado.\n";
