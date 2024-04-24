<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SystemConfig\SystemConfigService;

use Cbax\ModulLexicon\Components\LexiconReplacer;

class FrontendSubscriber implements EventSubscriberInterface
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';

    private $config = null;

    public function __construct(
        private readonly LexiconReplacer $lexiconReplacer,
        private readonly SystemConfigService $systemConfigService
    ) {

    }

	public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            MinimalQuickViewPageLoadedEvent::class => 'onQuickViewPageLoaded'
        ];
    }

	public function onProductPageLoaded(ProductPageLoadedEvent $event)
    {
        $request = $event->getRequest();
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->systemConfigService->get(self::CONFIG_PATH, $salesChannelId);

		if (!empty($this->config['active']))
		{
			if (!empty($this->config['activeArticle']) || !empty($this->config['activeProperties']))
			{
                $shopUrl = $request->attributes->get(RequestTransformer::STOREFRONT_URL);
                $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT)->getContext();

				$page = $event->getPage();
                if (empty($page)) return;
                $cmsPage = $page->getCmsPage();
                $product = $page->getProduct();
                if (empty($product)) return;

                if (!empty($cmsPage)) {
                    $productDescriptionReviewElement = $cmsPage->getFirstElementOfType('product-description-reviews');
                    if (!empty($productDescriptionReviewElement) && !empty($productDescriptionReviewElement->getData()))
                    {
                        $cmsProduct = $productDescriptionReviewElement->getData()->getProduct();
                    }
                }

				if (count($this->config['activeProperties']) > 0) {
					foreach ($product->get('sortedProperties')->getElements() as $propertyGroup) {
						if (in_array('name', $this->config['activeProperties'])) {
							$translatedGroup = $propertyGroup->getTranslated();
							$translatedGroup['name'] = $this->lexiconReplacer->getReplaceText($translatedGroup['name'], $salesChannelId, $shopUrl, $context, $salesChannelContext, $this->config);
							$propertyGroup->setTranslated($translatedGroup);
						}
						foreach ($propertyGroup->getOptions()->getElements() as $optionId => $option) {
							if (in_array('value', $this->config['activeProperties'])) {
								$translatedOption = $option->getTranslated();
								$translatedOption['name'] = $this->lexiconReplacer->getReplaceText($translatedOption['name'], $salesChannelId, $shopUrl, $context, $salesChannelContext, $this->config);
								$propertyGroup->getOptions()->getElements()[$optionId]->setTranslated($translatedOption);
							}
						}
					}
				}

				if (!empty($this->config['activeArticle']) || !empty($this->config['activeProductCustomFields'])) {
					$productTranslated = $product->getTranslated();

                    if (!empty($this->config['activeArticle'])) {
                        $productTranslated['description'] = $this->lexiconReplacer->getReplaceText($productTranslated['description'], $salesChannelId, $shopUrl, $context, $salesChannelContext, $this->config);
                    }

                    if (!empty($this->config['activeProductCustomFields']) && !empty($productTranslated['customFields'])) {
                        foreach ($this->config['activeProductCustomFields'] as $field) {
                            if (!empty($productTranslated['customFields'][$field])) {
                                $productTranslated['customFields'][$field] = $this->lexiconReplacer->getReplaceText($productTranslated['customFields'][$field], $salesChannelId, $shopUrl, $context, $salesChannelContext, $this->config);
                            }
                        }
                    }

					$product->assign(['translated' => $productTranslated]);

                    if (!empty($cmsProduct)) {
                        $cmsProduct->assign(['translated' => $productTranslated]);
                    }
				}
			}
		}
    }

    public function onQuickViewPageLoaded (MinimalQuickViewPageLoadedEvent $event)
	{
        $request = $event->getRequest();
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->systemConfigService->get(self::CONFIG_PATH, $salesChannelId);

		if (!empty($this->config['active']) && !empty($this->config['activeArticle']))
		{
            $shopUrl = $request->attributes->get(RequestTransformer::STOREFRONT_URL);

            $page = $event->getPage();
            if (empty($page)) return;

            $product = $page->getProduct();
            if (empty($product)) return;

            $productTranslated = $product->getTranslated();
            $productTranslated['description'] = $this->lexiconReplacer->getReplaceText($productTranslated['description'], $salesChannelId, $shopUrl, $salesChannelContext->getContext(), $salesChannelContext, $this->config);

            $product->assign(['translated' => $productTranslated]);
		}
    }
}
