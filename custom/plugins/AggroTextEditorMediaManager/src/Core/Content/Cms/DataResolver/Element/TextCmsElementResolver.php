<?php

namespace AggroTextEditorMediaManager\Core\Content\Cms\DataResolver\Element;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\TextStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\HtmlSanitizer;

#[Package('content')]
class TextCmsElementResolver extends AbstractCmsElementResolver
{
    public function __construct(private readonly AbstractCmsElementResolver $decorated, private readonly HtmlSanitizer $sanitizer)
    {
    }

    public function getType(): string
    {
        return $this->decorated->getType();
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return $this->decorated->collect($slot, $resolverContext);
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $text = new TextStruct();
        $slot->setData($text);

        $config = $slot->getFieldConfig()->get('content');
        if ($config === null) {
            return;
        }

        $content = null;

        if ($config->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $content = $this->resolveEntityValueToString($resolverContext->getEntity(), $config->getStringValue(), $resolverContext);
        }

        if ($config->isStatic()) {
            if ($resolverContext instanceof EntityResolverContext) {
                $content = (string) $this->resolveEntityValues($resolverContext, $config->getStringValue());
            } else {
                $content = $config->getStringValue();
            }
        }

        if ($content !== null) {
            $text->setContent($this->sanitizer->sanitize($content, ['img' => ['src', 'alt', 'title']]));
        }
    }
}