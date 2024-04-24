<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Content\Seo;

use Shopware\Core\Content\Seo\AbstractSeoResolver;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CachedSeoResolver extends AbstractSeoResolver
{
    const STORE_LOCATOR_DEFAULT_ROUTE = '/store-locator';

    private AbstractSeoResolver $decorated;
    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(
        AbstractSeoResolver $decorated,
        SystemConfigService $systemConfigService
    )
    {
        $this->decorated = $decorated;
        $this->systemConfigService = $systemConfigService;
    }

    public function getDecorated(): AbstractSeoResolver
    {
        return $this->decorated;
    }

    public function resolve(string $languageId, string $salesChannelId, string $pathInfo): array
    {
        $routePath = $this->systemConfigService->get('AcrisStoreLocatorCS.config.storeLocatorRoute', $salesChannelId);
        if (empty($routePath)) {
            return $this->decorated->resolve($languageId, $salesChannelId, $pathInfo);
        }

        $routePath = ltrim($routePath, '/');
        $seoPathInfo = ltrim($pathInfo, '/');

        if ($seoPathInfo === $routePath) {
            return ['pathInfo' => self::STORE_LOCATOR_DEFAULT_ROUTE, 'isCanonical' => false];
        }

        return $this->decorated->resolve($languageId, $salesChannelId, $pathInfo);
    }
}
