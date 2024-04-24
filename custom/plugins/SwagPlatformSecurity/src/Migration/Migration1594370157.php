<?php declare(strict_types=1);

namespace Swag\Security\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('services-settings')]
class Migration1594370157 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1594370157;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
CREATE TABLE IF NOT EXISTS `swag_security_config` (
  `ticket` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`ticket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
