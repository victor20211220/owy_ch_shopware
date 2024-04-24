if (empty($value) || !is_string($value)) {
    if (!empty($data) && is_array($data) && array_key_exists('id', $data) && !empty($data['id'])) {
        $value = $data['id'];
    }

    if (empty($value) && !empty($data) && is_array($data) && array_key_exists('internalId', $data) && !empty($data['internalId'])) {
        $internalId = $data['internalId'];
        $storeLocatorRepository = $this->container->get('acris_store_locator.repository');
        $storeId = $storeLocatorRepository->searchIds((new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria())->addFilter(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter('internalId', $internalId)), $context)->firstId();
        if(!empty($storeId) === true) {
            $value = $storeId;
        }
    }
}

if (empty($value) || !is_string($value)) {
    $value = \Shopware\Core\Framework\Uuid\Uuid::randomHex();
}
$name = 'id';
