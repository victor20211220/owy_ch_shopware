<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts;

use Doctrine\DBAL\Connection;
use Rhiem\RhiemRentalProducts\Installer\PayloadUpdater;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class RhiemRentalProducts extends Plugin
{
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->deactivateCustomfields($deactivateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->activateCustomfields($activateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeTables();
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        if (version_compare($updateContext->getCurrentPluginVersion(), '1.2.2', '<')) {
            /** @var Connection $connection */
            $connection = $this->container->get(Connection::class);
            $payloadUpdater = new PayloadUpdater($connection, $this->container);
            $payloadUpdater->update();
        }

        if (version_compare($updateContext->getCurrentPluginVersion(), '2.1.0', '<')) {
            /** @var Connection $connection */
            $connection = $this->container->get(Connection::class);
            $payloadUpdater = new PayloadUpdater($connection, $this->container);
            $payloadUpdater->removeSerializedRentalTimes();
        }

        parent::postUpdate($updateContext);
    }

    private function removeTables(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $connection->executeUpdate('DROP TABLE IF EXISTS `rental_product_price`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `rental_product_deposit_price`');
        $connection->executeUpdate('DROP TABLE IF EXISTS `rental_product`');
    }

    private function deactivateCustomfields(DeactivateContext $context): void
    {
        /**
         * Custom Fields should only ever be deactivated because they are used in order documents,emails, etc. which have to be
         * historically accurate and therefore have to be archived instead of deleted
         */
        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        $customFieldRepository->upsert(
            [
                [
                    'id' => '6cd39a8435e3432b92d9e7da7ec4c5b1',
                    'name' => 'rhiem_rental_products_rent_start',
                    'type' => CustomFieldTypes::DATETIME,
                    'active' => false,
                ],
                [
                    'id' => 'e8cf727fda8440139d68d300c6790d7a',
                    'name' => 'rhiem_rental_products_rent_end',
                    'type' => CustomFieldTypes::DATETIME,
                    'active' => false,
                ],
            ],
            $context->getContext()
        );
    }

    private function activateCustomfields(ActivateContext $context): void
    {
        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        $customFieldRepository->upsert(
            [
                [
                    'id' => '6cd39a8435e3432b92d9e7da7ec4c5b1',
                    'name' => 'rhiem_rental_products_rent_start',
                    'type' => CustomFieldTypes::DATETIME,
                    'active' => true,
                ],
                [
                    'id' => 'e8cf727fda8440139d68d300c6790d7a',
                    'name' => 'rhiem_rental_products_rent_end',
                    'type' => CustomFieldTypes::DATETIME,
                    'active' => true,
                ],
            ],
            $context->getContext()
        );
    }
}
