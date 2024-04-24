<?php declare(strict_types=1);

namespace Acris\StoreLocator\Custom;

use Acris\StoreLocator\Custom\Aggregate\StoreLocatorRuleDefinition;
use Acris\StoreLocator\Custom\Aggregate\StoreLocatorSalesChannelDefinition;
use Acris\StoreLocator\Custom\Aggregate\StoreLocatorTranslation\StoreLocatorTranslationDefinition;
use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;


class StoreLocatorDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'acris_store_locator';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return StoreLocatorCollection::class;
    }

    public function getEntityClass(): string
    {
        return StoreLocatorEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new StringField('internal_id','internalId'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new FkField('state_id', 'stateId', CountryStateDefinition::class))->addFlags(new ApiAware()),
            (new FkField('cms_page_id', 'cmsPageId', CmsPageDefinition::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(CmsPageDefinition::class))->addFlags(new ApiAware()),
            (new TranslatedField('name'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new TranslatedField('department'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new ManyToOneAssociationField('country', 'country_id', CountryDefinition::class))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new ManyToOneAssociationField('state', 'state_id', CountryStateDefinition::class))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new StringField('city','city'))->addFlags(new Required(), new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new StringField('zipcode','zipcode'))->addFlags(new Required(), new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new StringField('street','street'))->addFlags(new Required(), new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new TranslatedField('phone'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new TranslatedField('email'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new TranslatedField('url'))->addFlags(new ApiAware()),
            (new TranslatedField('opening_hours'))->addFlags(new ApiAware()),
            (new TranslatedField('seoUrl'))->addFlags(new ApiAware()),
            (new TranslatedField('metaTitle'))->addFlags(new ApiAware()),
            (new TranslatedField('metaDescription'))->addFlags(new ApiAware()),
            (new TranslatedField('slotConfig'))->addFlags(new ApiAware()),
            (new IntField('priority', 'priority'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new StringField('longitude','longitude'))->addFlags(new ApiAware()),
            (new StringField('latitude','latitude'))->addFlags(new ApiAware()),
            (new StringField('handlerpoints', 'handlerpoints'))->addFlags(new Required()),

            (new ManyToManyAssociationField('salesChannels', SalesChannelDefinition::class, StoreLocatorSalesChannelDefinition::class, 'store_locator_id', 'sales_channel_id'))->addFlags(new ApiAware()),
            (new ManyToManyAssociationField('rules', RuleDefinition::class, StoreLocatorRuleDefinition::class, 'store_locator_id', 'rule_id'))->addFlags(new ApiAware()),

            (new FkField('store_media_id', 'coverId', StoreMediaDefinition::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(StoreMediaDefinition::class))->addFlags(new ApiAware(), new Required()),

            (new FkField('store_group_id', 'storeGroupId', StoreGroupDefinition::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(StoreGroupDefinition::class))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('storeGroup', 'store_group_id', StoreGroupDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('acrisOrderDeliveryStore',OrderDeliveryStoreDefinition::class,'store_id','id'))->addFlags(new CascadeDelete(), new ApiAware()),
            (new ManyToOneAssociationField('cmsPage', 'cms_page_id', CmsPageDefinition::class))->addFlags(new ApiAware()),

            (new OneToManyAssociationField('media', StoreMediaDefinition::class, 'store_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new ManyToOneAssociationField('cover', 'store_media_id', StoreMediaDefinition::class, 'id', true))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(StoreLocatorTranslationDefinition::class, 'acris_store_locator_id'))->addFlags(new ApiAware())

        ]);
    }
}
