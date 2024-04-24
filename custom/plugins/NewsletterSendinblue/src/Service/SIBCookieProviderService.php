<?php

namespace NewsletterSendinblue\Service;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class SIBCookieProviderService implements CookieProviderInterface
{
    public const SIB_COOKIE_NAME = 'sib_cuid';

    private const SIB_COOKIE = [
        'snippet_name' => 'Brevo',
        'snippet_description' => 'Brevo tracker',
        'cookie' => self::SIB_COOKIE_NAME,
        'expiration' => '30',
        'value' => '1',
    ];

    private $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    public function getCookieGroups(): array
    {
        return array_merge(
            $this->originalService->getCookieGroups(),
            [
                self::SIB_COOKIE
            ]
        );
    }
}
