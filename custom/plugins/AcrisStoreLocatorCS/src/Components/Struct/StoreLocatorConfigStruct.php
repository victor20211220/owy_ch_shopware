<?php declare(strict_types=1);

namespace Acris\StoreLocator\Components\Struct;

use Shopware\Core\Framework\Struct\Struct;

class StoreLocatorConfigStruct extends Struct
{
    private string $checkoutSelection;
    private bool $checkoutSelectionRequired;

    public function __construct(
        string $checkoutSelection,
        bool $checkoutSelectionRequired)
    {
        $this->checkoutSelection = $checkoutSelection;
        $this->checkoutSelectionRequired = $checkoutSelectionRequired;
    }

    /**
     * @return string
     */
    public function getCheckoutSelection(): string
    {
        return $this->checkoutSelection;
    }

    /**
     * @param string $checkoutSelection
     */
    public function setCheckoutSelection(string $checkoutSelection): void
    {
        $this->checkoutSelection = $checkoutSelection;
    }

    /**
     * @return bool
     */
    public function isCheckoutSelectionRequired(): bool
    {
        return $this->checkoutSelectionRequired;
    }

    /**
     * @param bool $checkoutSelectionRequired
     */
    public function setCheckoutSelectionRequired(bool $checkoutSelectionRequired): void
    {
        $this->checkoutSelectionRequired = $checkoutSelectionRequired;
    }
}
