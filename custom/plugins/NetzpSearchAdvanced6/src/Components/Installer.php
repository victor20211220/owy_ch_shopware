<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Components;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Installer
{
    public function __construct(private readonly ContainerInterface $container) {
    }

    public function install()
    {
    }

    public function activate()
    {
        $defaultContext = new Context(new SystemSource());

        $repo = $this->container->get('plugin.repository');
        $plugin = $repo->search(
            (new Criteria())->addFilter(new EqualsFilter('name', 'NetzpSearchAdvanced6')),
            $defaultContext
        )->first();

        if($plugin)
        {
            $repo->update(
                [
                    [ 'id' => $plugin->getId(), 'installedAt' => (new \DateTime('2020-01-01'))->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
                ],
                $defaultContext
            );
        }
    }
}
