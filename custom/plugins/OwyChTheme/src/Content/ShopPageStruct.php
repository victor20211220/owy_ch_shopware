<?php declare(strict_types=1);

namespace OwyChTheme\Content;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Struct\Struct;

class ShopPageStruct extends Struct
{
    /**
     * @var CategoryCollection|null
     */
    protected $categories;

    public function getCategories(): ?CategoryCollection
    {
        return $this->categories;
    }

    public function setCategories(CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }
}
