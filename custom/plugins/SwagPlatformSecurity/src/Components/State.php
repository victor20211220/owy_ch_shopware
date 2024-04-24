<?php declare(strict_types=1);

namespace Swag\Security\Components;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class State
{
    final public const CONFIG_PREFIX = 'SwagPlatformSecurity.config.';

    /**
     * @param class-string<AbstractSecurityFix>[] $availableFixes
     * @param class-string<AbstractSecurityFix>[] $activeFixes
     */
    public function __construct(
        private readonly array $availableFixes,
        private readonly array $activeFixes
    ) {
    }

    /**
     * @return class-string<AbstractSecurityFix>[]
     */
    public function getActiveFixes(): array
    {
        return $this->activeFixes;
    }

    /**
     * @return class-string<AbstractSecurityFix>[]
     */
    public function getAvailableFixes(): array
    {
        return $this->availableFixes;
    }

    public function isActive(string $ticket): bool
    {
        foreach ($this->getActiveFixes() as $validFix) {
            if ($validFix::getTicket() === $ticket) {
                return true;
            }
        }

        return false;
    }
}
