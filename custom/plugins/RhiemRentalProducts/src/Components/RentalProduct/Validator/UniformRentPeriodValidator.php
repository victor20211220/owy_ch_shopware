<?php

declare(strict_types=1);

namespace Rhiem\RhiemRentalProducts\Components\RentalProduct\Validator;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Rhiem\RhiemRentalProducts\Components\Cart\CartHelper;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Rhiem\RhiemRentalProducts\Components\RentalTime\RentalTime;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\RentalProductModeInterface;
use Rhiem\RhiemRentalProducts\Components\RentalProduct\Exception\RentalProductRentPeriodInvalidException;

class UniformRentPeriodValidator extends Validator implements RentalProductModeInterface
{
    private readonly SystemConfigService $systemConfigService;

    private readonly CartHelper $cartHelper;

    public function __construct(
        SystemConfigService $systemConfigService,
        CartHelper $cartHelper
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->cartHelper = $cartHelper;
    }

    /**
     * @throws RentalProductRentPeriodInvalidException
     */
    public function validate(array $params): void
    {
        $uniformRentalPeriods = $this->systemConfigService->get('RhiemRentalProducts.config.uniformRentalPeriods');

        if (!$uniformRentalPeriods) return;

        /** @var LineItem $lineItem */
        $lineItem = $params['lineItem'];

        /** @var Cart $cart */
        $cart = $params['cart'];

        $currentRentalTime = $this->cartHelper->getCurrentRentalTime($cart, $lineItem->getId());

        if(!$currentRentalTime instanceof RentalTime) return;

        if (!$this->rentalPeriodValid($currentRentalTime, $this->getRentalTime($lineItem))) {
            throw new RentalProductRentPeriodInvalidException(
                $lineItem->getReferencedId(),
                $lineItem->getLabel(),
                $this->getRentalTime($lineItem)
            );
        }
    }

    protected function rentalPeriodValid(RentalTime $currentRentalTime, RentalTime $newRentalTime): bool
    {
        if (
            $currentRentalTime->getStartDate()->format('Y-m-d') !== $newRentalTime->getStartDate()->format('Y-m-d') ||
            $currentRentalTime->getEndDate()->format('Y-m-d') !== $newRentalTime->getEndDate()->format('Y-m-d')
        ) {
            return false;
        }

        return true;
    }

    protected function getRentalTime(LineItem $lineItem): RentalTime
    {
        return $lineItem->getPayload()['rentalProduct']['rentalTime'];
    }
}
