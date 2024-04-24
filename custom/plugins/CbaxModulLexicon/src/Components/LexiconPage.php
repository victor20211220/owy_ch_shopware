<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Components;

use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Storefront\Page\Page;

class LexiconPage extends Page
{
    /**
     * @var CmsPageEntity|null
     */
    protected $cmsPage;

    public function getCmsPage(): ?CmsPageEntity
    {
        return $this->cmsPage;
    }

    public function setCmsPage(CmsPageEntity $cmsPage): void
    {
        $this->cmsPage = $cmsPage;
    }
}
