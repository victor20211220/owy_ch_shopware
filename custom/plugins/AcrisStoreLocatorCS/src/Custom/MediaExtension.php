<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MediaExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'acrisStoreGroups',
                StoreGroupDefinition::class,
                'media_id',
                'id')
            )->addFlags(new CascadeDelete(), new ApiAware())
        );

        $collection->add(
            (new OneToManyAssociationField(
                'acrisStoreGroupIcons',
                StoreGroupDefinition::class,
                'icon_id',
                'id')
            )->addFlags(new CascadeDelete(), new ApiAware())
        );

        $collection->add(
            (new OneToManyAssociationField(
                'storeMedia',
                StoreMediaDefinition::class,
                'media_id',
                'id')
            )->addFlags(new CascadeDelete(), new ApiAware())
        );

    }

    public function getDefinitionClass(): string
    {
        return MediaDefinition::class;
    }
}
