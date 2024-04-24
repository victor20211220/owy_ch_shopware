<?php declare(strict_types=1);

namespace Acris\StoreLocator\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class StoreLocatorCookieProvider implements CookieProviderInterface {

    /**
     * @var CookieProviderInterface
     */
    private $cookieProviderInterface;

    function __construct(CookieProviderInterface $cookieProviderInterface)
    {
        $this->cookieProviderInterface = $cookieProviderInterface;
    }

    private const storeLocatorCookie = [
        'snippet_name' => 'acrisStoreLocator.cookie.name',
        'snippet_description' => 'acrisStoreLocator.cookie.description',
        'cookie' => 'store-locator-cookie',
        'value'=> 'true',
        'expiration' => '30'
    ];

    public function getCookieGroups(): array
    {
        return array_merge(
            $this->cookieProviderInterface->getCookieGroups(),
            [
                self::storeLocatorCookie
            ]
        );
    }
}
