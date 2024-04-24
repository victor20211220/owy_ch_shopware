<?php declare(strict_types=1);

namespace NetzpSearchAdvanced6\Components;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface SearchInterface
{
    public function doSearch(string $query, SalesChannelContext $salesChannelContext, bool $isSuggest = false): array;
}
