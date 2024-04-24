<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CmsPageExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField('acrisStoreLocator',
                StoreLocatorDefinition::class, 'cms_page_id'))
                ->addFlags(new CascadeDelete(), new ApiAware(), new Computed())
        );
    }

    public function getDefinitionClass(): string
    {
        return CmsPageDefinition::class;
    }
}
