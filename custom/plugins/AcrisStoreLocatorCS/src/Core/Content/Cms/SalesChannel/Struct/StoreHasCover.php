<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\Cms\SalesChannel\Struct;

use Shopware\Core\Framework\Struct\Struct;

class StoreHasCover extends Struct
{
    private bool $hasCover;

    public function __construct()
    {
        $this->hasCover = true;
    }

    /**
     * @return bool
     */
    public function hasCover(): bool
    {
        return $this->hasCover;
    }
}
