<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT32887;

use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NEXT32887Fix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-32887';
    }

    public static function getMinVersion(): string
    {
        return '6.3.0.0';
    }

    public static function getMaxVersion(): string
    {
        return '6.5.7.4';
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $container
            ->getDefinition(RequestCriteriaBuilder::class)
            ->setClass(RequestCriteriaBuilderFixed::class);

        $container
            ->getDefinition('api.request_criteria_builder')
            ->setClass(RequestCriteriaBuilderFixed::class);
    }
}
