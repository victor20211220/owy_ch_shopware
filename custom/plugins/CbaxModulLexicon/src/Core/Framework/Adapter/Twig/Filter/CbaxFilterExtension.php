<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\Core\Framework\Adapter\Twig\Filter;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Routing\RequestTransformer;

use Cbax\ModulLexicon\Components\LexiconReplacer;

class CbaxFilterExtension extends AbstractExtension
{
    const CONFIG_PATH = 'CbaxModulLexicon.config';

    private $config = null;
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly RequestStack $requestStack,
        private readonly LexiconReplacer $lexiconReplacer
    ) {

    }

    public function getFilters(): array
    {
        return [new TwigFilter('cbax_lexicon_replace', [$this, 'getReplaceText'])];
    }

    public function getReplaceText(?string $text): ?string
    {
        if (!is_string($text)) return $text;

        $request = $this->requestStack->getCurrentRequest();

        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $this->config = $this->config ?? $this->systemConfigService->get(self::CONFIG_PATH, $salesChannelId);

        if (!empty($this->config['active']))
        {
            $shopUrl = $request->attributes->get(RequestTransformer::STOREFRONT_URL);
            $context = $salesChannelContext->getContext();

            $newText = $this->lexiconReplacer->getReplaceText($text, $salesChannelId, $shopUrl, $context, $salesChannelContext, $this->config);

            return $newText;
        }

        return $text;
    }

}

