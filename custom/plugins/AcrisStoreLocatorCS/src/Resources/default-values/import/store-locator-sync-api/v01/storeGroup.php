// initiate services
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;

$valueExists = true;
$filters = [];

$valueId = null;
$valueName = null;
$valueInternalId = null;

if (!empty($data) && is_array($data) && array_key_exists('storeGroupId', $data) && !empty($data['storeGroupId'])) {
    $value = $data['storeGroupId'];
    $name = 'storeGroupId';
    return;
}

if (empty($value) || !is_array($value) || (!array_key_exists('id', $value) && !array_key_exists('internalId', $value) && !array_key_exists('name', $value))) {
    $valueName = 'Standard';
    $valueExists = false;
}

if (!empty($value) && is_array($value) && array_key_exists('id', $value) && !empty($value['id'])) {
    $valueId = $value['id'];
}

if (!empty($value) && empty($valueId) && is_array($value) && array_key_exists('internalId', $value) && !empty($value['internalId'])) {
    $valueInternalId = $value['internalId'];
}

if (!empty($value) && empty($valueInternalId) && is_array($value) && array_key_exists('name', $value) && !empty($value['name'])) {
    $valueName = $value['name'];
}

if (!empty($valueId)) $filters[] = new EqualsFilter('id', $valueId);
if (!empty($valueName)) $filters[] = new EqualsFilter('name', $valueName);
if (!empty($valueInternalId)) $filters[] = new EqualsFilter('internalId', $valueInternalId);

if (empty($filters)) {
    throw new \Exception("The store group was not found!");
}

$criteria = (new Criteria());

$filterConditions = new MultiFilter(MultiFilter::CONNECTION_OR, $filters);

if (!$valueExists) {
    $filterConditions = new OrFilter([new EqualsFilter('default', true), $filterConditions]);
}

$criteria->addFilter($filterConditions);

$storeGroupRepository = $this->container->get("acris_store_group.repository");
$searchResult = $storeGroupRepository->searchIds($criteria, $context);

$groupId = $searchResult->firstId();

if (empty($groupId)) {
    $newValueName = 'Standard';
    $newCriteria = (new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
        new EqualsFilter('name', $valueName),
        new EqualsFilter('default', true)
    ]));

    $searchResult = $storeGroupRepository->searchIds($newCriteria, $context);
    $groupId = $searchResult->firstId();

    if (empty($groupId)) {
        throw new \Exception("The store group was not found!");
    }
}

$value = $groupId;
$name = 'storeGroupId';
