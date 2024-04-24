<?php


namespace OwyChTheme\Struct;


use Shopware\Core\Framework\Struct\Struct;

class NavigationTree extends Struct
{

    private $navigationTree;

    public function __construct($navigationTree)
    {
        $this->navigationTree = $navigationTree;

    }

    public function getNavigationTree()
    {
        return $this->navigationTree;
    }

    public function setNavigationTree($navigationTree): void
    {
        $this->navigationTree = $navigationTree;
    }
}
