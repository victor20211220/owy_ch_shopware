<?php

namespace NewsletterSendinblue\Service;

use NewsletterSendinblue\NewsletterSendinblue;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\PluginEntity;

class VersionProvider
{
    /** @var string */
    private $shopwareVersion;

    /** @var EntityRepository */
    private $entityRepository;

    const SHOPWARE_VERSION_NUMBER = '6.4';

    public function __construct(string $shopwareVersion, EntityRepository $entityRepository)
    {
        $this->shopwareVersion = $shopwareVersion;
        $this->entityRepository = $entityRepository;
    }

    public function getShopwareVersion(): string
    {
        return $this->shopwareVersion;
    }

    public function getFormattedShopwareVersion(): string
    {
        return sprintf('Shopware %s', $this->shopwareVersion);
    }

    public function getPluginVersion(): string
    {
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new ContainsFilter('name', NewsletterSendinblue::PLUGIN_LABEL));

            $plugins = $this->entityRepository->search($criteria, Context::createDefaultContext());

            /** @var PluginEntity $plugin */
            $plugin = $plugins->first();

            return $plugin->get('version');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function checkShopwareComptability(): bool
    {
        if ($this->getShopwareVersion() >= self::SHOPWARE_VERSION_NUMBER) {
            return true;
        }
        return false;
    }
}
