<?php declare(strict_types=1);

namespace Ott\Base\Twig;

use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LoadCmsPageExtension extends AbstractExtension
{
    private const DEFAULT_TEMPLATE = '@Storefront/storefront/page/content/detail.html.twig';

    public function __construct(
        Environment $twig,
        SalesChannelCmsPageLoaderInterface $cmsPageLoader,
        RequestStack $requestStack
    )
    {
        $this->twig = $twig;
        $this->cmsPageLoader = $cmsPageLoader;
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('loadCmsPage', [$this, 'loadCmsPage'], ['is_safe' => ['all']]),
        ];
    }

    public function loadCmsPage(
        string $id,
        SalesChannelContext $context,
        string $template = self::DEFAULT_TEMPLATE,
        array $custom = []
    ): string
    {
        $cmsPage = $this->cmsPageLoader->load(
            $this->requestStack->getCurrentRequest(),
            new Criteria([$id]),
            $context
        )->first();

        return $this->twig->render($template, ['cmsPage' => $cmsPage, 'custom' => $custom]);
    }
}
