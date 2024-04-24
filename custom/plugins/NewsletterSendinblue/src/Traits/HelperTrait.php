<?php

namespace NewsletterSendinblue\Traits;

use NewsletterSendinblue\Service\ConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

trait HelperTrait
{

    /**
     * @param string|null $userConnectionId
     * @return string|null
     */
    protected function getSalesChannelIdByConnectionId(?string $userConnectionId = null): ?string
    {
        if (empty($userConnectionId)) {
            return null;
        }
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('configurationKey', ConfigService::CONFIG_PREFIX . ConfigService::CONFIG_USER_CONNECTION_ID));
        $criteria->addFilter(new ContainsFilter('configurationValue', $userConnectionId));
        $systemConfigEntity = $this->systemConfigRepository->search($criteria, Context::createDefaultContext())->last();
        if (empty($systemConfigEntity)) {
            return null;
        }
        return $systemConfigEntity->getSalesChannelId();
    }
}
