// initiate services
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;

$stateId = null;

if (!empty($data) && is_array($data) && array_key_exists('stateId', $data) && !empty($data['stateId'])) {
    $value = $data['stateId'];
    $name = 'stateId';
    return;
}

if (!empty($value) && is_array($value)) {
    $countryId = null;

    if (!empty($data) && is_array($data) && array_key_exists('countryId', $data) && !empty($data['countryId'])) {
        $countryId = $data['countryId'];
    }

    $valueCountryName = null;
    $valueCountryIso = null;

    if (empty($countryId) && !empty($data) && is_array($data) && array_key_exists('country', $data) && !empty($data['country']) && is_array($data['country'])) {
        if (array_key_exists('id', $data['country']) && !empty($data['country']['id'])) {
            $countryId = $data['country']['id'];
        }

        if (empty($countryId)) {
            $filters = [];
            if (array_key_exists('name', $data['country']) && !empty($data['country']['name'])) {
                $valueCountryName = $data['country']['name'];
            }

            if (empty($valueCountryName) && array_key_exists('iso', $data['country']) && !empty($data['country']['iso'])) {
                $valueCountryIso = $data['country']['iso'];
            }

            if (!empty($valueCountryName)) $filters[] = new EqualsFilter('name', $valueCountryName);
            if (!empty($valueCountryIso)) $filters[] = new EqualsFilter('iso', $valueCountryIso);

            if (empty($filters)) {
                $value = [];
                $name = 'noState';
                return;
            }

            $countryRepository = $this->container->get("country.repository");

            $countryCriteria = (new Criteria());
            $countryCriteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $filters));
            $searchResult = $countryRepository->searchIds($countryCriteria, $context);
            $countryId = $searchResult->firstId();
        }
    }

    if (empty($countryId)) {
        $value = [];
        $name = 'noState';
        return;
    }

    $criteria = (new Criteria());

    $stateFilters = [];

    $valueStateId = null;
    $valueStateName = null;

    if (array_key_exists('id', $value) && !empty($value['id'])) {
        $valueStateId = $value['id'];
    }

    if (array_key_exists('name', $value) && !empty($value['name'])) {
        $valueStateName = $value['name'];
    }

    if (!empty($valueStateId)) $stateFilters[] = new EqualsFilter('id', $valueStateId);
    if (!empty($valueStateName)) $stateFilters[] = new EqualsFilter('name', $valueStateName);

    if (empty($stateFilters)) {
        $value = [];
        $name = 'noState';
        return;
    }

    $filterConditions = new MultiFilter(MultiFilter::CONNECTION_AND, [
        new MultiFilter(MultiFilter::CONNECTION_OR, $stateFilters),
        new EqualsFilter('countryId', $countryId)
    ]);

    $criteria->addFilter($filterConditions);

    $countryStateRepository = $this->container->get("country_state.repository");
    $searchResult = $countryStateRepository->searchIds($criteria, $context);

    $stateId = $searchResult->firstId();
}

if (!empty($stateId)) {
    $value = $stateId;
    $name = 'stateId';
} else {
    $value = [];
    $name = 'noState';
}
